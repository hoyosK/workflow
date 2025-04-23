<?php

namespace App\Http\Controllers;

use app\core\Response;
use App\Models\RecargaSiniestralidad;
use Illuminate\Http\Request;


class RecargaSiniestralidadController extends Controller
{
    use Response;

    public function Save(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['admin/recargas/siniestralidad'])) return $AC->NoAccess();

        $recargas = $request->get('recargas');
        $ids = [];

        foreach($recargas as  $recarga){
            $dbrecarga = RecargaSiniestralidad::where('id', $recarga['id'])->first();
            if(empty($dbrecarga)) $dbrecarga = new RecargaSiniestralidad();
            $dbrecarga->valormin = $recarga['valormin'];
            $dbrecarga->valormax = $recarga['valormax'];
            $dbrecarga->recargo = $recarga['recargo'];
            $dbrecarga->renovar = $recarga['renovar'];
            $dbrecarga->save();
            $ids[] = $dbrecarga->id;
        }
        $items = RecargaSiniestralidad::whereNotIn('id', $ids)->delete();
        $items = RecargaSiniestralidad::all();
        return $this->ResponseSuccess('Cambios Guardados con éxito', $items);
    }

    public function Listado(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['admin/recargas/siniestralidad'])) return $AC->NoAccess();

        $item = RecargaSiniestralidad::all();
        return $this->ResponseSuccess('Recargas obtenidas con éxito', $item);
    }
}
