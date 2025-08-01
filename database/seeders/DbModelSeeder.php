<?php

namespace Database\Seeders;

use App\Models\DbModel;
use App\Models\DbModelField;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DbModelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DbModelField::query()->delete();
        DbModel::query()->delete();
        ini_set('memory_limit', '3G');
        $dir = app_path('Models');
        $files = scandir($dir);
        $models = [];
        foreach ($files as $file) {
            if (preg_match('/^(.*)\.php$/', $file, $matches)) {
                $models[] = $matches[1];
            }
        }

        $dbModels = \App\Models\DbModel::all()->pluck('name')->toArray();
        //if some model is not in db, save it
        foreach ($models as $model) {
            if (!in_array($model, $dbModels)) {
                $fillable = [];
                $appends = [];
                $casts = [];
                $relations = [];
                $modelClass = "App\\Models\\{$model}";
                if (class_exists($modelClass)) {
                    $modelInstance = new $modelClass();
                    $fillable = $modelInstance->getFillable();
                    $appends = $modelInstance->getAppends();
                    $casts = $modelInstance->getCasts();
                    $relations = [];
                    // Obtener métodos públicos definidos en la clase del modelo
                    $reflection = new \ReflectionClass($modelClass);
                    $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

                    foreach ($methods as $method) {
                        // Ignorar métodos heredados de la clase base Model
                        if ($method->class !== $modelClass) {
                            continue;
                        }
                        // Ignorar métodos mágicos y el constructor
                        if (strpos($method->name, '__') === 0 || $method->name === 'boot') {
                            continue;
                        }
                        // Intentar invocar el método y verificar si retorna una relación de Eloquent
                        try {
                            $result = $method->invoke($modelInstance);
                            if ($result instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
                                $relations[$method->name] = class_basename($result);
                            }
                        } catch (\Throwable) {
                            // Ignorar métodos que requieren argumentos o lanzan excepciones
                            continue;
                        }
                    }
                    $dbModel = \App\Models\DbModel::updateOrCreate(
                        ['name' => $model],
                        [
                            'table' => $modelInstance->getTable(),
                            'fillable' => json_encode($fillable),
                            'appends' => json_encode($appends),
                            'casts' => json_encode($casts),
                            'relations' => json_encode($relations),
                        ]
                    );

                    // Crear los campos asociados (DbModelField) para cada campo fillable
                    $this->createFieldsFromDatabaseSchema($dbModel, $modelInstance->getTable());
                }

            }
        }
    }

    /**
     * Crea campos basados en el esquema real de la base de datos
     */
    private function createFieldsFromDatabaseSchema($dbModel, $tableName)
    {
        // Usar el Schema Builder de Laravel para obtener información de las columnas
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing($tableName);

        foreach ($columns as $columnName) {
            // Obtener información detallada de la columna
            $columnType = \Illuminate\Support\Facades\Schema::getColumnType($tableName, $columnName);

            // Mapear el tipo a nuestros tipos de campo
            $mappedType = $this->mapLaravelTypeToFieldType($columnType);

            // Buscar el tipo de campo en la tabla db_model_field_types
            $dbFieldType = \App\Models\DbModelFieldType::where('column_type', $mappedType)->first();

            if ($dbFieldType) {
                // Obtener información adicional usando consultas SQL directas
                $columnInfo = $this->getColumnInfo($tableName, $columnName);

                \App\Models\DbModelField::updateOrCreate(
                    [
                        'db_model_id' => $dbModel->id,
                        'name' => $columnName
                    ],
                    [
                        'field_type_id' => $dbFieldType->id,
                        'label' => ucwords(str_replace('_', ' ', $columnName)),
                        'nullable' => $columnInfo['nullable'] ?? false,
                        'unique' => $columnInfo['unique'] ?? false,
                        'index' => $columnInfo['index'] ?? false,
                        'primary' => $columnInfo['primary'] ?? false,
                        'auto_increment' => $columnInfo['auto_increment'] ?? false,
                        'foreign' => $columnInfo['foreign'] ?? false,
                        'foreign_table' => $columnInfo['foreign_table'] ?? null,
                        'foreign_key' => $columnInfo['foreign_key'] ?? null,
                        'default' => $columnInfo['default'] ?? null,
                    ]
                );
            }
        }
    }

    /**
     * Obtiene información detallada de una columna específica
     */
    private function getColumnInfo($tableName, $columnName)
    {
        $connection = \Illuminate\Support\Facades\DB::connection();
        $database = $connection->getDatabaseName();

        // Consultar información de la columna desde INFORMATION_SCHEMA
        $columnInfo = $connection->select("
            SELECT
                COLUMN_NAME,
                IS_NULLABLE,
                COLUMN_DEFAULT,
                COLUMN_KEY,
                EXTRA
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
        ", [$database, $tableName, $columnName]);

        if (empty($columnInfo)) {
            return [];
        }

        $column = $columnInfo[0];

        // Verificar foreign keys
        $foreignKeyInfo = $connection->select("
            SELECT
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$database, $tableName, $columnName]);

        // Verificar índices únicos
        $uniqueIndexInfo = $connection->select("
            SELECT COUNT(*) as count
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND NON_UNIQUE = 0 AND INDEX_NAME != 'PRIMARY'
        ", [$database, $tableName, $columnName]);

        // Verificar índices normales
        $indexInfo = $connection->select("
            SELECT COUNT(*) as count
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND NON_UNIQUE = 1
        ", [$database, $tableName, $columnName]);

        return [
            'nullable' => $column->IS_NULLABLE === 'YES',
            'primary' => $column->COLUMN_KEY === 'PRI',
            'unique' => ($uniqueIndexInfo[0]->count ?? 0) > 0,
            'index' => ($indexInfo[0]->count ?? 0) > 0,
            'auto_increment' => strpos($column->EXTRA, 'auto_increment') !== false,
            'foreign' => !empty($foreignKeyInfo),
            'foreign_table' => $foreignKeyInfo[0]->REFERENCED_TABLE_NAME ?? null,
            'foreign_key' => $foreignKeyInfo[0]->REFERENCED_COLUMN_NAME ?? null,
            'default' => $column->COLUMN_DEFAULT,
        ];
    }

    /**
     * Mapea tipos de Laravel a nuestros tipos de campo
     */
    private function mapLaravelTypeToFieldType($laravelType)
    {
        $typeMap = [
            'bigint' => 'bigInteger',
            'int' => 'integer',
            'integer' => 'integer',
            'smallint' => 'smallInteger',
            'tinyint' => 'boolean',
            'decimal' => 'decimal',
            'float' => 'float',
            'double' => 'double',
            'varchar' => 'string',
            'char' => 'string',
            'text' => 'text',
            'longtext' => 'longText',
            'mediumtext' => 'mediumText',
            'date' => 'date',
            'datetime' => 'dateTime',
            'timestamp' => 'timestamp',
            'time' => 'time',
            'json' => 'json',
            'blob' => 'binary',
            'longblob' => 'binary',
        ];

        return $typeMap[$laravelType] ?? 'string';
    }

    /**
     * Mapea un cast de Laravel a un tipo de campo de base de datos
     */
    private function mapCastToFieldType($cast)
    {
        $castMap = [
            'int' => 'integer',
            'integer' => 'integer',
            'bool' => 'boolean',
            'boolean' => 'boolean',
            'float' => 'float',
            'double' => 'double',
            'decimal' => 'decimal',
            'string' => 'string',
            'array' => 'json',
            'json' => 'json',
            'object' => 'json',
            'collection' => 'json',
            'date' => 'date',
            'datetime' => 'dateTime',
            'timestamp' => 'timestamp',
        ];

        return $castMap[$cast] ?? 'string';
    }
}
