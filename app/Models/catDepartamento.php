<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class catDepartamento extends Eloquent {
    public $timestamps = false;
    protected $table = 'catDepartamento';
    protected $primaryKey = 'id';
}