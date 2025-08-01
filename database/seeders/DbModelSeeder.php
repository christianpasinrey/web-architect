<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DbModelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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
                }

                $dbModel = \App\Models\DbModel::updateOrCreate(
                    ['name' => $model],
                    [
                        'table' => strtolower($model),
                        'fillable' => json_encode($fillable),
                        'appends' => json_encode($appends),
                        'casts' => json_encode($casts),
                        'relations' => json_encode($relations),
                    ]
                );

                // Crear los campos asociados (DbModelField) para cada campo fillable
                foreach ($fillable as $fieldName) {
                    // Determinar el tipo de campo basado en los casts o usar string por defecto
                    $fieldType = 'string';
                    if (isset($casts[$fieldName])) {
                        $fieldType = $this->mapCastToFieldType($casts[$fieldName]);
                    }

                    // Buscar el tipo de campo en la tabla db_model_field_types
                    $dbFieldType = \App\Models\DbModelFieldType::where('column_type', $fieldType)->first();

                    if ($dbFieldType) {
                        \App\Models\DbModelField::updateOrCreate(
                            [
                                'db_model_id' => $dbModel->id,
                                'name' => $fieldName
                            ],
                            [
                                'field_type_id' => $dbFieldType->id,
                                'label' => ucwords(str_replace('_', ' ', $fieldName)), // Crear label amigable
                                'nullable' => true, // Por defecto asumimos que puede ser null
                                'unique' => false,
                                'index' => false,
                                'primary' => false,
                                'auto_increment' => false,
                                'foreign' => false,
                            ]
                        );
                    }
                }
            }
        }
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
