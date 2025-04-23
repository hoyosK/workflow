<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;


class CotizacionDetalleVehiculo extends Eloquent {
    public $timestamps = false;
    protected $table = 'cotizacionesDetalleVehiculos';
    protected $primaryKey = 'id';

    public function cotizacion() {
        return $this->belongsTo(Cotizacion::class, 'cotizacionId', 'id');
    }

    public function tipo() {
        return $this->belongsTo(catTipoVehiculo::class, 'tipoId', 'id');
    }


    public function linea() {
        return $this->belongsTo(catLinea::class, 'lineaId', 'id');
    }

    public function marca() {
        return $this->belongsTo(catMarca::class, 'marcaId', 'id');
    }

    public function subCotizacion() {
        return $this->hasMany(CotizacionDetalleVehiculoCotizacion::class, 'cotizacionDetalleVehiculoId', 'id');
    }
}
