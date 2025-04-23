<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;


class catLineaIntermediario extends Eloquent {
    public $timestamps = false;
    protected $table = 'catLineaIntermediario';
    protected $primaryKey = 'id';


    /*public function lineas() {
        return $this->hasMany(Lineas::class, 'marcaId', 'id');
    }*/

}
