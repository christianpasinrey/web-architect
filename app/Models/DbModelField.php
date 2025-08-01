<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DbModelField extends Model
{
    protected $table = 'db_model_fields';

    protected $fillable = [
        'db_model_id',
        'field_type_id',
        'name',
        'label',
        'default',
        'nullable',
        'unique',
        'index',
        'primary',
        'auto_increment',
        'foreign',
        'foreign_table',
        'foreign_key',
    ];

    public function dbModel()
    {
        return $this->belongsTo(DbModel::class, 'db_model_id');
    }

    public function fieldType()
    {
        return $this->belongsTo(DbModelFieldType::class, 'field_type_id');
    }
}
