<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;


class catProfesion extends Eloquent {
    public $timestamps = false;
    protected $table = 'catProfesion';
    protected $primaryKey = 'id';


    /*public function lineas() {
        return $this->hasMany(Lineas::class, 'marcaId', 'id');
    }*/

}
