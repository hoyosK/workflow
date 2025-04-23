<?php
namespace app\models;

use Illuminate\Database\Eloquent\Model as Eloquent;


class RecargaSiniestralidad extends Eloquent {
    public $timestamps = false;
    protected $table = 'recargaSiniestralidad';
    protected $primaryKey = 'id';
}