<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class UserJerarquiaDetail extends Eloquent {
    public $timestamps = false;
    protected $primaryKey = 'id';
    protected $table = 'usersJerarquiaDetail';

    public function canal() {
        return $this->belongsTo(UserCanal::class, 'canalId', 'id');
    }

    public function tienda() {
        return $this->belongsTo(UserTienda::class, 'canalId', 'id');
    }

    public function gruposUsuarios() {
        return $this->belongsTo(UserGrupoUsuario::class, 'userGroupId', 'userGroupId');
    }

    public function gruposRol() {
        return $this->belongsTo(UserGrupoRol::class, 'userGroupId', 'userGroupId');
    }

    public function rol() {
        return $this->belongsTo(Rol::class, 'rolId', 'id');
    }

    public function jerarquia() {
        return $this->belongsTo(UserJerarquia::class, 'jerarquiaId', 'id');
    }
}
