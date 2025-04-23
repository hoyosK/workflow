<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;


class catProductoTarifa extends Eloquent {
    public $timestamps = false;
    protected $table = 'catProductoTarifa';
    protected $primaryKey = 'id';


    /*public function lineas() {
        return $this->hasMany(Lineas::class, 'marcaId', 'id');
    }*/

    public function producto() {
        return $this->belongsTo(catProductos::class, 'idProducto', 'id');
    }
    public function tarifa() {
        return $this->belongsTo(catTarifas::class, 'idTarifa', 'id');
    }

}
