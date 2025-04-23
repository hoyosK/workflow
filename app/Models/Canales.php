<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;


class Canales extends Eloquent {
    public $timestamps = false;
    protected $table = 'canales';
    protected $primaryKey = 'id';


    public function users() {
        return $this->hasMany(CanalesUsuarios::class, 'canalId', 'id');
    }
}
