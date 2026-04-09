<?php

namespace App\Models\Auditoria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmplazamientoNn extends Model
{
    use HasFactory;

    public static function fromTable($table)
    {
        $instance = new static;
        $instance->setTable($table);
        return $instance;
    }
}
