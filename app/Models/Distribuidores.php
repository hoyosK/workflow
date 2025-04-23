<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;


class Distribuidores extends Eloquent {
    public $timestamps = false;
    protected $table = 'distribuidores';
    protected $primaryKey = 'id';


    public function users() {
        return $this->hasMany(DistribuidoresUsuarios::class, 'distribuidorId', 'id');
    }

    public function canales() {
        return $this->hasMany(DistribuidoresCanal::class, 'distribuidorId', 'id');
    }
}
