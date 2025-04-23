<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;


class catTipoTarifas extends Eloquent {
    public $timestamps = false;
    protected $table = 'catTipoTarifas';
    protected $primaryKey = 'id';


    /*public function lineas() {
        return $this->hasMany(Lineas::class, 'marcaId', 'id');
    }*/

}
