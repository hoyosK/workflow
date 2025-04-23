<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;


class catMarca extends Eloquent {
    public $timestamps = false;
    protected $table = 'catMarcas';
    protected $primaryKey = 'id';


    public function lineas() {
        return $this->hasMany(catLinea::class, 'marcaId', 'id');
    }

}
