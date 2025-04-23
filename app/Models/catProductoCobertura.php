<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;


class catProductoCobertura extends Eloquent {
    public $timestamps = false;
    protected $table = 'catProductoCobertura';
    protected $primaryKey = 'id';


    public function descuentoRecargo() {
        return $this->hasMany(catProductoTarifaDescuentoRecargo::class, 'idProductoCobertura', 'id');
    }

    public function tarifasCobertura() {
        return $this->hasMany(catProductoTarifaCobertura::class, 'idProductoCobertura', 'id');
    }
    public function cobertura() {
        return $this->belongsTo(catCoberturas::class, 'idCobertura', 'id');
    }

}
