<?php

namespace App\Http\Controllers;

use app\core\Response;
use App\Models\Configuration;
use App\Models\SistemaVariable;
use App\Models\Archivador;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ConfigController extends Controller {

    use Response;

    private function token($length = 50) {
        $bytes = random_bytes($length);
        return bin2hex($bytes);
    }

    public function GetList() {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['users/role/admin'])) return $AC->NoAccess();

        $itemList = Archivador::all();

        $response = [];

        foreach ($itemList as $item) {
            $response[] = [
                'id' => $item->id,
                'nombre' => $item->nombre,
                'urlLogin' => $item->urlLogin,
                'logo' => $item->logo,
            ];
        }

        if (!empty($itemList)) {
            return $this->ResponseSuccess('Ok', $response);
        }
        else {
            return $this->ResponseError('Error al obtener aplicaciones');
        }
    }

    public function Load() {

        $items = Configuration::all();

        $config = [];
        foreach ($items as $item) {
            $config[$item->slug] = ($item->typeRow === 'json') ? @json_decode($item->dataText) : $item->dataText;
        }

        if (!empty($config)) {
            return $this->ResponseSuccess('Ok', $config);
        }
        else {
            return $this->ResponseError('Error al obtener configuración');
        }
    }


    public function GetVars() {

        $items = SistemaVariable::all();

        if (!empty($items)) {
            return $this->ResponseSuccess('Ok', $items);
        }
        else {
            return $this->ResponseError('CNF-214', 'Error al obtener variables de sistema');
        }
    }

    public function SaveVars(Request $request) {

        $AC = new AuthController();
        //if (!$AC->CheckAccess(['users/role/admin'])) return $AC->NoAccess();

        $vars = $request->get('vars');

        foreach ($vars as $var) {
            if (!empty($var['id'])) {
                $row = SistemaVariable::find($var['id']);
                $row->slug = $var['slug'];
                $row->contenido = $var['contenido'] ?? '';
                $row->save();
            }
            else {
                SistemaVariable::updateOrCreate(['slug' => $var['slug']], ['contenido'  => $var['contenido'] ?? '']);
            }
        }

        return $this->ResponseSuccess('Variables actualizadas con éxito');
    }

    public function cacheClear(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['admin/system'])) return $AC->NoAccess();

        if (Cache::flush()) {
            return $this->ResponseSuccess('Caché borrado con éxito');
        }
        else {
            return $this->ResponseError('SYS-214', 'Error limpiando caché');
        }

    }

}
