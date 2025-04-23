<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class catTipoCliente extends Eloquent {
    public $timestamps = false;
    protected $table = 'catTipoCliente';
    protected $primaryKey = 'id';
}
