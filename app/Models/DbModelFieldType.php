<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DbModelFieldType extends Model
{
    protected $table = 'db_model_field_types';

    protected $fillable = [
        'label',
        'column_type',
    ];

    public function fields()
    {
        return $this->hasMany(DbModelField::class, 'field_type_id');
    }
}
