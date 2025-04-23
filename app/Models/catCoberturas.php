<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;


class catCoberturas extends Eloquent {
    public $timestamps = false;
    protected $table = 'catCoberturas';
    protected $primaryKey = 'id';


    /*public function lineas() {
        return $this->hasMany(Lineas::class, 'marcaId', 'id');
    }*/

}
