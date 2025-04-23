<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;


class catProductos extends Eloquent {
    public $timestamps = false;
    protected $table = 'catProductos';
    protected $primaryKey = 'id';


    /*public function lineas() {
        return $this->hasMany(Lineas::class, 'marcaId', 'id');
    }*/

}
