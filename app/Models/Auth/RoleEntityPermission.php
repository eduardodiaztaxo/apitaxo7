<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleEntityPermission extends Model
{
    use HasFactory;

    protected $connection = 'mysql_auth';

    protected $table = 'roles_entities_permissions';
}
