<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;


class cotizacionControlCalidad extends Eloquent {
    public $timestamps = false;
    protected $table = 'cotizacionesControlCalidad';
    protected $primaryKey = 'id';


    public function usuario() {
        return $this->belongsTo(User::class, 'usuarioId', 'id');
    }
    public function tipificacion() {
        return $this->belongsTo(cotizacionControlCalidadNomen::class, 'tipificacionId', 'id');
    }

}
