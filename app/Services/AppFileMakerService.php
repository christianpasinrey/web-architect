<?php

namespace App\Services;

use App\Models\{
    DbModel,
    FieldModel,
    DbModelFieldType
};
use Doctrine\DBAL\Schema\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Collection;

class AppFileMakerService
{
    protected $dbModel;
    protected $fieldTypes;
    public function __construct(DbModel $dbModel, Collection $fieldTypes)
    {
        $this->dbModel = $dbModel;
        $this->fieldTypes = $fieldTypes;
    }

    public function fillStubs($model_path, $migration_path, $model_name, $table_name, $fields, $relations, $appends, $casts, $dbModelId)
    {
        $this->fillModelStub($model_path, $model_name, $table_name, $fields, $relations, $appends, $casts);
        $this->fillMigrationStub($migration_path, $table_name, $dbModelId);
    }

    public function fillModelStub($model_path, $model_name, $table_name, $fields, $relations, $appends, $casts)
    {
        $stub = "<?php\n\nnamespace App\\Models;\n\nuse Illuminate\\Database\\Eloquent\\Model;\n\nclass {$model_name} extends Model\n{\n    protected \$table = '{$table_name}';\n\n    protected \$fillable = [\n";
        foreach ($fields as $field => $type) {
            $stub .= "        '{$field}',\n";
        }
        $stub .= "    ];\n\n";
        if (!empty($casts)) {
            $stub .= "    protected \$casts = [\n";
            foreach ($casts as $key => $type) {
                $stub .= "        '{$key}' => '{$type}',\n";
            }
            $stub .= "    ];\n\n";
        }
        if (!empty($appends)) {
            $stub .= "    protected \$appends = [\n";
            foreach ($appends as $app) {
                $attr = strtolower(preg_replace('/^get(.+)Attribute$/i', '$1', $app['name']));
                $stub .= "        '{$attr}',\n";
            }
            $stub .= "    ];\n\n";
        }
        if (!empty($relations)) {
            foreach ($relations as $rel) {
                if (!empty($rel['name']) && !empty($rel['foreignKey'])) {
                    $relatedModel = ucfirst($rel['name']);
                    $foreignKey = $rel['foreignKey'];
                    $stub .= "    public function {$rel['name']}()\n    {\n        return \$this->belongsTo(\\App\\Models\\{$relatedModel}::class, '{$foreignKey}');\n    }\n\n";
                }
            }
        }
        foreach ($appends as $app) {
            // Accesor para operación matemática
            if ($app['type'] === 'operacion_matematica' && isset($app['options']['formula'])) {
                $stub .= "    public function {$app['name']}()\n    {\n        return {$app['options']['formula']};\n    }\n\n";
            }
            // Accesor para concatenar
            if ($app['type'] === 'concatenar' && isset($app['fields']) && is_array($app['fields'])) {
                $methodName = 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $app['name']))) . 'Attribute';
                $fields = array_map(function($f) { return "\$this->$f"; }, $app['fields']);
                $separator = isset($app['options']['separator']) ? $app['options']['separator'] : ' ';
                // Escapar comillas simples en el separador
                $sepEscaped = str_replace("'", "\\'", $separator);
                $stub .= "    public function {$methodName}()\n    {\n        return implode('{$sepEscaped}', [" . implode(', ', $fields) . "]);\n    }\n\n";
            }
        }
        $stub .= "}\n";
        file_put_contents($model_path, $stub);
    }

    public function fillMigrationStub($migration_path, $table_name, $dbModelId)
    {
        // Obtener el modelo con sus campos y tipos de campo
        $dbModel = \App\Models\DbModel::with(['fields.fieldType'])->find($dbModelId);

        // Obtener nombre de clase de migración a partir del nombre de archivo
        $filename = basename($migration_path, '.php');
        // Extraer nombre de tabla para la clase
        $tableForClass = preg_replace('/^\d+_\d+_\d+_\d+_create_(.+)_table$/', '$1', $filename);
        $className = 'Create' . str_replace(' ', '', ucwords(str_replace('_', ' ', $tableForClass))) . 'Table';

        $stub = "<?php\n\nuse Illuminate\\Database\\Migrations\\Migration;\nuse Illuminate\\Database\\Schema\\Blueprint;\nuse Illuminate\\Support\\Facades\\Schema;\n\nclass {$className} extends Migration\n{\n    public function up()\n    {\n        Schema::create('{$table_name}', function (Blueprint \$table) {\n            \$table->id();\n";

        // Obtener relaciones para determinar claves foráneas
        $relations = json_decode($dbModel->relations ?? '[]', true);
        $foreignKeys = [];
        foreach ($relations as $rel) {
            if (!empty($rel['foreignKey'])) {
                $foreignKeys[] = $rel['foreignKey'];
            }
        }

        // Generar campos usando la información de DbModelField
        foreach ($dbModel->fields as $field) {
            if ($field->name === 'id') continue;

            $columnType = $field->fieldType->column_type;
            $laravelType = $this->mapFieldType($columnType);

            if (in_array($field->name, $foreignKeys)) {
                $stub .= "            \$table->foreignId('{$field->name}')";
            } else {
                $stub .= "            \$table->{$laravelType}('{$field->name}')";
            }

            // Aplicar modificadores de campo
            if ($field->nullable) $stub .= "->nullable()";
            if ($field->unique) $stub .= "->unique()";
            if ($field->index) $stub .= "->index()";
            if ($field->default) $stub .= "->default('{$field->default}')";
            if ($field->auto_increment) $stub .= "->autoIncrement()";

            $stub .= ";\n";

            // Agregar clave foránea si es necesario
            if ($field->foreign && $field->foreign_table && $field->foreign_key) {
                $stub .= "            \$table->foreign('{$field->name}')->references('{$field->foreign_key}')->on('{$field->foreign_table}');\n";
            }
        }        $stub .= "            \$table->timestamps();\n";

        // Relaciones adicionales (compatibilidad con el sistema anterior)
        foreach ($relations as $rel) {
            if (!empty($rel['foreignKey']) && !empty($rel['name'])) {
                $relatedTable = strtolower($rel['name']) . 's';
                $stub .= "            \$table->foreign('{$rel['foreignKey']}')->references('id')->on('{$relatedTable}');\n";
            }
        }

        $stub .= "        });\n    }\n\n    public function down()\n    {\n        Schema::dropIfExists('{$table_name}');\n    }\n}\n";

        file_put_contents($migration_path, $stub);
    }

    public function mapFieldType($type)
    {
        $map = [
            'string' => 'string',
            'int' => 'integer',
            'integer' => 'integer',
            'bigint' => 'bigInteger',
            'bigintunsigned' => 'unsignedBigInteger',
            'float' => 'float',
            'double' => 'double',
            'decimal' => 'decimal',
            'date' => 'date',
            'datetime' => 'dateTime',
            'text' => 'text',
            'boolean' => 'boolean',
            'json' => 'json',
            'jsonb' => 'json',
            'timestamp' => 'timestamp',
            'time' => 'time',
            'uuid' => 'uuid',
            'binary' => 'binary',
            'enum' => 'enum',
            'set' => 'set',
        ];
        $type = strtolower($type);
        return $map[$type] ?? 'string';
    }
}

