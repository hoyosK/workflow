<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class UserTienda extends Authenticatable {
    public $timestamps = false;
    protected $table = 'userTienda';
    protected $primaryKey = 'id';

    public function user() {
        return $this->belongsTo(User::class, 'userId', 'id');
    }

    public function tienda() {
        return $this->belongsTo(UsersTiendas::class, 'tiendaId', 'id');
    }
}
