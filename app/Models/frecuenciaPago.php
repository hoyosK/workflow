<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;


class frecuenciaPago extends Eloquent {
    public $timestamps = false;
    protected $table = 'frecuenciasPago';
    protected $primaryKey = 'id';

}
