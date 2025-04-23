<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;


class catGrupoCoberturas extends Eloquent {
    public $timestamps = false;
    protected $table = 'catGrupoCoberturas';
    protected $primaryKey = 'id';


    /*public function lineas() {
        return $this->hasMany(Lineas::class, 'marcaId', 'id');
    }*/

}