<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AppCommandService;
use Inertia\Inertia;

class DbModelController extends Controller
{
    protected $appCommandService;

    public function __construct(AppCommandService $appCommandService)
    {
        $this->appCommandService = $appCommandService;
    }

    public function index()
    {
        $models = \App\Models\DbModel::with(['fields.fieldType'])->get();
        $fieldTypes = \App\Models\DbModelFieldType::all();

        return Inertia::render('models/Manager', [
            'models' => $models,
            'fieldTypes' => $fieldTypes,
        ]);
    }

    public function search(Request $request)
    {
        $query = $request->input('query');
        $dir = app_path('Models');
        $files = scandir($dir);
        $models = [];
        foreach ($files as $file) {
            if (preg_match('/^(.*)\.php$/', $file, $matches)) {
                $models[] = $matches[1];
            }
        }
        $results = array_filter($models, fn($model) => str_contains(strtolower($model), strtolower($query)));
        return response()->json($results);
    }

    public function store(Request $request)
    {
        // Adaptar para aceptar el payload del frontend
        $model_name = $request->input('name');
        $table_name = $request->input('table');
        $fieldsArr = $request->input('fillable', []); // array de objetos {name, type}
        $relationsArr = $request->input('relations', []); // array de objetos {name, foreignKey}
        $appendsArr = $request->input('appends', []); // array de objetos {name, fields, type, options}
        $castsArr = $request->input('casts', []); // array de objetos {key, type}

        // Convertir fillable a array asociativo campo => tipo
        $fields = [];
        foreach ($fieldsArr as $field) {
            if (isset($field['name']) && isset($field['type'])) {
                $fields[$field['name']] = $field['type'];
            }
        }

        // Relaciones: array de ['name' => ..., 'foreignKey' => ...]
        $relations = [];
        foreach ($relationsArr as $rel) {
            if (isset($rel['name']) && isset($rel['foreignKey'])) {
                $relations[] = $rel;
            }
        }

        // Appends: array de nombres y definición
        $appends = [];
        foreach ($appendsArr as $app) {
            if (isset($app['name'])) {
                $appends[] = $app;
            }
        }

        // Casts: array asociativo campo => tipo
        $casts = [];
        foreach ($castsArr as $cast) {
            if (isset($cast['key']) && isset($cast['type'])) {
                $casts[$cast['key']] = $cast['type'];
            }
        }

        $model_path = app_path("Models/{$model_name}.php");
        $migration_path = database_path("migrations/" . date('Y_m_d_His') . "_create_{$table_name}_table.php");

        if (file_exists($model_path) || file_exists($migration_path)) {
            return response()->json(['error' => 'One of the following files already exists: ' . basename($model_path) . ', ' . basename($migration_path)], 400);
        }

        // Crear el modelo principal en la base de datos
        $dbModel = \App\Models\DbModel::create([
            'name' => $model_name,
            'table' => $table_name,
            'fillable' => json_encode(array_keys($fields)),
            'relations' => json_encode($relations),
            'appends' => json_encode($appends),
            'casts' => json_encode($casts),
        ]);

        // Crear los campos asociados
        foreach ($fieldsArr as $fieldData) {
            $fieldType = \App\Models\DbModelFieldType::where('column_type', $fieldData['type'])->first();

            if ($fieldType) {
                \App\Models\DbModelField::create([
                    'db_model_id' => $dbModel->id,
                    'field_type_id' => $fieldType->id,
                    'name' => $fieldData['name'],
                    'label' => $fieldData['label'] ?? ucwords(str_replace('_', ' ', $fieldData['name'])),
                    'default' => $fieldData['default'] ?? null,
                    'nullable' => $fieldData['nullable'] ?? false,
                    'unique' => $fieldData['unique'] ?? false,
                    'index' => $fieldData['index'] ?? false,
                    'primary' => $fieldData['primary'] ?? false,
                    'auto_increment' => $fieldData['auto_increment'] ?? false,
                    'foreign' => $fieldData['foreign'] ?? false,
                    'foreign_table' => $fieldData['foreign_table'] ?? null,
                    'foreign_key' => $fieldData['foreign_key'] ?? null,
                ]);
            }
        }

        // Generar archivos físicos
        $this->fillStubs($model_path, $migration_path, $model_name, $table_name, $fields, $relations, $appends, $casts, $dbModel->id);

        return response()->json(['success' => 'Model and migration created successfully'], 201);
    }

