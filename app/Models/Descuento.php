<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;


class Descuento extends Eloquent {
    public $timestamps = false;
    protected $table = 'descuentos';
    protected $primaryKey = 'id';

}
