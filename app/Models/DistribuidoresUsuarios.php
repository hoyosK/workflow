<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;


class DistribuidoresUsuarios extends Eloquent {
    public $timestamps = false;
    protected $table = 'distribuidoresUsuarios';
    protected $primaryKey = 'id';
}
