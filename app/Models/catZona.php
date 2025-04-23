<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;


class catZona extends Eloquent {
    public $timestamps = false;
    protected $table = 'catZonas';
    protected $primaryKey = 'id';


    /*public function lineas() {
        return $this->hasMany(Lineas::class, 'marcaId', 'id');
    }*/

}
