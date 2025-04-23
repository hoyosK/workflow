<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class catTipoSociedad extends Eloquent {
    public $timestamps = false;
    protected $table = 'catTipoSociedad';
    protected $primaryKey = 'id';
}
