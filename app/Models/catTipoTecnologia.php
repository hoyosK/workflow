<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class catTipoTecnologia extends Eloquent {
    public $timestamps = false;
    protected $table = 'catTipoTecnologia';
    protected $primaryKey = 'id';
}
