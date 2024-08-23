<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OldUser extends Model
{
    use HasFactory;

    protected $connection = 'mysql_base';

    protected $table = 'users_cloud';

    protected $fillable = ['user_pw'];

    protected $primaryKey = 'user_id';

    public function clienteCloud()
    {
        return $this->hasOne(ClienteCloud::class, 'clie_id', 'clie_id_fk');
    }
}
