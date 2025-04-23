<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;


class catBeneficiarios extends Eloquent {
    public $timestamps = false;
    protected $table = 'catBeneficiarios';
    protected $primaryKey = 'id';


    /*public function lineas() {
        return $this->hasMany(Lineas::class, 'marcaId', 'id');
    }*/

}
