<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;


class catTipoCuentaBancaria extends Eloquent {
    public $timestamps = false;
    protected $table = 'catTipoCuentaBancaria';
    protected $primaryKey = 'id';


    /*public function lineas() {
        return $this->hasMany(Lineas::class, 'marcaId', 'id');
    }*/

}
