<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyTypeToWhomType extends Model
{
    use HasFactory;

    protected $table = 'property_type_to_whom_type';

    protected $fillable = [
        'property_type_id',
        'to_whom_type_id',
    ];
}
