<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;


class catTipoDocumento extends Eloquent {
    public $timestamps = false;
    protected $table = 'catTipoDocumento';
    protected $primaryKey = 'id';


    /*public function lineas() {
        return $this->hasMany(Lineas::class, 'marcaId', 'id');
    }*/

}
