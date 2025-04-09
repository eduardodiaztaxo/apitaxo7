<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecScUser extends Model
{
    use HasFactory;

    protected $table = 'sec_users';

    protected $primaryKey = 'login';

    public $timestamps = false;
}
