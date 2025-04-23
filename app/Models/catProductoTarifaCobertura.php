<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;


class catProductoTarifaCobertura extends Eloquent {
    public $timestamps = false;
    protected $table = 'catProductoTarifaCobertura';
    protected $primaryKey = 'id';


    public function descuentoRecargo() {
        return $this->hasMany(catProductoTarifaDescuentoRecargo::class, 'idProductoCobertura', 'id');
    }
    public function cobertura() {
        return $this->belongsTo(catCoberturas::class, 'idCobertura', 'id');
    }

    public function tarifa() {
        return $this->belongsTo(catProductoTarifa::class, 'idProductoTarifa', 'id');
    }

}
