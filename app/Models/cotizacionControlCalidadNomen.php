<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;


class cotizacionControlCalidadNomen extends Eloquent {
    public $timestamps = false;
    protected $table = 'cotizacionesControlCalidadNomen';
    protected $primaryKey = 'id';


    /*public function lineas() {
        return $this->hasMany(Lineas::class, 'marcaId', 'id');
    }*/

}
