<?php
namespace App\Http\Controllers;

use app\core\Response;
use App\Models\Canales;
use App\Models\Cotizacion;
use App\Models\Segmentos;
use App\Models\Tareas;
use App\Models\cotizacionControlCalidad;
use App\Models\cotizacionControlCalidadNomen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ControlCalidadController extends Controller {

    use Response;



    /**
     * Get Steps
     * @param Request $request
     * @return array|false|string
     */
    public function getLeadChannels(Request $request) {

        try {
            $canales = Canales::orderBy('id', 'ASC')->get();
            if(!empty($canales)){
                return $this->ResponseSuccess( 'Ok', $canales);
            }
            else{
                return $this->ResponseSuccess( 'Ok', []);
            }

        } catch (\Throwable $th) {
            return $this->ResponseError('AUTH-AF6440F', 'Error al generar canales');
        }
    }

    public function GetFicha(Request $request) {

        // Resumen de tarea
        $cotizacionId = $request->get('token');
        $AC = new AuthController();

        $cotizacion = Cotizacion::where([['token', '=', $cotizacionId]])->first();

        if (empty($cotizacion)) {
            return $this->ResponseError('COT-632', 'Tarea no válida');
        }

        $producto = $cotizacion->producto;
        if (empty($producto)) {
            return $this->ResponseError('COT-600', 'Producto no válido');
        }

        $flujo = $producto->flujo->first();
        if (empty($flujo)) {
            return $this->ResponseError('COT-601', 'Flujo no válido');
        }

        $flujoConfig = @json_decode($flujo->flujo_config, true);
        if (!is_array($flujoConfig)) {
            return $this->ResponseError('COT-601', 'Error al interpretar flujo, por favor, contacte a su administrador');
        }

        $camposCoti = $cotizacion->campos;

        // Recorro campos para hacer resumen
        $resumen = [];
        foreach ($flujoConfig['nodes'] as $nodo) {
            //$resumen
            if (!empty($nodo['formulario']['secciones']) && count($nodo['formulario']['secciones']) > 0) {

                foreach ($nodo['formulario']['secciones'] as $keySeccion => $seccion) {

                    $resumen[$keySeccion]['nombre'] = $seccion['nombre'];

                    foreach ($seccion['campos'] as $keyCampo => $campo) {

                        /*var_dump($nodo['id']);
                        var_dump($seccion['nombre']);
                        var_dump($campo['id']);
                        var_dump($campo['visible']);*/

                        if (empty($campo['visible'])) {
                            if (!$AC->CheckAccess(['admin/show-hidden-fields'])) {
                                continue;
                            };
                        }
                        $campoTmp = $camposCoti->where('campo', $campo['id'])->first();
                        $resumen[$keySeccion]['campos'][$campo['id']] = ['value' => $campoTmp->valorLong ?? '', 'label' => $campo['nombre'], 'id' => $campo['id'], 't' => $campo['tipoCampo'],];
                    }
                }
            }
        }

        $items = cotizacionControlCalidad::where('cotizacionId', $request->get('id'))->with(['usuario' => function ($query) {
            $query->select('id', 'name');
        }, 'tipificacion'])->get();
        $nomenclatura = cotizacionControlCalidadNomen::all();

        $nomenclaturaArray = [];
        foreach ($nomenclatura as $key => $value) {
            $nomenclaturaArray[$value->principal][$value->id] = $value->secundaria;
        }

        return $this->ResponseSuccess( 'Ok', [
            'cToken' => $cotizacion->token,
            'ficha' => $items,
            'nomen' => $nomenclaturaArray,
            'resumen' => $resumen,
        ]);
    }

    public function Save(Request $request) {

        // Resumen de tarea
        $AC = new AuthController();

        $cotizacionId = $request->get('cotizacionId');
        $comentario = $request->get('comentario');
        $nomenclatura = $request->get('nomenclatura');
        $usuarioLogueado = auth('sanctum')->user();

        $controlCalidad = new cotizacionControlCalidad();
        $controlCalidad->cotizacionId = $cotizacionId;
        $controlCalidad->usuarioId = $usuarioLogueado->id;
        $controlCalidad->tipificacionId = $nomenclatura;
        $controlCalidad->comentario = $comentario;
        $controlCalidad->type = 'comment';
        $controlCalidad->save();


        return $this->ResponseSuccess( 'Comentario guardado con éxito',);
    }
    /**
     * Get Steps
     * @param Request $request
     * @return array|false|string
     */
    public function addLeadChannels(Request $request) {

        try {
            $validateForm = Validator::make($request->all(),
                [
                    'nombre' => 'required|string',
                    'correo' => 'required|email',
                    'telefono' => 'required',
                    'categoria' => '',
                    'contactoName' => 'required'
                ]);

            if ($validateForm->fails()) {
                return $this->ResponseError('AUTH-AF10dsF', 'Faltan Campos');
            }
            $canal = new Canales();
            $canal->nombre = $request->nombre??'';
            $canal->correo = $request->correo??'';
            $canal->telefono = $request->telefono??'';
            $canal->categoria = $request->categoria??'';
            $canal->contactoName = $request->contactoName??'';
            if($canal->save()){
                return $this->ResponseSuccess( 'Ok', $canal);
            }
            else{
                return $this->ResponseSuccess( 'Ok', []);
            }

        } catch (\Throwable $th) {
            return $this->ResponseError('AUTH-AF6440F', 'Error al generar canales');
        }
    }
    public function addLeadSegmento(Request $request) {

        try {
            $validateForm = Validator::make($request->all(),
                [
                    'nombre' => 'required|string',
                    'descripcion' => '',
                    'contactoName' => 'required'
                ]);

            if ($validateForm->fails()) {
                return $this->ResponseError('AUTH-AF10dsF', 'Faltan Campos');
            }
            $canal = new Segmentos();
            $canal->nombre = $request->nombre??'';
            $canal->correo = $request->correo??'';
            $canal->telefono = $request->telefono??'';
            $canal->categoria = $request->categoria??'';
            $canal->contactoName = $request->contactoName??'';
            if($canal->save()){
                return $this->ResponseSuccess( 'Ok', $canal);
            }
            else{
                return $this->ResponseSuccess( 'Ok', []);
            }

        } catch (\Throwable $th) {
            return $this->ResponseError('AUTH-AF6440F', 'Error al generar canales');
        }
    }
}
