<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;


class CotizacionDetalleVehiculoCotizacionCobertura extends Eloquent {
    public $timestamps = false;
    protected $table = 'cotizacionesDetalleVehiculosCotCober';
    protected $primaryKey = 'id';

    public function subCotizacion() {
        return $this->belongsTo(Cotizacion::class, 'cotizacionDetalleVehiculoCotId', 'id');
    }

    public function cobertura() {
        return $this->belongsTo(catCoberturas::class, 'coberturaId', 'id');
    }
}
