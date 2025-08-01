<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DbModel extends Model
{
    protected $table = 'db_models';
    protected $fillable = [
        'name',
        'table',
        'fillable',
        'guarded',
        'with',
        'hidden',
        'appends',
        'casts',
        'relations',
    ];

    public function fields()
    {
        return $this->hasMany(DbModelField::class, 'db_model_id');
    }
}
