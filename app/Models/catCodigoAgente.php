<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;


class catCodigoAgente extends Eloquent {
    public $timestamps = false;
    protected $table = 'catCodigoAgente';
    protected $primaryKey = 'id';


    /*public function lineas() {
        return $this->hasMany(Lineas::class, 'marcaId', 'id');
    }*/

}
