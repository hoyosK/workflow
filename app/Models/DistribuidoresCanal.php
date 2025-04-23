<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;


class DistribuidoresCanal extends Eloquent {
    public $timestamps = false;
    protected $table = 'distribuidoresCanales';
    protected $primaryKey = 'id';
}
