<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;


class catLinea extends Eloquent {
    public $timestamps = false;
    protected $table = 'catLineas';
    protected $primaryKey = 'id';
}
