<?php

namespace App\Http\Controllers;

use app\core\Response;
use app\models\Clientes;
use App\Models\Cotizacion;
use App\Models\CotizacionDetalle;
use App\Models\CotizacionBitacora;
use App\Models\Productos;
use App\Models\Descuento;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Symfony\Component\VarDumper\VarDumper;
use Mailgun\Exception\HttpClientException;
use Mailgun\Mailgun;
use App\Models\CotizacionDetalleBitacora;
use App\Models\Flujos;


class DescuentosController extends Controller {

    use Response;

    // plantillas pdf
    public function Save(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['admin/descuentos'])) return $AC->NoAccess();

        $id = $request->get('id');
        $nombre = $request->get('nombre');
        $activo = $request->get('activo');
        $producto = $request->get('flujos');
        $monto = $request->get('monto');
        $valormin = $request->get('valormin');
        $valormax = $request->get('valormax');
        $tipo = $request->get('tipo');
        $visibilidad = $request->get('visibilidad');

        $item = Descuento::where('id', $id)->first();

        if (empty($item)) {
            $item = new Descuento();
        }

        $arrConfig = [];
        $arrConfig['p'] = $producto;
        $arrConfig['visibilidad'] = $visibilidad;

        $item->nombre = strip_tags($nombre);
        $item->activo = intval($activo);
        $item->config = @json_encode($arrConfig) ?? null;
        $item->tipo = $tipo;
        $item->monto = $monto;
        $item->valormin = $valormin;
        $item->valormax = $valormax;
        $item->save();

        return $this->ResponseSuccess('Plantilla guardada con éxito', ['id' => $item->id]);
    }

    public function ListadoAll(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['admin/descuentos'])) return $AC->NoAccess();

        $item = Descuento::all();
        return $this->ResponseSuccess('Descuentos obtenidos con éxito', $item);
    }

    public function Listado_bk(Request $request) {
        $AC = new AuthController();

        $productos = $request->get('products');

        $usuarioLogueado = auth('sanctum')->user();
        $rolUsuarioLogueado = ($usuarioLogueado) ? $usuarioLogueado->rolAsignacion->rol : 0;

        $discount = Descuento::where('activo', 1)->get();
        $data = [];

        foreach($discount as $item){
            $visibilidad = json_decode($item->config, true)['visibilidad']?? [];
            $access = $AC->CalculateVisibility($usuarioLogueado->id, $rolUsuarioLogueado->id ?? 0, false, $visibilidad['roles'] ?? [], $visibilidad['grupos'] ?? [], $visibilidad['canales'] ?? []);
            if (!$access &&  !in_array($usuarioLogueado->id, $visibilidad['users']?? [])) continue;
            $data[] = $item;
        }
        return $this->ResponseSuccess('Descuentos obtenidos con éxito', $data);
    }

    public function Listado(Request $request) {
        $AC = new AuthController();

        $productos = $request->get('products');

        $usuarioLogueado = auth('sanctum')->user();
        $rolUsuarioLogueado = ($usuarioLogueado) ? $usuarioLogueado->rolAsignacion->rol : 0;

        $discount = Descuento::where('activo', 1)->get();
        $data = [];

        foreach($discount as $item){
            $visibilidad = json_decode($item->config, true)['visibilidad']?? [];

            $access = $AC->CalculateVisibility($usuarioLogueado->id, $rolUsuarioLogueado->id ?? 0, false, $visibilidad['roles'] ?? [], $visibilidad['grupos'] ?? [], $visibilidad['canales'] ?? []);
            $NoHasAccessVisibility = (!$access &&  !in_array($usuarioLogueado->id, $visibilidad['users']?? []));

            if (!$NoHasAccessVisibility) {
                $data[] = $item;

                // validación por producto
                if (!empty($productos) && is_array($productos) && !empty($visibilidad['productos']) && is_array($visibilidad['productos'])) {
                    //var_dump($visibilidad);
                    foreach ($productos as $productoId) {
                        if (in_array($productoId, $visibilidad['productos'])) {
                            $item->pra = $visibilidad['productos'];
                            $data[] = $item;
                        }
                    }
                }
            }


        }
        return $this->ResponseSuccess('Descuentos obtenidos con éxito', $data);
    }

    public function ListadoFlujos(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['admin/descuentos'])) return $AC->NoAccess();

        $item = Productos::where('status', 1)->get();
        $item->makeHidden(['descripcion', 'token', 'extraData', 'imagenData']);
        return $this->ResponseSuccess('Descuentos obtenidos con éxito', $item);
    }

    public function GetDescuento(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['admin/descuentos'])) return $AC->NoAccess();

        $id = $request->get('id');

        $item = Descuento::where('id', $id)->first();

        if (empty($item)) {
            return $this->ResponseError('RPT-014', 'Error al obtener descuento');
        }

        $item->c = @json_decode($item->config);
        $item->makeHidden(['config']);
        $item->makeHidden(['mailconfig']);

        return $this->ResponseSuccess('Descuento obtenido con éxito', $item);
    }

    public function DeleteDescuento(Request $request) {
        $AC = new AuthController();
        if (!$AC->CheckAccess(['admin/descuentos'])) return $AC->NoAccess();

        $id = $request->get('id');
        try {
            $item = Descuento::find($id);

            if (!empty($item)) {
                $item->delete();
                return $this->ResponseSuccess('Eliminado con éxito', $item->id);
            }
            else {
                return $this->ResponseError('AUTH-R6321', 'Error al eliminar');
            }
        } catch (\Throwable $th) {
            var_dump($th->getMessage());
            return $this->ResponseError('AUTH-R6302', 'Error al eliminar');
        }
    }

}
