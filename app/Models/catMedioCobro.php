<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;


class catMedioCobro extends Eloquent {
    public $timestamps = false;
    protected $table = 'catMedioCobro';
    protected $primaryKey = 'id';


    /*public function lineas() {
        return $this->hasMany(Lineas::class, 'marcaId', 'id');
    }*/

}
