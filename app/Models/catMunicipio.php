<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class catMunicipio extends Eloquent {
    public $timestamps = false;
    protected $table = 'catMunicipio';
    protected $primaryKey = 'id';
}
