<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClienteCloud extends Model
{
    use HasFactory;

    protected $connection= 'mysql_base';

    protected $table = 'clientes_cloud';

    public function __construct(array $attributes = [])
    {
        
        $this->table = $this->prefix.$this->table;
       
 
        parent::__construct($attributes);
    }
}
