<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;


class PaginasPromociones extends Eloquent {
    public $timestamps = false;
    protected $table = 'paginas_promociones';
    protected $primaryKey = 'id';

    public function accesos(){
        return $this->hasMany(PaginasPromocionesAccess::class, 'paginaId', 'id');
    }
}
