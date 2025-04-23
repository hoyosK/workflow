<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;


class DataMetodoPago extends Eloquent {
    public $timestamps = false;
    protected $table = 'dataMetodoPago';
    protected $primaryKey = 'id';

    /*public function lineas() {
        return $this->hasMany(Lineas::class, 'marcaId', 'id');
    }*/

}
