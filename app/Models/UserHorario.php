<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class UserHorario extends Authenticatable {
    public $timestamps = false;
    protected $table = 'userHorario';
    protected $primaryKey = 'id';

    /*public function user() {
        return $this->belongsTo(User::class, 'rolId', 'id');
    }

    public function rol() {
        return $this->belongsTo(Rol::class, 'rolId', 'id');
    }*/
}
