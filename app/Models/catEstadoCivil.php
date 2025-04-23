<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;


class catEstadoCivil extends Eloquent {
    public $timestamps = false;
    protected $table = 'catEstadoCivil';
    protected $primaryKey = 'id';


    /*public function lineas() {
        return $this->hasMany(Lineas::class, 'marcaId', 'id');
    }*/

}
