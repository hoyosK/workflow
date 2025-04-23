<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;


class catProductoTarifaGrupoUsuario extends Eloquent {
    public $timestamps = false;
    protected $table = 'catProductoTarifaGrupoUsuario';
    protected $primaryKey = 'id';


    /*public function lineas() {
        return $this->hasMany(Lineas::class, 'marcaId', 'id');
    }*/

}
