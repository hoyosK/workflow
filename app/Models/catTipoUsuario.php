<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class catTipoUsuario extends Eloquent {
    public $timestamps = false;
    protected $table = 'catTipoUsuario';
    protected $primaryKey = 'id';
}
