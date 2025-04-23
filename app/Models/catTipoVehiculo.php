<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;


class catTipoVehiculo extends Eloquent {
    public $timestamps = false;
    protected $table = 'catTipoVehiculo';
    protected $primaryKey = 'id';


    /*public function lineas() {
        return $this->hasMany(Lineas::class, 'marcaId', 'id');
    }*/

}
