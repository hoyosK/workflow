<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;


class PaginasPromocionesAccess extends Eloquent {
    public $timestamps = false;
    protected $table = 'paginasPromocionesAccess';
    protected $primaryKey = 'id';
}
