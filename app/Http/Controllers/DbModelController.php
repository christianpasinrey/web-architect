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
        $model_name = $request->input('name');
        $table_name = $request->input('table');
        $fieldsArr = $request->input('fillable', []);
        $relationsArr = $request->input('relations', []);
        $appendsArr = $request->input('appends', []);
        $castsArr = $request->input('casts', []);

        $fields = [];
        foreach ($fieldsArr as $field) {
            if (isset($field['name']) && isset($field['type'])) {
                $fields[$field['name']] = $field['type'];
            }
        }

        $relations = [];
        foreach ($relationsArr as $rel) {
            if (isset($rel['name']) && isset($rel['foreignKey'])) {
                $relations[] = $rel;
            }
        }

        $appends = [];
        foreach ($appendsArr as $app) {
            if (isset($app['name'])) {
                $appends[] = $app;
            }
        }

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

        $dbModel = \App\Models\DbModel::create([
            'name' => $model_name,
            'table' => $table_name,
            'fillable' => json_encode(array_keys($fields)),
            'relations' => json_encode($relations),
            'appends' => json_encode($appends),
            'casts' => json_encode($casts),
        ]);

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

        $service = new \App\Services\AppFileMakerService($dbModel, collect(\App\Models\DbModelFieldType::all()));
        $service->fillStubs(
            $model_path, $migration_path, $model_name,
            $table_name, $fields, $relations, $appends,
            $casts, $dbModel->id
        );

        return response()->json(['success' => 'Model and migration created successfully'], 201);
    }

    public function show($id)
    {
        $model = \App\Models\DbModel::with(['fields.fieldType'])->findOrFail($id);
        $fieldTypes = \App\Models\DbModelFieldType::all();

        $modelFileContent = null;
        $modelPath = app_path("Models/{$model->name}.php");
        if (file_exists($modelPath)) {
            $modelFileContent = file_get_contents($modelPath);
        }

        return response()->json([
            'model' => $model,
            'fieldTypes' => $fieldTypes,
            'modelFileContent' => $modelFileContent,
        ]);
    }

    public function edit($id)
    {
        $model = \App\Models\DbModel::with(['fields.fieldType'])->findOrFail($id);
        $fieldTypes = \App\Models\DbModelFieldType::all();

        return Inertia::render('models/Contexts/Edit', [
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

        $dbModel->update([
            'name' => $model_name,
            'table' => $table_name,
            'fillable' => json_encode(array_column($fieldsArr, 'name')),
            'relations' => json_encode($relationsArr),
            'appends' => json_encode($appendsArr),
            'casts' => json_encode($castsArr),
        ]);

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

        $dbModel->fields()->delete();

        $dbModel->delete();

        return response()->json(['success' => 'Model deleted successfully'], 200);
    }

    public function getFieldTypes()
    {
        return response()->json(\App\Models\DbModelFieldType::all());
    }


}
