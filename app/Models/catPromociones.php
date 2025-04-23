<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class catPromociones extends Eloquent {
    public $timestamps = false;
    protected $table = 'catPromociones';
    protected $primaryKey = 'id';
}