    public function show($id)
    {
        $model = \App\Models\DbModel::with(['fields.fieldType'])->findOrFail($id);
        $fieldTypes = \App\Models\DbModelFieldType::all();

        return Inertia::render('models/Show', [
            'model' => $model,
            'fieldTypes' => $fieldTypes,
        ]);
    }

    public function edit($id)
    {
        $model = \App\Models\DbModel::with(['fields.fieldType'])->findOrFail($id);
        $fieldTypes = \App\Models\DbModelFieldType::all();

        return Inertia::render('models/Edit', [
            'model' => $model,
            'fieldTypes' => $fieldTypes,
        ]);
    }

    public function update(Request $request, $id)
    {
        $dbModel = \App\Models\DbModel::findOrFail($id);

        $model_name = $request->input('name');
        $table_name = $request->input('table');
        $fieldsArr = $request->input('fillable', []);
        $relationsArr = $request->input('relations', []);
        $appendsArr = $request->input('appends', []);
        $castsArr = $request->input('casts', []);

        // Actualizar el modelo principal
        $dbModel->update([
            'name' => $model_name,
            'table' => $table_name,
            'fillable' => json_encode(array_column($fieldsArr, 'name')),
            'relations' => json_encode($relationsArr),
            'appends' => json_encode($appendsArr),
            'casts' => json_encode($castsArr),
        ]);

        // Eliminar campos existentes y crear nuevos
        $dbModel->fields()->delete();

        foreach ($fieldsArr as $fieldData) {
            $fieldType = \App\Models\DbModelFieldType::where('column_type', $fieldData['type'])->first();

            if ($fieldType) {
                \App\Models\DbModelField::create([
                    'db_model_id' => $dbModel->id,
                    'field_type_id' => $fieldType->id,
                    'name' => $fieldData['name'],
                    'label' => $fieldData['label'] ?? ucwords(str_replace('_', ' ', $fieldData['name'])),
                    'default' => $fieldData['default'] ?? null,
                    'nullable' => $fieldData['nullable'] ?? false,
                    'unique' => $fieldData['unique'] ?? false,
                    'index' => $fieldData['index'] ?? false,
                    'primary' => $fieldData['primary'] ?? false,
                    'auto_increment' => $fieldData['auto_increment'] ?? false,
                    'foreign' => $fieldData['foreign'] ?? false,
                    'foreign_table' => $fieldData['foreign_table'] ?? null,
                    'foreign_key' => $fieldData['foreign_key'] ?? null,
                ]);
            }
        }

        return response()->json(['success' => 'Model updated successfully'], 200);
    }

    public function destroy($id)
    {
        $dbModel = \App\Models\DbModel::findOrFail($id);

        // Eliminar campos asociados
        $dbModel->fields()->delete();

        // Eliminar el modelo
        $dbModel->delete();

        return response()->json(['success' => 'Model deleted successfully'], 200);
    }

    public function getFieldTypes()
    {
        return response()->json(\App\Models\DbModelFieldType::all());
    }

    private function fillStubs($model_path, $migration_path, $model_name, $table_name, $fields, $relations, $appends, $casts, $dbModelId)
    {
        $this->fillModelStub($model_path, $model_name, $table_name, $fields, $relations, $appends, $casts);
        $this->fillMigrationStub($migration_path, $table_name, $dbModelId);
    }

    private function fillModelStub($model_path, $model_name, $table_name, $fields, $relations, $appends, $casts)
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

    private function fillMigrationStub($migration_path, $table_name, $dbModelId)
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

    private function mapFieldType($type)
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
            // Agrega más si necesitas
        ];
        $type = strtolower($type);
        return $map[$type] ?? 'string';
    }
}
