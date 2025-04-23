<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;


class CotizacionDetalleVehiculoCotizacion extends Eloquent {
    public $timestamps = false;
    protected $table = 'cotizacionesDetalleVehiculosCot';
    protected $primaryKey = 'id';

    public function vehiculo() {
        return $this->belongsTo(CotizacionDetalleVehiculo::class, 'cotizacionDetalleVehiculoId', 'id');
    }

    public function tarifa() {
        return $this->belongsTo(catTarifas::class, 'tarifaId', 'id');
    }

    public function producto() {
        return $this->belongsTo(catProductos::class, 'productoId', 'id');
    }

    public function formaPago() {
        return $this->belongsTo(catFormaPago::class, 'formaPagoId', 'id');
    }

    public function coberturas() {
        return $this->hasMany(CotizacionDetalleVehiculoCotizacionCobertura::class, 'cotizacionDetalleVehiculoCotId', 'id');
    }
}
