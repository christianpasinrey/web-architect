<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\DbModelFieldType;

class DbModelFieldTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $column_data_types = [
            ['label' => 'String', 'column_type' => 'string'],
            ['label' => 'Text', 'column_type' => 'text'],
            ['label' => 'Medium Text', 'column_type' => 'mediumText'],
            ['label' => 'Long Text', 'column_type' => 'longText'],
            ['label' => 'Char', 'column_type' => 'char'],
            ['label' => 'Varchar', 'column_type' => 'varchar'],
            ['label' => 'Tiny Text', 'column_type' => 'tinyText'],
            ['label' => 'Integer', 'column_type' => 'integer'],
            ['label' => 'Big Integer', 'column_type' => 'bigInteger'],
            ['label' => 'Small Integer', 'column_type' => 'smallInteger'],
            ['label' => 'Tiny Integer', 'column_type' => 'tinyInteger'],
            ['label' => 'Medium Integer', 'column_type' => 'mediumInteger'],
            ['label' => 'Unsigned Integer', 'column_type' => 'unsignedInteger'],
            ['label' => 'Unsigned Big Integer', 'column_type' => 'unsignedBigInteger'],
            ['label' => 'Unsigned Small Integer', 'column_type' => 'unsignedSmallInteger'],
            ['label' => 'Unsigned Tiny Integer', 'column_type' => 'unsignedTinyInteger'],
            ['label' => 'Unsigned Medium Integer', 'column_type' => 'unsignedMediumInteger'],
            ['label' => 'Float', 'column_type' => 'float'],
            ['label' => 'Double', 'column_type' => 'double'],
            ['label' => 'Decimal', 'column_type' => 'decimal'],
            ['label' => 'Boolean', 'column_type' => 'boolean'],
            ['label' => 'Bit', 'column_type' => 'bit'],
            ['label' => 'Enum', 'column_type' => 'enum'],
            ['label' => 'Set', 'column_type' => 'set'],
            ['label' => 'JSON', 'column_type' => 'json'],
            ['label' => 'Date', 'column_type' => 'date'],
            ['label' => 'DateTime', 'column_type' => 'dateTime'],
            ['label' => 'Timestamp', 'column_type' => 'timestamp'],
            ['label' => 'Time', 'column_type' => 'time'],
            ['label' => 'Year', 'column_type' => 'year'],
            ['label' => 'Binary', 'column_type' => 'binary'],
            ['label' => 'Varbinary', 'column_type' => 'varbinary'],
            ['label' => 'Tiny Blob', 'column_type' => 'tinyBlob'],
            ['label' => 'Blob', 'column_type' => 'blob'],
            ['label' => 'Medium Blob', 'column_type' => 'mediumBlob'],
            ['label' => 'Long Blob', 'column_type' => 'longBlob'],
            ['label' => 'Geometry', 'column_type' => 'geometry'],
            ['label' => 'Point', 'column_type' => 'point'],
            ['label' => 'LineString', 'column_type' => 'lineString'],
            ['label' => 'Polygon', 'column_type' => 'polygon'],
            ['label' => 'MultiPoint', 'column_type' => 'multiPoint'],
            ['label' => 'MultiLineString', 'column_type' => 'multiLineString'],
            ['label' => 'MultiPolygon', 'column_type' => 'multiPolygon'],
            ['label' => 'GeometryCollection', 'column_type' => 'geometryCollection'],
        ];

        foreach ($column_data_types as $data_type) {
            DbModelFieldType::create($data_type);
        }
    }
}
