<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class UsersTiendas extends Eloquent {
    public $timestamps = false;
    protected $table = 'usersTiendas';
    protected $primaryKey = 'id';

    public function tienda() {
        return $this->belongsTo(UserTienda::class, 'userCanalId', 'id');
    }
}
