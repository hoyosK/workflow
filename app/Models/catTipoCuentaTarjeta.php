<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;


class catTipoCuentaTarjeta extends Eloquent {
    public $timestamps = false;
    protected $table = 'catTipoCuentaTarjeta';
    protected $primaryKey = 'id';


    /*public function lineas() {
        return $this->hasMany(Lineas::class, 'marcaId', 'id');
    }*/

}
