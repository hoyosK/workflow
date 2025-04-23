<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;


class Inspeccion extends Eloquent {
    public $timestamps = false;
    protected $table = 'inspeccion';
    protected $primaryKey = 'id';

    public function usuario(){
        return $this->belongsTo(User::class, 'userIdAsignado', 'id');
    }
}
