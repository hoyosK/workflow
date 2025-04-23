<?php

namespace App\Http\Controllers;

use App\core\Response;
use App\Extra\ClassCache;
use App\Models\catLinea;
use App\Models\catMedioCobro;
use App\Models\catProductos;
use App\Models\catProductoTarifa;
use App\Models\catTarifas;
use App\Models\Cotizacion;
use App\Models\CotizacionSoporte;
use App\Models\CotizacionComentario;
use App\Models\CotizacionDetalle;
use App\Models\CotizacionDetalleVehiculo;
use App\Models\CotizacionDetalleVehiculoCotizacion;
use App\Models\CotizacionDetalleVehiculoCotizacionCobertura;
use App\Models\CotizacionBitacora;
use App\Models\CotizacionesUserNodo;
use App\Models\PdfTemplate;
use App\Models\Flujos;
use App\Models\Productos;
use App\Models\RecargaSiniestralidad;
use App\Models\Rol;
use App\Models\RolAccess;
use App\Models\RolApp;
use App\Models\SistemaVariable;
use App\Models\User;
use App\Models\OrdenAsignacion;
use App\Models\UserCanalGrupo;
use App\Models\UserGrupoRol;
use App\Models\UserGrupoUsuario;
use App\Models\UserRol;
use App\Models\UserCodigoAgente;
use App\Models\UserJerarquiaDetail;
use App\Models\catCodigoAgente;
use App\Models\Archivador;
use App\Models\ArchivadorDetalle;
use App\Models\DataMetodoPago;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Dflydev\DotAccessData\Data;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Mailgun\Exception\HttpClientException;
use Mailgun\Mailgun;
use Matrix\Exception;
use PhpOffice\PhpWord\TemplateProcessor;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

use App\Models\catClaseTarjeta;
use App\Models\catFormaPago;
use App\Models\catCoberturas;
use App\Models\catGrupoCoberturas;

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use App\Models\Descuento;
use Intervention\Image\ImageManager;


class TareaController extends Controller {

    use Response;

    public function clearMoney($value) {
        if (!empty($value)) {
            if (strpos($value, 'Q') !== -1) {
                return preg_replace("/[^0-9.]/", "", $value);
            }
            else {
                return $value;
            }
        }
        else {
            return 0;
        }
    }

    public function Load($rolId) {

        $item = Formulario::where([['id', '=', $rolId]])->with('seccion', 'seccion.campos', 'seccion.campos.archivadorDetalle', 'seccion.campos.archivadorDetalle.archivador')->first();

        if (!empty($item)) {

            $arrSecciones = $item->toArray();

            usort($arrSecciones['seccion'], function ($a, $b) {
                if ($a['orden'] > $b['orden']) {
                    return 1;
                }
                elseif ($a['orden'] < $b['orden']) {
                    return -1;
                }
                return 0;
            });

            return $this->ResponseSuccess('Ok', $arrSecciones);
        }
        else {
            return $this->ResponseError('Aplicación inválida');
        }
    }

    public function Save(Request $request) {

        $AC = new AuthController();
        //if (!$AC->CheckAccess(['users/role/admin'])) return $AC->NoAccess();

        $id = $request->get('id');
        $nombre = $request->get('nombre');
        $urlAmigable = $request->get('urlAmigable');
        $activo = $request->get('activo');

        $secciones = $request->get('campos');

        if (!empty($id)) {
            $item = Formulario::where([['id', '=', $id]])->first();
        }
        else {
            $item = new Formulario();
        }

        $activo = ($activo === 'true' || $activo === true) ? true : false;

        if (empty($item)) {
            return $this->ResponseError('APP-5412', 'Formulario no válido');
        }

        // valido url amigable
        $urlForm = Formulario::where([['urlAmigable', '=', $urlAmigable]])->first();
        if (!empty($urlForm) && !empty($item) && ($item->id !== $urlForm->id)) {
            return $this->ResponseError('APP-0412', 'La url amigable ya se encuentra en uso');
        }

        $item->nombre = $nombre;
        $item->urlAmigable = $urlAmigable;
        $item->activo = $activo;
        $item->save();

        // guardo secciones
        foreach ($secciones as $seccion) {
            //dd($seccion);

            if (!empty($seccion['id'])) {
                $seccionTmp = FormularioSeccion::where([['id', '=', $seccion['id']]])->first();
            }
            else {
                $seccionTmp = new FormularioSeccion();
            }

            if (empty($seccionTmp)) {
                return $this->ResponseError('APP-S5412', 'Sección inválida');
            }

            $seccionTmp->nombre = $seccion['nombre'] ?? 'Sin nombre de sección';
            $seccionTmp->formularioId = $item->id;
            $seccionTmp->orden = $seccion['orden'];
            $seccionTmp->save();

            // traigo todos los campos
            foreach ($seccion['campos'] as $campo) {

                if (empty($campo['id'])) {
                    $campoTmp = new FormularioDetalle();
                }
                else {
                    $campoTmp = FormularioDetalle::where('id', $campo['id'])->first();
                }

                $campoTmp->formularioId = $item->id;
                $campoTmp->seccionId = $seccionTmp->id;
                $campoTmp->archivadorDetalleId = $campo['archivadorDetalleId'];
                $campoTmp->nombre = $campo['nombre'];
                $campoTmp->layoutSizePc = $campo['layoutSizePc'] ?? 4;
                $campoTmp->layoutSizeMobile = $campo['layoutSizeMobile'] ?? 12;
                $campoTmp->cssClass = $campo['cssClass'] ?? '';
                $campoTmp->requerido = $campo['requerido'] ?? 0;
                $campoTmp->deshabilitado = $campo['deshabilitado'] ?? 0;
                $campoTmp->visible = $campo['visible'] ?? 1;
                $campoTmp->activo = $campo['activo'] ?? 1;

                $campoTmp->save();
            }
        }

        if (!empty($item)) {
            return $this->ResponseSuccess('Guardado con éxito', $item->id);
        }
        else {
            return $this->ResponseError('AUTH-RL934', 'Error al crear rol');
        }
    }

    public function Delete(Request $request) {

        $AC = new AuthController();
        //if (!$AC->CheckAccess(['users/role/admin'])) return $AC->NoAccess();

        $id = $request->get('id');
        try {
            $item = Formulario::find($id);

            if (!empty($item)) {
                $item->delete();
                return $this->ResponseSuccess('Eliminado con éxito', $item->id);
            }
            else {
                return $this->ResponseError('AUTH-R5321', 'Error al eliminar');
            }
        } catch (\Throwable $th) {
            var_dump($th->getMessage());
            return $this->ResponseError('AUTH-R5302', 'Error al eliminar');
        }
    }

    // Cotizaciones
    public function IniciarCotizacion(Request $request) {

        $productoToken = $request->get('token');
        $codigoAgente = $request->get('ca');
        $identificador = $request->get('identificador');
        $usuarioLogueado = auth('sanctum')->user();

        if (!empty($usuarioLogueado)) {
            $AC = new AuthController();
            if (!$AC->CheckAccess(['tareas/admin/start-cot'])) return $AC->NoAccess();
        }

        // traigo el producto
        $producto = Productos::where([['token', '=', $productoToken]])->first();

        if (empty($producto)) {
            return $this->ResponseError('COT-15', 'Producto inválido');
        }

        $flujo = $producto->flujo->first();
        if (empty($flujo)) {
            return $this->ResponseError('COT-611', 'Flujo no válido');
        }

        $flujoConfig = @json_decode($flujo->flujo_config, true);
        if (!is_array($flujoConfig)) {
            return $this->ResponseError('COT-610', 'Error al interpretar flujo, por favor, contacte a su administrador');
        }

        // Validación si el nodo es público
        $tipoForm = false;
        foreach ($flujoConfig['nodes'] as $nodo) {
            if (empty($nodo['typeObject'])) continue;

            // si es inicio
            if ($nodo['typeObject'] === 'start' && !empty($nodo['formulario']['tipo'])) {
                $tipoForm = $nodo['typeObject'];
            }
        }

        if (!$tipoForm) {
            return $this->ResponseError('COT-615', 'Error al iniciar cotización, el formulario se encuentra desconfigurado (flujo sin inicio)');
        }

        if ($tipoForm === 'privado' && !$usuarioLogueado) {
            return $this->ResponseError('COT-616', 'Error al iniciar cotización, el formulario no posee visibilidad pública');
        }

        $item = new Cotizacion();
        $item->usuarioId = $usuarioLogueado->id ?? 0;
        $item->usuarioIdAsignado = $usuarioLogueado->id ?? 0;
        $item->identificador = $identificador ?? null;
        $item->token = trim(bin2hex(random_bytes(18))).time();
        $item->estado = 'creada';
        $item->productoId = $producto->id;
        $item->codigoAgente = $codigoAgente ?? null;

        if ($item->save()) {
            $campo = CotizacionDetalle::where('cotizacionId', $item->id)->where('campo', 'ESTADO_ACTUAL')->first();
            if (empty($campo)) {
                $campo = new CotizacionDetalle();
            }
            $campo->cotizacionId = $item->id;
            $campo->seccionKey = 0;
            $campo->campo = 'ESTADO_ACTUAL';
            $campo->label = '';
            $campo->useForSearch = 0;
            $campo->tipo ='default';
            $campo->valorLong = $item->estado;
            $campo->save();

            $this->saveReplaceCustomVar($item->id, "SYS_USUARIO_CREADOR", $usuarioLogueado->id ?? 0);

            // TRAE EL NOMBRE DEL AGENTE
            $codigoAg = catCodigoAgente::where('codigo', $item->codigoAgente)->first();
            $this->saveReplaceCustomVar($item->id, "SYS_NOMBRE_AGENTE", $codigoAg->nombre ?? 'Agente sin nombre');

            $anioHoy = date('Y');
            $mesHoy = date('m');
            $diaHoy = date('d');
            $this->saveReplaceCustomVar($item->id, "SYS_COT_DAY", $diaHoy);
            $this->saveReplaceCustomVar($item->id, "SYS_COT_MONTH", $mesHoy);
            $this->saveReplaceCustomVar($item->id, "SYS_COT_YEAR", $anioHoy);
            $this->saveReplaceCustomVar($item->id, "CODIGO_AGENTE", $codigoAgente);

            return $this->ResponseSuccess('Tarea iniciada con éxito', ['token' => $item->token, 'id' => $item->id]);
        }
        else {
            return $this->ResponseError('COT-014', 'Error al iniciar tarea, por favor intente de nuevo');
        }
    }

    public function RevivirCotizacion(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['tareas/admin/revivir-cot'])) return $AC->NoAccess();

        $cotizacionToken = $request->get('token');
        $usuarioLogueado = auth('sanctum')->user();
        $usuarioLogueadoId = $usuarioLogueado->id ?? 0;

        // traigo la cotización
        $cotizacion = Cotizacion::where([['token', '=', $cotizacionToken]])->first();

        if (empty($cotizacion)) {
            return $this->ResponseError('COT-R10', 'Cotización inválida');
        }

        $producto = $cotizacion->producto;

        $revivirComportamiento = '';
        if (isset($producto->extraData) && $producto->extraData !== '') {
            $tmp = json_decode($producto->extraData, true);
            $revivirComportamiento = $tmp['revC'] ?? '';
        }

        $item = new Cotizacion();
        $item->usuarioId = $cotizacion->usuarioId ?? 0;
        $item->usuarioIdAsignado = ($usuarioLogueadoId) ? $usuarioLogueadoId : ($cotizacion->usuarioIdAsignado ?? 0);
        $item->token = trim(bin2hex(random_bytes(18))).time();
        $item->estado = 'creada';
        $item->productoId = $cotizacion->productoId;

        // Si hay que revivir desde el último nodo
        if ($revivirComportamiento === 'u') {
            $item->nodoActual = $cotizacion->nodoActual;
        }
        else if ($revivirComportamiento === 'i') { // nodo inicial
            $item->nodoActual = null;
        }
        else if ($revivirComportamiento === 'd') { // desactivado
            return $this->ResponseError('COT-R40', 'Revivir cotización desactivado');
        }
        else {
            return $this->ResponseError('COT-R41', 'Configuración para revivir cotización no seleccionada');
        }

        $item->save();
        $campo = CotizacionDetalle::where('cotizacionId', $item->id)->where('campo', 'ESTADO_ACTUAL')->first();
        if (empty($campo)) {
            $campo = new CotizacionDetalle();
        }
        $campo->cotizacionId = $item->id;
        $campo->seccionKey = 0;
        $campo->campo = 'ESTADO_ACTUAL';
        $campo->label = '';
        $campo->useForSearch = 0;
        $campo->tipo ='default';
        $campo->valorLong = $item->estado;
        $campo->save();

        $detalleAll = CotizacionDetalle::where('cotizacionId', $cotizacion->id)->get();
        foreach ($detalleAll as $detalle) {
            $newDetalle = $detalle->replicate();
            $newDetalle->cotizacionId = $item->id; // the new project_id
            $newDetalle->save();
        }

        if ($item->save()) {

            // Guardo la bitacora actual
            $bitacoraCoti = new CotizacionBitacora();
            $bitacoraCoti->cotizacionId = $item->id;
            $bitacoraCoti->usuarioId = $usuarioLogueado->id;
            $bitacoraCoti->log = "Cotización revivida por usuario \"{$usuarioLogueado->name}\", desde cotización No.{$cotizacion->id}";
            $bitacoraCoti->save();

            return $this->ResponseSuccess('Cotización revivida con éxito', ['token' => $item->token]);
        }
        else {
            return $this->ResponseError('COT-R11', 'Error al iniciar tarea, por favor intente de nuevo');
        }
    }

    // Cotizaciones
    public function GetCotizacion($cotizacionId) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['tareas/mis-tareas'])) return $AC->NoAccess();

        $usuarioLogueado = $usuario = auth('sanctum')->user();

        $item = Cotizacion::where([['id', '=', $cotizacionId], ['usuarioIdAsignado', '=', $usuarioLogueado->id]])->first();

        if (empty($item)) {
            return $this->ResponseError('COT-016', 'La tarea no existe o se encuentra asignada a otro usuario');
        }

        return $this->ResponseSuccess('Tarea obtenida con éxito', $item);
    }

    public function GetProductos(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['tareas/mis-tareas'])) return $AC->NoAccess();

        $filterSearch = $request->get('filterSearch');
        $productoId = $request->get('productoId');
        $estadoFilter = $request->get('estadoFilter');

        $fechaIni = $request->get('fechaIni');
        $fechaFin = $request->get('fechaFin');

        $currentPage = $request->get('currentPage') ?? 1;
        $perPage =  $request->get('perPage') ?? 20;

        $fechaIni = Carbon::parse($fechaIni);
        $fechaFin = Carbon::parse($fechaFin);
        $fechaIni = $fechaIni->toDateString()." 00:00:00";
        $fechaFin = $fechaFin->toDateString()." 23:59:59";

        $usuarioLogueado = auth('sanctum')->user();
        $userHandler = new AuthController();
        $rolUsuarioLogueado = ($usuarioLogueado) ? $usuarioLogueado->rolAsignacion->rol : 0;

        $etapas = [];

        // los productos del usuario
        $productosTmp = DB::table('productos')->get();
        //var_dump($productosTmp);

        $configFlujoEd = [];
        $configFlujo = [];
        foreach ($productosTmp as $producto) {

            $flujo = Flujos::Where('productoId', '=', $producto->id)->where('activo', '=', 1)->first();

            if (isset($producto->extraData) && $producto->extraData !== '') {
                $configFlujoEd[$producto->id] = json_decode($producto->extraData, true);
            }

            $configFlujo[$producto->id] = @json_decode($flujo->flujo_config, true);

            // etapas
            if(empty($configFlujo[$producto->id]) || empty($configFlujo[$producto->id]['nodes'])) continue;
            foreach ($configFlujo[$producto->id]['nodes'] as $node) {
                if ($node['typeObject'] === 'input' || $node['typeObject'] === 'review' || $node['typeObject'] === 'start') {
                    $etapas[$producto->id][$node['id']] = $node['nodoName'];
                }
            }
        }

        $productosTmp->map(function ($producto) use ($usuarioLogueado, $configFlujoEd, $configFlujo) {


            /*if (!empty($flujo)) {
                $producto->flujo = @json_decode($flujo->flujo_config, true);
                $producto->flujoId = $flujo->id;
            }*/
            $producto->flujo = $configFlujo[$producto->id];
            //$producto->flujoId = $configFlujo[$producto->id]->id;

            $producto->roles_assign = $configFlujoEd[$producto->id]['roles_assign'] ?? [];
            $producto->grupos_assign = $configFlujoEd[$producto->id]['grupos_assign'] ?? [];
            $producto->canales_assign = $configFlujoEd[$producto->id]['canales_assign'] ?? [];

            return $producto;
        });
        //var_dump($productosTmp);

        $authHandler = new AuthController();
        $productos = [];
        foreach ($productosTmp as $pr) {
            $access = $authHandler->CalculateVisibility($usuarioLogueado->id, $rolUsuarioLogueado->id ?? 0, false, $pr->roles_assign ?? [], $pr->grupos_assign ?? [], $pr->canales_assign ?? []);
            if (!$access) continue;
            $productos[] = [
                'id' => $pr->id,
                'token' => $pr->token,
                'nombreProducto' => $pr->nombreProducto,
            ];
        }

        return $this->ResponseSuccess('Productos obtenidos con éxito', ['p' => $productos, 'e' => $etapas]);
    }

    public function GetCotizaciones(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['tareas/mis-tareas'])) return $AC->NoAccess();

        $filterSearch = $request->get('filterSearch');
        $productoId = $request->get('productoId');
        $estadoFilter = $request->get('estadoFilter');

        $fechaIni = $request->get('fechaIni');
        $fechaFin = $request->get('fechaFin');


        $currentPage = $request->get('currentPage') ?? 1;
        $perPage = $request->get('perPage') ?? 5000000;

        $fechaIni = Carbon::parse($fechaIni);
        $fechaFin = Carbon::parse($fechaFin);
        $fechaIni = $fechaIni->toDateString()." 00:00:00";
        $fechaFin = $fechaFin->toDateString()." 23:59:59";

        $usuarioLogueado = auth('sanctum')->user();
        // dd($usuarioLogueado);

        $userHandler = new AuthController();
        $CalculateAccess = $userHandler->CalculateAccess();

        $items = Cotizacion::where([['dateCreated', '>=', $fechaIni], ['dateCreated', '<=', $fechaFin]])
            ->where(function($query) use ($CalculateAccess, $usuarioLogueado) {
                $query->whereIn('usuarioIdAsignado', $CalculateAccess['all']);
                $query->orWhere('usuarioId', $usuarioLogueado->id);
            });

        $resultadosEstado = Cotizacion::select(DB::raw('LOWER(estado) as estado'), DB::raw('count(*) as total'))
            ->groupBy(DB::raw('LOWER(estado)'))
            ->where([['dateCreated', '>=', $fechaIni], ['dateCreated', '<=', $fechaFin]])
            ->where(function($query) use ($CalculateAccess, $usuarioLogueado) {
                $query->whereIn('usuarioIdAsignado', $CalculateAccess['all']);
                $query->orWhere('usuarioId', $usuarioLogueado->id);
            });

        if (!empty($estadoFilter) && $estadoFilter !== '__all__') {
            $items->where('estado', $estadoFilter);
            $resultadosEstado->where('estado', $estadoFilter);
        }

        if (!empty($productoId)) {
            $items->where('productoId', $productoId);
            $resultadosEstado->where('productoId', $productoId);
        }

        if (!empty($filterSearch)) {
            $items->where(function ($query) use ($filterSearch){
                $query->where('id', $filterSearch)
                    ->orWhere('identificador', $filterSearch)
                    ->orWhereHas('usuarioAsignado', function ($subQuery) use ($filterSearch) {
                        $subQuery->where('name', 'LIKE', "%{$filterSearch}%");
                    })
                    ->orWhereHas('usuario', function ($subQuery) use ($filterSearch) {
                        $subQuery->where('name', 'LIKE', "%{$filterSearch}%");
                    })
                    ->orWhereHas('campos', function ($subQuery) use ($filterSearch) {
                        $subQuery->where('useForSearch', 1)->where('valorLong', 'LIKE', "%{$filterSearch}%");
                    })
                    ->orWhereHas('campos', function ($subQuery) use ($filterSearch) {
                        $subQuery->where('campo', 'EMISION_AS400.datosIdEmpresaGC.datos03.datosdePolizaGestorComercial.poliza')
                        ->where('valorLong', 'LIKE', "%{$filterSearch}%");
                    })
                    ->orWhereHas('cotizaciones', function ($subQuery) use ($filterSearch) {
                        $subQuery->where('numeroCotizacionAS400', 'LIKE', "%{$filterSearch}%");
                    })
                    ->orWhereHas('vehiculos', function ($subQuery) use ($filterSearch) {
                        $subQuery->where('modelo', 'LIKE', "%{$filterSearch}%")
                            ->orWhere('placa', 'LIKE', "%{$filterSearch}%")
                            ->orWhereHas('marca', function($ssubQuery) use ($filterSearch) {
                                $ssubQuery->where('nombre', 'LIKE', "%{$filterSearch}%");
                            })
                            ->orWhereHas('linea', function($ssubQuery) use ($filterSearch) {
                                $ssubQuery->where('nombre', 'LIKE', "%{$filterSearch}%");
                            });
                    });
            });

            $resultadosEstado->where(function ($query) use ($filterSearch){
                $query->where('id', $filterSearch)
                    ->orWhere('identificador', $filterSearch)
                    ->orWhereHas('usuarioAsignado', function ($subQuery) use ($filterSearch) {
                        $subQuery->where('name', 'LIKE', "%{$filterSearch}%");
                    })
                    ->orWhereHas('usuario', function ($subQuery) use ($filterSearch) {
                        $subQuery->where('name', 'LIKE', "%{$filterSearch}%");
                    })
                    ->orWhereHas('campos', function ($subQuery) use ($filterSearch) {
                        $subQuery->where('useForSearch', '1')->where('valorLong', 'LIKE', "%{$filterSearch}%");
                    })
                    ->orWhereHas('cotizaciones', function ($subQuery) use ($filterSearch) {
                        $subQuery->where('numeroCotizacionAS400', 'LIKE', "%{$filterSearch}%");
                    })
                    ->orWhereHas('vehiculos', function ($subQuery) use ($filterSearch) {
                        $subQuery->where('modelo', 'LIKE', "%{$filterSearch}%")
                            ->orWhere('placa', 'LIKE', "%{$filterSearch}%")
                            ->orWhereHas('marca', function($ssubQuery) use ($filterSearch) {
                                $ssubQuery->where('nombre', 'LIKE', "%{$filterSearch}%");
                            })
                            ->orWhereHas('linea', function($ssubQuery) use ($filterSearch) {
                                $ssubQuery->where('nombre', 'LIKE', "%{$filterSearch}%");
                            });
                    });
            });
        }

        $totalPages = ceil($items->count()/$perPage);
        if($currentPage > $totalPages) $currentPage = 1;
        $startIndex = ($currentPage - 1) * $perPage;

        $resultadosEstado = $resultadosEstado->get();

        $conteoEstados = [];
        foreach ($resultadosEstado as $key => $resultado) {
            $conteoEstados[$resultado->estado]['n'] = ucwords($resultado->estado);
            $conteoEstados[$resultado->estado]['c'] = $resultado-> total;
        }


        $items = $items
            ->with(['usuario', 'usuarioAsignado', 'producto', 'campos'])
            ->orderBy('id', 'DESC')
            ->skip($startIndex)
            ->take($perPage)
            ->get();

        $arrCache = [];

        foreach ($items as $key => $item) {
            if (!isset($arrCache[$item->productoId])) {

                $flujoConfig = $this->getFlujoFromCotizacion($item);

                if (!$flujoConfig['status']) {
                   // return $this->ResponseError($flujoConfig['error-code'], $flujoConfig['msg']);
                   continue;
                }
                else {
                    $flujoConfig = $flujoConfig['data'];
                }
                $arrCache[$item->productoId] = $flujoConfig;
            }
        }

        $cotizaciones = [];


        foreach ($items as $key => $item) {

            if (isset($arrCache[$item->productoId])) {
                $camposCoti = $item->campos->where('useForSearch', 1);
                $vehiculos = $item->vehiculos;
                $cotizacionesVeh = $item->cotizaciones;
                // campos
                $agenteAsignado = $item->usuarioAsignado->name ?? 'Sin usuario asignado';
                $usuario = $item->usuario->name ?? 'Usuario no disponible';
                $producto = $item->producto->nombreProducto ?? 'Flujo no especificado';

                $searchedOk = false;
                $resumen = [];
                $resumenVeh = [];

                // campos de búsqueda por defecto
                $camposDefault = [
                    ['l' => 'Id', 'v' => $item->id],
                    ['l' => 'A', 'v' => $agenteAsignado],
                    ['l' => 'U', 'v' => $usuario],
                    ['l' => 'PR', 'v' => $producto],
                ];
                $resumenCotizacionesVeh = [];
                $polizas = [];
                $camposPoli = $item->campos->where('campo', 'EMISION_AS400.datosIdEmpresaGC.datos03.datosdePolizaGestorComercial.poliza');

                foreach ($camposPoli as $tmp) {
                    if(!empty($tmp->valorLong)) {
                        $polizas[] = $tmp->valorLong;
                        $cotizacionesVehId = $tmp->cotizacionDetalleVehiculoCotId ?? 0;
                        $resumenCotizacionesVeh[$cotizacionesVehId]['poliza'] = $tmp->valorLong;
                    }
                }

                /*var_dump($camposCoti);
                die;*/

                foreach ($camposCoti as $tmp) {
                    $valorTmp = (!empty($tmp->valorShow) ? $tmp->valorShow : $tmp->valorLong);
                    // if(empty($tmp->label) || empty($valorTmp)) continue;
                    $resumen[] = [
                        'l' => $tmp->label ?? $tmp->campo,
                        'v' => $valorTmp,
                    ];
                }

                foreach($vehiculos as $veh){
                    $resumenVeh[$veh->id] = [
                        [
                            'l' => 'placa',
                            'v' => $veh->placa ?? 'PE-PENDIENTE',
                        ],
                        [
                            'l' => 'modelo',
                            'v' => $veh->modelo ?? 'Sin modelo',
                        ],
                        [
                            'l' => 'marca',
                            'v' => !empty($veh->marca)? $veh->marca->nombre : 'Sin marca',
                        ],
                        [
                            'l' => 'linea',
                            'v' => !empty($veh->linea)? $veh->linea->nombre : 'Sin linea',
                        ]
                    ];
                }
                $resumenCotVeh = [];
                foreach($cotizacionesVeh as $vehC){
                    if(!empty($vehC->numeroCotizacionAS400)) {
                        $resumenCotVeh[] = $vehC->numeroCotizacionAS400;
                        $resumenCotizacionesVeh[$vehC->id]['numeroCotizacionAS400'] = $vehC->numeroCotizacionAS400;
                        $resumenCotizacionesVeh[$vehC->id]['producto'] = !empty($vehC->producto)? $vehC->producto->nombre : 'Sin producto';
                    }
                }


                $cotizaciones['c'][$key]['id'] = $item->id;
                $cotizaciones['c'][$key]['identificador'] = $item->identificador;
                $cotizaciones['c'][$key]['dateCreated'] = Carbon::parse($item->dateCreated)->setTimezone('America/Guatemala')->toDateTimeString();
                $cotizaciones['c'][$key]['token'] = $item->token;
                $cotizaciones['c'][$key]['estado'] = $item->estado;
                $cotizaciones['c'][$key]['productoTk'] = $item->producto->token ?? '';
                $cotizaciones['c'][$key]['productoId'] = $item->productoId ?? '0';
                $cotizaciones['c'][$key]['producto'] = $producto;
                $cotizaciones['c'][$key]['usuario'] = $usuario;
                $cotizaciones['c'][$key]['usuarioAsignado'] = $agenteAsignado;
                $cotizaciones['c'][$key]['resumen'] = $resumen;
                $cotizaciones['c'][$key]['vehiculos'] = $resumenVeh;
                $cotizaciones['c'][$key]['cotizaciones'] = $resumenCotVeh;
                $cotizaciones['c'][$key]['polizas'] = $polizas;
                $cotizaciones['c'][$key]['resumenCotizacionesVeh'] = $resumenCotizacionesVeh;
                $cotizaciones['c'][$key]['expireAt'] = (!empty($item->dateExpire)) ? Carbon::parse($item->dateExpire)->format('d-m-Y') : 'No expira';
            }
        }

        $cotizaciones['e'] = $conteoEstados;

        $cotizaciones['totalPages'] = $totalPages;
        $cotizaciones['currentPage'] = $currentPage;


        if (empty($items)) {
            return $this->ResponseError('COT-016', 'Tarea inválida');
        }

        return $this->ResponseSuccess('Tareas obtenidas con éxito', $cotizaciones);
    }

    public function GetCotizacionesV2(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['tareas/mis-tareas'])) return $AC->NoAccess();

        $filterSearch = $request->get('filterSearch');
        $productoId = $request->get('productoId');
        $etapaNodoId = $request->get('etapaNodoId');
        $estadoFilter = $request->get('estadoFilter');

        $fechaIni = $request->get('fechaIni');
        $fechaFin = $request->get('fechaFin');

        $fechaIni = Carbon::parse($fechaIni);
        $fechaFin = Carbon::parse($fechaFin);
        $fechaIni = $fechaIni->toDateString()." 00:00:00";
        $fechaFin = $fechaFin->toDateString()." 23:59:59";

        /*$usuarioLogueado = auth('sanctum')->user();
        $rolUsuarioLogueado = ($usuarioLogueado) ? $usuarioLogueado->rolAsignacion->rol : 0;*/
        // dd($usuarioLogueado);



        /*var_dump($productos);
        die();*/

        $userHandler = new AuthController();
        $CalculateAccess = $userHandler->CalculateAccess();

        $items = Cotizacion::where([['dateCreated', '>=', $fechaIni], ['dateCreated', '<=', $fechaFin]])->whereIn('usuarioIdAsignado', $CalculateAccess['all']);

        if (!empty($estadoFilter) && $estadoFilter !== '__all__') {
            $items->where('estado', $estadoFilter);
        }

        if (!empty($etapaNodoId)) {
            $items->where('nodoActual', $etapaNodoId);
        }
        if (!empty($productoId)) {
            $items->where('productoId', $productoId);
        }
        else if (!empty($filterSearch)) {
            $items->orWhere('id', $filterSearch);
        }

        $items = $items->with(['usuario', 'usuarioAsignado', 'producto', 'campos'])->limit(1500)->orderBy('id', 'DESC')->get();

        $arrCache = [];
        $arrCacheResumen = [];
        $arrProductos = [];

        foreach ($items as $key => $item) {
            if (!isset($arrCache[$item->productoId])) {

                $flujoConfig = $this->getFlujoFromCotizacion($item);

                if (!$flujoConfig['status']) {
                    // return $this->ResponseError($flujoConfig['error-code'], $flujoConfig['msg']);
                    continue;
                }
                else {
                    $flujoConfig = $flujoConfig['data'];
                }
                $arrCache[$item->productoId] = $flujoConfig;

                $arrCacheResumen[$item->productoId] = [
                    '_id' => [ 'l' => 'No.', 'id' => '_id' ],
                    '_dateCreated' => [ 'l' => 'Fecha creación', 'id' => '_dateCreated' ],
                    '_estado' => [ 'l' => 'Estado', 'id' => '_estado' ],
                    '_ag_asig' => [ 'l' => 'Agente asignado', 'id' => '_ag_asig' ],
                    '_creado_p' => [ 'l' => 'Creado Por', 'id' => '_creado_p' ],
                ];

                foreach ($flujoConfig['nodes'] as $node) {
                    foreach ($node['formulario']['secciones'] as $seccion) {
                        foreach ($seccion['campos'] as $campo) {
                            if (!empty($campo['showInReports'])) {
                                $arrCacheResumen[$item->productoId][$campo['id']] = [
                                    'id' => $campo['id'],
                                    'l' => $campo['nombre'],
                                ];
                            }
                        }
                    }
                }

                $arrProductos[$item->productoId] = $item->producto->nombreProducto;
            }
        }

        // var_dump($arrProductos);

        // die;

        $cotizaciones = [];
        // $cotizaciones['p'] = $productos;

        $conteoEstados = [];
        $resumenH = [];

        foreach ($items as $key => $item) {

            $estado = (!empty($item->estado)) ? $item->estado : 'sin estado';
            if (!isset($conteoEstados[$estado])) {
                $conteoEstados[$estado]['n'] = ucwords($estado);
                $conteoEstados[$estado]['c'] = 1;
            }
            else {
                $conteoEstados[$estado]['c']++;
            }

            if (isset($arrCache[$item->productoId])) {
                //$camposCoti = CotizacionDetalle::where('cotizacionId', $item->id)->where('useForSearch', 1)->get();
                $camposCoti = $item->campos->where('useForSearch', 1);

                // campos
                $agenteAsignado = $item->usuarioAsignado->name ?? 'Sin usuario asignado';
                $usuario = $item->usuario->name ?? 'Usuario no disponible';
                $producto = $item->producto->nombreProducto ?? 'Flujo no especificado';

                $searchedOk = false;
                $resumen = [
                    '_id' => $item->id,
                    '_dateCreated' => $item->dateCreated,
                    '_estado' => $item->estado,
                    '_ag_asig' => $agenteAsignado,
                    '_creado_p' => $usuario,
                ];

                if (!empty($arrCacheResumen[$item->productoId])) {
                    foreach ($arrCacheResumen[$item->productoId] as $cache) {

                        if ($cache['id'] === '_id' || $cache['id'] === '_dateCreated' || $cache['id'] === '_estado' || $cache['id'] === '_ag_asig' || $cache['id'] === '_creado_p') continue;

                        $valorTmp = '';
                        foreach ($camposCoti as $tmp) {
                            if ($tmp->campo === $cache['id']) {
                                $valorTmp = (!empty($tmp->valorShow) ? $tmp->valorShow : $tmp->valorLong);
                            }
                        }
                        $resumen[$cache['id']] = $valorTmp;
                    }
                }

                if (!empty($filterSearch)) {
                    if (!$searchedOk) {
                        foreach ($resumen as $tmp) {
                            if (str_contains(strtolower($tmp), strtolower($filterSearch))) {
                                $searchedOk = true;
                            }
                        }
                    }
                    if (!$searchedOk) continue;
                }

                $cotizaciones['c'][$item->productoId][$key] = $resumen;
            }
        }

        $cotizaciones['p'] = $arrProductos;
        $cotizaciones['h'] = $arrCacheResumen;
        $cotizaciones['e'] = $conteoEstados;

        if (empty($items)) {
            return $this->ResponseError('COT-016', 'Tarea inválida');
        }

        return $this->ResponseSuccess('Tareas obtenidas con éxito', $cotizaciones);
    }

    public function GetCotizacionesFastCount(Request $request, $noJson = false) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['tareas/mis-tareas'])) return $AC->NoAccess();

        $fechaIni = Carbon::now()->subDays(5);
        $fechaFin = Carbon::now();

        $fechaIni = $fechaIni->toDateString()." 00:00:00";
        $fechaFin = $fechaFin->toDateString()." 23:59:59";

        $usuarioLogueado = auth('sanctum')->user();

        $items = Cotizacion::where([['usuarioIdAsignado', '=', $usuarioLogueado->id], ['dateCreated', '>=', $fechaIni], ['dateCreated', '<=', $fechaFin]]);
        $items = $items->with(['usuario', 'usuarioAsignado', 'producto', 'campos'])->limit(10)->orderBy('id', 'DESC')->get();

        $cotizaciones = [];
        $conteoEstados = [];

        foreach ($items as $key => $item) {

            $estado = (!empty($item->estado)) ? $item->estado : 'sin estado';
            if (!isset($conteoEstados[$estado])) {
                $conteoEstados[$estado]['n'] = ucwords($estado);
                $conteoEstados[$estado]['c'] = 1;
            }
            else {
                $conteoEstados[$estado]['c']++;
            }

            $cotizaciones['c'][$key]['id'] = $item->id;
            $cotizaciones['c'][$key]['dateCreated'] = $item->dateCreated;
            $cotizaciones['c'][$key]['token'] = $item->token;
            $cotizaciones['c'][$key]['estado'] = $item->estado;
            $cotizaciones['c'][$key]['productoId'] = $item->productoId ?? '0';
            $cotizaciones['c'][$key]['productoTk'] = $item->producto->token ?? '';
            $cotizaciones['c'][$key]['producto'] = $item->producto->nombreProducto ?? 'Producto no especificado';
            $cotizaciones['c'][$key]['usuario'] = $item->usuario->name ?? '';
            $cotizaciones['c'][$key]['usuarioAsignado'] = $item->usuarioAsignado->name ?? '';
            $cotizaciones['c'][$key]['expireAt'] = (!empty($item->dateExpire)) ? Carbon::parse($item->dateExpire)->format('d-m-Y') : 'No expira';
        }

        $cotizaciones['e'] = $conteoEstados;

        // conteo por productos
        $strQueryFull = "SELECT COUNT(C.id) as c, P.nombreProducto as p, P.id as pid
                        FROM cotizaciones AS C
                        JOIN productos AS P ON C.productoId = P.id
                        WHERE 
                            C.usuarioIdAsignado = '{$usuarioLogueado->id}'
                            AND C.dateCreated >= '{$fechaIni}'
                            AND C.dateCreated <= '{$fechaFin}'
                        AND P.status = 1
                        GROUP BY P.nombreProducto, P.id";

        $cotizaciones['pc'] = DB::select(DB::raw($strQueryFull));

        $cotizaciones['l'] = SistemaVariable::where('slug', 'LINK_AYUDA')->first() ?? '';

        if ($noJson) {
            return$cotizaciones;
        }

        if (empty($items)) {
            return $this->ResponseError('COT-016', 'Tarea inválida');
        }

        return $this->ResponseSuccess('Tareas obtenidas con éxito', $cotizaciones);
    }

    public function GetCotizacionResumen(Request $request, $returnArray = false) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['tareas/mis-tareas'])) return $AC->NoAccess();

        $usuarioLogueado = $usuario = auth('sanctum')->user();
        $cotizacionId = $request->get('token');

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

        $camposAllTmp = $cotizacion->campos;
        $camposAll = [];
        foreach ($camposAllTmp as $field) {
            $camposAll[$field->campo] = $field;
        }

        $showHiddenFields = $AC->CheckAccess(['admin/show-hidden-fields']);

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
                            if (!$showHiddenFields) {
                                continue;
                            };
                        }

                        $campoTmp = $camposAll[$campo['id']] ?? false;

                        if ($returnArray) {
                            $resumen[$keySeccion]['campos'][$campo['id']] = ['value' => $campoTmp->valorLong ?? '', 'label' => $campo['nombre'], 'id' => $campo['id'], 't' => $campo['tipoCampo'],];
                        }
                        else {
                            if (!empty($campoTmp->valorLong)) {
                                $resumen[$keySeccion]['campos'][$campo['id']] = ['value' => $campoTmp->valorLong ?? '', 'label' => $campo['nombre'], 'id' => $campo['id'], 't' => $campo['tipoCampo'],];
                            }
                        }
                    }
                }
            }
        }

        if ($returnArray) {
            return $resumen;
        }
        else {
            return $this->ResponseSuccess('Resumen generado con éxito', $resumen);
        }
    }


    public function CambiarUsuarioCotizacion(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['tareas/admin/usuario-asignado'])) return $AC->NoAccess();

        $usuario = $request->get('usuarioId');
        $cotizacionId = $request->get('token');
        $usuarioLogueado = auth('sanctum')->user();

        $item = Cotizacion::where([['token', '=', $cotizacionId]])->first();

        if (empty($item)) {
            return $this->ResponseError('COT-015', 'Tarea inválida');
        }

        $usuarioDetail = User::find($usuario);

        // Cambio el estado al nodo actual
        $item->usuarioIdAsignado = $usuario;
        $item->save();

        // Guardo la bitacora actual
        $bitacoraCoti = new CotizacionBitacora();
        $bitacoraCoti->cotizacionId = $item->id;
        $bitacoraCoti->usuarioId = $usuarioLogueado->id;
        $bitacoraCoti->log = "Editado usuario asignado por \"{$usuarioLogueado->name}\", asignado: {$usuarioDetail->name}";
        $bitacoraCoti->save();

        if ($item->save()) {
            return $this->ResponseSuccess('Usuario actualizada con éxito', ['id' => $item->id]);
        }
        else {
            return $this->ResponseError('COT-016', 'Error al actualizar tarea, por favor intente de nuevo');
        }
    }

    public function EditarEstadoCotizacion(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['tareas/admin/usuario-asignado'])) return $AC->NoAccess();

        $estado = $request->get('estado');
        $cotizacionId = $request->get('token');
        $usuarioLogueado = auth('sanctum')->user();

        $item = Cotizacion::where([['token', '=', $cotizacionId]])->first();

        if (empty($item)) {
            return $this->ResponseError('COT-015', 'Tarea inválida');
        }

        // Cambio el estado al nodo actual
        $item->estado = $estado;
        $item->save();

        $campo = CotizacionDetalle::where('cotizacionId', $item->id)->where('campo', 'ESTADO_ACTUAL')->first();
        if (empty($campo)) {
            $campo = new CotizacionDetalle();
        }
        $campo->cotizacionId = $item->id;
        $campo->seccionKey = 0;
        $campo->campo = 'ESTADO_ACTUAL';
        $campo->label = '';
        $campo->useForSearch = 0;
        $campo->tipo ='default';
        $campo->valorLong = $item->estado;
        $campo->save();

        // Guardo la bitacora actual
        $bitacoraCoti = new CotizacionBitacora();
        $bitacoraCoti->cotizacionId = $item->id;
        $bitacoraCoti->usuarioId = $usuarioLogueado->id;
        $bitacoraCoti->log = "Editado estado de cotización, usuario: \"{$usuarioLogueado->name}\", asignado estado: {$estado}";
        $bitacoraCoti->save();

        if ($item->save()) {
            return $this->ResponseSuccess('Estado editado con éxito', ['id' => $item->id]);
        }
        else {
            return $this->ResponseError('COT-016', 'Error al actualizar cotización, por favor intente de nuevo');
        }
    }

    public function getCotizacionLink($tokenPr, $tokenCot) {
        return env('APP_URL') . '#/f/' . $tokenPr . '/' . $tokenCot;
    }

    public function getConstantVars($cotizacion) {
        $campos = [];
        $tmpUser = User::where('id', $cotizacion->usuarioId)->first();
        $rolUser = UserRol::where('userId', $cotizacion->usuarioId)->first();
        $rol = null;
        if(!empty($rolUser)) $rol = Rol::where('id',  $rolUser -> rolId)->first();

        //Tiendas
        $tiendas = $tmpUser->tiendas ?? [];
        $tiendasNombre = [];
        $tiendasId = [];
        foreach($tiendas as $tienda){
            $dataTienda = $tienda->tienda;
            if(!empty($dataTienda)) {
                $tiendasNombre[] = $dataTienda->nombre ?? '';
                $tiendasId[] = $dataTienda->id;
            }
        }

        //Distribuidor y canales
        $gruposNombre = [];
        $gruposForUser = $tmpUser->grupos ?? [];
        $gruposForRol = $rol->grupos ?? [];

        $canalesNombre = [];
        $canalesNombreCod = [];
        $gruposId = [];
        foreach($gruposForUser as $group){
            $dataGrupo = $group->grupo;
            if(!empty($dataGrupo)) {
                $gruposNombre[] = $dataGrupo->nombre ?? '';
                $gruposId[] = $dataGrupo->id;
            };
        }

        foreach($gruposForRol as $group){
            $dataGrupo = $group->grupo;
            if(!empty($dataGrupo)) {
                $gruposNombre[] = $dataGrupo->nombre ?? '';
                $gruposId[] = $dataGrupo->id;
            };

            $canales = $dataGrupo->canales ?? [];
            foreach($canales as $canal){
                $dataCanal = $canal->canal;
                if(!empty($dataCanal)) {
                    $canalesNombre[] = $dataCanal->nombre ?? '';
                    $canalesNombreCod[$dataCanal->codigoInterno] = $dataCanal->codigoInterno ?? '';
                }
            }
        }

        $ejecutivos = $this->CalculateEjecutivo($tmpUser->id ?? 0, $rol->id ?? 0, $gruposId, $tiendasId);

        $campos['FECHA_COTIZACION'] = $cotizacion->dateCreated;
        $campos['FECHA_HOY'] = Carbon::now()->toDateTimeString();
        $campos['ID_COTIZACION'] = $cotizacion->id;
        $campos['HOY_SUM_1_YEAR'] = Carbon::now()->addYear()->toDateTimeString();
        $campos['HOY_SUM_1_YEAR_F1'] = Carbon::now()->addYear()->format('d/m/Y');
        $campos['CREADOR_NOMBRE'] = (!empty($tmpUser) ? $tmpUser->name : 'Sin nombre');
        $campos['CREADOR_CORP'] = (!empty($tmpUser) ? $tmpUser->corporativo : 'Sin corporativo');
        $campos['CODIGO_AGENTE'] = $cotizacion->codigoAgente;
        $campos['CREADOR_NOMBRE_USUARIO'] = (!empty($tmpUser) ? $tmpUser->nombreUsuario : 'Sin nombre');
        $campos['CREADOR_ROL'] = (!empty($rol) ? $rol->name : 'Sin rol');
        $campos['CREADOR_CANAL'] = (count($canalesNombre) > 0 ? implode(', ', $canalesNombre) : 'Sin distribuidor');
        $campos['CREADOR_CANAL_CODIGO_INTERNO'] = (count($canalesNombreCod) > 0 ? implode(', ', $canalesNombreCod) : 'Sin codigo de canal');
        $campos['CREADOR_DISTRIBUIDOR'] = (count($gruposNombre) > 0 ? implode(', ', $gruposNombre) : 'Sin distribuidor');
        $campos['CREADOR_TIENDA'] = (count($tiendasNombre) > 0 ? implode(', ', $tiendasNombre) : 'Sin tienda');
        $campos['CREADOR_EJECUTIVO'] = (count($ejecutivos) > 0 ? implode(', ', $ejecutivos) : 'Sin Ejecutivo');
        return $campos;
    }

    public function CambiarEstadoCotizacionPublic(Request $request) {
        return $this->CambiarEstadoCotizacion($request, false, false, false, true);
    }

    public function CambiarEstadoCotizacion(Request $request, $recursivo = false, $desdeDecision = false, $originalStep = false, $public = false) {

        $campos = $request->get('campos');
        $paso = $request->get('paso');
        $estado = $request->get('estado');
        $token = $request->get('token');
        $seccionKey = $request->get('seccionKey');
        $comentarioRechazo = $request->get('rG');
        $usuarioLogueado = auth('sanctum')->user();
        $usuarioLogueadoId = ($usuarioLogueado) ? $usuarioLogueado->id : 0;
        $vehiculosCot = $request->get('vehiculosCot');
        $vehiculoIdAgrupadorNodo = $request->get('vehiculoIdAgrupadorNodo');

        if (!empty($usuarioLogueadoId)) {
            $AC = new AuthController();
            if (!$AC->CheckAccess(['tareas/admin/cambio-paso'])) return $AC->NoAccess();
        }

        // Actual
        $userHandler = new AuthController();
        $CalculateAccess = $userHandler->CalculateAccess();

        // si es supervisor
        $arrUsers = false;
        if (in_array($usuarioLogueadoId, $CalculateAccess['sup'])) {
            $arrUsers = $CalculateAccess['all'];
        }
        else {
            $arrUsers = $CalculateAccess['det'];
        }

        $item = Cotizacion::where([['token', '=', $token]])->first();

        if (empty($item)) {
            return $this->ResponseError('COT-015', 'Tarea inválida');
        }

        if ($item->siniesBlock === 1) {
            return $this->ResponseError('COT-SINIESPEN', 'La aprobación por parte de soporte aún se encuentra pendiente');
        }

        // cambio de estado a cancelada
        if (!empty($estado)) {
            $item->estado = $estado;
            $item->save();

            $campo = CotizacionDetalle::where('cotizacionId', $item->id)->where('campo', 'ESTADO_ACTUAL')->first();
            if (empty($campo)) {
                $campo = new CotizacionDetalle();
            }
            $campo->cotizacionId = $item->id;
            $campo->seccionKey = $seccionKey;
            $campo->campo = 'ESTADO_ACTUAL';
            $campo->label = '';
            $campo->useForSearch = 0;
            $campo->tipo ='default';
            $campo->valorLong = $estado;
            $campo->save();

            return $this->ResponseSuccess('Estado actualizado con éxito');
        }

        // Recorro campos para tener sus datos de configuración
        $flujoConfig = $this->getFlujoFromCotizacion($item);
        $fieldsData = [];
        if (!empty($flujoConfig['data']['nodes'])) {
            foreach ($flujoConfig['data']['nodes'] as $nodo) {
                //$resumen
                if (!empty($nodo['formulario']['secciones']) && count($nodo['formulario']['secciones']) > 0) {
                    foreach ($nodo['formulario']['secciones'] as $keySeccion => $seccion) {
                        foreach ($seccion['campos'] as $keyCampo => $campo) {
                            $fieldsData[$campo['id']] = $campo;
                        }
                    }
                }
            }
        }

        $flujo = $this->CalcularPasos($request, true, $public, true);

        if (empty($flujo['actual']['nodoId'])) {
            return $this->ResponseError('COT-010', 'Hubo un error al calcular el flujo, por favor intente de nuevo');
        }

        // Cambio el estado al nodo actual
        if (!empty($flujo['actual']['estOut']) && ($flujo['actual']['estIo'] === 's')) $item->estado = $flujo['actual']['estOut'];

        //Calculate data
        $camposAllTmp = $item->campos;

        $nodosForTrajectory = ['start', 'input', 'review', 'contrast', 'output', 'vehiculo', 'vehiculo_comp', 'pagador'];
        $trajectory = empty($item->trajectory)? [] : @json_decode($item->trajectory, true);
        if(in_array($flujo['actual']['typeObject'], $nodosForTrajectory)
            && $paso === 'next'
            && empty($flujo['actual']['saltoAutomatico'])
            && end($trajectory) !== $flujo['actual']['nodoId']
        ){
            $trajectory[] = $flujo['actual']['nodoId'];
            $item->trajectory = json_encode($trajectory, JSON_FORCE_OBJECT);
            $item->save();
        }
        $verifyIsForGroupVehicle = ($flujo['actual']['gVh'] === 'a') && !empty($vehiculoIdAgrupadorNodo);
        // Si se está saliendo de un rechazo
        if ($paso === 'next' && $flujo['actual']['typeObject'] === 'review') {
            if ($desdeDecision) {
                return $this->ResponseSuccess('Tarea actualizada con éxito', $flujo);
            }
            else {
                $rechazo = false;
                $camposTmp = [];
                foreach ($campos as $campoKey => $valor) {
                    if (!empty($valor['r'])) {
                        $rechazo = true;
                        $camposTmp[$campoKey] = true;
                    }
                }

                if ($rechazo) {

                    $rechazoTmp = @json_decode($item->rechazoData, true);
                    if (is_array($rechazoTmp)) {
                        $tmp = [];
                        $tmp['f'] = $camposTmp;
                        $tmp['c'] = $comentarioRechazo;
                        $tmp['d'] = Carbon::now()->format('d-m-Y H:i');
                        $rechazoTmp[$flujo['actual']['nodoId']][] = $tmp;
                    }
                    else {
                        $rechazoTmp = [];
                        $tmp = [];
                        $tmp['f'] = $camposTmp;
                        $tmp['c'] = $comentarioRechazo;
                        $tmp['d'] = Carbon::now()->format('d-m-Y H:i');
                        $rechazoTmp[$flujo['actual']['nodoId']][] = $tmp;
                    }

                    $item->ultRechazo = $flujo['actual']['nodoId'];
                    $item->rechazoData = json_encode($rechazoTmp);
                    $item->save();

                    // si se rechazó, lo devuelvo
                    $flujoConfig = $this->getFlujoFromCotizacion($item);
                    $flujoConfig = $flujoConfig['data'];

                    $nodoRegresar = false;
                    foreach ($flujoConfig['nodes'] as $key => $nodo) {
                        //var_dump($nodo);
                        if (empty($nodo['typeObject'])) continue;

                        foreach ($nodo['formulario']['secciones'] as $seccion) {
                            foreach ($seccion['campos'] as $campo) {

                                // si encuentra el campo, lo regresa
                                if (isset($camposTmp[$campo['id']])) {

                                    // voy a traer el usuario que operó el nodo
                                    $userNodo = CotizacionesUserNodo::where('cotizacionId', $item->id)->where('nodoId', $nodo['id'])->orderBy('createdAt', 'DESC')->first();

                                    $nodoRegresar = $nodo['id'];
                                    $item->nodoActual = $nodoRegresar;
                                    $item->nodoPrevio = $flujo['actual']['nodoId'];
                                    $item->usuarioIdAsignado = $userNodo->usuarioId ?? 0;
                                    if (!empty($nodo['estOut']) && ($nodo['estIo'] === 'e')) $item->estado = $nodo['estOut'];
                                    $item->save();

                                    // Guardo la bitacora actual
                                    if (!empty($userNodo->usuarioId)) {
                                        $bitacoraCoti = new CotizacionBitacora();
                                        $bitacoraCoti->cotizacionId = $item->id;
                                        $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                                        $bitacoraCoti->log = "Asignado usuario automático por rechazo de campos \"{$flujo['actual']['label']}\"";
                                        $bitacoraCoti->save();
                                    }


                                    return $this->ResponseSuccess('Tarea actualizada con éxito', $flujo);
                                }
                                if ($nodoRegresar) break;
                            }
                            if ($nodoRegresar) break;
                        }
                    }
                }
            }
            // quita los campos para que en los rechazos no se guarde nada
            $campos = false;
        }

        //Si esta saliendo de un nodo pagador
        if ($paso === 'next' && $flujo['actual']['typeObject'] === 'pagador') {

            $camposAllTmp = $item->campos;
            $camposAllLevelTarea = [];

            foreach ($camposAllTmp as $field) {
                $newField = $field->toArray();

                if(!empty($newField['cotizacionDetalleVehiculoCotId']) && isset($ordenCotizacionVehiculosCotizaciones[$newField['cotizacionDetalleVehiculoCotId']])){
                    $newField['campo'] = 'cot' . ($ordenCotizacionVehiculosCotizaciones[$newField['cotizacionDetalleVehiculoCotId']]+1) . '|' . $newField['campo'];
                }

                if(!empty($newField['cotizacionVehiculoId']) && isset($ordenCotizacionVehiculos[$newField['cotizacionVehiculoId']])){
                    $newField['campo'] = 'veh' . ($ordenCotizacionVehiculos[$newField['cotizacionVehiculoId']]+1) . '|' . $newField['campo'];
                }
                $camposAllLevelTarea[$newField['campo']] = $newField;
            }

            $sysCotRec = $camposAllLevelTarea['SYS_COT_REC'] ?? '';
            if(empty($sysCotRec)) return $this->ResponseError('COTW-010', 'Antes de proceder con el pago, debe verificar siniestralidad');


            $conditionsCot = [
                '02' => ['numCuentaTarjeta', 'bancoEmisor', 'tipoCuentaBancarias'],
                '03' => ['numCuentaTarjeta', 'tipoCuentaTarjeta', 'claseTarjeta','venciTarjeta', 'bancoEmisor', 'nombreTarjeta'],
            ];

            $errorsPagador = [];
            $sucessPagador = [];
            if(!empty($vehiculosCot)){
                $countVehi = 1;
                foreach($vehiculosCot as $vehiculoId => $vehiculo){
                    //Calculo la data por vehiculo
                    $camposAllVehi = [];
                    foreach ($camposAllTmp as $field) {
                        $field = $field->toArray();
                        if(empty($field['cotizacionVehiculoId']) ||
                        $field['cotizacionVehiculoId'] == $vehiculoId ||
                        $field['cotizacionVehiculoId'] == 0 ) $camposAllVehi[] = $field;
                    }

                    $countCoti = 1;
                    foreach($vehiculo['c'] as $cotId => $cot){
                        //Calculo la data por cotizacion
                        $camposAllCoti = [];
                        foreach ($camposAllVehi as $field) {
                            if(empty($field['cotizacionDetalleVehiculoCotId']) ||
                            $field['cotizacionDetalleVehiculoCotId'] == $cotId ||
                            $field['cotizacionDetalleVehiculoCotId'] == 0 ) $camposAllCoti[$field['campo']] = $field;
                        }
                        //Verificacion de que exista la data
                        if(empty($cot['emitirPoliza'])) continue;
                        if(empty($cot['medioCobro'])){
                            $errorsPagador[] = "Faltan Datos de pago en la cotización {$cotId}";
                            continue;
                        }

                        $medioCobro = catMedioCobro::where('codigo', $cot['medioCobro'])->first();
                        $this->saveReplaceCustomVar($item->id, "medio_cobro", $medioCobro->nombre ?? 'N/D', $vehiculoId, $countVehi, $countCoti, $cotId);

                        /*if(($cot['medioCobro'] === '02' || $cot['medioCobro'] === '03') && count(array_filter(
                            array_map(function($type) use ($cot){ return !empty($cot[$type]);}, $conditionsCot[$cot['medioCobro']]),
                            function($datapago) {
                                return empty($datapago);
                        })) > 0){
                            $errorsPagador[] = "Faltan Datos de pago en la cotización {$cotId}";
                            continue;
                        }*/
                        //datos de pago guarda
                        $idCorrelativo = (int) $cot['idCorrelativo'];
                        $listaDePagos = $cot['COTIZACION_AS400']['datosIdEmpresaGC']['datos03']['datosCotizacionGestorComercial2']['listaFrecuenciaPagos']['listaPagos'];
                        $findCorrelativo = '';
                        if(is_array($listaDePagos) && empty($listaDePagos['listaPagosMensual'])){
                            $findCorrelativo = '.' . strval($idCorrelativo-1);
                        }

                        $monto = $camposAllCoti["COTIZACION_AS400.datosIdEmpresaGC.datos03.datosCotizacionGestorComercial2.listaFrecuenciaPagos.listaPagos{$findCorrelativo}.listaPagosMensual.primaTotalMensual"];

                        $tmpdbpago = DataMetodoPago::where('cotizacionesDetalleVehiculoCotId', (int) $cotId)->first();
                        if(empty($tmpdbpago)) $tmpdbpago = new DataMetodoPago();
                        if(!empty($tmpdbpago->autorizacion)) {
                            $sucessPagador[] = "El pago de la cotización {$cotId} ya fue procesado";
                            continue;
                        }

                        if(empty($monto)){
                            $errorsPagador[] = "PAG-10: Monto no existe en cotizacion: {$cotId}";
                            continue;
                        } else {
                            $tmpdbpago->monto = $monto['valorLong'];
                        }

                        $codautorizacion = $tmpdbpago->autorizacion;
                        $tmpdbpago->cotizacionesDetalleVehiculoCotId = (int) $cotId;
                        $tmpdbpago->lastDigits = substr($cot['numCuentaTarjeta'] ?? '', -4);
                        $dataTmp = json_encode(
                            [
                                'medioCobro' => $cot['medioCobro'] ?? '',
                                'numCuentaTarjeta' => $cot['numCuentaTarjeta'] ?? '',
                                'tipoCuentaTarjeta' => $cot['tipoCuentaTarjeta'] ?? '',
                                //'codCuentaTarjeta' => $cot['codCuentaTarjeta'] ?? '',
                                'claseTarjeta' => $cot['claseTarjeta'] ?? '',
                                'venciTarjeta' => $cot['venciTarjeta'] ?? '',
                                'bancoEmisor' => $cot['bancoEmisor'] ?? '',
                                'tipoCuentaBancarias' => $cot['tipoCuentaBancarias'] ?? '',
                                'numeroCuotas' => $cot['numeroCuotas'] ?? '',
                                'nombreTarjeta' => $cot['nombreTarjeta'] ?? '',
                            ]
                        );
                        $tmpdbpago->datac = $this->encriptar($dataTmp);

                        //Verificacion del CVV
                        if($cot['medioCobro'] === '03' && empty($tmpdbpago->autorizacion)){

                            $getOrdenCotizacionesVehiculos = $this->getOrdenCotizacionesVehiculos($item->id);
                            $ordenCotizacionVehiculosCotizaciones = $getOrdenCotizacionesVehiculos['ordenCotizacionVehiculosCotizaciones'];
                            $ordenCotizacionVehiculos = $getOrdenCotizacionesVehiculos['ordenCotizacionVehiculos'];

                            $headers = array(
                                'Content-Type: application/json',
                            );

                            $noTarjeta = str_replace('-', '', ($cot['numCuentaTarjeta'] ?? ''));

                            $jsonSend = [
                                "clienteDireccion" => "{{datos_cliente_direccion_entrega}}",
                                "clienteEmail" => "",
                                "clienteNombre" => "{{primer_nombre}} {{primer_apelllido}}",
                                "clienteTaxId" => "CF",
                                "clienteTelefono" => "{{telefono_celular}}",
                                "ordenDetalle" => [
                                    "0" => [
                                        "productoCantidad" => 1,
                                        "productoId" => 0,
                                        "productoPrecio" => $monto['valorLong'],
                                        "productoDescMontoDirecto" => "Pago de monto directo en sitio Cotizador Auto"
                                    ]
                                ],
                                "ordenId" => 0,
                                "ordenMonto" => $monto['valorLong'],
                                "ordenTiendaDomain" => "elroblecobros.anysubscriptions.com",
                                "pagoMetodo" => "card",
                                "pagoTcCvv" => '',
                                "pagoTcExp" => $cot['venciTarjeta'],
                                "pagoTcNombre" => $cot['nombreTarjeta'],
                                "pagoTcNumero" => $noTarjeta,
                                "pagoCuotas" => $cot['numeroCuotas'] ?? 0,
                                "pagoAfiliacion" => $flujo['actual']['afiliacion']?? '',
                            ];

                            /*var_dump($jsonSend);
                            die;*/

                            //                                "pagoTcCvv" => $cot['codCuentaTarjeta'] ?? '',

                            if (!empty($noTarjeta)) {
                                $dataSend = $this->reemplazarValoresSalida($camposAllLevelTarea, json_encode($jsonSend));

                                $url = env('ANY_SUBSCRIPTIONS_URL', '') . '/payments/create-pay-order';
                                $ch = curl_init($url);
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                curl_setopt($ch, CURLOPT_POSTFIELDS, $dataSend);
                                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                                $data = curl_exec($ch);
                                $info = curl_getinfo($ch);
                                curl_close($ch);
                                $dataResponse = @json_decode($data, true);

                                // Guardo la bitacora actual
                                $jsonSend["pagoTcCvv"] = 'Encriptado';
                                $jsonSend["pagoTcNumero"] = 'Encriptado';
                                $enviadoTmp = json_encode($jsonSend);
                                $bitacoraCoti = new CotizacionBitacora();
                                $bitacoraCoti->cotizacionId = $item->id;
                                $bitacoraCoti->usuarioId = $usuarioLogueado->id;
                                $bitacoraCoti->log = "REALIZADO PAGO PAYGATEWAY, URL: {$url}, ENVIADO: \"{$enviadoTmp}\", RECIBIDO: {$data}";
                                $bitacoraCoti->save();

                                if (empty($dataResponse['status'])) {
                                    // Guardo la bitácora
                                    $responseencode = json_encode($dataResponse);
                                    $errorsPagador[] = 'PAG-11: No se realizó el cobro correcto del servicio' . '\n' . "resultado: {$responseencode}";
                                }
                                else {
                                    $responseencode = json_encode($dataResponse);
                                    $codautorizacion = $dataResponse['data']['extra']['auth'];
                                    // Guardo la bitácora
                                    $sucessPagador[] = "Cobro Exitoso en cotización {$cotId}: {$responseencode}";

                                    //Medio Cobro
                                    $dataTarjeta = [
                                        'NUMERO_TARJETA' => ['v' => substr($cot['numCuentaTarjeta']?? '', -4)],
                                        'NOMBRE_TARJETA' => ['v' => $cot['nombreTarjeta']?? ''],
                                        'AUTORIZACION' => ['v' => $codautorizacion ?? ''],
                                    ];
                                    foreach($dataTarjeta as $cmpId => $cmp){
                                        $campoDefault = CotizacionDetalle::where('cotizacionId', $item->id)
                                            ->where('cotizacionDetalleVehiculoCotId', (int) $cotId)
                                            ->where('campo', $cmpId)
                                            ->first();
                                        if (empty($campoDefault)) {
                                            $campoDefault = new CotizacionDetalle();
                                        }
                                        $campoDefault->cotizacionId = $item->id;
                                        $campoDefault->cotizacionVehiculoId = $vehiculoId ?? null;
                                        $campoDefault->cotizacionDetalleVehiculoCotId = $cotId;
                                        $campoDefault->campo = $cmpId;
                                        $campoDefault->valorLong = $cmp['v'];
                                        $campoDefault->save();
                                    }
                                }
                            }
                            else {
                                // Guardo la bitacora actual
                                $bitacoraCoti = new CotizacionBitacora();
                                $bitacoraCoti->cotizacionId = $item->id;
                                $bitacoraCoti->usuarioId = $usuarioLogueado->id;
                                $bitacoraCoti->log = "NO REALIZADO PAGO PAYGATEWAY, URL: {$url}, ENVIADO: \"No se ingresaron datos de pago\", RECIBIDO: ''";
                                $bitacoraCoti->save();
                            }


                        }

                        $tmpdbpago->autorizacion = $codautorizacion;
                        $tmpdbpago->save();

                        $countCoti++;
                    }

                    $countVehi++;
                }
            }
            if(count($errorsPagador) > 0){
                $bitacoraCoti = new CotizacionBitacora();
                $bitacoraCoti->cotizacionId = $item->id;
                $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                $bitacoraCoti->log = "No se completaron todos los pagos";
                $bitacoraCoti->dataInfo = "<p>" . implode('<hr/>', $errorsPagador)  . "</p>";
                $bitacoraCoti->save();
                return $this->ResponseError('COT-025', 'No se completaron todos los pagos');
            }

            if(count($sucessPagador) > 0){
                $bitacoraCoti = new CotizacionBitacora();
                $bitacoraCoti->cotizacionId = $item->id;
                $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                $bitacoraCoti->log = "Cobro Exitoso";
                $bitacoraCoti->dataInfo = "<p>" . implode('<hr/>', $sucessPagador)  . "</p>";
                $bitacoraCoti->save();
            }
        }
        else if($paso === 'next' && $flujo['actual']['typeObject'] === 'vehiculo_comp'){
            $errorsPagador = [];
            $sucessPagador = [];
            if(!empty($vehiculosCot)){
                foreach($vehiculosCot as $vehiculo){
                    foreach($vehiculo['c'] as $cotId => $cot){
                        //Verificacion de que exista la data
                        if(empty($cot['idCorrelativo']) && !!$cot['emitirPoliza']){
                            $errorsPagador[] = "Faltan Opciones de Pago";
                            continue;
                        }
                        $options = $cot['COTIZACION_AS400']['datosIdEmpresaGC']['datos03']['datosCotizacionGestorComercial2']['listaFrecuenciaPagos']['listaPagos'];
                        $cotizacionVehiculo = CotizacionDetalleVehiculoCotizacion::where('id', $cotId)->first();
                        $dataOptionPago = $options;
                        if(empty($options['listaPagosMensual'])) $dataOptionPago = $options[(int) $cot['idCorrelativo'] - 1];
                        $formaPago = catFormaPago::where('codigo', $dataOptionPago['listaPagosMensual']['idFrecuencia'])->first();
                        $cotizacionVehiculo->formaPagoId = !empty($formaPago)? $formaPago->id : 0;
                        $cotizacionVehiculo->numeroPagos = $dataOptionPago['listaPrimas']['primas']['numeroPagos'];
                        $cotizacionVehiculo->primaNeta = $dataOptionPago['listaPrimas']['primas']['primaNeta'];
                        $cotizacionVehiculo->primaTotal = $dataOptionPago['listaPrimas']['primas']['primaTotal'];
                        $cotizacionVehiculo->idCorrelativo = $cot['idCorrelativo'];
                        $cotizacionVehiculo->save();
                        $sucessPagador[] = "Guardado con exito Opción de pago en $cotId";
                    }
                }
            }
            if(count($errorsPagador) > 0){
                $bitacoraCoti = new CotizacionBitacora();
                $bitacoraCoti->cotizacionId = $item->id;
                $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                $bitacoraCoti->log = "No se guardo las opciones de pago";
                $bitacoraCoti->dataInfo = "<p>" . implode('<hr/>', $errorsPagador)  . "</p>";
                $bitacoraCoti->save();
                return $this->ResponseError('COT-025', 'No se completaron todos los pagos');
            }

            if(count($sucessPagador) > 0){
                $bitacoraCoti = new CotizacionBitacora();
                $bitacoraCoti->cotizacionId = $item->id;
                $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                $bitacoraCoti->log = "Exitoso";
                $bitacoraCoti->dataInfo = "<p>" . implode('<hr/>', $sucessPagador)  . "</p>";
                $bitacoraCoti->save();
            }

        }

        if (!$originalStep) {
            $originalStep = $item->nodoActual;
        }

        // se guarda el nodo actual
        if (!$recursivo && $paso === 'next') {
            if(!empty($item->nodoActual)) {
                $item->nodoPrevio = $item->nodoActual;
            }
        }

        // Cambio el estado al nodo actual
        $item->nodoActual = $flujo['actual']['nodoId'];
        $item->save();

        $campo = CotizacionDetalle::where('cotizacionId', $item->id)->where('campo', 'ESTADO_ACTUAL')->first();
        if (empty($campo)) {
            $campo = new CotizacionDetalle();
        }
        $campo->cotizacionId = $item->id;
        $campo->seccionKey = 0;
        $campo->campo = 'ESTADO_ACTUAL';
        $campo->label = '';
        $campo->useForSearch = 0;
        $campo->tipo ='default';
        $campo->valorLong = $item->estado;
        $campo->save();

        // guardo operación del nodo
        $userNodo = new CotizacionesUserNodo();
        $userNodo->cotizacionId = $item->id;
        $userNodo->usuarioId = $usuarioLogueadoId;
        $userNodo->nodoId = $flujo['actual']['nodoId'];
        $userNodo->save();
        if(!empty($campos) && !empty($flujo['actual']['nodoNameId'])){
            $nodeUser = User::where('id', $usuarioLogueadoId)->first();
            $rolUser = UserRol::where('userId', $usuarioLogueadoId)->first();
            $rol = null;
            if(!empty($rolUser)) $rol = Rol::where('id',  $rolUser -> rolId)->first();
            $codigoA = UserCodigoAgente::where('userId', $usuarioLogueadoId)->first();

            $campos['FECHA_ACT_NODO_'.$flujo['actual']['nodoNameId']]['v'] = Carbon::parse($userNodo->createdAt)->setTimezone('America/Guatemala')->format('d/m/Y H:i');
            $campos['USUARIO_ACT_NODO_'.$flujo['actual']['nodoNameId']]['v'] = (!empty($nodeUser) ? $nodeUser->name : 'Sin nombre');
            $campos['ID_USUARIO_ACT_NODO_'.$flujo['actual']['nodoNameId']]['v'] = (!empty($nodeUser) ? $nodeUser->id : 'Sin Id');
            $campos['ROL_USUARIO_ACT_NODO_'.$flujo['actual']['nodoNameId']]['v'] = (!empty($rol) ? $rol->name : 'Sin rol');
            $campos['CORP_USUARIO_ACT_NODO_'.$flujo['actual']['nodoNameId']]['v'] = (!empty($nodeUser) ? $nodeUser->corporativo : 'Sin Corporativo');
            $campos['COD_AG_USUARIO_ACT_NODO_'.$flujo['actual']['nodoNameId']]['v'] = (!empty($codigoA) ? $codigoA->codigoAgente : 'Sin Codigo Agente');

        }
        /*var_dump('actual');
        var_dump($flujo['actual']['nodoName']);
        var_dump('next');
        var_dump($flujo['next']['nodoName']);*/

        // Guardo campos
        if (!empty($campos) && is_array($campos) && !$recursivo) {

            // Variables por defecto
            $tmpUser = User::where('id', $item->usuarioId)->first();
            $rolUser = UserRol::where('userId', $item->usuarioId)->first();
            $rol = null;
            if(!empty($rolUser)) $rol = Rol::where('id',  $rolUser -> rolId)->first();

            //Tiendas
            $tiendas = $tmpUser->tiendas ?? [];
            $tiendasNombre = [];
            $tiendasId = [];
            foreach($tiendas as $tienda){
                $dataTienda = $tienda->tienda;
                if(!empty($dataTienda)) {
                    $tiendasNombre[] = $dataTienda->nombre ?? '';
                    $tiendasId[] = $dataTienda->id;
                }
            }

            //Distribuidor y canales
            $gruposNombre = [];
            $gruposForUser = $tmpUser->grupos ?? [];
            $gruposForRol = $rol->grupos ?? [];

            $canalesNombre = [];
            $canalesNombreCod = [];
            $gruposId = [];
            foreach($gruposForUser as $group){
                $dataGrupo = $group->grupo;
                if(!empty($dataGrupo)) {
                    $gruposNombre[] = $dataGrupo->nombre ?? '';
                    $gruposId[] = $dataGrupo->id;
                };
            }

            foreach($gruposForRol as $group){
                $dataGrupo = $group->grupo;
                if(!empty($dataGrupo)) {
                    $gruposNombre[] = $dataGrupo->nombre ?? '';
                    $gruposId[] = $dataGrupo->id;
                };

                $canales = $dataGrupo->canales ?? [];
                foreach($canales as $canal){
                    $dataCanal = $canal->canal;
                    if(!empty($dataCanal)) {
                        $canalesNombre[$dataCanal->codigoInterno] = $dataCanal->nombre ?? '';
                        $canalesNombreCod[$dataCanal->codigoInterno] = $dataCanal->codigoInterno ?? '';
                    }
                }
            }

            $ejecutivos = $this->CalculateEjecutivo($tmpUser->id ?? 0, $rol->id ?? 0, $gruposId, $tiendasId);


            $campos['FECHA_COTIZACION']['v'] = $item->dateCreated;
            $campos['FECHA_HOY']['v'] = Carbon::now()->toDateTimeString();
            $campos['ID_COTIZACION']['v'] = $item->id;
            $campos['HOY_SUM_1_YEAR']['v'] = Carbon::now()->addYear()->toDateTimeString();
            $campos['HOY_SUM_1_YEAR_F1']['v'] = Carbon::now()->addYear()->format('d/m/Y');
            $campos['CREADOR_NOMBRE']['v'] = (!empty($tmpUser) ? $tmpUser->name : 'Sin nombre');
            $campos['CREADOR_CORP']['v'] = (!empty($tmpUser) ? $tmpUser->corporativo : 'Sin corporativo');
            $campos['CODIGO_AGENTE']['v'] = $item->codigoAgente;
            $campos['CREADOR_NOMBRE_USUARIO']['v'] = (!empty($tmpUser) ? $tmpUser->nombreUsuario : 'Sin nombre');
            $campos['CREADOR_ROL']['v'] = (!empty($rol) ? $rol->name : 'Sin rol');
            $campos['CREADOR_CANAL']['v'] = (count($canalesNombre) > 0 ? implode(', ', $canalesNombre) : 'Sin canal');
            $campos['CREADOR_CANAL_CODIGO_INTERNO']['v'] = (count($canalesNombreCod) > 0 ? implode(', ', $canalesNombreCod) : 'Sin codigo de canal');
            $campos['CREADOR_DISTRIBUIDOR']['v'] = (count($gruposNombre) > 0 ? implode(', ', $gruposNombre) : 'Sin distribuidor');
            $campos['CREADOR_TIENDA']['v'] = (count($tiendasNombre) > 0 ? implode(', ', $tiendasNombre) : 'Sin tienda');
            $campos['CREADOR_EJECUTIVO']['v'] = (count($ejecutivos) > 0 ? implode(', ', $ejecutivos) : 'Sin Ejecutivo');

            // producto
            $productoTk = $item->producto->token ?? '';
            $campos['LINK_FORM']['v'] = $this->getCotizacionLink($productoTk, $item->token);

            foreach ($campos as $campoKey => $valor) {
                if ($valor['v'] === '__SKIP__FILE__') continue;

                // tipos de archivo que no se guardan
                if (!empty($valor['t']) && ($valor['t'] === 'txtlabel' || $valor['t'] === 'subtitle')) {
                    continue;
                }

                if(!empty($valor['t']) && $valor['t'] === 'encrypt'){
                    $valor['v'] = $this->encriptar($valor['v']);
                }
                $campo = null;
                if($verifyIsForGroupVehicle) $campo = CotizacionDetalle::where('campo', $campoKey)->where('cotizacionId', $item->id)->where('cotizacionVehiculoId', $vehiculoIdAgrupadorNodo)->first();
                else $campo = CotizacionDetalle::where('campo', $campoKey)->where('cotizacionId', $item->id)->first();
                if (empty($campo)) {
                    $campo = new CotizacionDetalle();
                }
                $campo->cotizacionId = $item->id;
                $campo->seccionKey = $seccionKey;
                $campo->campo = $campoKey;
                $campo->label = (!empty($fieldsData[$campoKey]['nombre']) ? $fieldsData[$campoKey]['nombre'] : '');
                $campo->useForSearch = (!empty($fieldsData[$campoKey]['showInReports']) ? 1 : 0);
                $campo->tipo = $valor['t'] ?? 'default';
                if($verifyIsForGroupVehicle) $campo->cotizacionVehiculoId = $vehiculoIdAgrupadorNodo;
                if ($campo->tipo === 'signature') {

                    // solo se guarda la firma si viene en base 64, quiere decir que cambió
                    if (str_contains($valor['v'], 'data:image/')) {
                    $marcaToken = $item->marca->token ?? false;
                    $name = md5(uniqid()).'.png';
                    $dir = "{$marcaToken}/{$item->token}/{$name}";
                    $image = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $valor['v']));
                    $disk = Storage::disk('s3');
                    $path = $disk->put($dir, $image);
                    $campo->isFile = 1;
                    $campo->valorLong = $dir;
                }
                }
                else {
                    if (is_array($valor['v'])) {
                        $campo->valorLong = json_encode($valor['v'], JSON_FORCE_OBJECT);
                    }
                    else {
                        $campo->valorLong = $valor['v'];
                    }
                }
                $campo->valorShow = (!empty($valor['vs']) ? $valor['vs'] : null);

                if (!empty($campo->valorShow)) {
                    $campoTmp = null;
                    if($verifyIsForGroupVehicle) $campoTmp = CotizacionDetalle::where('campo', "{$campoKey}_DESC")->where('cotizacionId', $item->id)->where('cotizacionVehiculoId', $vehiculoIdAgrupadorNodo)->first();
                    else $campoTmp = CotizacionDetalle::where('campo', "{$campoKey}_DESC")->where('cotizacionId', $item->id)->first();
                    if (empty($campoTmp)) {
                        $campoTmp = new CotizacionDetalle();
                    }
                    $campoTmp->cotizacionId = $item->id;
                    $campoTmp->seccionKey = $seccionKey;
                    $campoTmp->campo = "{$campoKey}_DESC";
                    $campoTmp->label = (!empty($fieldsData[$campoKey]['nombre']) ? $fieldsData[$campoKey]['nombre'] : '');
                    $campoTmp->useForSearch = (!empty($fieldsData[$campoKey]['showInReports']) ? 1 : 0);
                    $campoTmp->tipo = $valor['t'] ?? 'default';
                    $campoTmp->valorLong = $campo->valorShow;
                    if($verifyIsForGroupVehicle) $campoTmp->cotizacionVehiculoId = $vehiculoIdAgrupadorNodo;
                    $campoTmp->save();
                }

                if ($campo->tipo === 'currency') {
                    $campoTmp = null;
                    if($verifyIsForGroupVehicle) $campoTmp = CotizacionDetalle::where('cotizacionId', $item->id)->where('campo', "{$campoKey}_DECIMAL")->where('cotizacionVehiculoId', $vehiculoIdAgrupadorNodo)->first();
                    else $campoTmp = CotizacionDetalle::where('cotizacionId', $item->id)->where('campo', "{$campoKey}_DECIMAL")->first();
                    if (empty($campoTmp)) {
                        $campoTmp = new CotizacionDetalle();
                    }

                    $valorCurrency = $campo->valorLong ;

                    $parts = explode('.', $valorCurrency);

                    if (empty($parts) || empty($parts[0])) {
                        $valorCurrency = '0.00';
                    }

                    if (!isset($parts[1])) {
                        $valorCurrency .= '.00';
                    } elseif (strlen($parts[1]) === 0) {
                        $valorCurrency .= '00';
                    } elseif (strlen($parts[1]) === 1) {
                        $valorCurrency .= '0';
                    }

                    $campoTmp->cotizacionId = $item->id;
                    $campoTmp->seccionKey = $seccionKey;
                    $campoTmp->campo = "{$campoKey}_DECIMAL";
                    $campoTmp->label = (!empty($fieldsData[$campoKey]['nombre']) ? $fieldsData[$campoKey]['nombre'] : '');
                    $campoTmp->useForSearch = (!empty($fieldsData[$campoKey]['showInReports']) ? 1 : 0);
                    $campoTmp->tipo = $valor['t'] ?? 'default';
                    $campoTmp->valorLong = $valorCurrency;
                    if($verifyIsForGroupVehicle) $campoTmp->cotizacionVehiculoId = $vehiculoIdAgrupadorNodo;
                    $campoTmp->save();
                }

                $campo->save();
            }

            if($paso === 'save') return $this->ResponseSuccess('Datos guardados con éxito', ['id' => $item->id]);

            // Guardo la bitacora actual
            $bitacoraCoti = new CotizacionBitacora();
            $bitacoraCoti->cotizacionId = $item->id;
            $bitacoraCoti->usuarioId = $usuarioLogueadoId;
            $bitacoraCoti->log = "Guardados datos en paso \"{$flujo['actual']['label']}\"";
            $bitacoraCoti->save();
        }

        $autoSaltarASiguiente = false;
        $decisionTomada = false;

        // Cambio a siguiente paso
        if ($paso === 'next') {
            //calculo de data
            //Calculate data
            $item = $item->fresh();
            $camposAllTmp = $item->campos;
            $getOrdenCotizacionesVehiculos = $this->getOrdenCotizacionesVehiculos($item->id);
            $ordenCotizacionVehiculosCotizaciones = $getOrdenCotizacionesVehiculos['ordenCotizacionVehiculosCotizaciones'];
            $ordenCotizacionVehiculos = $getOrdenCotizacionesVehiculos['ordenCotizacionVehiculos'];
            // refactorizados campos all, ahora busca por llave del array en lugar del eloquent ->where

            $camposAllLevelTarea = [];

            foreach ($camposAllTmp as $field) {

                /*

                if(!empty($newField['cotizacionDetalleVehiculoCotId']) && isset($ordenCotizacionVehiculosCotizaciones[$newField['cotizacionDetalleVehiculoCotId']])){
                    $newField['campo'] = 'cot' . ($ordenCotizacionVehiculosCotizaciones[$newField['cotizacionDetalleVehiculoCotId']]+1) . '|' . $newField['campo'];
                }

                if(!empty($newField['cotizacionVehiculoId']) && isset($ordenCotizacionVehiculos[$newField['cotizacionVehiculoId']])){
                    $newField['campo'] = 'veh' . ($ordenCotizacionVehiculos[$newField['cotizacionVehiculoId']]+1) . '|' . $newField['campo'];
                }*/
                $newField = $field->toArray();
                $camposAllLevelTarea[$newField['campo']] = $newField;
            }

            /*$calculateDataVehicule = array_map(function($e){
                $e['id'] = $e['campo'];
                $e['valor'] = $e['valorLong'];
                return $e;
            }, $this->calculateDataVehicule($item->id));

            foreach($calculateDataVehicule as $dataVehicle){
                $camposAllLevelTarea[$dataVehicle['id']] = $dataVehicle;
            }

            unset($calculateDataVehicule);*/

            if (!empty($flujo['actual']['expiracionNodo']) && $flujo['actual']['expiracionNodo'] > 0) {
                // $fechaHoy = Carbon::now();
                $fechaExpira = Carbon::now()->addDays($flujo['actual']['expiracionNodo']);

                $item->dateExpire = $fechaExpira->format('Y-m-d');

                /*if ($fechaHoy->gt($fechaExpira)) {
                    $item->estado = 'expirada';
                    $bitacoraCoti = new CotizacionBitacora();
                    $bitacoraCoti->cotizacionId = $item->id;
                    $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                    $bitacoraCoti->log = "Tarea expiró, finalizando \"{$flujo['actual']['nodoName']}\"";
                    $bitacoraCoti->save();

                    return $this->ResponseError('COT-024', 'La tarea expiró');
                }*/
                $item->save();
            }

            // si es condicion, hay que volver a evaluarla
            if ($flujo['actual']['typeObject'] === 'condition') {
                $flujo['next'] = $flujo['actual'];
            }

            // Si viene el resultado desde decisión
            if (isset($desdeDecision['result'])) {
                // dd($flujo);

                if ($desdeDecision['result']) {
                    $nodoSiguiente = $flujo['actual']['nodosSalidaDecision']['si'];
                }
                else {
                    $nodoSiguiente = $flujo['actual']['nodosSalidaDecision']['no'];
                }
                if (empty($nodoSiguiente)) {
                    return $this->ResponseError('COT-010', 'Hubo un error al continuar flujo, decisión mal configurada (sin una salida)');
                }

                $item->nodoActual = $nodoSiguiente;
                $item->save();

                $flujo = $this->CalcularPasos($request, true, false, true);

                /*if ($flujo['actual']['typeObject'] === 'setuser') {
                    dd($flujo);
                }*/

                // Si el nodo actual es de estos, lo tengo que ejecutar, entonces lo pongo como next
                if ($flujo['actual']['typeObject'] === 'process' || $flujo['actual']['typeObject'] === 'condition' || $flujo['actual']['typeObject'] === 'setuser'|| $flujo['actual']['typeObject'] === 'vehiculo' || $flujo['actual']['typeObject'] === 'output') {
                    $flujo['next'] = $flujo['actual'];
                }
                else if ($flujo['actual']['typeObject'] === 'input' || $flujo['actual']['typeObject'] === 'review' || $flujo['actual']['typeObject'] === 'vehiculo_comp' || $flujo['actual']['typeObject'] === 'pagador') {
                    //return $flujo;
                    if (!empty($flujo['actual']['estOut']) && ($flujo['actual']['estIo'] === 'e')) {
                        $item->estado = $flujo['actual']['estOut'];
                        $item->save();
                    }
                    return $this->ResponseSuccess('Tarea actualizada con éxito', $flujo);
                }
            }
            else {
                if ($desdeDecision) {
                    if ($flujo['actual']['typeObject'] === 'input' || $flujo['actual']['typeObject'] === 'output' || $flujo['actual']['typeObject'] === 'review' || $flujo['actual']['typeObject'] === 'vehiculo' || $flujo['actual']['typeObject'] === 'vehiculo_comp' || $flujo['actual']['typeObject'] === 'pagador') {
                        //return $flujo;
                        return $this->ResponseSuccess('Tarea actualizada con éxito', $flujo);
                    }
                }
            }

            // Si no existe un next es porque es el último paso
            if (empty($flujo['next']['typeObject'])) {

                // Cambio el flujo al nodo next
                /*if (empty($estado)) {
                    $item->estado = 'ultimo_paso';
                    $item->save();
                }*/

                // Guardo la bitácora
                /*$bitacoraCoti = new CotizacionBitacora();
                $bitacoraCoti->cotizacionId = $item->id;
                $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                $bitacoraCoti->log = "Tarea en último paso \"{$flujo['actual']['nodoName']}\"";
                $bitacoraCoti->save();*/

                // si no tengo ninguno siguiente, pues es actual para ejecutar los procesos necesarios
                $flujo['next'] = $flujo['actual'];
            }

            // Verifico si es de procesos, acá siempre solo es uno
            if ($flujo['next']['typeObject'] === 'process') {
                // valida si no hay un ws custom
                if ($flujo['next']['procesos'][0]['identificadorWs'] === 'EMISION_AS400') {
                    $sysCotRec = $camposAllLevelTarea['SYS_COT_REC'] ?? '';
                    if(empty($item->producto->sincronizar)) return $this->ResponseError('COTW-011', 'No se puede continuar con la emision, no esta activa la sincronización con AS400');
                    if(empty($sysCotRec)) return $this->ResponseError('COTW-010', 'Verificar siniestralidad');
                }

                $wsData = $this->wsCustomAuto($flujo['next']['procesos'][0]['identificadorWs'] ?? false, $item->id, $flujo['next']['procesos'][0]['entrada'], $camposAllTmp);

                if (!empty(env('PROCESS_TO_BITACORA')) && env('PROCESS_TO_BITACORA') == '1') {
                    $debug = print_r($wsData, true);
                    $bitacoraCoti = new CotizacionBitacora();
                    $bitacoraCoti->cotizacionId = $item->id;
                    $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                    $bitacoraCoti->onlyPruebas = 1;
                    $bitacoraCoti->log = "XML generados pre evento: \"{$debug}\"";
                    $bitacoraCoti->save();
                }

                if (!empty(env('PROCESS_KILL')) && env('PROCESS_KILL') == '1') {
                    die('Detenida ejecución de proceso');
                }

                /*var_dump($wsData);
                die;*/
                // si es cotización
           /*     if ($wsData['type'] === 'COTIZACION_AS400') {
                    // se borran las normales
                    CotizacionDetalle::where('cotizacionid', $item->id)->whereNotNull('cotizacionDetalleVehiculoCotId')->where('campo')->delete();
                }*/

                if($wsData['cancelar']){
                    $item->estado = 'cancelada';
                    $item->save();
                }

                if (count($wsData['errors']) > 0) {
                    return $this->ResponseError('COTW-004', $wsData['errors'][0]);
                }

                // si se tiene que ejecutar por los vehículos
                if (!empty($flujo['next']['procesos'][0]['execVehi'])) {

                    $cotizacionVehiculo = CotizacionDetalleVehiculo::where('cotizacionId', $item->id)->with('linea')->get();

                    $wsData = [
                        'type' => '',
                        'list' => [],
                        'errors' => [],
                    ];

                    foreach ($cotizacionVehiculo as $vehi) {
                        $dataSendTmp = [];
                        foreach ($camposAllTmp as $field) {
                            $field = $field->toArray();
                            if(empty($field['cotizacionVehiculoId']) ||
                            $field['cotizacionVehiculoId'] == $vehi->id ||
                            $field['cotizacionVehiculoId'] == 0 ) $dataSendTmp[$field['campo']] = $field;
                        }

                        $dataSendTmp[] = ['id' => 'VEHI_NO_CHASIS', 'campo' => 'VEHI_NO_CHASIS', 'valorLong' => $vehi->noChasis ?? ''];
                        $dataSendTmp[] = ['id' => 'VEHI_NO_MOTOR', 'campo' => 'VEHI_NO_MOTOR', 'valorLong' => $vehi->noMotor ?? ''];
                        $dataSendTmp[] = ['id' => 'VEHI_MODELO', 'campo' => 'VEHI_MODELO', 'valorLong' => $vehi->modelo ?? ''];
                        $dataSendTmp[] = ['id' => 'VEHI_PLACA', 'campo' => 'VEHI_PLACA', 'valorLong' => $vehi->placa ?? ''];
                        $dataSendTmp[] = ['id' => 'VEHI_VALOR', 'campo' => 'VEHI_VALOR', 'valorLong' => $vehi->valorProm ?? ''];
                        $dataSendTmp[] = ['id' => 'VEHI_MARCA', 'campo' => 'VEHI_MARCA', 'valorLong' => $vehi->marca->codigo ?? ''];
                        $dataSendTmp[] = ['id' => 'VEHI_LINEA', 'campo' => 'VEHI_LINEA', 'valorLong' => $vehi->linea->codigo ?? ''];
                        //print_r($dataSendTmp);

                        $wsData['list'][] = [
                            'entrada' => $flujo['next']['procesos'][0]['entrada'],
                            'vehiculoId' => $vehi->id,
                            'data' => $dataSendTmp,
                        ];

                        /*var_dump($wsData);
                        die;*/
                    }
                }

                /*var_dump($wsData);
                die;*/

                $continuarConExcepcion = !empty($flujo['next']['procesos'][0]['manErrE']);

                // OVERRIDES A XML
                if (count($wsData['list']) > 0) {
                    foreach ($wsData['list'] as $vehiNumber => $tmpWsData) {
                        $flujo['next']['procesos'][0]['entrada'] = $tmpWsData['entrada'];

                        /*var_dump($tmpWsData);
                        die;*/
                        $resultado = $this->consumirServicio($flujo['next']['procesos'][0], $tmpWsData['data'] ?? $item->campos, $flujo['next']['id'] ?? '', $item);


                        // var_dump($resultado);

                        $dataLog = "<h5>Data enviada</h5> <br> " . htmlentities($resultado['log']['enviado'] ?? '') . " <br><br> <h5>Headers enviados</h5> <br> ".($resultado['log']['enviadoH'] ?? '')." <br><br> <h5>Data recibida</h5> <br> " . htmlentities($resultado['log']['recibido'] ?? '') . " <br><br> <h5>Data procesada</h5> <br> " . htmlentities(print_r($resultado['data'] ?? '', true));
                        $identificadorForWs = $flujo['next']['procesos'][0]['identificadorWs'];

                        // si es menores con cobertura
                        if (!empty($resultado['data'][$identificadorForWs .'.datosIdEmpresaGC.datos03.menoresEdadConCoberturasGestorComercial.msgRespuesta']) && trim($resultado['data'][$identificadorForWs .'.datosIdEmpresaGC.datos03.menoresEdadConCoberturasGestorComercial.msgRespuesta']) === 'TRANSACCION NO CORRESPONDE') {
                            $resultado['data'][$identificadorForWs .'.datosIdEmpresaGC.mensajeRespuesta'] = 'SATISFACTORIO';
                        }

                        if (!$continuarConExcepcion && (empty($resultado['status'])
                            || empty($resultado['data'])
                            ||(in_array($identificadorForWs, ['EMISION_AS400', 'SINIESTRALIDAD_AS400', 'EMISION_DATOS_CLIENTE_AS400', 'COTIZACION_AS400'])
                            && (empty($resultado['data'][$identificadorForWs .'.datosIdEmpresaGC.mensajeRespuesta']) || $resultado['data'][$identificadorForWs .'.datosIdEmpresaGC.mensajeRespuesta'] !== 'SATISFACTORIO')))) {
                            $bitacoraCoti = new CotizacionBitacora();
                            $bitacoraCoti->cotizacionId = $item->id;
                            $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                            $bitacoraCoti->onlyPruebas = 1;
                            $bitacoraCoti->dataInfo = $dataLog;
                            $bitacoraCoti->log = "Error ejecutando proceso. Saliendo de \"{$flujo['actual']['nodoName']}\", URL: {$flujo['next']['procesos'][0]['url']}";
                            $bitacoraCoti->save();

                            if ($originalStep) {
                                $item->nodoActual = $originalStep;
                                $item->save();
                            }

                            //dd($resultado);
                            $mensajeResult = $resultado['msg'];

                            // reversa de emision
                            /*if ($identificadorForWs === 'EMISION_AS400') {

                            }*/

                            $manejoErroresPConf = (!empty($flujo['next']['procesos'][0]['manErrC'])) ? @json_decode($flujo['next']['procesos'][0]['manErrC'], true) : [];
                            $manejoErroresPConf = $manejoErroresPConf[0] ?? [];
                            if (!empty($manejoErroresPConf['procesoOnError'])) {

                                $requestTmpReverse = new \Illuminate\Http\Request();
                                $requestTmpReverse->replace(['token' => $item->token, 'nodoId' => $manejoErroresPConf['procesoOnError']]);
                                $this->execProcess($requestTmpReverse);
                            }

                            if(!empty($resultado['data'][$identificadorForWs . '.0']) && strpos($resultado['data'][$identificadorForWs . '.0'], "500 :") === 0) $mensajeResult = 'Error en servicio';
                            if(!empty($resultado['data'][$identificadorForWs .'.datosIdEmpresaGC.mensajeRespuesta'])) $mensajeResult = $resultado['data'][$identificadorForWs .'.datosIdEmpresaGC.mensajeRespuesta'];

                            if (!empty($resultado['msgErrP'])) {
                                return $this->ResponseError('COTW-001', $resultado['msgErrP']);
                            }
                            else {
                                return $this->ResponseError('COTW-008', "Ha ocurrido realizando el proceso de envío de datos. {$mensajeResult}");
                            }

                            //return $this->ResponseError('COTW-08', "Ha ocurrido realizando el proceso de envío de datos. {$mensajeResult}");
                        }
                        else {
                            if(!empty($tmpWsData['process']) && $tmpWsData['process'] === '7'){
                                $dataMetodoPago = DataMetodoPago::where('cotizacionesDetalleVehiculoCotId', $tmpWsData['cotizacionDetalleVehiculoCotId'])->first();

                                if(!empty($dataMetodoPago)){
                                    $pattern = "/<numeroCuentaTarjeta>[\d-]+<\/numeroCuentaTarjeta>/";
                                    $replace = "<numeroCuentaTarjeta>XXXX-XXXX-XXXX-{$dataMetodoPago->lastDigits}</numeroCuentaTarjeta>";
                                    $dataLog = preg_replace($pattern, $replace, $dataLog);
                                }
                                //$dataMetodoPago->datac = null;
                                //$dataMetodoPago->save();
                            }
                            // Si tiene identificador de WS, se guardan los campos de una
                            if ((in_array($wsData['type'], ['EMISION_AS400', 'SINIESTRALIDAD_AS400', 'EMISION_DATOS_CLIENTE_AS400', 'COTIZACION_AS400']) || !empty($flujo['next']['procesos'][0]['execVehi'])) && !empty($flujo['next']['procesos'][0]['identificadorWs'])) {
                                if($flujo['next']['procesos'][0]['identificadorWs'] === 'EMISION_AS400') {
                                    $resultado['data']['FECHA_EMISION'] = Carbon::now()->setTimezone('America/Guatemala')->toDateTimeString();
                                }
                                foreach ($resultado['data'] as $campoKey => $campoValue) {
                                    // data si es cotización de vehículo
                                    $campo = null;
                                    if (!empty($tmpWsData['cotizacionDetalleVehiculoCotId'])) {
                                        // si es cotización de vehículo se borran las anteriores

                                        $campo = CotizacionDetalle::where('campo', $campoKey)->where('cotizacionDetalleVehiculoCotId', $tmpWsData['cotizacionDetalleVehiculoCotId'])->first();
                                        if (empty($campo)) {
                                            $campo = new CotizacionDetalle();
                                        }
                                        $campo->cotizacionDetalleVehiculoCotId = $tmpWsData['cotizacionDetalleVehiculoCotId'];
                                    }
                                    else {
                                        $campo = CotizacionDetalle::where('campo', $campoKey)->where('cotizacionId', $item->id)->first();
                                        if (empty($campo)) {
                                            $campo = new CotizacionDetalle();
                                        }
                                    }

                                    if (!empty($tmpWsData['vehiculoId'])) {
                                        $campo->cotizacionVehiculoId = $tmpWsData['vehiculoId'];
                                    }

                                    $campo->cotizacionId = $item->id;
                                    $campo->campo = $campoKey;
                                    if (is_array($campoValue)) {
                                        $campo->valorLong = json_encode($campoValue, JSON_FORCE_OBJECT);
                                    }
                                    else {
                                        $campo->valorLong = $campoValue;
                                    }
                                    $campo->isFromWs = 1;
                                    $campo->save();

                                    // Prima neta
                                    if ($campoKey === 'COTIZACION_AS400.datosIdEmpresaGC.datos03.datosCotizacionGestorComercial2.listaFrecuenciaPagos.listaPagos.listaPrimas.primas.primaNeta' || $campoKey === 'COTIZACION_AS400.datosIdEmpresaGC.datos03.datosCotizacionGestorComercial2.listaFrecuenciaPagos.listaPagos.0.listaPrimas.primas.primaNeta') {
                                        if (!empty($campoValue)) {
                                            $this->saveExtraVar("veh{$tmpWsData['vehiNumber']}|cot{$tmpWsData['cotiNumber']}|primaNeta", $item->id, $campoValue);
                                        }
                                    }

                                    // Prima total
                                    if ($campoKey === 'COTIZACION_AS400.datosIdEmpresaGC.datos03.datosCotizacionGestorComercial2.listaFrecuenciaPagos.listaPagos.listaPrimas.primas.primaTotal' || $campoKey === 'COTIZACION_AS400.datosIdEmpresaGC.datos03.datosCotizacionGestorComercial2.listaFrecuenciaPagos.listaPagos.0.listaPrimas.primas.primaTotal') {
                                        if (!empty($campoValue)) {
                                            $this->saveExtraVar("veh{$tmpWsData['vehiNumber']}|cot{$tmpWsData['cotiNumber']}|primaTotal", $item->id, $campoValue);
                                        }
                                    }

                                    // Prima total
                                    if ($campoKey === 'COTIZACION_AS400.datosIdEmpresaGC.datos03.datosCotizacionGestorComercial2.listaFrecuenciaPagos.listaPagos.listaPrimas.primas.numeroPagos' || $campoKey === 'COTIZACION_AS400.datosIdEmpresaGC.datos03.datosCotizacionGestorComercial2.listaFrecuenciaPagos.listaPagos.0.listaPrimas.primas.numeroPagos') {
                                        if (!empty($campoValue)) {
                                            $this->saveExtraVar("veh{$tmpWsData['vehiNumber']}|cot{$tmpWsData['cotiNumber']}|numeroPagos", $item->id, $campoValue);
                                        }
                                    }

                                    // guarda el número de cotización
                                    if ($campoKey === 'COTIZACION_AS400.datosIdEmpresaGC.datos03.datosCotizacionGestorComercial2.numeroCotizacion') {
                                        $cotTmp = CotizacionDetalleVehiculoCotizacion::where('id', $tmpWsData['cotizacionDetalleVehiculoCotId'])->first();
                                        $cotTmp->numeroCotizacionAS400 = $campoValue;
                                        $cotTmp->save();
                                        $this->saveExtraVar("veh{$tmpWsData['vehiNumber']}|cot{$tmpWsData['cotiNumber']}|numeroCotizacion", $item->id, $campoValue);
                                    }

                                    // guarda el número de cotización
                                    if ($campoKey === 'EMISION_AS400.datosIdEmpresaGC.datos03.datosdePolizaGestorComercial.poliza') {
                                        $this->saveExtraVar("veh{$tmpWsData['vehiNumber']}|polizaEmitida", $item->id, $campoValue);
                                    }
                                }
                            }

                            $bitacoraCoti = new CotizacionBitacora();
                            $bitacoraCoti->cotizacionId = $item->id;
                            $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                            $bitacoraCoti->onlyPruebas = 1;
                            $bitacoraCoti->dataInfo = "<h5>URL:</h5> {$flujo['next']['procesos'][0]['url']} <br/><br/>" . $dataLog;
                            $bitacoraCoti->log = "Ejecutado proceso saliendo de \"{$flujo['actual']['nodoName']}\"";
                            $bitacoraCoti->save();
                        }
                    }

                    $autoSaltarASiguiente = true;
                }
                else {

                    $resultado = $this->consumirServicio($flujo['next']['procesos'][0], $camposAllLevelTarea, $flujo['next']['id'] ?? '', $item);
                    //dd($resultado);

                    $dataLog = "<h5>Data enviada</h5> <br> " . htmlentities($resultado['log']['enviado'] ?? '') . " <br><br> <h5>Headers enviados</h5> <br> ".($resultado['log']['enviadoH'] ?? '')." <br><br> <h5>Data recibida</h5> <br> " . htmlentities($resultado['log']['recibido'] ?? '') . " <br><br> <h5>Data procesada</h5> <br> " . htmlentities(print_r($resultado['data'] ?? '', true));

                    if (empty($resultado['status']) && !$continuarConExcepcion) {
                        $bitacoraCoti = new CotizacionBitacora();
                        $bitacoraCoti->cotizacionId = $item->id;
                        $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                        $bitacoraCoti->onlyPruebas = 1;
                        $bitacoraCoti->dataInfo = $dataLog;
                        $bitacoraCoti->log = "Error ejecutando proceso. Saliendo de \"{$flujo['actual']['nodoName']}\", URL: {$flujo['next']['procesos'][0]['url']}";
                        $bitacoraCoti->save();

                        if ($originalStep) {
                            $item->nodoActual = $originalStep;
                            $item->save();
                        }

                        return $this->ResponseError('COTW-002', "Ha ocurrido realizando el proceso de envío de datos. {$resultado['msg']}");
                    }
                    else {

                        // Si tiene identificador de WS, se guardan los campos de una
                        if (!empty($flujo['next']['procesos'][0]['identificadorWs'])) {
                            if($flujo['next']['procesos'][0]['identificadorWs'] === 'EMISION_AS400') {
                                $resultado['data']['FECHA_EMISION'] = Carbon::now()->setTimezone('America/Guatemala')->toDateTimeString();
                            }
                            foreach ($resultado['data'] as $campoKey => $campoValue) {
                                $campo = CotizacionDetalle::where('campo', $campoKey)->where('cotizacionId', $item->id)->first();
                                if (empty($campo)) {
                                    $campo = new CotizacionDetalle();
                                }
                                $campo->cotizacionId = $item->id;
                                $campo->campo = $campoKey;
                                if (is_array($campoValue)) {
                                    $campo->valorLong = json_encode($campoValue, JSON_FORCE_OBJECT);
                                }
                                else {
                                    $campo->valorLong = $campoValue;
                                }
                                $campo->save();
                            }
                        }

                        $bitacoraCoti = new CotizacionBitacora();
                        $bitacoraCoti->cotizacionId = $item->id;
                        $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                        $bitacoraCoti->onlyPruebas = 1;
                        $bitacoraCoti->dataInfo = "<h5>URL:</h5> {$flujo['next']['procesos'][0]['url']} <br/><br/>" . $dataLog;
                        $bitacoraCoti->log = "Ejecutado proceso saliendo de \"{$flujo['actual']['nodoName']}\"";
                        $bitacoraCoti->save();
                    }

                    $autoSaltarASiguiente = true;
                }
            }
            else if ($flujo['next']['typeObject'] === 'condition') {

                $decisionCumple = true;
                $valuacionValores = '';
                $logicaCondicional = $flujo['next']['decisionesL'] ?? false;

                if (!empty($flujo['next']['decisiones'])) {

                    $camposTmp = $item->campos;
                    $firstCond = true;

                    foreach ($flujo['next']['decisiones'] as $decision) {

                        // Si el campo existe
                        $cumplio = false;
                        $variableDinamica = (!empty($decision['vDin']) ? str_replace("{{", '', str_replace("}}", '', $decision['vDin'])) : false);
                        if ($variableDinamica) {
                            $decision['campoId'] = $variableDinamica;
                        }


                        /*var_dump($camposAllLevelTarea);
                        die();*/

                        if (empty($logicaCondicional) || $logicaCondicional === 'ev') {
                            if (!isset($camposAllLevelTarea[$decision['campoId']])) {
                                return $this->ResponseError('T-226', 'Una variable evaluada no existe en el flujo');
                            }
                        }
                        else {
                            if ($firstCond) {
                                $decisionCumple = false;
                                $decision['glue'] = 'OR';
                                $firstCond = false;
                            }
                        }

                        $campoTmp = $camposAllLevelTarea[$decision['campoId']] ?? false;

                        //var_dump($campoTmp);

                        if (!empty($campoTmp)) {
                            $campoTmp = json_encode($campoTmp);
                            $campoTmp = json_decode($campoTmp);
                            $valorJsonDecode = @json_decode($campoTmp->valorLong, true);
                            if (!is_array($valorJsonDecode)) {

                                $isInt = (is_integer($decision['value']));
                                $campoTmp->valorLong = ($isInt) ? intval($campoTmp->valorLong) : (string) $campoTmp->valorLong;
                                $decision['value'] = ($isInt) ? intval($decision['value']) : (string) $decision['value'];

                                if ($decision['campoIs'] === '=') {
                                    if ($campoTmp->valorLong == $decision['value']) $cumplio = true;
                                }
                                else if ($decision['campoIs'] === '<') {
                                    if ($campoTmp->valorLong < $decision['value']) $cumplio = true;
                                }
                                else if ($decision['campoIs'] === '<=') {
                                    if ($campoTmp->valorLong <= $decision['value']) $cumplio = true;
                                }
                                else if ($decision['campoIs'] === '>') {
                                    if ($campoTmp->valorLong > $decision['value']) $cumplio = true;
                                }
                                else if ($decision['campoIs'] === '>=') {
                                    if ($campoTmp->valorLong >= $decision['value']) $cumplio = true;
                                }
                                else if ($decision['campoIs'] === '<>') {
                                    if ($campoTmp->valorLong != $decision['value']) $cumplio = true;
                                }
                                else if ($decision['campoIs'] === 'like') {
                                    $decision['value'] = (string) $decision['value'];
                                    $campoTmp->valorLong = (string) $campoTmp->valorLong;
                                    if (str_contains($campoTmp->valorLong, $decision['value'])) $cumplio = true;
                                }
                            }
                            else {

                                foreach ($valorJsonDecode as $valorTmp) {

                                    $valorTmp = (is_numeric($campoTmp->valorLong) ? $campoTmp->valorLong : trim((string) $campoTmp->valorLong));
                                    $decision['value'] = (is_numeric($decision['value']) ? $decision['value'] : trim((string) $decision['value']));

                                    if ($decision['campoIs'] === '=') {
                                        if ($valorTmp == $decision['value']) $cumplio = true;
                                        break;
                                    }
                                    else if ($decision['campoIs'] === '<') {
                                        if ($valorTmp < $decision['value']) $cumplio = true;
                                        break;
                                    }
                                    else if ($decision['campoIs'] === '<=') {
                                        if ($valorTmp <= $decision['value']) $cumplio = true;
                                        break;
                                    }
                                    else if ($decision['campoIs'] === '>') {
                                        if ($valorTmp > $decision['value']) $cumplio = true;
                                        break;
                                    }
                                    else if ($decision['campoIs'] === '>=') {
                                        if ($valorTmp >= $decision['value']) $cumplio = true;
                                        break;
                                    }
                                    else if ($decision['campoIs'] === 'like') {
                                        if (str_contains($valorTmp, $decision['value'])) $cumplio = true;
                                        break;
                                    }
                                }
                            }

                            $valuacionValores .= " {$decision['glue']} {$campoTmp->valorLong} {$decision['campoIs']} {$decision['value']}";

                            //var_dump($cumplio);

                            if ($decision['glue'] === 'AND') {
                                $decisionCumple = ($decisionCumple && $cumplio);
                            }
                            else if ($decision['glue'] === 'OR') {
                                $decisionCumple = ($decisionCumple || $cumplio);
                            }
                        }
                    }
                }

                /*var_dump($decisionCumple);
                var_dump($valuacionValores);
                die();*/

                $valuacionValores .= ' ====> ' . ($decisionCumple ? 'true' : 'false');
                $decisionTomada = ['result' => $decisionCumple];

                $bitacoraCoti = new CotizacionBitacora();
                $bitacoraCoti->cotizacionId = $item->id;
                $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                $bitacoraCoti->log = "Evaluado condicional saliendo de \"{$flujo['actual']['nodoName']}\"";
                $bitacoraCoti->onlyPruebas = 1;
                $bitacoraCoti->dataInfo = $valuacionValores;
                $bitacoraCoti->save();

                // si es condición siempre salta al siguiente
                $autoSaltarASiguiente = true;
            }
            else if ($flujo['next']['typeObject'] === 'setuser') {

                if (!empty($flujo['next']['userAssign']['variable'])){
                    $variable = str_replace("{{", '', str_replace("}}", '', $flujo['next']['userAssign']['variable']));
                    $valorDetalle = CotizacionDetalle::where('campo', $variable)->where('cotizacionId', $item->id)->first();
                    $user = User::where('id', $valorDetalle->valorLong)->first();

                    if (!empty($user)) {
                        $item->usuarioIdAsignadoPrevio = $item->usuarioIdAsignado;
                        $item->usuarioIdAsignado = $user->id;
                        $item->nodoActual = $flujo['next']['nodoId'];
                        $item->save();
                    }
                    else {
                        // Guardo la bitácora
                        $bitacoraCoti = new CotizacionBitacora();
                        $bitacoraCoti->cotizacionId = $item->id;
                        $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                        $bitacoraCoti->log = "Error de asignación a usuario, el usuario no se encuentra o es inválido";
                        $bitacoraCoti->save();
                    }
                }
                else if (!empty($flujo['next']['userAssign']['user'])) {

                    if ($flujo['next']['userAssign']['user'] === '_PREV_') {
                        $user = User::where('id', $item->usuarioIdAsignadoPrevio)->where('active', 1)->first();
                    }
                    else if ($flujo['next']['userAssign']['user'] === '_ORI_') {
                        $user = User::where('id', $item->usuarioId)->where('active', 1)->first();
                    }
                    else {
                        $user = User::where('id', $flujo['next']['userAssign']['user'])->where('active', 1)->first();
                    }

                    if (!empty($user)) {
                        $item->usuarioIdAsignadoPrevio = $item->usuarioIdAsignado;
                        $item->usuarioIdAsignado = $user->id;
                        $item->nodoActual = $flujo['next']['nodoId'];
                        $item->save();
                    }
                    else {
                        // Guardo la bitácora
                        $bitacoraCoti = new CotizacionBitacora();
                        $bitacoraCoti->cotizacionId = $item->id;
                        $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                        $bitacoraCoti->log = "Error de asignación a usuario, el usuario no se encuentra o es inválido";
                        $bitacoraCoti->save();
                    }
                }


               /*  else if (!empty($flujo['next']['userAssign']['node'])){
                    $user = CotizacionesUserNodo::where('cotizacionId', $item->id)->where('nodoId', $flujo['next']['userAssign']['node'])->orderBy('createdAt', 'DESC')->first();

                    if (!empty($user)) {
                        $item->usuarioIdAsignadoPrevio = $item->usuarioIdAsignado;
                        $item->usuarioIdAsignado = $user->usuarioId;
                        $item->nodoActual = $flujo['next']['nodoId'];
                        $item->save();
                    }
                    else {
                        // Guardo la bitácora
                        $bitacoraCoti = new CotizacionBitacora();
                        $bitacoraCoti->cotizacionId = $item->id;
                        $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                        $bitacoraCoti->log = "Error de asignación a usuario, el usuario no se encuentra o es inválido";
                        $bitacoraCoti->save();
                    }
                } */
                else {
                    if (!empty($flujo['next']['userAssign']['role']) || !empty($flujo['next']['userAssign']['group'])) {

                        $userIdAsignar = 0;
                        $usersToAssign = [];
                        $roles = '';
                        $verifyIsForGroup = false;

                        // roles por grupo
                        if (!empty($flujo['next']['userAssign']['group'])) {

                            $rolId = [];
                            $strQueryFull = "SELECT GU.*
                                                FROM usersGroupRoles AS GU
                                                WHERE GU.userGroupId = '{$flujo['next']['userAssign']['group']}'";
                            $usuariosTmp = DB::select(DB::raw($strQueryFull));

                            foreach ($usuariosTmp as $tmp) {
                                $rolId[] = $tmp->rolId;
                            }

                            $roles = implode(', ', $rolId);


                            $strQueryFull = "SELECT GU.*
                                                FROM usersGroupUsuarios AS GU
                                                WHERE GU.userGroupId = '{$flujo['next']['userAssign']['group']}'";
                            $usuariosTmp = DB::select(DB::raw($strQueryFull));
                            foreach ($usuariosTmp as $tmp) {
                                $usersToAssign[] = $tmp->userId;
                            }


                            $verifyIsForGroup = true;

                        }

                        // rol individual
                        if (!empty($flujo['next']['userAssign']['role'])) {
                            $roles = ($roles === '' ? $flujo['next']['userAssign']['role'] :( $roles . ", {$flujo['next']['userAssign']['role']}"));
                        }

                        if(!empty($roles)){
                        $strQueryFull = "SELECT U.id
                                            FROM users AS U
                                            JOIN user_rol AS UR ON U.id = UR.userId
                                            WHERE UR.rolId IN ({$roles})
                                            AND U.fueraOficina = 0
                                            AND U.active = 1";

                        $usuariosTmp = DB::select(DB::raw($strQueryFull));
                        foreach ($usuariosTmp as $tmp) {
                            $usersToAssign[] = $tmp->id;
                        }
                        }

                        $usersToFind = implode(', ', $usersToAssign);

                        // búsqueda de datos para usuario
                        $strQueryFull = "SELECT C.id, C.usuarioIdAsignado
                                        FROM cotizaciones AS C
                                        WHERE usuarioIdAsignado IN ({$usersToFind})
                                        AND LOWER(C.estado) <> 'finalizada'
                                        AND LOWER(C.estado) <> 'cancelada'";

                        $cotizacionesConteo = [];
                        $conteo = DB::select(DB::raw($strQueryFull));
                        foreach ($conteo as $tmp) {
                            if (!isset($cotizacionesConteo[$tmp->usuarioIdAsignado])) {
                                $cotizacionesConteo[$tmp->usuarioIdAsignado] = [
                                    'conteo' => 0,
                                    'detalle' => [],
                                ];
                            }
                            $cotizacionesConteo[$tmp->usuarioIdAsignado]['conteo']++;
                            $cotizacionesConteo[$tmp->usuarioIdAsignado]['detalle'][] = $tmp->id;
                        }

                        if ($flujo['next']['userAssign']['setuser_method'] === 'load') {

                            // coloco los que no tienen asignado nada
                            foreach ($usersToAssign as $keyAssig) {
                                if (!isset($cotizacionesConteo[$keyAssig])) {
                                    $cotizacionesConteo[$keyAssig]['conteo'] = 0;
                                    $cotizacionesConteo[$keyAssig]['detalle'] = [];
                                }
                            }

                            // calculo la menor carga
                            if (count($cotizacionesConteo) > 1) {
                                $conteos = min(array_column($cotizacionesConteo, 'conteo'));
                                foreach ($cotizacionesConteo as $user => $tmp) {
                                    if ($tmp['conteo'] === $conteos) {
                                        $userIdAsignar = $user;
                                        break;
                                    }
                                }
                            }
                            else {
                                $userIdAsignar = $usersToAssign[0] ?? 0;
                            }

                        }
                        else if ($flujo['next']['userAssign']['setuser_method'] === 'random') {
                            if (count($cotizacionesConteo) === 0) {
                                $cotizacionesConteo[] = $usersToAssign[0] ?? 0;
                            }
                            $userIdAsignar = array_rand($cotizacionesConteo);
                        }
                        else if ($flujo['next']['userAssign']['setuser_method'] === 'order') {
                            if($verifyIsForGroup){
                                $bitacoraCoti = new CotizacionBitacora();
                                $bitacoraCoti->cotizacionId = $item->id;
                                $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                                $bitacoraCoti->log = "Error al asignar usuario. La asignación por orden es incompatible con grupos; solo debe usarse con roles.";
                                $bitacoraCoti->save();
                            }

                            $lastUserAsig = 0;
                            $UserAsig = 0;
                            $lastUser = OrdenAsignacion::where('productoId', $item->productoId)->where('rolId', $roles)->first();
                            if (!empty($lastUser)) $lastUserAsig = $lastUser->userId;


                            $userDetected = false;
                            foreach ($usersToAssign as $userTmp) {
                                if (empty($lastUserAsig) || $userDetected) {
                                    $UserAsig = $userTmp;
                                    break;
                                }
                                else {
                                   if ($userTmp === $lastUserAsig) {
                                       $userDetected = true;
                                   }
                                }
                            }

                            // si ya pasó la vuelta
                            if (empty($UserAsig)) {
                                $UserAsig = $usersToAssign[0] ?? 0;
                            }

                            if (empty($lastUser)) {
                                $lastUser = new OrdenAsignacion();
                            }

                            $userIdAsignar = $UserAsig;

                            $lastUser->productoId = $item->productoId;
                            $lastUser->userId = $UserAsig;
                            $lastUser->rolId = $roles;
                            $lastUser->save();
                        }

                        if (!empty($userIdAsignar)) {
                            $item->usuarioIdAsignadoPrevio = $item->usuarioIdAsignado;
                            $item->usuarioIdAsignado = $userIdAsignar;
                            $item->nodoActual = $flujo['next']['nodoId'];
                            $item->save();
                        }
                        else {
                            $bitacoraCoti = new CotizacionBitacora();
                            $bitacoraCoti->cotizacionId = $item->id;
                            $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                            $bitacoraCoti->log = "Error al asignar usuario, no existe ningún usuario que cumpla la asignación";
                            $bitacoraCoti->save();
                        }
                    }
                }

                $user = User::where('id', $item->usuarioIdAsignado)->first();
                // dd($user);

                // Guardo la bitácora
                $bitacoraCoti = new CotizacionBitacora();
                $bitacoraCoti->cotizacionId = $item->id;
                $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                $bitacoraCoti->log = "Asignación de usuario \"".($user->name ?? 'Sin nombre')."\"";
                $bitacoraCoti->save();

                // se recalcula el flujo
                $autoSaltarASiguiente = true;
                $decisionTomada = true;
            }
            else if ($flujo['next']['typeObject'] === 'output') {

                // Guardo la bitácora
                $bitacoraCoti = new CotizacionBitacora();
                $bitacoraCoti->cotizacionId = $item->id;
                $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                $bitacoraCoti->log = "Salida de datos \"{$flujo['actual']['nodoName']}\" -> \"{$flujo['next']['nodoName']}\"";
                $bitacoraCoti->save();

                $docsPlusToken = $flujo['next']['salidaPDFDp'] ?? false;
                $campoConfig = $flujo['next']['salidaPDFconf'] ?? false;

                // Si es pdf
                if (!empty($flujo['next']['salidaIsPDF'])) {

                    if (!empty($flujo['next']['pdfTpl'])  || $docsPlusToken) {

                        $userController = new AuthController();
                        $cintillo = $userController->GetCintillo();

                        $itemTemplate = PdfTemplate::where('id', intval($flujo['next']['pdfTpl']))->first();

                        $dir = '';
                        if (!empty($campoConfig['path'])) {
                            $dir = $campoConfig['path'];
                        }

                        $ch = curl_init();

                        $groupsPdf = [];
                        if(!empty($flujo['next']['salidaPDFGroup'])){
                            /*if($flujo['next']['salidaPDFGroup'] === 'veh'){
                                $dataForVehOrder =  $this->calculateDataVehiculeForVeh($item->id);
                                foreach($dataForVehOrder as $keyDataVeh => $dataForVeh){
                                    $sub =  strval($keyDataVeh + 1);
                                    $vehiculoId = $dataForVeh['vehId'];
                                    $groupsPdf[] = [
                                        'id' => $flujo['next']['salidaPDFId'] ."_". $sub,
                                        //'dataTables'=> $dataForVeh['dataTables'],
                                        'data'=> CotizacionDetalle::where('cotizacionId', $item->id)
                                            ->where(function ($query) use ($vehiculoId) {
                                                $query->where('cotizacionVehiculoId', $vehiculoId)
                                                    ->orWhereNull('cotizacionVehiculoId')
                                                    ->orWhere('cotizacionVehiculoId', 0);
                                            })
                                            ->get(),
                                    ];
                                };
                            }*/

                            if($flujo['next']['salidaPDFGroup'] === 'cot' || $flujo['next']['salidaPDFGroup'] === 'cot_emi'){
                                $dataForCotOrder =  $this->calculateDataVehiculeForCot($item->id);
                                $tmpCotiTables = $this->getVehiculosCotizacionesTables($item->id);

                                /*var_dump($dataForCotOrder);
                                var_dump($tmpCotiTables);

                                die;*/

                                /*$item->nodoActual = 'nodo_1710476372639';
                                $item->save();*/

                                //die('asdfasdfsdf');

                                foreach ($dataForCotOrder as $vehiDesc => $cotiTmp) {

                                    $countCot = 1;
                                    foreach($cotiTmp as $keyDataCot => $dataForCot){
                                        $cotId = $dataForCot['cotId'];
                                        $vehiculoId = $dataForCot['veh|id'];

                                        if ($flujo['next']['salidaPDFGroup'] === 'cot_emi') {
                                            $cotiEmi = CotizacionDetalleVehiculoCotizacion::where('id', $cotId)->first();
                                            if (empty($cotiEmi->emitirPoliza)) {
                                                continue;
                                            }
                                        }

                                        if (empty($cotId)) continue;
                                        if (empty($vehiculoId)) continue;

                                        /*var_dump($vehiculoId);
                                        var_dump($cotId);*/

                                        $dataFields = [];
                                        $dataFields['DISTRIBUIDOR_CINTILLO'] = ['id' => 'DISTRIBUIDOR_CINTILLO', 'nombre' => '', 'valorLong' => $cintillo];
                                        $dataFields['PDF_PAGOS_TABLE'] = ['id' => 'PDF_PAGOS_TABLE', 'nombre' => '', 'valorLong' => $tmpCotiTables[$vehiculoId][$cotId]['PDF_PAGOS_TABLE'] ?? '{"headers": [""], "rows": [[""]]}', 'noDecodeJson' => true];
                                        $dataFields['COBERTURA_GRUPO_1'] = ['id' => 'COBERTURA_GRUPO_1', 'nombre' => '', 'valorLong' => $tmpCotiTables[$vehiculoId][$cotId]['COBERTURA_GRUPO_1'] ?? '{"headers": [""], "rows": [[""]]}', 'noDecodeJson' => true];
                                        $dataFields['COBERTURA_GRUPO_2'] = ['id' => 'COBERTURA_GRUPO_2', 'nombre' => '', 'valorLong' => $tmpCotiTables[$vehiculoId][$cotId]['COBERTURA_GRUPO_2'] ?? '{"headers": [""], "rows": [[""]]}', 'noDecodeJson' => true];
                                        $dataFields['COBERTURA_GRUPO_3'] = ['id' => 'COBERTURA_GRUPO_3', 'nombre' => '', 'valorLong' => $tmpCotiTables[$vehiculoId][$cotId]['COBERTURA_GRUPO_3'] ?? '{"headers": [""], "rows": [[""]]}', 'noDecodeJson' => true];
                                        $dataFields['COBERTURA_GRUPO_4'] = ['id' => 'COBERTURA_GRUPO_4', 'nombre' => '', 'valorLong' => $tmpCotiTables[$vehiculoId][$cotId]['COBERTURA_GRUPO_4'] ?? '{"headers": [""], "rows": [[""]]}', 'noDecodeJson' => true];

                                        $dataTmp = CotizacionDetalle::where('cotizacionId', $item->id)
                                            ->where(function ($query) use ($cotId) {
                                                $query->where('cotizacionDetalleVehiculoCotId', $cotId)
                                                    ->orWhereNull('cotizacionDetalleVehiculoCotId')
                                                    ->orWhere('cotizacionDetalleVehiculoCotId', 0);
                                            })
                                            ->where(function ($query) use ($vehiculoId) {
                                                $query->where('cotizacionVehiculoId', $vehiculoId)
                                                    ->orWhereNull('cotizacionVehiculoId')
                                                    ->orWhere('cotizacionVehiculoId', 0);
                                            })
                                            ->get();


                                        foreach ($dataTmp as $itemTmp) {
                                            $dataFields[$itemTmp->campo] =['id' => $itemTmp->campo, 'nombre' => '', 'valorLong' => $itemTmp->valorLong];
                                        }

                                        foreach ($dataForCot as $campo => $data) {
                                            $dataFields[$campo] =['id' => $campo, 'nombre' => '', 'valorLong' => $data];
                                        }

                                        $groupsPdf[] = [
                                            'id' => $flujo['next']['salidaPDFId'] ."_". $vehiDesc . "_" . $countCot,
                                            //'dataTables'=> $dataForCot['dataTables'],
                                            'data'=> $dataFields,
                                        ];
                                        $countCot++;
                                    };
                                }


                            }
                        }
                        else {
                            $groupsPdf = [['id' => $flujo['next']['salidaPDFId'], 'data'=> $item->campos, 'dataTables'=> $this->calculateDataVehicule($item->id)]];
                        }

                        /*var_dump($groupsPdf);
                        $item->nodoActual = 'nodo_1710476372639';
                        $item->save();
                        die();*/

                        $conteoPdfs = 1;
                        foreach($groupsPdf as $groupPdf){
                            $docsPlusJson = $campoConfig['jsonSend'] ?? false;

                            $data = $groupPdf['data'];
                            $dataTables = $groupPdf['dataTables'] ?? [];
                            $flujo['next']['salidaPDFId'] = $groupPdf['id'];

                            if (!empty($docsPlusJson)) {
                                $docsPlusJson = $this->reemplazarValoresSalida($data, $docsPlusJson, false, false, true); // En realidad es salida pero lo guardan como entrada
                            }

                            $arrArchivo = [];
                            $expedientesNew = $campoConfig['expNewConf'] ?? [];
                            $urlExp = env('EXPEDIENTES_URL') . '/?api=true&opt=upload';

                            // Si usará nueva estructura de expedientes
                            if (!empty($expedientesNew['label'])) {

                                $urlExp = env('EXPEDIENTES_NEW_URL') . '/?api=true&opt=upload';

                                $arrArchivo['folderPath'] = trim(trim($dir), '/');
                                $arrArchivo['ramo'] = $expedientesNew['ramo'] ?? '';
                                $arrArchivo['label'] = ($expedientesNew['label'] ?? '')."-{$conteoPdfs}";
                                $arrArchivo['filetype'] = $expedientesNew['tipo'] ?? '';
                                $arrArchivo['sourceaplication'] = 'Gestor Comercial Automovil';
                                $arrArchivo['bucket'] = 'EXPEDIENTES';
                                $arrArchivo['overwrite'] = (!empty($expedientesNew['sobreescribir']) && $expedientesNew['sobreescribir'] === 'S') ? 'Y' : 'N';

                                /*if (!empty($campoSalida->expToken)) {
                                    $arrArchivo['token'] = $campoSalida->expToken;
                                }*/

                                foreach ($expedientesNew['attr'] as $attr) {
                                    $arrArchivo[$attr['attr']] = $attr['value'];
                                }
                            }
                            else {
                                // Se mandan indexados de la forma viejita

                                $arrArchivo['folderPath'] = trim(trim($dir), '/');
                                $arrArchivo['ramo'] = $campoConfig['fileRamo'] ?? '';
                                $arrArchivo['producto'] = $campoConfig['fileProducto'] ?? '';
                                $arrArchivo['fechaCaducidad'] = $campoConfig['fileFechaExp'] ?? '';
                                $arrArchivo['reclamo'] = $campoConfig['fileReclamo'] ?? '';
                                $arrArchivo['poliza'] = $campoConfig['filePoliza'] ?? '';
                                $arrArchivo['estadoPoliza'] = $campoConfig['fileEstadoPoliza'] ?? '';
                                $arrArchivo['nit'] = $campoConfig['fileNit'] ?? '';
                                $arrArchivo['dpi'] = $campoConfig['fileDPI'] ?? '';
                                $arrArchivo['cif'] = $campoConfig['fileCIF'] ?? '';
                                $arrArchivo['label'] = ($campoConfig['fileLabel'] ?? '')."-{$conteoPdfs}";
                                $arrArchivo['filetype'] = $campoConfig['fileTipo'] ?? '';
                                $arrArchivo['filetypeSecondary'] = $campoConfig['fileTipo2'] ?? '';
                                $arrArchivo['source'] = 'Gestor Comercial Automovil';
                            }

                            $arrSend = [];
                            foreach ($arrArchivo as $key => $itemTmp) {
                                $arrSend[$key] = $this->reemplazarValoresSalida($data, $itemTmp, false, $key === 'folderPath', true); // En realidad es salida pero lo guardan como entrada
                            }

                            $finalFilePath = '';
                            $errorPdfLog = '';
                            $fileNameHash = '';
                            $tmpPath = '';
                            $tmpFile = '';
                            $outputTmp = '';
                            $outputTmpPdf ='';

                            if (empty($docsPlusToken)) {

                                $fileNameHash = md5(uniqid());
                                $tmpPath = storage_path("tmp/");
                                $tmpFile = storage_path("tmp/".md5(uniqid()).".docx");
                                $outputTmp = storage_path("tmp/".$fileNameHash.".docx");
                                $outputTmpPdf = $fileNameHash.".pdf";

                                $s3_file = Storage::disk('s3')->get($itemTemplate->urlTemplate);
                                file_put_contents($tmpFile, $s3_file);

                                // reemplazo valores
                                $templateProcessor = new TemplateProcessor($tmpFile);
                                //dd($item->campos);

                                foreach ($dataTables as $campoTmp) {
                                    if (is_array($campoTmp['valorLong'] ?? '')) {
                                        $campoTmp['valorLong'] = implode(', ', $campoTmp['valorLong'] ?? '');
                                    }
                                    if(!empty($campoTmp['tipo']) && $campoTmp['tipo'] === 'encrypt'){
                                        $campoTmp['valorLong'] = $this->desencriptar($campoTmp['valorLong']);
                                    }
                                    $templateProcessor->setValue($campoTmp['campo'], htmlspecialchars($campoTmp['valorLong'] ?? ''));
                                }
                                foreach ($data as $campoTmp) {

                                    if (is_array($campoTmp)) continue;
                                    if (is_array($campoTmp->valorLong ?? '')) {
                                        $campoTmp->valorLong = implode(', ', $campoTmp->valorLong ?? '');
                                    }
                                    if(($campoTmp->tipo === 'signature' || $campoTmp->tipo === 'file' || $campoTmp->tipo === 'fileER') && !empty($campoTmp->valorLong)){
                                        $campoSign = CotizacionDetalle::where('cotizacionId', $item->id)->where('campo', $campoTmp->campo)->first();
                                        $extensionesImagen = ['jpg', 'jpeg', 'png'];
                                        $expresionRegular = '/\.(' . implode('|', $extensionesImagen) . ')$/i';
                                        if(!preg_match($expresionRegular, $campoSign->valorLong) && $campoTmp->tipo !== 'fileER') continue;
                                        if($campoTmp->tipo === 'fileER'){
                                            $headers = get_headers($campoSign->valorLong, 1);
                                            if(!empty($headers['Location'])){
                                                $extension = pathinfo($headers['Location'], PATHINFO_EXTENSION);
                                                if(!array_reduce($extensionesImagen, function ($carry, $item) use ($extension) {
                                                    return $carry || strpos($extension, $item) !== false;
                                                }, false)) continue;
                                            }

                                            if(empty($headers['Content-Type']) || (!is_array($headers['Content-Type']) && strpos($headers['Content-Type'], 'image') === false)) continue;
                                        };
                                        $temporarySignedUrl = $campoTmp->tipo !== 'fileER'
                                        ? Storage::disk('s3')->temporaryUrl($campoSign->valorLong, now()->addMinutes(10))
                                        : $campoSign->valorLong;

                                            foreach ($templateProcessor->getVariables() as $variable) {
                                                if (strpos($variable, $campoTmp->campo) !== false) {
                                                    if (!$templateProcessor->setImageValue($campoTmp->campo, $temporarySignedUrl)) continue;
                                                }
                                            }
                                    } else {
                                        $templateProcessor->setValue($campoTmp->campo, htmlspecialchars($campoTmp->valorLong ?? ''));
                                    }
                                }
                                // dd($templateProcessor->getVariables());
                                foreach($templateProcessor->getVariables() as $variable){
                                    if (!$public && strpos($variable, 'CINTILLO_TIENDA') !== false && !empty($userHandler->GetCintillo())) {
                                        if (!$templateProcessor->setImageValue('CINTILLO_TIENDA', $userHandler->GetCintillo())) continue;
                                    }
                                    else $templateProcessor->setValue($variable, '');
                                }
                                $templateProcessor->saveAs($outputTmp);

                                // lowriter, pdf conversion
                                putenv('PATH=/usr/local/bin:/bin:/usr/bin:/usr/local/sbin:/usr/sbin:/sbin');
                                putenv('HOME=' . $tmpPath);
                                exec("/usr/bin/lowriter --convert-to pdf {$outputTmp} --outdir '{$tmpPath}'", $outputInfo);
                                if (file_exists($tmpPath)) {
                                    $errorPdfLog = json_encode($outputInfo);
                                }
                                else {
                                    $errorPdfLog = 'No se pudo cargar el template';
                                }

                                $finalFilePath = "{$tmpPath}{$outputTmpPdf}";
                            }
                            else {
                                $headers = array(
                                    'Content-Type: application/json',
                                    'Authorization: Bearer ' . env('ANY_SUBSCRIPTIONS_TOKEN')
                                );

                                if (empty($docsPlusJson)) {
                                    $dataSend = [];
                                    $dataSend['token'] = $docsPlusToken;
                                    $dataSend['operation'] = 'generate';
                                    $dataSend['response'] = 'url';
                                    $dataSend['data'] = [];
                                    foreach ($data as $campo) {
                                        if(empty($campo->campo)) {
                                            continue;
                                        }
                                        if ($campo->tipo === 'text' ||
                                            $campo->tipo === 'option' ||
                                            $campo->tipo === 'select' ||
                                            $campo->tipo === 'textArea' ||
                                            $campo->tipo === 'default' ||
                                            $campo->tipo === 'number' ||
                                            $campo->tipo === 'date'
                                        ) {
                                            $dataSend['data'][$campo->campo] = $campo->valorLong;
                                        }

                                        if ($campo->tipo === 'signature') {
                                            if (empty($campo->valorLong)) continue;
                                            $dataSend['data'][$campo->campo] = Storage::disk('s3')->temporaryUrl($campo->valorLong, now()->addMinutes(80));
                                        }

                                        if ($campo->tipo === 'file' && !empty($campo->valorLong)) {
                                            if (empty($dataSend['data'][$campo->campo])) {
                                                $dataSend['data'][$campo->campo] = [];
                                            }
                                            $dataSend['data'][$campo->campo][] = Storage::disk('s3')->temporaryUrl($campo->valorLong, now()->addMinutes(80));
                                        }

                                        if (empty($campo->tipo)) {
                                            $dataSend['data'][$campo->campo] = $campo->valorLong;
                                        }

                                        if ($campo->tipo === 'checkbox' || $campo->tipo === 'multiselect') {
                                            if (empty($campo->valorLong)) continue;
                                            if(!is_array($campo->valorLong)) $campo->valorLong = json_decode($campo->valorLong, true);
                                            $dataSend['data'][$campo->campo] = implode(", ", $campo->valorLong);
                                        }
                                    }
                                }

                                $dataToSend = (empty($docsPlusJson) ? json_encode($dataSend) : $docsPlusJson);

                                $ch = curl_init(env('ANY_SUBSCRIPTIONS_URL', '') . '/formularios/docs-plus/generate');
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                curl_setopt($ch, CURLOPT_POSTFIELDS, $dataToSend);
                                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                                $data = curl_exec($ch);
                                $info = curl_getinfo($ch);
                                curl_close($ch);
                                $dataResponse = @json_decode($data, true);

                                $tmpDataRece = (string) $data;
                                $bitacoraCoti = new CotizacionBitacora();
                                $bitacoraCoti->cotizacionId = $item->id;
                                $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                                $bitacoraCoti->log = "Enviando a Docs+, Enviado: {$dataToSend}, Recibido: {$tmpDataRece}";
                                $bitacoraCoti->save();

                                if (empty($dataResponse['status'])) {
                                    // Guardo la bitácora
                                    $bitacoraCoti = new CotizacionBitacora();
                                    $bitacoraCoti->cotizacionId = $item->id;
                                    $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                                    $bitacoraCoti->log = "Error al crear PDF, verifique sus credenciales de acceso o el token de plantilla";
                                    $bitacoraCoti->save();
                                }
                                else {
                                    if (!empty($dataResponse['data']['url'])) {

                                        $finalFilePath = storage_path("tmp/" . md5(uniqid()) . ".pdf");
                                        file_put_contents($finalFilePath, file_get_contents($dataResponse['data']['url']));

                                        // Guardo la bitácora
                                        $bitacoraCoti = new CotizacionBitacora();
                                        $bitacoraCoti->cotizacionId = $item->id;
                                        $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                                        $bitacoraCoti->log = "Archivo PDF generado con éxito, token Docs+: {$flujo['next']['salidaPDFDp']}";
                                        $bitacoraCoti->save();
                                    }
                                }
                            }

                            $path = '';
                            $token = null;
                            if (file_exists($finalFilePath)) {

                                // $disk = Storage::disk('s3');
                                //$path = $disk->putFileAs("/".md5($itemTemplate->id)."/files", $finalFilePath, md5(uniqid()).".pdf");

                                if (empty($arrSend['folderPath'])) {
                                    return $this->ResponseError('T-223', 'Uno o más campos son requeridos previo a la subida de este archivo');
                                }

                                $arrSend['file'] = new \CurlFile($finalFilePath, 'application/pdf');
                                $arrSend['file']->setPostFilename($arrSend['folderPath'] . '/' . ($arrSend['label']."_{$conteoPdfs}") . '.pdf');

                                $headers = [
                                    'Authorization: Bearer 1TnwxbcvSesYkiqzl2nsmPgULTlYZFgSrcb3hSb383Tkv0ZzyaBz0sjD7LM2ymh',
                                ];
                                //dd($arrArchivo);

                                curl_setopt($ch, CURLOPT_URL, $urlExp);
                                curl_setopt($ch, CURLOPT_POST, 1);
                                curl_setopt($ch, CURLOPT_POSTFIELDS, $arrSend);
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                                $server_output = curl_exec($ch);
                                $server_output = @json_decode($server_output, true);
                                curl_close($ch);

                                //dd($server_output);

                                if (!empty($server_output['status'])) {
                                    /*
                                    $campo = CotizacionDetalle::where('campo', 'SYSTEM_TEMPLATE')->where('cotizacionId', $item->id)->first();

                                    if (empty($campo)) {
                                        $campo = new CotizacionDetalle();
                                    }
                                    $campo->cotizacionId = $item->id;
                                    $campo->seccionKey = 0;
                                    $campo->campo = 'SYSTEM_TEMPLATE';
                                    $campo->valorLong = $server_output['data']['exp-url'];
                                    $campo->isFile = 1;
                                    $campo->fromSalida = 1;
                                    $campo->save(); */

                                    $path = $server_output['data']['exp-url'];
                                    $token = $server_output['data']['token'] ?? null;

                                    /*return $this->ResponseSuccess('Archivo subido con éxito', [
                                        'key' => $server_output['data']['s3-url-tmp']
                                    ]);*/
                                }
                                else {
                                    $errorMsg = 'Error al cargar archivo, por favor intente de nuevo';
                                    if(!empty($server_output['msg'])) $errorMsg = $server_output['msg'];
                                    return $this->ResponseError('T-222', $errorMsg);
                                }
                            }
                            else {
                                $bitacoraCoti = new CotizacionBitacora();
                                $bitacoraCoti->cotizacionId = $item->id;
                                $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                                $bitacoraCoti->log = "Error al generar PDF, la plantilla parece corrupta. \"{$flujo['actual']['nodoName']}\" -> \"{$flujo['next']['nodoName']}\"";
                                $bitacoraCoti->dataInfo = $errorPdfLog;
                                $bitacoraCoti->save();
                            }

                            if (file_exists($tmpFile)) unlink($tmpFile);
                            if (file_exists($outputTmp)) unlink($outputTmp);
                            if (file_exists("{$tmpPath}{$outputTmpPdf}")) unlink("{$tmpPath}{$outputTmpPdf}");

                            $campoKeyTmp = (!empty($flujo['next']['salidaPDFId'])) ? $flujo['next']['salidaPDFId'] : 'SALIDA_'.($flujo['next']['nodoId']);
                            //$campoKeyTmp = $campoKeyTmp."_{$conteoPdfs}";
                            $campoSalida = CotizacionDetalle::where('campo', $campoKeyTmp)->where('cotizacionId', $item->id)->first();
                            if (empty($campoSalida)) {
                                $campoSalida = new CotizacionDetalle();
                            }
                            $campoSalida->cotizacionId = $item->id;
                            $campoSalida->seccionKey = 0;
                            $campoSalida->campo = $campoKeyTmp;
                            $campoSalida->label = ($flujo['next']['salidaPDFconf']['fileLabel'] ?? 'Archivo sin nombre')."-{$conteoPdfs}";
                            $campoSalida->expToken = $token;
                            $campoSalida->valorLong = $path;
                            $campoSalida->isFile = true;
                            $campoSalida->fromSalida = true;
                            $campoSalida->save();

                            $conteoPdfs++;
                        };

                    }
                }

                $item->refresh();

                if (!empty($flujo['next']['salidaIsWhatsapp']) && empty($flujo['next']['procesoWhatsapp']['autoSend'])) {
                    $whatsappToken = $flujo['next']['procesoWhatsapp']['token'] ?? '';
                    $whatsappUrl = $flujo['next']['procesoWhatsapp']['url'] ?? '';
                    $whatsappAttachments = $flujo['next']['procesoWhatsapp']['attachments'] ?? '';

                    $whatsappData = (!empty($flujo['next']['procesoWhatsapp']['data'])) ? $this->reemplazarValoresSalida($camposAllLevelTarea, $flujo['next']['procesoWhatsapp']['data']) : false;

                    // chapus para yalo
                    $tmpData = json_decode($whatsappData, true);
                    if (isset($tmpData['users'][0]['params']['document']['link'])) {
                        $tmpData['users'][0]['params']['document']['link'] = $this->getWhatsappUrl($tmpData['users'][0]['params']['document']['link']);
                        $whatsappData = json_encode($tmpData, JSON_UNESCAPED_SLASHES);
                    }

                    $headers = [
                        'Authorization: Bearer ' . $whatsappToken ?? '',
                        'Content-Type: application/json',
                    ];

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $whatsappUrl ?? '');
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $whatsappData);  //Post Fields
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    $server_output = curl_exec($ch);
                    $yaloTmp = $server_output;
                    $server_output = @json_decode($server_output, true);
                    // dd($server_output);
                    curl_close($ch);

                    $bitacoraCoti = new CotizacionBitacora();
                    $bitacoraCoti->cotizacionId = $item->id;
                    $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                    $bitacoraCoti->onlyPruebas = 1;
                    $bitacoraCoti->dataInfo = "<b>URL:</b> {$whatsappUrl}, <b>Enviado:</b> {$whatsappData}, <b>Recibido:</b> {$yaloTmp}";
                    $bitacoraCoti->log = "Enviado Whatsapp";
                    $bitacoraCoti->save();

                    if (empty($server_output['success'])) {
                        // Guardo la bitácora
                        $bitacoraCoti = new CotizacionBitacora();
                        $bitacoraCoti->cotizacionId = $item->id;
                        $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                        $bitacoraCoti->onlyPruebas = 1;
                        $bitacoraCoti->log = "Error al enviar WhatsApp: {$whatsappData}";
                        $bitacoraCoti->save();
                    }
                    else {
                        $bitacoraCoti = new CotizacionBitacora();
                        $bitacoraCoti->cotizacionId = $item->id;
                        $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                        $bitacoraCoti->log = "Enviado WhatsApp con éxito";
                        $bitacoraCoti->save();
                    }
                }

                if (!empty($flujo['next']['salidaIsEmail']) && empty($flujo['next']['procesoEmail']['autoSend'])) {

                    // dd($flujo['next']);

                    $destino = (!empty($flujo['next']['procesoEmail']['destino'])) ? $this->reemplazarValoresSalida($camposAllLevelTarea, $flujo['next']['procesoEmail']['destino']) : false;
                    $asunto = (!empty($flujo['next']['procesoEmail']['asunto'])) ? $this->reemplazarValoresSalida($camposAllLevelTarea, $flujo['next']['procesoEmail']['asunto']) : false;
                    $config = $flujo['next']['procesoEmail']['mailgun'] ?? [];

                    // reemplazo plantilla
                    $contenido = $flujo['next']['procesoEmail']['salidasEmail'];
                    $contenido = $this->reemplazarValoresSalida($camposAllLevelTarea, $contenido);

                    $attachments = $flujo['next']['procesoEmail']['attachments'] ?? false;

                    $attachmentsSend = [];
                    if ($attachments) {
                        $attachments = explode(',', $attachments);

                        foreach ($attachments as $attach) {
                            $campoTmp = CotizacionDetalle::where('campo', $attach)->where('cotizacionId', $item->id)->first();

                            if (!empty($campoTmp) && !empty($campoTmp['valorLong'])){
                                $ext = 'pdf';
                               // $ext = pathinfo($campoTmp['valorLong'] ?? '', PATHINFO_EXTENSION);
                               // $s3_file = Storage::disk('s3')->get($campoTmp['valorLong']);
                                $s3_file = file_get_contents($campoTmp['valorLong']);
                                $attachmentsSend[] = ['fileContent'=>$s3_file, 'filename'=>($campoTmp['label'] ?? 'Sin nombre').'.'.$ext];
                            } else {
                                $bitacoraCoti = new CotizacionBitacora();
                                $bitacoraCoti->cotizacionId = $item->id;
                                $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                                $bitacoraCoti->log = "Error al enviar adjunto  \"{$attach}\" en el correo";
                                $bitacoraCoti->save();

                            }
                        }
                    }

                    // reemplazo
                    $config['domain'] = $this->reemplazarValoresSalida($camposAllLevelTarea, $config['domain']);
                    $config['from'] = $this->reemplazarValoresSalida($camposAllLevelTarea, $config['from']);
                    $config['apiKey'] = $this->reemplazarValoresSalida($camposAllLevelTarea, $config['apiKey']);

                    $config['domain'] = $config['domain'] ?? 'N/D';

                    try {
                        $mg = Mailgun::create($config['apiKey'] ?? ''); // For US servers
                        $email = $mg->messages()->send($config['domain'] ?? '', [
                            'from'    => $config['from'] ?? '',
                            'to'      => $destino ?? '',
                            'subject' => $asunto ?? '',
                            'html'    => $contenido,
                            'attachment' => $attachmentsSend
                        ]);

                        // Guardo la bitácora
                        $bitacoraCoti = new CotizacionBitacora();
                        $bitacoraCoti->cotizacionId = $item->id;
                        $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                        $bitacoraCoti->log = "Enviado correo electrónico \"{$destino}\" desde \"{$config['from']}\"";
                        $bitacoraCoti->save();
                        // return $this->ResponseSuccess( 'Si tu cuenta existe, llegará un enlace de recuperación');
                    }
                    catch (HttpClientException $e) {
                        // Guardo la bitácora
                        $bitacoraCoti = new CotizacionBitacora();
                        $bitacoraCoti->cotizacionId = $item->id;
                        $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                        $bitacoraCoti->log = "Error al enviar correo electrónico \"{$destino}\" desde \"{$config['from']}\", dominio de salida: {$config['domain']}";
                        $bitacoraCoti->save();
                        // return $this->ResponseError('AUTH-RA94', 'Error al enviar notificación, verifique el correo o la configuración del sistema');
                    }
                }

                // salto automático para outputs
                if (!empty($flujo['next']['saltoAutomatico']) && empty($flujo['next']['salidaIsHTML'])) {
                    $autoSaltarASiguiente = true;
                }
            }
            else if ($flujo['next']['typeObject'] === 'vehiculo') {
                // si no existen vehículos en la cotización, se crea uno
                $cotizacionVehiculo = CotizacionDetalleVehiculo::where('cotizacionId', $item->id)->first();

                if (empty($cotizacionVehiculo)) {
                    $cotizacionVehiculo = new CotizacionDetalleVehiculo();
                    $cotizacionVehiculo->cotizacionId = $item->id;
                    $cotizacionVehiculo->save();

                    // se crea una cotización
                    $tmpSubCoti = new CotizacionDetalleVehiculoCotizacion();
                    $tmpSubCoti->tarifaId = 0;
                    $tmpSubCoti->cotizacionId = $item->id;
                    $tmpSubCoti->cotizacionDetalleVehiculoId = $cotizacionVehiculo->id;
                    $tmpSubCoti->formaPagoId = 0;
                    $tmpSubCoti->numeroPagos = 0;
                    $tmpSubCoti->descuentoPorcentaje = 0;
                    $tmpSubCoti->save();
                }
            }

            // Cambio el flujo al nodo next si existe
            if (!empty($flujo['next']['nodoId'])) {
                $item->nodoActual = $flujo['next']['nodoId'];
                if (!empty($flujo['next']['estOut']) && ($flujo['next']['estIo'] === 'e')) $item->estado = $flujo['next']['estOut'];
                $item->save();// Cambio el flujo al nodo next
            }
            else {
                $autoSaltarASiguiente = false;
            }
        }
        else if ($paso === 'prev') {

            if (!empty($item->trajectory)){
                $trajectory = @json_decode($item->trajectory, true);
                $lastNode = array_pop($trajectory);
                $item->nodoActual = $lastNode;
                $item->trajectory = count($trajectory) === 0 ? null : json_encode($trajectory, JSON_FORCE_OBJECT);
                foreach ($flujoConfig['data']['nodes'] as $node) {
                    if ($node['id'] === $lastNode) {
                        $flujo['prev']['nodoName'] = $node['nodoName'];
                    }
                }
                // $flujoConfig['data']['nodes']
            }
            elseif (!empty($item->nodoPrevio)) {
                if ((!empty($flujo['prev']['procesos'][0]) && !empty($flujo['prev']['procesos'][0]['url']) || ($flujo['prev']['typeObject'] === 'condition'))) {
                    $autoSaltarASiguiente = true;
                }

                else if ($flujo['prev']['typeObject'] === 'setuser') {
                    $autoSaltarASiguiente = true;
                }

                // Cambio el flujo al nodo next
                $item->nodoActual = $flujo['prev']['nodoId'];
            }
            else {
                $item->nodoActual = $item->nodoPrevio;
            }

            $idNodoActual = $item->nodoActual;

            foreach ($flujoConfig['data']['nodes'] as $nodo) {

                if (empty($nodo['estIo'])) {
                    $nodo['estIo'] = '';
                }

                if ($nodo['id'] === $idNodoActual) {
                    if (!empty($nodo['estOut']) && ($nodo['estIo'] === 'e')) $item->estado = $nodo['estOut'];
                }
                else if(empty($idNodoActual)){
                    if ($nodo['typeObject'] === 'start' && !empty($nodo['formulario']['tipo'])){
                        if (!empty($nodo['estOut']) && ($nodo['estIo'] === 'e')) $item->estado = $nodo['estOut'];
                    }
                }
            }

            $item->save();

            // Guardo la bitácora
            $bitacoraCoti = new CotizacionBitacora();
            $bitacoraCoti->cotizacionId = $item->id;
            $bitacoraCoti->usuarioId = $usuarioLogueadoId;
            $bitacoraCoti->log = "Regreso de paso \"{$flujo['actual']['nodoName']}\" -> \"{$flujo['prev']['nodoName']}\"";
            $bitacoraCoti->save();
        }
        else if ($paso === 'start'){
            if (!empty($usuarioLogueadoId)) {
                $AC = new AuthController();
                if (!$AC->CheckAccess(['tareas/show-button-re-start'])) return $AC->NoAccess();
            }
            $nodoStart = '';
            foreach ($flujoConfig['data']['nodes'] as $nodo) {
                if ($nodo['typeObject'] === 'start' && !empty($nodo['formulario']['tipo'])) {
                   $nodoStart = $nodo['id'];
                   $nodoEstOut = $nodo['estOut'];
                    $nodoEstIo = $nodo['estIo'];
                }
            }
            if(!empty($nodoStart)){
                $item->nodoActual = $nodoStart;
                if (!empty($nodoEstOut) && ($nodoEstIo === 'e')) $item->estado = $nodoEstOut;
                $item->save();

                // Guardo la bitácora
                $bitacoraCoti = new CotizacionBitacora();
                $bitacoraCoti->cotizacionId = $item->id;
                $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                $nodoPrev = $flujo['prev']? $flujo['prev']['nodoName'] : $flujo['actual']['nodoName'];
                $bitacoraCoti->log = "Regreso al inicio de \"{$flujo['actual']['nodoName']}\" -> \"{$nodoPrev}\"";
                $bitacoraCoti->save();

            } else {
                return $this->ResponseError('COT-011', 'Error al actualizar tarea, no se encontro no de inicio');
            }

        }

        // Si no está visible el next, valido la lógica de asignación
        /*if (!empty($flujo['next']['visible'])) {
            if (!empty($flujo['next']['userAssign']['logicaAsig']) && $flujo['next']['userAssign']['logicaAsig'] === 'saltar') {
                $autoSaltarASiguiente = true;
            }
        }
        if (!empty($flujo['prev']['visible'])) {
            if (!empty($flujo['prev']['userAssign']['logicaAsig']) && $flujo['prev']['userAssign']['logicaAsig'] === 'saltar') {
                $autoSaltarASiguiente = true;
            }
        }*/

        /*if ($flujo['actual']['nodoName'] === 'USERTEST') {
            dd($flujo);
        }*/

        if ($autoSaltarASiguiente) {
            return $this->CambiarEstadoCotizacion($request, true, $decisionTomada, $originalStep, $public);
        }

        if ($item->save()) {
            return $this->ResponseSuccess('Tarea actualizada con éxito', ['id' => $item->id]);
        }
        else {
            return $this->ResponseError('COT-016', 'Error al actualizar tarea, por favor intente de nuevo');
        }
    }

    private function wsCustomAuto($identificadorWs, $cotizacionId, $entradaAnterior, $campos, $vehiculoId = null) {

        $ejecuciones = [
            'type' => '',
            'list' => [],
            'errors' => [],
            'cancelar' => false,
        ];

        $camposAll = [];

        foreach ($campos as $field) {
            $field = $field->toArray();
            $camposAll[$field['campo']] = $field;
        }

        // Cotización

        if ($identificadorWs === 'COTIZACION_AS400') {

            $cotizacion = Cotizacion::where('id', $cotizacionId)->first();

            $ejecuciones['type'] = $identificadorWs;

            $strVehicuos = "";
            $codigoAgente = $camposAll['CODIGO_AGENTE']['valorLong'] ?? "";
            $cotizacionVehiculo = CotizacionDetalleVehiculo::where('cotizacionId', $cotizacionId)->with('linea')->get();
            $productoId = 0;


            $vehiNumber = 1;
            foreach ($cotizacionVehiculo as $vehi) {

                if (empty($vehi->noPasajeros)) {
                    $ejecuciones['errors'][] = 'Debe ingresar el número de pasajeros.';
                }
                if (empty($vehi->tipoId)) {
                    $ejecuciones['errors'][] = 'Debe seleccionar tipo de vehículo.';
                }

                $coti = CotizacionDetalleVehiculoCotizacion::where('cotizacionDetalleVehiculoId', $vehi->id)->with('tarifa', 'formaPago')->get();

                $cotiNumber = 1;
                foreach ($coti as $cotiTmp) {

                    // traigo cotizaciones y coberturas
                    $coberturas = "";

                    // producto
                    $productoTarifaTmp = catProductoTarifa::where('idTarifa', $cotiTmp->tarifaId)->where('idProducto', $cotiTmp->productoId)->with('producto')->first();
                    //$productoId = $productoTarifaTmp->producto->codigoProducto;

                    $coberturaTmp = CotizacionDetalleVehiculoCotizacionCobertura::where('cotizacionDetalleVehiculoCotId', $cotiTmp->id)->get();
                    $isBlindaje = false;
                    $isMinors = false;
                    $blindaje = "";
                    foreach ($coberturaTmp as $cober) {

                        $tieneDescuento = $cober->cobertura->tieneDescuento;

                        $descuentoPorcentaje = '';
                        if (!empty($tieneDescuento)) {
                            $descuentoPorcentaje = $cotiTmp->descuentoPorcentaje;
                        }

                        $cotRecargo = "{{SYS_COT_REC}}";
                        if ($cotizacion->siniesBlockRecargo !== null || $cotizacion->siniesBlockRecargo > 0) {
                            $cotRecargo = $cotizacion->siniesBlockRecargo;
                        }

                        $montoTmp = floatval($cober->monto);
                        //$montoCobertura = ((!empty($cober->monto) && $cober->monto > 0) ? $cober->monto : $vehi->valorProm);
                        $montoCobertura = (!empty($cober->monto) ? $cober->monto : 0);
                        $coberturas .= "<cobertura><idCobertura>{$cober->codigoCobertura}</idCobertura><sumaAsegurada>{$montoCobertura}</sumaAsegurada><valorDescuento>0</valorDescuento><idDescuentoPorRol>0</idDescuentoPorRol><porcentajeDescuentoRol>{$descuentoPorcentaje}</porcentajeDescuentoRol><porcentajeDescuentoRango>{$cotRecargo}</porcentajeDescuentoRango></cobertura>";

                        if(empty($cober) || empty($cober->cobertura)) continue;
                        $isBlindaje = $isBlindaje || $cober->cobertura->blindaje;
                        $isMinors = $isMinors || $cober->cobertura->minors;
                        if(!empty($cober->cobertura->blindaje)) $blindaje = $montoTmp;
                    }

                    if(empty($cotiTmp->frecuenciaPagos)){
                        $ejecuciones['errors'][] = 'Debe seleccionar frecuencia de pago para todas las cotizaciones.';
                        continue;
                    }

                    $dataFrecuenciaPagos = json_decode($cotiTmp->frecuenciaPagos,true);

                    // valida frecuencias
                    foreach ($dataFrecuenciaPagos as $frec) {
                        if(empty($frec['f'])){
                            $ejecuciones['errors'][] = 'Debe seleccionar frecuencia de pago para todas las cotizaciones';
                            continue;
                        }
                        if(empty($frec['p']) || count($frec['p']) === 0){
                            $ejecuciones['errors'][] = 'Debe seleccionar número de pagos para todas las cotizaciones';
                            continue;
                        }
                    }

                    $frecuenciasToSend = [];

                    foreach($dataFrecuenciaPagos as $data){
                        if(empty($data['f'])) continue;
                        $frecuenciaSelect = catFormaPago::where('id', (int) $data['f'])->first();
                        foreach($data['p'] as $p){
                            $frecuenciasToSend[] = ['f' => $frecuenciaSelect->codigo, 'p' => $p];
                        }
                    }

                    if(count($frecuenciasToSend) < 1){
                        $ejecuciones['errors'][] = 'Debe seleccionar forma de pago para todas las cotizaciones';
                    }

                    /*if (empty($cotiTmp->formaPago->codigo)) {
                        $ejecuciones['errors'][] = 'Debe seleccionar forma de pago para todas las cotizaciones';
                        continue;
                    }
                    $numeroPagos = intval($cotiTmp->numeroPagos);*/

                    $listaPagos = "";
                    foreach($frecuenciasToSend as $frecuenciadetail){
                        $listaPagos = $listaPagos . "<listaFrecuencia><frecuencia>{$frecuenciadetail['f']}</frecuencia><numeroPagos>{$frecuenciadetail['p']}</numeroPagos></listaFrecuencia>";
                    }
                    //<frecuencia>{$cotiTmp->formaPago->codigo}</frecuencia><numeroPagos>{$cotiTmp->numeroPagos}</numeroPagos>

                    $data = <<<EOD
                            {
                                "nprogram": "XXPD539",
                                "dtainput": "<datosIdEmpresaGC><idEmpresa>01</idEmpresa><datos01><datosCotizacionGestorComercial2><idTarifa>{$cotiTmp->tarifa->idTarifa}</idTarifa><codigoAgente>{$codigoAgente}</codigoAgente><moneda>{$productoTarifaTmp->producto->idMoneda}</moneda><idProducto>{$productoId}</idProducto><listaFrecuenciaPagos>{$listaPagos}</listaFrecuenciaPagos><listaCoberturas>{$coberturas}</listaCoberturas><datosVehiculo><marcaLinea>{$vehi->linea->codigo}</marcaLinea><placa>{$vehi->placa}</placa><chasis>{$vehi->noChasis}</chasis><motor>{$vehi->noMotor}</motor><modeloVehiculo>{$vehi->modelo}</modeloVehiculo><valorBlindaje>{$blindaje}</valorBlindaje><numeroPasajeros>{$vehi->noPasajeros}</numeroPasajeros><idOpcionDeducibleRobo>{$vehi->altoRiesgoDisp}</idOpcionDeducibleRobo></datosVehiculo></datosCotizacionGestorComercial2></datos01><datos02></datos02></datosIdEmpresaGC>"
                            }
                        EOD;
                    $ejecuciones['list'][] = [
                        'vehiculoId' => $cotiTmp->vehiculo->id,
                        'cotizacionDetalleVehiculoCotId' => $cotiTmp->id,
                        'entrada' => $data,
                        'cotiNumber' => $cotiNumber,
                        'vehiNumber' => $vehiNumber,
                    ];
                    $cotiNumber++;
                }

                $vehiNumber++;
            }
            /*var_dump($ejecuciones);
            die();*/
        }
        else if ($identificadorWs === 'EMISION_DATOS_CLIENTE_AS400') {

            $ejecuciones['type'] = $identificadorWs;

            $strVehicuos = "";

            $cotizacionVehiculo = CotizacionDetalleVehiculo::where('cotizacionId', $cotizacionId)->with('linea')->get();
            $productoId = 0;

            $vehiNumber = 1;
            foreach ($cotizacionVehiculo as $vehi) {

                // traigo cotizaciones y coberturas
                $coberturas = "";

                $coti = CotizacionDetalleVehiculoCotizacion::where('cotizacionDetalleVehiculoId', $vehi->id)->with('tarifa', 'formaPago')->get();

                $cotiNumber = 1;
                foreach ($coti as $cotiTmp) {

                    // producto
                    $productoTarifaTmp = catProductoTarifa::where('idTarifa', $cotiTmp->tarifaId)->with('producto')->first();
                    $productoId = $productoTarifaTmp->codigoProducto;

                    $coberturaTmp = CotizacionDetalleVehiculoCotizacionCobertura::where('cotizacionDetalleVehiculoCotId', $cotiTmp->id)->get();

                    $isBlindaje = false;
                    $isMinors = false;
                    $blindaje = "";

                    foreach ($coberturaTmp as $cober) {
                        $montoTmp = floatval($cober->monto);
                        $montoCobertura = (!empty($cober->monto) ? $cober->monto : $vehi->valorProm);
                        $coberturas .= "<cobertura><idCobertura>{$cober->codigoCobertura}</idCobertura><sumaAsegurada>{$montoCobertura}</sumaAsegurada><valorDescuento>0</valorDescuento><idDescuentoPorRol>0</idDescuentoPorRol><porcentajeDescuentoRol>{$cotiTmp->descuentoPorcentaje}</porcentajeDescuentoRol></cobertura>";

                        if(empty($cober) || empty($cober->cobertura)) continue;
                        $isBlindaje = $isBlindaje || $cober->cobertura->blindaje;
                        $isMinors = $isMinors || $cober->cobertura->minors;
                        if(!empty($cober->cobertura->blindaje)) $blindaje = $montoTmp;
                    }

                    $data = $camposAll;
                    $data['SYS_NO_COTIZACION'] =['id' => 'SYS_NO_COTIZACION', 'nombre' => '', 'valorLong' => $cotiTmp->numeroCotizacionAS400];
                    $data['SYS_FECHA_COTIZACION'] = ['id' => 'SYS_FECHA_COTIZACION', 'nombre' => '', 'valorLong' => Carbon::now()->format('d/m/Y')];

                    $dataSend = $this->reemplazarValoresSalida($data, $entradaAnterior);
                    /*var_dump($dataSend);
                    die();*/
                    $ejecuciones['list'][] = [
                        'cotizacionDetalleVehiculoCotId' => $cotiTmp->id,
                        'entrada' => $dataSend,
                        'cotiNumber' => $cotiNumber,
                        'vehiNumber' => $vehiNumber,
                    ];
                    $cotiNumber++;
                }
                $vehiNumber++;
            }
        }
        // Emisión final
        else if ($identificadorWs === 'EMISION_AS400') {

            $ejecuciones['type'] = $identificadorWs;

            /*var_dump($identificadorWs);
            die();*/



            $strVehicuos = "";

            $cotizacionVehiculo = CotizacionDetalleVehiculo::where('cotizacionId', $cotizacionId)->with('linea')->get();
            $productoId = 0;


            $fechaTmp = $this->reemplazarValoresSalida($camposAll, '{{datos_cliente_fecha_contratacion}}');
            $fechaHoyRaw = Carbon::parse($fechaTmp);
            $fechaHoy = Carbon::now()->format('d/m/Y');

            $vigenciaDesde = $fechaHoyRaw->format('d/m/Y');
            $vigenciaHasta = $fechaHoyRaw->addYears(1)->format('d/m/Y');

            $tipoLinea = '';
            $tipoCartera = '';
            $tipoProduccion = '';
            $tipoMovimiento = '';
            $subtipoMovimiento = '';
            $tipoDocumento = '';
            $tipoUsuario = '';
            $tipoAsignacion = '';

            $AC = new AuthController();
            $canal = $AC->getFirstCanal();

            if(!empty($canal)){
                $tipoLinea = $canal->tipoLinea ?? '';
                $tipoCartera = $canal->tipoCartera ?? '';
                $tipoProduccion = $canal->tipoProduccion ?? '';
                $tipoMovimiento = $canal->tipoMovimiento ?? '';
                $subtipoMovimiento = $canal->subtipoMovimiento ?? '';
                $tipoDocumento = $canal->tipoDocumento ?? '';
                $tipoUsuario = $canal->tipoUsuario ?? '';
                $tipoAsignacion = $canal->tipoAsignacion ?? '';
            }

            $vehiNumber = 1;
            foreach ($cotizacionVehiculo as $vehi) {

                // traigo cotizaciones y coberturas
                $coberturas = "";

                $coti = CotizacionDetalleVehiculoCotizacion::where('cotizacionDetalleVehiculoId', $vehi->id)->where('emitirPoliza', 1)->with('tarifa', 'formaPago')->get();
                $vehiculoId = $vehi->id;
                $dataVehiculo = [];

                foreach ($campos as $field) {
                    $field = $field->toArray();
                    if(empty($field['cotizacionVehiculoId']) ||
                        $field['cotizacionVehiculoId'] == $vehiculoId ||
                        $field['cotizacionVehiculoId'] == 0 ) $dataVehiculo[$field['campo']] = $field;
                }

                $camposForVehi = CotizacionDetalle::where('cotizacionId', $cotizacionId)->where('cotizacionVehiculoId', $vehiculoId)->get();

                $cotiNumber = 1;
                foreach ($coti as $cotiTmp) {

                    $vehiculo = $cotiTmp->vehiculo;

                    // producto
                    $productoTarifaTmp = catProductoTarifa::where('idTarifa', $cotiTmp->tarifaId)->with('producto')->first();
                    $productoId = $productoTarifaTmp->codigoProducto;

                    // variables
                    $dataVars = $dataVehiculo;
                    $dataVars['SYS_NO_COTIZACION'] =['id' => 'SYS_NO_COTIZACION', 'nombre' => '', 'valorLong' => $cotiTmp->numeroCotizacionAS400];
                    $dataVars['SYS_FECHA_COTIZACION'] = ['id' => 'SYS_FECHA_COTIZACION', 'nombre' => '', 'valorLong' => Carbon::now()->format('d/m/Y')];

                    $coberturaTmp = CotizacionDetalleVehiculoCotizacionCobertura::where('cotizacionDetalleVehiculoCotId', $cotiTmp->id)->get();
                    $isBlindaje = false;
                    $isMinors = false;
                    $blindaje = "";

                    $menorNombrado = false;

                    foreach ($coberturaTmp as $cober) {
                        $montoTmp = floatval($cober->monto);
                        if(empty($cober) || empty($cober->cobertura)) continue;
                        $isBlindaje = $isBlindaje || $cober->cobertura->blindaje;
                        $isMinors = $isMinors || $cober->cobertura->minors;
                        if(!empty($cober->cobertura->blindaje)) $blindaje = $montoTmp;

                        if ($cober->codigoCobertura == 47 || $cober->codigoCobertura == 46) {
                            $menorNombrado = $cober->codigoCobertura;
                        }
                    }

                    $isBlindajeSend = $blindaje ? 'S' : 'N';

                    $isPaid = !empty($dataVehiculo['datos_cliente_es_pagador']) ? $dataVehiculo['datos_cliente_es_pagador']['valorLong'] : '';
                    $tipoCliente = !empty($dataVehiculo['tipo_cliente']) ? $dataVehiculo['tipo_cliente']['valorLong'] : '';

                    //<colonia>{{zon_Empre_Juri}}</colonia>

                    // Datos de cliente
                    if ($tipoCliente == '02') { // empresarial
                        $xmlSend = <<<XML
                            <datosIdEmpresaGC>
                            <idEmpresa>01</idEmpresa>
                            <datos01>
                                <datosClienteEmpresarialGestorComercial>           
                                   <numeroCotizacion>{$cotiTmp->numeroCotizacionAS400}</numeroCotizacion>       
                                   <fechaCotizacion>{$fechaHoy}</fechaCotizacion>         
                                   <nitEmpresa>{{nit_Juri}}</nitEmpresa>                   
                                   <tipoSociedad>{{tip_Soc_Juri}}</tipoSociedad>                
                                   <nombreEmpresa>{{nom_Com_Juri}}</nombreEmpresa>             
                                   <razonSocial>{{razs_Soci_Juri}}</razonSocial>                 
                                   <actividadEconomica>{{act_Econ_Juri}}</actividadEconomica>    
                                   <nacionalidadEmpresa>{{nac_Juri}}</nacionalidadEmpresa>  
                                   <lugarEmision>{{lug_emi}}</lugarEmision>               
                                   <proveedorEstado>{{provee_Juri}}</proveedorEstado>          
                                   <emailEmpresa>{{correoJuri}}</emailEmpresa>               
                                   <numeroEscritura>{{num_Escri_Juri}}</numeroEscritura>         
                                   <fechaEscritura>{{fec_Escri_Juri_Format}}</fechaEscritura>           
                                   <fechaConstitucion>{{fec_Consti_Juri_Format}}</fechaConstitucion>     
                                   <codigoCifBanco>{{WS_CLIENTE.datosIdEmpresaGC.datos03.consultaDatosClienteGestorComercial.clientePersonal.codigoCifBanco}}</codigoCifBanco>           
                                   <codigoCliente>{{WS_CLIENTE.datosIdEmpresaGC.datos03.consultaDatosClienteGestorComercial.clientePersonal.codigoCliente}}</codigoCliente>          
                                   <tipoTarifa>{$cotiTmp->tarifa->idTarifa}</tipoTarifa>                    
                                   <tipoCliente>{{tipo_cliente}}</tipoCliente>                
                                  <codigoAgente>{{CODIGO_AGENTE}}</codigoAgente>                       
                                  <direccionEntregaSO>{{WS_CLIENTE.datosIdEmpresaGC.datos03.consultaDatosClienteGestorComercial.clientePersonal.direcciones.direccionCasa.direccionC}}</direccionEntregaSO>      
                                  <esProductoObligatorio>No</esProductoObligatorio>     
                                  <usuarioCotizadorWeb>webcotigc</usuarioCotizadorWeb>        
                                  <empresaCotizadorWeb>CotizadorAuto</empresaCotizadorWeb>        
                                  <esClientePagador>N</esClientePagador>   
                                  <idFlotilla>{$cotiTmp->numeroCotizacionAS400}</idFlotilla>                        
                                  <direccionEmpresa>                                     
                                     <direccionE>{{WS_CLIENTE.datosIdEmpresaGC.datos03.consultaDatosClienteGestorComercial.clientePersonal.direcciones.direccionCasa.direccionC}}</direccionE>                           
                                     <paisE>{{pais_Empre_Juri}}</paisE>                                  
                                     <departamentoE>{{dep_Empre_Juri}}</departamentoE>                  
                                     <municipioE>{{muni_Empre_Juri}}</municipioE>                        
                                     <zonaE>{{zon_Empre_Juri}}</zonaE>                                 
                                     <telefonoCasa>{{telt_Princ_Juri}}</telefonoCasa>                   
                                     <telefonoOficina>{{telt_Princ_Juri}}</telefonoOficina>             
                                     <celular>{{cel_Empre_Juri}}</celular>                             
                                     <whatsApp>S</whatsApp>                
                                     <colonia>{{colonia_Juri}}</colonia>                         
                                  </direccionEmpresa>                                    
                                  <datosRepresentanteLegal>                              
                                     <primerNombreRep>{{pri_nom_RepLega}}</primerNombreRep>                
                                     <segundoNombreRep>{{seg_Nom_RepLegal}}</segundoNombreRep>              
                                     <primerApellidoRep>{{pri_Apell_RepLegal}}</primerApellidoRep>            
                                     <segundoApellidoRep>{{seg_Apell_RepLegal}}</segundoApellidoRep>          
                                     <apellidoCasadaRep>{{apellCas_RepLegal}}</apellidoCasadaRep>            
                                     <dpiRep>{{dpi_RepLegal}}</dpiRep>                                  
                                     <nitRep>{{nit_RepLegal}}</nitRep>                                  
                                     <nacionalidadRep>{{nac_RepLegal}}</nacionalidadRep>                 
                                     <emailRep>{{email_RepLegal}}</emailRep>                              
                                     <fechaNacimiento>{{fecNac_RepLegal_Fomat}}</fechaNacimiento>                
                                     <pasaporteRep>{{pasa_RepLegal}}</pasaporteRep>                      
                                     <sexoRep>{{gen_RepLegal}}</sexoRep>                                 
                                     <profesionRep>{{prof_RepLegal}}</profesionRep>                       
                                     <estadoCivilRep>{{estCivi_RepLegal}}</estadoCivilRep>                   
                                     <registroRep>{{reg_RepLegal}}</registroRep>                        
                                     <expedienteRep>{{exp_RepLegal}}</expedienteRep>                    
                                     <extendidaEn>{{extendeexp_RepLegal}}</extendidaEn>                        
                                     <fechaInscripcion>{{fec_inscri_RepLegal_Format}}</fechaInscripcion>              
                                     <libro>{{numlib_RepLegal}}</libro>                                    
                                     <folio>{{numFol_RepLegal}}</folio>                                    
                                     <ingresoMensual>{{ingMens_RepLegal}}</ingresoMensual>              
                                     <egresoMensual></egresoMensual>             
                                     <fuenteIngreso>{{fuentIng_RepLegal}}</fuenteIngreso>                 
                                     <condicionMigratoria></condicionMigratoria>  
                                     <expuestoPoliticamente></expuestoPoliticamente> 
                                     <lugarContratacion></lugarContratacion>
                                     <fechaContratacion></fechaContratacion>
                                     <direccionRepresentanteLegal>                      
                                        <direccionR>{{dir_RepLegal}}</direccionR>                
                                        <paisR>{{pais_RepLegal}}</paisR>                              
                                        <departamentoR>{{dep_RepLegal}}</departamentoR>              
                                        <municipioR>{{muni_RepLegal}}</municipioR>                    
                                        <zonaR>{{zon_RepLegal}}</zonaR>                             
                                        <telefonoCasaR>{{teltcasa_RepLegal}}</telefonoCasaR>             
                                        <telefonoOficinaR></telefonoOficinaR>       
                                        <celularR>{{cel_RepLegal}}</celularR>                       
                                        <whatsAppR>S</whatsAppR>                
                                        <coloniaR>{{col_RepLegal}}</coloniaR>              
                                     </direccionRepresentanteLegal>                     
                                 </datosRepresentanteLegal>                             
                                </datosClienteEmpresarialGestorComercial>
                            </datos01>
                            <datos02></datos02>
                        </datosIdEmpresaGC>
                    XML;
                    }
                    else {
                        $xmlSend = <<<XML
                        <datosIdEmpresaGC>
                            <idEmpresa>01</idEmpresa>
                            <datos01>
                                <datosClientePersonalGestorComercial>
                                    <numeroCotizacion>{$cotiTmp->numeroCotizacionAS400}</numeroCotizacion>
                                    <fechaCotizacion>{$fechaHoy}</fechaCotizacion>
                                    <primerNombre>{{primer_nombre}}</primerNombre>
                                    <segundoNombre>{{segundo_nombre}}</segundoNombre>
                                    <primerApellido>{{primer_apelllido}}</primerApellido>
                                    <segundoApellido>{{segundo_apellido}}</segundoApellido>
                                    <apellidoCasada>{{apellido_casada}}</apellidoCasada>
                                    <nit>{{nit}}</nit>
                                    <dpi>{{datos_cliente_dpi}}</dpi>
                                    <profesion>{{datos_cliente_profesion}}</profesion>
                                    <lugarDeEmision>{{datos_cliente_lugar_emision}}</lugarDeEmision>
                                    <fechaNacimiento>{{fecha_nacimiento_format}}</fechaNacimiento>
                                    <sexo>{{datos_cliente_genero}}</sexo>
                                    <codigoCliente>{{WS_CLIENTE.datosIdEmpresaGC.datos03.consultaDatosClienteGestorComercial.clientePersonal.codigoCliente}}</codigoCliente>
                                    <codigoCifBanco></codigoCifBanco>
                                    <pasaporte>{{datos_cliente_pasaporte}}</pasaporte>
                                    <estadoCivil>{{datos_cliente_estado_civil}}</estadoCivil>
                                    <nacionalidad>{{datos_cliente_nacionalidad}}</nacionalidad>
                                    <tipoLicencia>{{datos_cliente_tipo_licencia}}</tipoLicencia>
                                    <numeroLicencia>{{datos_cliente_numero_licencia}}</numeroLicencia>
                                    <email>{{correo_electronico}}</email>
                                    <tipoTarifa>{$cotiTmp->tarifa->idTarifa}</tipoTarifa>
                                    <codigoAgente>{{CODIGO_AGENTE}}</codigoAgente>
                                    <direccionEntregaSO>{{datos_cliente_direccion_entrega}}</direccionEntregaSO>
                                    <esProductoObligatorio>NO</esProductoObligatorio>
                                    <usuarioCotizadorWeb>webcotigc</usuarioCotizadorWeb>
                                    <empresaCotizadorWeb>CotizadorAuto</empresaCotizadorWeb>
                                    <ingresoMensual>{{datos_cliente_ingreso_mensual}}</ingresoMensual>
                                    <fuenteIngreso>{{datos_cliente_fuente_ingreso}}</fuenteIngreso>
                                    <condicionMigratoria>001</condicionMigratoria>
                                    <fechaContratacion>{{datos_cliente_fecha_contratacion_format}}</fechaContratacion>
                                    <lugarContratacion>EL ROBLE</lugarContratacion>
                                    <clienteVip>N</clienteVip>
                                    <esClientePagador>{{datos_cliente_es_pagador}}</esClientePagador>
                                    <tipoCliente>{{tipo_cliente}}</tipoCliente>
                                    <direcciones>
                                        <direccionCorrespondencia>
                                            <direccion>{{datos_cliente_direccion_entrega}}</direccion>
                                            <pais>{{datos_cliente_nacionalidad}}</pais>
                                            <departamento>{{datos_cliente_departamento}}</departamento>
                                            <municipio>{{datos_cliente_municipio}}</municipio>
                                            <zona>{{datos_cliente_zona}}</zona>
                                            <telefonoCasa>{{telefono_casa}}</telefonoCasa>
                                            <telefonoOficina>{{telefono_oficina}}</telefonoOficina>
                                            <celular>{{telefono_celular}}</celular>
                                            <whatsApp>S</whatsApp>
                                            <colonia>{{datos_cliente_colonia}}</colonia>
                                        </direccionCorrespondencia>
                                        <direccionCobroCtePersonal>
                                            <direccionCobro>{{datos_cliente_direccion_cobro}}</direccionCobro>
                                            <paisCobro>{{datos_cliente_pais_cobro}}</paisCobro>
                                            <departamentoCobro>{{datos_cliente_departamento_cobro}}</departamentoCobro>
                                            <municipioCobro>{{datos_cliente_municipio_cobro}}</municipioCobro>
                                            <zonaCobro>{{datos_cliente_zona_cobro}}</zonaCobro>
                                            <telefonoCobro>{{telefono_casa}}</telefonoCobro>
                                            <telefonoOficinaCobro>{{telefono_oficina}}</telefonoOficinaCobro>
                                            <celularCobro>{{telefono_celular}}</celularCobro>
                                            <whatsAppCobro>S</whatsAppCobro>
                                            <coloniaCobro>{{colonia_cobro}}</coloniaCobro>
                                        </direccionCobroCtePersonal>
                                    </direcciones>
                                </datosClientePersonalGestorComercial>
                            </datos01>
                            <datos02></datos02>
                        </datosIdEmpresaGC>
                    XML;
                    }

                    $dataSend = <<<EOD
                            {
                                "nprogram": "XXPD539",
                                "dtainput": "{$xmlSend}"
                            }
                    EOD;

                    $ejecuciones['list'][] = [
                        'cotizacionDetalleVehiculoCotId' => $cotiTmp->id,
                        'entrada' => $dataSend,
                        'process' => '1',
                        'data' => $dataVehiculo,
                        'cotiNumber' => $cotiNumber,
                        'vehiNumber' => $vehiNumber,
                    ];

                    if($isPaid !== 'S'){

                        if ($tipoCliente == '02') { // empresarial
                            //Pagador Personal
                            $xmlSend = <<<XML
                            <datosIdEmpresaGC>
                                <idEmpresa>01</idEmpresa>
                                <datos01>
                                    <datosPagadorGestorComercial>
                                       <numeroCotizacion>{$cotiTmp->numeroCotizacionAS400}</numeroCotizacion>
                                       <fechaCotizacion>{$fechaHoy}</fechaCotizacion>
                                       <primerNombrePagador></primerNombrePagador>
                                       <segundoNombrePagador></segundoNombrePagador>
                                       <primerApellidoPagador></primerApellidoPagador>
                                       <segundoApellidoPagador></segundoApellidoPagador>
                                       <apellidoCasadaPagador></apellidoCasadaPagador>
                                       <nitPagador>{{nit_Juri}}</nitPagador>
                                       <dpiPagador></dpiPagador>
                                       <profesionPagador></profesionPagador>
                                       <lugarDeEmisionPagador></lugarDeEmisionPagador>
                                       <fechaNacimientoPagador></fechaNacimientoPagador>
                                       <sexoPagador></sexoPagador>
                                       <codigoCifBancoPagador>{{WS_CLIENTE.datosIdEmpresaGC.datos03.consultaDatosClienteGestorComercial.clientePersonal.codigoCifBanco}}</codigoCifBancoPagador>
                                       <codigoClientePagador>{{WS_CLIENTE.datosIdEmpresaGC.datos03.consultaDatosClienteGestorComercial.clientePersonal.codigoCliente}}</codigoClientePagador>
                                       <pasaportePagador></pasaportePagador>
                                       <estadoCivilPagador></estadoCivilPagador>
                                       <nacionalidadPagador></nacionalidadPagador>
                                       <tipoLicenciaPagador></tipoLicenciaPagador>
                                       <numeroLicenciaPagador></numeroLicenciaPagador>
                                       <emailPagador>{{correoJuri}}</emailPagador>
                                       <ingresoMensualPagador>0</ingresoMensualPagador>
                                       <fuenteIngresoPagador></fuenteIngresoPagador>
                                       <condicionMigratoriaPagador></condicionMigratoriaPagador>
                                       <fechaContratacionPagador></fechaContratacionPagador>
                                       <lugarContratacionPagador></lugarContratacionPagador>
                                       <tipoClientePagador>{{tipo_cliente}}</tipoClientePagador>
                                       <tipoSociedad>{{tip_Soc_Juri}}</tipoSociedad>
                                       <nombreEmpresa>{{nom_Com_Juri}}</nombreEmpresa>
                                       <razonSocial>{{razs_Soci_Juri}}</razonSocial>
                                       <actividadEconomica>{{act_Econ_Juri}}</actividadEconomica>
                                       <proveedorEstado>{{provee_Juri}}</proveedorEstado>
                                       <numeroEscritura>{{num_Escri_Juri}}</numeroEscritura>
                                       <fechaEscritura>{{fec_Escri_Juri_Format}}</fechaEscritura>
                                       <fechaConstitucion>{{fec_Consti_Juri_Format}}</fechaConstitucion>
                                       <direccionesPagador>
                                          <direccionCorrespondenciaPagador>
                                             <direccionCP>{{WS_CLIENTE.datosIdEmpresaGC.datos03.consultaDatosClienteGestorComercial.clientePersonal.direcciones.direccionCasa.direccionC}}</direccionCP>
                                             <paisCP>{{pais_Empre_Juri}}</paisCP>
                                             <departamentoCP>{{dep_Empre_Juri}}</departamentoCP>
                                             <municipioCP>{{muni_Empre_Juri}}</municipioCP>
                                             <zonaCP>{{zon_Empre_Juri}}</zonaCP>
                                             <telefonoCasaCP>{{telt_Princ_Juri}}</telefonoCasaCP>
                                             <telefonoOficinaCP>{{telt_Princ_Juri}}</telefonoOficinaCP>
                                             <celularCP>{{cel_Empre_Juri}}</celularCP>
                                             <whatsAppCP>S</whatsAppCP>
                                             <coloniaCP></coloniaCP>
                                          </direccionCorrespondenciaPagador>
                                          <direccionCobroPagador>
                                             <direccionTP>{{WS_CLIENTE.datosIdEmpresaGC.datos03.consultaDatosClienteGestorComercial.clientePersonal.direcciones.direccionCasa.direccionC}}</direccionTP>
                                             <paisTP>{{pais_Empre_Juri}}</paisTP>
                                             <departamentoTP>{{dep_Empre_Juri}}</departamentoTP>
                                             <municipioTP>{{muni_Empre_Juri}}</municipioTP>
                                             <zonaTP>{{zon_Empre_Juri}}</zonaTP>
                                             <telefonoCasaTP>{{telt_Princ_Juri}}</telefonoCasaTP>
                                             <telefonoOficinaTP>{{telt_Princ_Juri}}</telefonoOficinaTP>
                                             <celularTP>{{cel_Empre_Juri}}</celularTP>
                                             <whatsAppTP>S</whatsAppTP>
                                             <coloniaTP></coloniaTP>
                                          </direccionCobroPagador>
                                       </direccionesPagador>
                                    </datosPagadorGestorComercial>
                                </datos01>
                                <datos02></datos02>
                            </datosIdEmpresaGC>
                        XML;
                        }
                        else {
                            //Pagador Personal
                            $xmlSend = <<<XML
                            <datosIdEmpresaGC>
                                <idEmpresa>01</idEmpresa>
                                <datos01>
                                    <datosPagadorGestorComercial>
                                        <numeroCotizacion>{$cotiTmp->numeroCotizacionAS400}</numeroCotizacion>
                                        <fechaCotizacion>{$fechaHoy}</fechaCotizacion>
                                        <primerNombrePagador>{{pagador_personal_primer_nombre}}</primerNombrePagador>
                                        <segundoNombrePagador>{{pagador_personal_segundo_nombre}}</segundoNombrePagador>
                                        <primerApellidoPagador>{{pagador_personal_primer_apellido}}</primerApellidoPagador>
                                        <segundoApellidoPagador>{{pagador_personal_segundo_apellido}}</segundoApellidoPagador>
                                        <apellidoCasadaPagador>{{pagador_personal_apellido_casada}}</apellidoCasadaPagador>
                                        <nitPagador>{{pagador_personal_nit}}</nitPagador>
                                        <dpiPagador>{{pagador_personal_dpi}}</dpiPagador>
                                        <profesionPagador>{{pagador_personal_profesion}}</profesionPagador>
                                        <lugarDeEmisionPagador>Guatemala</lugarDeEmisionPagador>
                                        <fechaNacimientoPagador>{{pagador_personal_fecha_nacimiento_formateada}}</fechaNacimientoPagador>
                                        <sexoPagador>{{pagador_personal_sexo}}</sexoPagador>
                                        <codigoCifBancoPagador></codigoCifBancoPagador>
                                        <codigoClientePagador>{{pagador_cod_cliente}}</codigoClientePagador>
                                        <pasaportePagador>{{pagador_personal_pasaporte}}</pasaportePagador>
                                        <estadoCivilPagador>{{pagador_personal_estado_civil}}</estadoCivilPagador>
                                        <nacionalidadPagador>1</nacionalidadPagador>
                                        <tipoLicenciaPagador>{{pagador_personal_tipo_licencia}}</tipoLicenciaPagador>
                                        <numeroLicenciaPagador>{{pagador_personal_numero_licencia}}</numeroLicenciaPagador>
                                        <emailPagador>{{pagador_personal_email}}</emailPagador>
                                        <ingresoMensualPagador>{{pagador_personal_ingreso_mensual}}</ingresoMensualPagador>
                                        <fuenteIngresoPagador>{{pagador_personal_fuente_ingreso}}</fuenteIngresoPagador>
                                        <condicionMigratoriaPagador>001</condicionMigratoriaPagador>
                                        <fechaContratacionPagador>{{pagador_personal_fecha_contratacion_formateada}}</fechaContratacionPagador>
                                        <lugarContratacionPagador>{{pagador_personal_lugar_contratacion}}</lugarContratacionPagador>
                                        <tipoClientePagador>{{pagador_tipo}}</tipoClientePagador>
                                        <tipoSociedad>{{pagador_tipo_sociedad}}</tipoSociedad>
                                        <nombreEmpresa>{{pagador_nombre_empresa}}</nombreEmpresa>
                                        <razonSocial>{{pagador_razon_socia}}</razonSocial>
                                        <actividadEconomica>{{pagador_actividad_economica}}</actividadEconomica>
                                        <proveedorEstado>{{pagador_proveedor_estado}}</proveedorEstado>
                                        <numeroEscritura>{{pagador_numero_escritura}}</numeroEscritura>
                                        <fechaEscritura>{{pagador_fecha_escritura_formateada}}</fechaEscritura>
                                        <fechaConstitucion>{{pagador_fecha_constitucion_formateada}}</fechaConstitucion>
                                        <direccionesPagador>
                                            <direccionCorrespondenciaPagador>
                                                <direccionCP>{{pagador_personal_dir_corr_direccion}}</direccionCP>
                                                <paisCP>{{pagador_proveedor_pais}}</paisCP>
                                                <departamentoCP>{{pagador_proveedor_departamento}}</departamentoCP>
                                                <municipioCP>{{pagador_proveedor_municipio}}</municipioCP>
                                                <zonaCP>{{pagador_proveedor_zona}}</zonaCP>
                                                <telefonoCasaCP>{{pagador_personal_dir_corr_telefono_casa}}</telefonoCasaCP>
                                                <telefonoOficinaCP>{{pagador_personal_dir_corr_telefono_oficina}}</telefonoOficinaCP>
                                                <celularCP>{{pagador_personal_dir_corr_celular}}</celularCP>
                                                <whatsAppCP>{{pagador_personal_dir_corr_whatsapp}}</whatsAppCP>
                                                <coloniaCP>{{pagador_personal_dir_corr_colonia}}</coloniaCP>
                                            </direccionCorrespondenciaPagador>
                                            <direccionCobroPagador>
                                                <direccionTP>{{pagador_dir_cob_direccion}}</direccionTP>
                                                <paisTP>{{pagador_dir_cob_pais}}</paisTP>
                                                <departamentoTP>{{pagador_dir_cob_departamento}}</departamentoTP>
                                                <municipioTP>{{pagador_dir_cob_municipio}}</municipioTP>
                                                <zonaTP>{{pagador_dir_cob_zona}}</zonaTP>
                                                <telefonoCasaTP>{{pagador_dir_cob_telefono_casa}}</telefonoCasaTP>
                                                <telefonoOficinaTP>{{pagador_dir_cob_telefono_oficina}}</telefonoOficinaTP>
                                                <celularTP>{{pagador_dir_cob_celular}}</celularTP>
                                                <whatsAppTP>{{pagador_dir_cob_whatsapp}}</whatsAppTP>
                                                <coloniaTP>{{pagador_dir_cob_colonia}}</coloniaTP>
                                            </direccionCobroPagador>
                                        </direccionesPagador>
                                    </datosPagadorGestorComercial>
                                </datos01>
                                <datos02></datos02>
                            </datosIdEmpresaGC>
                        XML;
                        }

                        $dataSend = <<<EOD
                                {
                                    "nprogram": "XXPD539",
                                    "dtainput": "{$xmlSend}"
                                }
                        EOD;
                        $ejecuciones['list'][] = [
                            'cotizacionDetalleVehiculoCotId' => $cotiTmp->id,
                            'entrada' => $dataSend,
                            'process' => '2',
                            'data' => $dataVehiculo,
                            'cotiNumber' => $cotiNumber,
                            'vehiNumber' => $vehiNumber,
                        ];

                    }

                    $chasis = $vehiculo->noChasis;
                    $motor = $vehiculo->noMotor;
                    $placa = $vehiculo->placa;

                    $datosChasis = !empty($dataVehiculo['datos_vehiculo_chasis']) ? $dataVehiculo['datos_vehiculo_chasis']['valorLong'] : '';
                    $datosMotor = !empty($dataVehiculo['datos_vehiculo_motor']) ? $dataVehiculo['datos_vehiculo_motor']['valorLong'] : '';
                    $datosPlaca = !empty($dataVehiculo['datos_vehiculo_placa']) ? $dataVehiculo['datos_vehiculo_placa']['valorLong'] : '';

                    if(!empty($chasis)){
                        if($chasis !== $datosChasis){
                            $ejecuciones['cancelar'] = true;
                            $ejecuciones['errors'][] = 'El chasis es diferente al cotizado';
                            continue;
                        }
                    }

                    if(!empty($motor)){
                        if($motor !== $datosMotor){
                            $ejecuciones['cancelar'] = true;
                            $ejecuciones['errors'][] = 'El motor es diferente al cotizado';
                            continue;
                        }
                    }

                    if(!empty($placa) && $placa !== 'PE-PENDIENTE'){
                        if($placa !== $datosPlaca){
                            $ejecuciones['cancelar'] = true;
                            $ejecuciones['errors'][] = 'La placa es diferente a la cotizada';
                            continue;
                        }
                    }

                    if(!empty($datosChasis)) $chasis = $datosChasis;
                    if(!empty($datosMotor)) $motor = $datosMotor;
                    if(!empty($datosPlaca)) $placa = $datosPlaca;
                    if(empty($placa)) $placa = 'PE-PENDIENTE';


                    $preexistentesAllFilter = '';
                    for ($i=1; $i<=5; $i++) {

                        $tmpItem = $camposForVehi->where('campo', "danos_prex{$i}")->first()->valorLong ?? '';
                        if (!empty($tmpItem)) {
                            $preexistentesAllFilter .= "<lista>
                                                            <numeroLinea>{$i}</numeroLinea>                            
                                                            <descripcionLinea>{$tmpItem}</descripcionLinea>
                                                        </lista>";
                        }
                    }

                    $preexistentes = '';
                    if (!empty($preexistentesAllFilter)) {
                        $preexistentes .= "<listaDanos>                                     
                                                {$preexistentesAllFilter}                                                   
                                            </listaDanos>";
                    }


                    $equipoEspecialAllFilter = '';
                    for ($i=1; $i<=5; $i++) {

                        $tmpItem = $camposForVehi->where('campo', "equip_espe{$i}")->first()->valorLong ?? '';
                        if (!empty($tmpItem)) {
                            $equipoEspecialAllFilter .= "<listaEquipo>                                
                                                            <numeroLineaEE>{$i}</numeroLineaEE>                            
                                                            <descripcion>{$tmpItem}</descripcion>
                                                         </listaEquipo>";
                        }
                    }

                    $equipoEspecial = '';
                    if (!empty($equipoEspecialAllFilter)) {
                        $equipoEspecial .= "<listaEquipoEspecial>                           
                                                {$equipoEspecialAllFilter}                           
                                            </listaEquipoEspecial>";
                    }



                    // Datos de vehículo
                    $xmlSend = <<<XML
                        <datosIdEmpresaGC>
                            <idEmpresa>01</idEmpresa>
                            <datos01>
                                <datosdeVehiculoGestorComercial>
                                    <numeroCotizacion>{$cotiTmp->numeroCotizacionAS400}</numeroCotizacion>
                                    <fechaCotizacion>{$fechaHoy}</fechaCotizacion>
                                    <fechaFactura>{{datos_vehiculo_fecha_factura_format}}</fechaFactura>
                                    <marcaLinea>{$vehiculo->linea->codigo}</marcaLinea>
                                    <tipoVehiculo>{$vehiculo->tipo->codigo}</tipoVehiculo>
                                    <modeloVehiculo>{$vehiculo->modelo}</modeloVehiculo>
                                    <moneda>Q</moneda>
                                    <propietarioVehiculo>{{nom_prop_vehi}}</propietarioVehiculo>
                                    <chasis>{$chasis}</chasis>
                                    <motor>{$motor}</motor>
                                    <placa>{$placa}</placa>
                                    <tipoTarifa>{$cotiTmp->tarifa->idTarifa}</tipoTarifa>
                                    <tipoUso>{{datos_vehiculo_tipo_uso}}</tipoUso>
                                    <blindaje>{$isBlindajeSend}</blindaje>
                                    <valorBlindaje>{$blindaje}</valorBlindaje>
                                    <codigoAlarma>{{datos_vehiculo_codigo_alarma}}</codigoAlarma>
                                    <numeroPasajeros>{$vehiculo->noPasajeros}</numeroPasajeros>
                                    <color>{{datos_vehiculo_color}}</color>
                                    <centimetrosCubicos>{{datos_vehiculo_centimetros_cubicos}}</centimetrosCubicos>
                                    <numeroCilindros>{{datos_vehiculo_numero_cilindros}}</numeroCilindros>
                                    <numeroEjes>{{datos_vehiculo_numero_ejes}}</numeroEjes>
                                    <numeroPuertas>{{datos_vehiculo_numero_puertas}}</numeroPuertas>
                                    <valorVehiculo>{$cotiTmp->sumaAsegurada}</valorVehiculo>
                                    <kilometraje>{{datos_vehiculo_kilometraje}}</kilometraje>
                                    <tipoKilometraje>{{datos_vehiculo_tipo_kilometraje}}</tipoKilometraje>
                                    <tipoCombustible>{{datos_vehiculo_tipo_combustible}}</tipoCombustible>
                                    <tonelaje>{{datos_vehiculo_tonelaje}}</tonelaje>
                                    <numeroPrestamo>{{datos_vehiculo_numero_prestamo}}</numeroPrestamo>
                                    <garantia>{{datos_vehiculo_garantia}}</garantia>
                                    <codigoBeneficiarioGarantia>{{datos_vehiculo_codigo_beneficiario}}</codigoBeneficiarioGarantia>
                                    <seleccion>{{datos_vehiculo_seleccion}}</seleccion>
                                    <numeroInspeccion>{{prueba_inspeccion}}</numeroInspeccion>
                                    <danosPreexistentes>{{datos_vehiculo_danios_preexistentes}}</danosPreexistentes>
                                    {$preexistentes}
                                    <equipoEspecial>{{datos_vehiculo_equipo_especial}}</equipoEspecial>
                                    {$equipoEspecial}
                                    <tipoTecnologia>{{datos_vehiculo_tipo_tecnologia}}</tipoTecnologia>
                                    <terceros>N</terceros>
                                </datosdeVehiculoGestorComercial>
                            </datos01>
                            <datos02></datos02>
                        </datosIdEmpresaGC>
                    XML;

                    $dataSend = <<<EOD
                            {
                                "nprogram": "XXPD539",
                                "dtainput": "{$xmlSend}"
                            }
                    EOD;
                    $ejecuciones['list'][] = [
                        'cotizacionDetalleVehiculoCotId' => $cotiTmp->id,
                        'entrada' => $dataSend,
                        'process' => '3',
                        'data' => $dataVehiculo,
                        'cotiNumber' => $cotiNumber,
                        'vehiNumber' => $vehiNumber,
                    ];
                    $zonaemison = !empty($productoTarifaTmp->producto->zonaEmision) ? $productoTarifaTmp->producto->zonaEmision : '';
                    // Datos de póliza
                    $xmlSend = <<<XML
                        <datosIdEmpresaGC>
                            <idEmpresa>01</idEmpresa>
                            <datos01>
                                <datosdePolizaGestorComercial>
                                    <numeroCotizacion>{$cotiTmp->numeroCotizacionAS400}</numeroCotizacion>
                                    <fechaCotizacion>{$fechaHoy}</fechaCotizacion>
                                    <zonaEmision>{$zonaemison}</zonaEmision>
                                    <formaPago>01</formaPago>
                                    <tipoPoliza>1</tipoPoliza>
                                    <tipoProducto>1</tipoProducto>
                                    <codigoAgente>{{CODIGO_AGENTE}}</codigoAgente>
                                    <tipoLinea>{$tipoLinea}</tipoLinea>
                                    <tipoProduccion>{$tipoProduccion}</tipoProduccion>
                                    <vigenciaDesde>{$vigenciaDesde}</vigenciaDesde>
                                    <vigenciaHasta>{$vigenciaHasta}</vigenciaHasta>
                                    <tipoCartera>{$tipoCartera}</tipoCartera>
                                    <tasaDescuento>{$cotiTmp->descuentoPorcentaje}</tasaDescuento>
                                    <polizaPorProducto>{$productoTarifaTmp->codigoProducto}</polizaPorProducto>
                                    <esProductoObligatorio>NO</esProductoObligatorio>
                                    <moneda>{$productoTarifaTmp->idMoneda}</moneda>
                                    <ultimoRecibo>N</ultimoRecibo>
                                </datosdePolizaGestorComercial>
                            </datos01>
                            <datos02></datos02>
                        </datosIdEmpresaGC>
                    XML;

                    $dataSend = <<<EOD
                            {
                                "nprogram": "XXPD539",
                                "dtainput": "{$xmlSend}"
                            }
                    EOD;
                    $ejecuciones['list'][] = [
                        'cotizacionDetalleVehiculoCotId' => $cotiTmp->id,
                        'entrada' => $dataSend,
                        'process' => '4',
                        'data' => $dataVehiculo,
                        'cotiNumber' => $cotiNumber,
                        'vehiNumber' => $vehiNumber,
                    ];

                    $moneda = $productoTarifaTmp->idMoneda ?? 'Q';

                    // Datos de workflow
                    $xmlSend = <<<XML
                        <datosIdEmpresaGC>
                            <idEmpresa>01</idEmpresa>
                            <datos01>
                                <datosWorkFlowGestorComercial>
                                    <numeroCotizacion>{$cotiTmp->numeroCotizacionAS400}</numeroCotizacion>
                                    <fechaCotizacion>{$fechaHoy}</fechaCotizacion>
                                    <tipoMovimiento>{$tipoMovimiento}</tipoMovimiento>
                                    <subTipoMovimiento>{$subtipoMovimiento}</subTipoMovimiento>
                                    <tipoDocumento>{$tipoDocumento}</tipoDocumento>
                                    <fechaDocumento>{$fechaHoy}</fechaDocumento>
                                    <fechaRecepcion>{$fechaHoy}</fechaRecepcion>
                                    <nombreAsegurado>{{primer_nombre}} {{primer_apelllido}}</nombreAsegurado>
                                    <observaciones></observaciones>
                                    <urgente>N</urgente>
                                    <linea>1</linea>
                                    <fechaRevision>{$fechaHoy}</fechaRevision>
                                    <fechaAsignacion>{$fechaHoy}</fechaAsignacion>
                                    <fechaEmision>{$fechaHoy}</fechaEmision>
                                    <codigoBarras></codigoBarras>
                                    <moneda>{$moneda}</moneda>
                                    <tipoUsuario>{$tipoUsuario}</tipoUsuario>
                                    <tipoAsignacion>{$tipoAsignacion}</tipoAsignacion>
                                </datosWorkFlowGestorComercial>
                            </datos01>
                            <datos02></datos02>
                        </datosIdEmpresaGC>
                    XML;

                    $dataSend = <<<EOD
                            {
                                "nprogram": "XXPD539",
                                "dtainput": "{$xmlSend}"
                            }
                        EOD;
                    $ejecuciones['list'][] = [
                        'cotizacionDetalleVehiculoCotId' => $cotiTmp->id,
                        'entrada' => $dataSend,
                        'process' => '5',
                        'data' => $dataVehiculo,
                        'cotiNumber' => $cotiNumber,
                        'vehiNumber' => $vehiNumber,
                    ];

                    //Menores con cobertura
                    if ($menorNombrado) {
                        $xmlSend = <<<XML
                        <datosIdEmpresaGC>
                            <idEmpresa>01</idEmpresa>
                            <datos01>
                                <menoresEdadConCoberturasGestorComercial>
                                    <numeroCotizacion>{$cotiTmp->numeroCotizacionAS400}</numeroCotizacion>
                                    <codigoCobertura>{$menorNombrado}</codigoCobertura>
                                    <nombreMenor>{{nom_menor_nom}}</nombreMenor>
                                    <direccionMenor>{{dir_menor_nom}}</direccionMenor>
                                    <fechaNacimientoMenor>{{fec_nac_men_nom_format}}</fechaNacimientoMenor>
                                    <edadMenor>{{edad_men_nom}}</edadMenor>
                                    <tipoLicenciaMenor>{{tip_lic_men_nom}}</tipoLicenciaMenor>
                                    <numeroLicenciaMenor>{{num_lic_men_nom}}</numeroLicenciaMenor>
                                    <fechaVencimientoDesde>{{fec_ven_desde_format}}</fechaVencimientoDesde>
                                    <fechaVencimientoHasta>{{fec_ven_lic_men_nom_format}}</fechaVencimientoHasta>
                                    <idFlotilla>1</idFlotilla>
                                </menoresEdadConCoberturasGestorComercial>
                            </datos01>
                            <datos02></datos02>
                        </datosIdEmpresaGC>
                    XML;

                        $dataSend = <<<EOD
                            {
                                "nprogram": "XXPD539",
                                "dtainput": "{$xmlSend}"
                            }
                    EOD;
                        $ejecuciones['list'][] = [
                            'cotizacionDetalleVehiculoCotId' => $cotiTmp->id,
                            'entrada' => $dataSend,
                            'process' => '6',
                            'data' => $dataVehiculo,
                            'cotiNumber' => $cotiNumber,
                            'vehiNumber' => $vehiNumber,
                        ];
                    }


                    $dataMetodoPago = DataMetodoPago::where('cotizacionesDetalleVehiculoCotId', $cotiTmp->id)->first();

                    $enviarMedios = true; //para pruebas
                    if (!empty($dataMetodoPago->datac) && $enviarMedios) {

                        $datadesencript = json_decode($this->desencriptar($dataMetodoPago->datac), true);

                        if (!empty($datadesencript['medioCobro']) && ($datadesencript['medioCobro'] === '03' || $datadesencript['medioCobro'] === '07') && empty($datadesencript['numCuentaTarjeta'])) {
                            $ejecuciones['errors'][] = 'Error al enviar medios de pago, por favor verifique información';
                            continue;
                        }
                        $claseTarjetaRedux = catClaseTarjeta::where('codigo', $datadesencript['claseTarjeta'] ?? '')->first();
                        $cctarjeta = (empty($claseTarjetaRedux) || empty($claseTarjetaRedux->claseTarjeta))? '' : $claseTarjetaRedux->claseTarjeta;

                        if (empty($datadesencript['bancoEmisor'])) {
                            $datadesencript['bancoEmisor'] = '011';
                        }

                        $aplicapago = 'N';
                        if ($datadesencript['medioCobro'] != '03' && $datadesencript['medioCobro'] != '02') {
                            $datadesencript['numCuentaTarjeta'] = '';
                            $datadesencript['tipoCuentaTarjeta'] = '';
                            $datadesencript['claseTarjeta'] = '';
                            $datadesencript['venciTarjeta'] = '';
                            $datadesencript['numeroCuotas'] = '';
                            $datadesencript['bancoEmisor'] = '';
                            $datadesencript['tipoCuentaBancarias'] = '';
                            $dataMetodoPago->autorizacion = '';
                        }

                        if ($datadesencript['medioCobro'] == '03') {
                            $aplicapago = 'S';
                        }

                        // Medios de pago
                        //$aplicapago = (empty($cotiTmp->numeroCotizacionAS400))? 'N' : 'S';
                        $xmlSend = <<<XML
                            <datosIdEmpresaGC>
                                <idEmpresa>01</idEmpresa>
                                <datos01>
                                    <mediosdePagoGestorComercial>
                                        <numeroCotizacion>{$cotiTmp->numeroCotizacionAS400}</numeroCotizacion>
                                        <medioCobro>{$datadesencript['medioCobro']}</medioCobro>
                                        <numeroCuentaTarjeta>{$datadesencript['numCuentaTarjeta']}</numeroCuentaTarjeta>
                                        <tipoCuentaTarjeta>{$datadesencript['tipoCuentaTarjeta']}</tipoCuentaTarjeta>
                                        <codigoCuentaTarjeta>{$datadesencript['claseTarjeta']}</codigoCuentaTarjeta>
                                        <claseTarjeta>{$cctarjeta}</claseTarjeta>
                                        <fechaVencimientoTarjeta>{$datadesencript['venciTarjeta']}</fechaVencimientoTarjeta>
                                        <numeroCuotas>{$datadesencript['numeroCuotas']}</numeroCuotas>
                                        <moneda>Q</moneda>
                                        <numeroAutorizacion>{$dataMetodoPago->autorizacion}</numeroAutorizacion>
                                        <totalaPagar>{$dataMetodoPago->monto}</totalaPagar>
                                        <bancoEmisor>{$datadesencript['bancoEmisor']}</bancoEmisor>
                                        <tipoCuentaBancaria>{$datadesencript['tipoCuentaBancarias']}</tipoCuentaBancaria>
                                        <pagoRequerimientoFlotilla>{{inc_flotilla}}</pagoRequerimientoFlotilla>
                                        <aplicaPago>{$aplicapago}</aplicaPago>
                                        <correlativo>{$cotiTmp->idCorrelativo}</correlativo>
                                    </mediosdePagoGestorComercial>
                                </datos01>
                                <datos02></datos02>
                            </datosIdEmpresaGC>
                        XML;

                        $dataSend = <<<EOD
                            {
                                "nprogram": "XXPD539",
                                "dtainput": "{$xmlSend}"
                            }
                        EOD;

                        $ejecuciones['list'][] = [
                            'cotizacionDetalleVehiculoCotId' => $cotiTmp->id,
                            'entrada' => $dataSend,
                            'process' => '7',
                            'data' => $dataVehiculo,
                            'cotiNumber' => $cotiNumber,
                            'vehiNumber' => $vehiNumber,
                        ];
                    }

                    $cotiNumber++;
                }
                $vehiNumber++;
            }
        }
        else if($identificadorWs === 'SINIESTRALIDAD_AS400') {

            $ejecuciones['type'] = $identificadorWs;

            /*var_dump($identificadorWs);
            die();*/
            $strVehicuos = "";

            $cotizacionVehiculo = CotizacionDetalleVehiculo::where('cotizacionId', $cotizacionId)->with('linea')->get();
            $productoId = 0;

            // Datos de cliente
            /*$xmlSend = <<<XML
                <datosIdEmpresaGC>
                    <idEmpresa>01</idEmpresa>
                    <datos01>
                        <consultaDatosClienteGestorComercial>
                            <nit>{{nit}}</nit>
                            <dpi>{{datos_cliente_dpi}}</dpi>
                            <fechaNacimiento>{{fecha_nacimiento_format}}</fechaNacimiento>
                            <cifCliente></cifCliente>
                            <codigoCliente>{{WS_CLIENTE.datosIdEmpresaGC.datos03.consultaDatosClienteGestorComercial.clientePersonal.codigoCliente}}</codigoCliente>
                            <tipoCliente>{{datos_tipo_cliente}}</tipoCliente>
                        </consultaDatosClienteGestorComercial>
                    </datos01>
                    <datos02></datos02>
                </datosIdEmpresaGC>
            XML;*/
            $nitTmp = trim($camposAll['nit_personal']['valorLong'] ?? '');
            $nitTmp2 = trim($camposAll['nit']['valorLong'] ?? '');
            $nit = (!empty($nitTmp))?  $nitTmp : (!empty($nitTmp2)?  $nitTmp2 : '');

            $xmlSend = <<<XML
                <datosIdEmpresaGC>
                <idEmpresa>01</idEmpresa>
                <datos01>
                    <datosConsultaPolizasGestorComercial>
                        <nit>{$nit}</nit>
                        <moneda>Q</moneda>
                    </datosConsultaPolizasGestorComercial>
                </datos01>
                <datos02></datos02>
                </datosIdEmpresaGC>
            XML;

            $dataSend = <<<EOD
                    {
                        "nprogram": "XXPD539",
                        "dtainput": "{$xmlSend}"
                    }
                EOD;
            $ejecuciones['list'][] = [
                'cotizacionDetalleVehiculoCotId' => 1,
                'entrada' => $dataSend,
                'tipo' => 'cliente'
            ];

            foreach ($cotizacionVehiculo as $vehiculo) {

                $chasis = $vehiculo->noChasis;
                $motor = $vehiculo->noMotor;
                $placa = $vehiculo->placa;

                $vehiculoId = $vehiculo->id;
                $dataVehiculo = [];

                foreach ($campos as $field) {
                    $field = $field->toArray();
                    if(empty($field['cotizacionVehiculoId']) ||
                    $field['cotizacionVehiculoId'] == $vehiculoId ||
                    $field['cotizacionVehiculoId'] == 0 ) $dataVehiculo[$field['campo']] = $field;
                }

                $datosChasis = !empty($dataVehiculo['datos_vehiculo_chasis']) ?  $dataVehiculo['datos_vehiculo_chasis']['valorLong'] : '';
                $datosMotor = !empty($dataVehiculo['datos_vehiculo_motor']) ?  $dataVehiculo['datos_vehiculo_motor']['valorLong'] : '';
                $datosPlaca = !empty($dataVehiculo['datos_vehiculo_placa']) ?  $dataVehiculo['datos_vehiculo_placa']['valorLong'] : '';

                if(!empty($datosChasis)) $chasis = $datosChasis;
                if(!empty($datosMotor)) $motor = $datosMotor;
                if(!empty($datosPlaca)) $placa = $datosPlaca;

                // Datos de vehículo
                $xmlSend = <<<XML
                    <datosIdEmpresaGC>
                        <idEmpresa>01</idEmpresa>
                        <datos01>
                            <consultaDatosVehiculoGestorComercial>
                                <placa>{$placa}</placa>
                                <chasis>{$chasis}</chasis>
                                <motor>{$motor}</motor>
                                <moneda>Q</moneda>
                            </consultaDatosVehiculoGestorComercial>
                        </datos01>
                        <datos02></datos02>
                    </datosIdEmpresaGC>
                XML;

                $dataSend = <<<EOD
                        {
                            "nprogram": "XXPD539",
                            "dtainput": "{$xmlSend}"
                        }
                EOD;
                $ejecuciones['list'][] = [
                    'cotizacionDetalleVehiculoCotId' => 0,
                    'entrada' => $dataSend,
                    'tipo' => 'vehiculo',
                    'placa'=> $placa,
                    'chasis'=> $chasis,
                    'motor'=> $motor,
                ];

            }
        }
        else if($identificadorWs === 'AUTOINSPECCION_AS400') {

            $ejecuciones['type'] = $identificadorWs;

            /*var_dump($identificadorWs);
            die();*/
            $strVehicuos = "";

            $vehiculo = CotizacionDetalleVehiculo::where('id', $vehiculoId)->first();
            $productoId = 0;

            $dataVehiculo = [];

            foreach ($campos as $field) {
                $field = $field->toArray();
                if(empty($field['cotizacionVehiculoId']) ||
                $field['cotizacionVehiculoId'] == $vehiculoId ||
                $field['cotizacionVehiculoId'] == 0 ) $dataVehiculo[$field['campo']] = $field;
            }

            $placa = $vehiculo->placa;
            $datosPlaca = !empty($dataVehiculo['datos_vehiculo_placa']) ? $dataVehiculo['datos_vehiculo_placa']['valorLong'] : '';
            if(!empty($datosPlaca)) $placa = $datosPlaca;

            $dataSend = <<<EOD
                    {
                        "case": "{{ID_SOLICITUD}} / {{ID_SOLICITUD}}",
                        "insuredName":"{{primer_nombre}}",
                        "insuredSurname":"{{primer_apelllido}}",
                        "plate":"{$placa}",
                        "phone": "+502{{telefono_celular}}",
                        "notificationChannel":"none",
                        "inspection":"y"
                    }
                EOD;

            $dataSend = $this->reemplazarValoresSalida($dataVehiculo, $dataSend);
            $ejecuciones['list'][] = [
                'cotizacionDetalleVehiculoId' => $vehiculoId,
                'entrada' => $dataSend,
                'tipo' => 'vehiculo',
                'placa'=> $placa,
            ];
        }

        /*var_dump($ejecuciones);
        die();*/

        return $ejecuciones;
    }

    public function getFlujoFromCotizacion($cotizacionObject) {

        $fromCache = false;
        if (empty($cotizacionObject)) {
            return $this->ResponseError('COT-4211', 'Flujo inválido', [], false, false);
        }

        $cacheH = ClassCache::getInstance();
        $producto = $cacheH->get("PR_COTI_{$cotizacionObject->id}");
        if (empty($producto)) {
            $producto = $cotizacionObject->producto;
            $cacheH->set("PR_COTI_{$cotizacionObject->id}", $producto);
        }

        if (empty($producto)) {
            return $this->ResponseError('COT-4213', 'Producto no válido', [], false, false);
        }

        $flujo = $cacheH->get("FL_PR_{$producto->id}");
        if (empty($flujo)) {
            $flujo = $producto->flujo->first();
            $cacheH->set("FL_PR_{$producto->id}", $flujo);
        }

        if (empty($flujo)) {
            return $this->ResponseError('COT-4212', 'Flujo no válido', [], false, false);
        }

        $flujoConfig = $cacheH->get("FL_CF_{$flujo->id}");
        if (empty($flujoConfig)) {
            $flujoConfig = @json_decode($flujo->flujo_config, true);
            $cacheH->set("FL_CF_{$flujo->id}", $flujoConfig);
        }
        else {
            $fromCache = true;
        }

        if (!is_array($flujoConfig)) {
            return $this->ResponseError('COT-610', 'Error al interpretar flujo, por favor, contacte a su administrador', [], false, false);
        }

        return $this->ResponseSuccess(($fromCache ? 'From cache' : 'Ok'), $flujoConfig,false);
    }

    public function CalcularPasos(Request $request, $onlyArray = false, $public = false, $toggle = false) {

        $AC = new AuthController();
        //if (!$AC->CheckAccess(['users/role/admin'])) return $AC->NoAccess();
        $usuarioLogueado = auth('sanctum')->user();
        $usuarioLogueadoId = ($usuarioLogueado) ? $usuarioLogueado->id : 0;

        $cotizacionId = $request->get('token');
        $vehiculoId = $request->get('vehiculoIdAgrupadorNodo');
        $cotizacion = Cotizacion::where([['token', '=', $cotizacionId]])->first();

        if (empty($cotizacion)) {
            return $this->ResponseError('COT-632', 'Cotización no válida');
        }

        $producto = $cotizacion->producto;
        $flujoConfig = $this->getFlujoFromCotizacion($cotizacion);

        if (!$flujoConfig['status']) {
            return $this->ResponseError($flujoConfig['error-code'], $flujoConfig['msg']);
        }
        else {
            $flujoConfig = $flujoConfig['data'];
        }


        // Estados
        $estados = [];
        if (!$public && isset($producto->extraData) && $producto->extraData !== '') {
            $estados = json_decode($producto->extraData, true);
            $estados = $estados['e'] ?? [];
        }
        // estados default
        $estados[] = 'expirada';

        // El flujo se va a orientar en orden según un array
        $allFields = [];
        $flujoOrientado = [];
        $flujoPrev = [];
        $flujoActual = [];
        $flujoNext = [];

        $reviewNodes = [];
        $reviewFields = [];

        // dd($flujoConfig['nodes']);
        // usuario asignado, variables
        $userAsigTmp = User::where('id', $cotizacion->usuarioIdAsignado)->first();
        $userAsigTmpVars = (!empty($userAsigTmp->userVars) ? @json_decode($userAsigTmp->userVars, true) : false);

        $camposAllTmp = [];
        $ordenCotizacionVehiculosCotizaciones = [];
        $ordenCotizacionVehiculos = [];

        if(!empty($vehiculoId)) {
            $camposAllTmp = CotizacionDetalle::where('cotizacionId', $cotizacion->id)
                ->where(function ($query) use ($vehiculoId) {
                    $query->where('cotizacionVehiculoId', $vehiculoId)
                        ->orWhereNull('cotizacionVehiculoId')
                        ->orWhere('cotizacionVehiculoId', 0);
                })
                ->get();
        }
        else {
            $camposAllTmp = CotizacionDetalle::where('cotizacionId', $cotizacion->id)->get();
            $getOrdenCotizacionesVehiculos = $this->getOrdenCotizacionesVehiculos($cotizacion->id);

            $ordenCotizacionVehiculosCotizaciones = $getOrdenCotizacionesVehiculos['ordenCotizacionVehiculosCotizaciones'];
            $ordenCotizacionVehiculos = $getOrdenCotizacionesVehiculos['ordenCotizacionVehiculos'];
        }

        // refactorizados campos all, ahora busca por llave del array en lugar del eloquent ->where
        $camposAll = [];
        foreach ($camposAllTmp as $field) {
            /*if(empty($vehiculoId) && !empty($field->campo)){
                if(!empty($field->cotizacionDetalleVehiculoCotId) && isset($ordenCotizacionVehiculosCotizaciones[$field->cotizacionDetalleVehiculoCotId])){
                    $field->campo = 'cot' . ($ordenCotizacionVehiculosCotizaciones[$field->cotizacionDetalleVehiculoCotId]+1) . '|' . $field->campo;
                }

                if(!empty($field->cotizacionVehiculoId) && isset($ordenCotizacionVehiculos[$field->cotizacionVehiculoId])){
                    $field->campo = preg_replace('/veh\d*\|/', 'veh|', $field->campo);
                }
            }*/

            // si trae vehiculo agrupador
            if (!empty($vehiculoId) && preg_match('/veh\d*\|/', $field->campo)) {

                $contextField = preg_replace('/veh\d*\|/', 'veh|', $field->campo);
                $campoTmp = new \stdClass();
                $campoTmp->id = $contextField;
                $campoTmp->campo = $contextField;
                $campoTmp->nombre = '';
                $campoTmp->valorLong = $field->valorLong;
                $camposAll[$contextField] = $campoTmp;
            }

            $camposAll[$field->campo] = $field;
        }

        unset($camposAllTmp);

        /*if(empty($vehiculoId)) {
            $calculateDataVehicule = array_map(function($e){
                $e['id'] = $e['campo'];
                $e['valor'] = $e['valorLong'];
                return $e;
            }, $this->calculateDataVehicule($cotizacion->id));

            foreach($calculateDataVehicule as $dataVehicle){
                $allFields[$dataVehicle['id']] = $dataVehicle;
            }
        }*/

        $inspeccion = CotizacionDetalle::where('cotizacionId', $cotizacion->id)->where('campo', 'wsAutoInspeccion.url')->first();
        if(!empty($inspeccion)) $inspeccion = $inspeccion->valorLong;
        $tmpUser = User::where('id', $cotizacion->usuarioId)->first();
        $grupoNombre = '';
        $tmpUserGrupo = UserGrupoUsuario::where('userId', $tmpUser->id ?? 0)->first();
        if (!empty($tmpUserGrupo)) {
            $grupoNombre = $tmpUserGrupo->grupo->nombre ?? '';
        }

        // variables de sistema
        if (!$public) {
            $tmpUserGrupo = SistemaVariable::all();
            foreach ($tmpUserGrupo as $varTmp) {
                $allFields[$varTmp->slug] = ['id' => $varTmp->slug, 'nombre' => '', 'valor' => $varTmp->contenido];
            }
        }

        // Variables defecto
        $allFields['FECHA_COTIZACION'] = ['id' => 'FECHA_COTIZACION', 'nombre' => '', 'valor' => Carbon::parse($cotizacion->dateCreated)->toDateTimeString()];
        $allFields['FECHA_HOY'] = ['id' => 'FECHA_HOY', 'nombre' => '', 'valor' => Carbon::now()->toDateTimeString()];

        // variables de usuario
        if (!$public) {
            $rolUser = UserRol::where('userId', $cotizacion->usuarioId)->first();
            $rol = null;
            if(!empty($rolUser)) $rol = Rol::where('id',  $rolUser -> rolId)->first();

            //Tiendas
            $tiendas = $tmpUser->tiendas ?? [];
            $tiendasNombre = [];
            $tiendasId = [];
            foreach($tiendas as $tienda){
                $dataTienda = $tienda->tienda;
                if(!empty($dataTienda)) {
                    $tiendasNombre[] = $dataTienda->nombre ?? '';
                    $tiendasId[] = $dataTienda->id;
                }
            }

            //Distribuidor y canales
            $gruposNombre = [];
            $gruposForUser = $tmpUser->grupos ?? [];
            $gruposForRol = $rol->grupos ?? [];

            $canalesNombre = [];
            $canalesNombreCod = [];
            $gruposId = [];
            foreach($gruposForUser as $group){
                $dataGrupo = $group->grupo;
                if(!empty($dataGrupo)) {
                    $gruposNombre[] = $dataGrupo->nombre ?? '';
                    $gruposId[] = $dataGrupo->id;
                };
            }

            foreach($gruposForRol as $group){
                $dataGrupo = $group->grupo;
                if(!empty($dataGrupo)) {
                    $gruposNombre[] = $dataGrupo->nombre ?? '';
                    $gruposId[] = $dataGrupo->id;
                };

                $canales = $dataGrupo->canales ?? [];
                foreach($canales as $canal){
                    $dataCanal = $canal->canal;
                    if(!empty($dataCanal)) {
                        $canalesNombre[$dataCanal->codigoInterno] = $dataCanal->nombre ?? '';
                        $canalesNombreCod[$dataCanal->codigoInterno] = $dataCanal->codigoInterno ?? '';
                    }
                }
            }

            $ejecutivos = $this->CalculateEjecutivo($tmpUser->id ?? 0, $rol->id ?? 0, $gruposId, $tiendasId);


            $allFields['CREADOR_NOMBRE'] = ['id' => 'CREADOR_NOMBRE', 'nombre' => '', 'valor' => (!empty($tmpUser) ? $tmpUser->name : 'Sin nombre')];
            $allFields['CREADOR_CORP'] = ['id' => 'CREADOR_CORP', 'nombre' => '', 'valor' => (!empty($tmpUser) ? $tmpUser->corporativo : 'Sin corporativo')];
            $allFields['CREADOR_GRUPO'] = ['id' => 'CREADOR_GRUPO', 'nombre' => '', 'valor' => $grupoNombre];
            $allFields['CREADOR_NOMBRE_USUARIO'] = ['id' => 'CREADOR_NOMBRE_USUARIO', 'nombre' => '', 'valor' => (!empty($tmpUser) ? $tmpUser->nombreUsuario : 'Sin nombre')];
            $allFields['CREADOR_ROL'] = ['id' => 'CREADOR_ROL', 'nombre' => '', 'valor' => (!empty($rol) ? $rol->name : 'Sin rol')];
            $allFields['CREADOR_CANAL'] = ['id' => 'CREADOR_CANAL', 'nombre' => '', 'valor' => (count($canalesNombre) > 0 ? implode(', ', $canalesNombre) : 'Sin canal')];
            $allFields['CREADOR_CANAL_CODIGO_INTERNO'] = ['id' => 'CREADOR_CANAL_CODIGO_INTERNO', 'nombre' => '', 'valor' => (count($canalesNombreCod) > 0 ? implode(', ', $canalesNombreCod) : 'Sin codigo de canal')];
            $allFields['CREADOR_DISTRIBUIDOR'] = ['id' => 'CREADOR_DISTRIBUIDOR', 'nombre' => '', 'valor' => (count($gruposNombre) > 0 ? implode(', ', $gruposNombre) : 'Sin distribuidor')];
            $allFields['CREADOR_TIENDA'] = ['id' => 'CREADOR_TIENDA', 'nombre' => '', 'valor' => (count($tiendasNombre) > 0 ? implode(', ', $tiendasNombre) : 'Sin tienda')];
            $allFields['CREADOR_EJECUTIVO'] = ['id' => 'CREADOR_EJECUTIVO', 'nombre' => '', 'valor' => (count($ejecutivos) > 0 ? implode(', ', $ejecutivos) : 'Sin Ejecutivo')];

            if (is_array($userAsigTmpVars)) {
                foreach ($userAsigTmpVars as $varTmp) {
                    $allFields[$varTmp['nombre']] = ['id' => $varTmp['nombre'], 'nombre' => '', 'valor' => $varTmp['valor']];
                }
            }
        }

        foreach ($flujoConfig['nodes'] as $nodo) {
            if (empty($nodo['typeObject'])) continue;
            if ($nodo['typeObject'] === 'review') {
                $reviewNodes[$nodo['id']]['c'] = $nodo['review'] ?? [];
                $reviewNodes[$nodo['id']]['f'] = [];
            }
        }

        //die('test');

        //var_dump($reviewNodes);
        // Recorro las lineas primero
        foreach ($flujoConfig['nodes'] as $key => $nodo) {

            if (empty($nodo['typeObject'])) continue;

            $privacidad = $nodo['priv'] ?? 'n';

            // todos los campos
            foreach ($nodo['formulario']['secciones'] as $seccion) {
                //$allFields[$keySeccion]['nombre'] = $seccion['nombre'];
                foreach ($seccion['campos'] as $campo) {

                    if (empty($campo['id'])) continue;

                    $campoTmp = $camposAll[$campo['id']] ?? false;
                    $valorTmp = $campo['valor'] ?? '';

                    if (!empty($campoTmp) && !empty($campoTmp->valorLong)) {
                        $valorTmp = $campoTmp->valorLong;
                        $jsonTmp = @json_decode($campoTmp->valorLong, true);
                        if ($jsonTmp) {
                            $valorTmp = $jsonTmp;
                        }
                    }

                    $allFields[$campo['id']] = [
                        'id' => $campo['id'],
                        'nombre' => $campo['id'],
                        'valor' => $valorTmp,
                    ];

                    if (!empty($campoTmp->valorShow)) {
                        $allFields["{$campo['id']}_DESC"] = [
                            'id' => "{$campo['id']}_DESC",
                            'nombre' => "{$campo['id']}_DESC",
                            'valor' => $campoTmp->valorShow,
                        ];
                    }

                    // agregar campos a revisión
                    foreach ($reviewNodes as $nodoId => $reviewFieldsTmp) {
                        if (in_array($campo['id'], $reviewFieldsTmp['c']) && !isset($reviewFields[$nodoId][$campo['id']])) {
                            $campo['valor'] = $valorTmp;
                            $reviewNodes[$nodoId]['f'][] = $campo;
                            $reviewFields[$nodoId][$campo['id']] = 1;
                        }
                    }
                }
            }

            $allFieldsSecure = $allFields;

            // se agreagan todas las variables guardadas del flujo, esto sirve también para los WS
            foreach ($camposAll as $campoTmp) {
                if (!isset($allFields[$campoTmp->campo])) { // reparado campo

                    $valorTmp = $campoTmp->valorLong;
                    $jsonTmp = @json_decode($campoTmp->valorLong, true);
                    if ($jsonTmp) {
                        $valorTmp = $jsonTmp;
                    }

                    $allFields[$campoTmp->campo] = [
                        'id' => $campoTmp->campo,
                        'nombre' => $campoTmp->campo,
                        'valor' => $valorTmp,
                    ];
                }
            }

            //continue;

            $lineasTemporalEntrada = [];
            $lineasTemporalSalida = [];
            $lineasTemporalSalidaDecision = ['si' => [], 'no' => [],];
            foreach ($flujoConfig['edges'] as $linea) {
                if ($linea['source'] === $nodo['id']) {
                    $lineasTemporalSalida[] = $linea['target'];

                    if ($linea['sourceHandle'] === 'salidaTrue') {
                        $lineasTemporalSalidaDecision['si'] = $linea['target'];
                    }
                    else if ($linea['sourceHandle'] === 'salidaFalse') {
                        $lineasTemporalSalidaDecision['no'] = $linea['target'];
                    }

                }
                if ($linea['target'] === $nodo['id']) {
                    $lineasTemporalEntrada[] = $linea['source'];
                }
            }

            $flujoOrientado[$nodo['id']] = [
                'nodoId' => $nodo['id'],
                'typeObject' => $nodo['typeObject'],
                'estOut' => $nodo['estOut'] ?? null, // Estado out
                'estIo' => $nodo['estIo'] ?? 's',
                'cmT' => $nodo['cmT'] ?? '', // Comentarios Tipo
                'gVh' => $nodo['gVh'] ?? 'd', // Comentarios Tipo
                'ocr' => $nodo['ocr'] ?? 'd', // Comentarios Tipo
                'ocrDesc' => $nodo['ocrDesc'] ?? '', // Comentarios Tipo
                'ocrTpl' => $nodo['ocrTpl'] ?? '', // Comentarios Tipo
                'ocrVC' => $nodo['ocrVC'] ?? '', // vincular a campo
                'expiracionNodo' => $nodo['expiracionNodo'] ?? false,
                'nodoName' => $nodo['nodoName'],
                'nodoClass' => preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $nodo['nodoName'])),
                'nodoNameId' => $nodo['nodoId'] ?? '',
                'type' => $nodo['type'],
                'label' => $nodo['label'] ?? '',
                'formulario' => $nodo['formulario'] ?? [],
                'btnText' => [
                    'prev' => $nodo['btnTextPrev'] ?? '',
                    'next' => $nodo['btnTextNext'] ?? '',
                    'finish' => $nodo['btnTextFinish'] ?? '',
                    'cancel' => $nodo['btnTextCancel'] ?? '',
                ],
            ];

            $flujoOrientado[$nodo['id']]['nodosEntrada'] = $lineasTemporalEntrada;
            $flujoOrientado[$nodo['id']]['nodosSalida'] = $lineasTemporalSalida;
            $flujoOrientado[$nodo['id']]['nodosSalidaDecision'] = $lineasTemporalSalidaDecision;

            $flujoOrientado[$nodo['id']]['userAssign'] = [
                'user' => $nodo['setuser_user'] ?? '',
                'role' => $nodo['setuser_roles'] ?? [],
                'group' => $nodo['setuser_group'] ?? [],
                'canal' => $nodo['canales_assign'] ?? [],
                'node' => $nodo['setuser_node'] ?? '',
                'variable' => $nodo['setuser_variable'] ?? '',
                'setuser_method' => $nodo['setuser_method'] ?? [],
            ];
            $flujoOrientado[$nodo['id']]['expiracionNodo'] = $nodo['expiracionNodo'] ?? false;
            $flujoOrientado[$nodo['id']]['procesos'] = $nodo['procesos'];
            $flujoOrientado[$nodo['id']]['decisiones'] = $nodo['decisiones'];
            $flujoOrientado[$nodo['id']]['decisionesL'] = $nodo['decisionesL'] ?? '';
            $flujoOrientado[$nodo['id']]['salidas'] = $nodo['salidas'];
            $flujoOrientado[$nodo['id']]['salidaIsPDF'] = $nodo['salidaIsPDF'];
            $flujoOrientado[$nodo['id']]['salidaPDFconf'] = $nodo['salidaPDFconf'] ?? [];
            $flujoOrientado[$nodo['id']]['salidaIsHTML'] = $nodo['salidaIsHTML'];
            $flujoOrientado[$nodo['id']]['salidaIsEmail'] = $nodo['salidaIsEmail'];
            $flujoOrientado[$nodo['id']]['salidaIsWhatsapp'] = $nodo['salidaIsWhatsapp'];
            $flujoOrientado[$nodo['id']]['procesoWhatsapp'] = $nodo['procesoWhatsapp'];
            $flujoOrientado[$nodo['id']]['procesoEmail'] = $nodo['procesoEmail'];
            $flujoOrientado[$nodo['id']]['roles_assign'] = $nodo['roles_assign'];
            $flujoOrientado[$nodo['id']]['tareas_programadas'] = $nodo['tareas_programadas'];
            $flujoOrientado[$nodo['id']]['pdfTpl'] = $nodo['pdfTpl'] ?? [];
            $flujoOrientado[$nodo['id']]['salidaPDFId'] = $nodo['salidaPDFId'] ?? '';
            $flujoOrientado[$nodo['id']]['salidaPDFGroup'] = $nodo['salidaPDFGroup'] ?? '';
            $flujoOrientado[$nodo['id']]['salidaPDFDp'] = $nodo['salidaPDFDp'] ?? '';
            $flujoOrientado[$nodo['id']]['salidaPDFLabel'] = $nodo['salidaPDFLabel'] ?? '';
            $flujoOrientado[$nodo['id']]['saltoAutomatico'] = $nodo['saltoAutomatico'] ?? '';
            $flujoOrientado[$nodo['id']]['addcvv'] = $nodo['addcvv'] ?? false;
            $flujoOrientado[$nodo['id']]['afiliacion'] = $nodo['afiliacion'] ?? '';
        }

        // die('asdfasdf');


        // Si el nodo actual está vacío, debe ser que está iniciando
        if (empty($cotizacion->nodoActual)) {

            // Validación de nodo de entrada
            $entradaDetectada = false;
            foreach ($flujoOrientado as $nodo) {
                // Si es de entrada
                if ($nodo['type'] === 'input') {

                    // valido si existen dos entradas
                    if (!$entradaDetectada) {
                        $flujoActual = $nodo;
                        $entradaDetectada = true;
                    }
                    else {
                        return $this->ResponseError('COT-048', 'El flujo se encuentra mal configurado, existen dos nodos de entrada');
                    }
                }
            }
        }
        else {
            foreach ($flujoOrientado as $nodo) {
                if ($nodo['nodoId'] === $cotizacion->nodoActual) {
                    $flujoActual = $nodo;
                }
            }
        }

        if (empty($flujoActual)) {
            return $this->ResponseError('COT-058', 'Esta cotización no puede visualizarse, ha cambiado o se han eliminado etapas');
        }

        // Traigo los nodos de entrada
        if (!empty($flujoActual['nodosEntrada'])) {
            foreach ($flujoActual['nodosEntrada'] as $id) {
                if (isset($flujoOrientado[$id])) {
                    $flujoPrev = $flujoOrientado[$id];
                }
            }
        }

        // dd($flujoActual);

        // Traigo los nodos de salida
        if (!empty($flujoActual['nodosSalida'])) {
            foreach ($flujoActual['nodosSalida'] as $id) {
                if (isset($flujoOrientado[$id])) {
                    $flujoNext = $flujoOrientado[$id];
                }
            }
        }

        //var_dump($reviewNode);

        // agrega campos a revisión
        if (count($reviewNodes) > 0) {
            if (isset($reviewNodes[$flujoActual['nodoId']])) {
                $flujoActual['formulario']['secciones'][0]['nombre'] = 'Revisión';
                $flujoActual['formulario']['secciones'][0]['campos'] = $reviewNodes[$flujoActual['nodoId']]['f'];
                $flujoActual['formulario']['secciones'][0]['condiciones'] = [];
            }

        }

        //dd($camposAll);

        // Se calculan los valores que se traen
        if (!empty($flujoActual['formulario']['secciones'])) {
            foreach ($flujoActual['formulario']['secciones'] as $keySeccion => $seccion) {

                $keySeccion = (string) $keySeccion;

                $flujoActual['formulario']['secciones'][$keySeccion]['seccionId'] = $keySeccion;
                $allGroupCamps = 0;

                foreach ($seccion['campos'] as $keyCampo => $campo) {
                    if ($campo['tipoCampo'] === 'add') {
                        $campoTmp = $camposAll[$campo['id']] ?? false;
                        $longMin = $this->reemplazarValoresSalida($allFields, $campo['longitudMin'] ?? '');
                        $cant = (!empty($campoTmp) && !empty($campoTmp->valorLong))? intval($campoTmp->valorLong) : 1;
                        if($cant < intval($longMin)) $cant = intval($longMin);
                        $fieldsGroupFilter = array_filter($seccion['campos'],
                            function($camp) use($campo) {
                                return $camp['group'] === $campo['id'];
                        });
                        $fieldsGroupAll = array_map(function($idF){ return $idF['id'];}, $fieldsGroupFilter);
                        for ($x = 1; $x <= $cant; $x++) {
                            $fieldsGroup =
                                array_map(
                                    function($campGroup) use($campo, $x, $camposAll, $fieldsGroupFilter, $fieldsGroupAll){
                                        if (is_array($campGroup['dependOn'])) {
                                            $campGroup['dependOn'] = array_map(function($c) use ($campGroup, $fieldsGroupFilter, $x) {
                                                $campoId = $c['campoId'];
                                                if (!empty($c['campoId']) && array_reduce($fieldsGroupFilter, function($carry, $camp) use ($c) {
                                                    return $carry || ($camp['id'] === $c['campoId']);
                                                }, false)) {
                                                    $campoId = $campGroup['group'] . '_' . $c['campoId'] . '_' . $x;
                                                }
                                                $c['campoId'] = $campoId;
                                                return $c;
                                            }, $campGroup['dependOn']);
                                        }
                                        if(in_array($campGroup['catFId'], $fieldsGroupAll)){
                                            $campGroup['catFId'] = "{$campo['id']}_{$campGroup['catFId']}_{$x}";
                                        }
                                        $campGroup['id'] = "{$campo['id']}_{$campGroup['id']}_{$x}";
                                        $campGroup['group'] = '';
                                        $campGroupDb = $camposAll[$campGroup['id']] ?? false;
                                        if(!empty($campGroupDb)) $campGroup['valor'] = $campGroupDb->valorLong;

                                        foreach($campGroup as $keyg => $camg){
                                            if(!is_string($camg)) continue;
                                            $campGroup[$keyg] = $this->reemplazarValorGroup($fieldsGroupFilter, $camg, $x);
                                        }

                                        return $campGroup;
                                    },$fieldsGroupFilter);
                            array_splice($flujoActual['formulario']['secciones'][$keySeccion]['campos'], $keyCampo + $allGroupCamps + count($fieldsGroup)*($x-1), 0, $fieldsGroup);
                        }
                        $allGroupCamps += ($cant* count($fieldsGroupFilter));
                    }
                }

                foreach ($flujoActual['formulario']['secciones'][$keySeccion]['campos'] as $keyCampo => $campo) {

                    $campoTmp = $camposAll[$campo['id']] ?? false;

                    // defaults
                    if (empty($flujoActual['formulario']['secciones'][$keySeccion]['campos'][$keyCampo]['longitudMax'])) $flujoActual['formulario']['secciones'][$keySeccion]['campos'][$keyCampo]['longitudMax'] = 20;

                    // Reemplazo de parámetros de campo
                    $flujoActual['formulario']['secciones'][$keySeccion]['campos'][$keyCampo]['ph'] = $this->reemplazarValoresSalida($allFields, $flujoActual['formulario']['secciones'][$keySeccion]['campos'][$keyCampo]['ph'] ?? '');
                    $flujoActual['formulario']['secciones'][$keySeccion]['campos'][$keyCampo]['ttp'] = $this->reemplazarValoresSalida($allFields, $flujoActual['formulario']['secciones'][$keySeccion]['campos'][$keyCampo]['ttp'] ?? '');
                    $flujoActual['formulario']['secciones'][$keySeccion]['campos'][$keyCampo]['desc'] = $this->reemplazarValoresSalida($allFields, $flujoActual['formulario']['secciones'][$keySeccion]['campos'][$keyCampo]['desc'] ?? '');
                    $flujoActual['formulario']['secciones'][$keySeccion]['campos'][$keyCampo]['nombre'] = $this->reemplazarValoresSalida($allFields, $flujoActual['formulario']['secciones'][$keySeccion]['campos'][$keyCampo]['nombre'] ?? '');
                    $flujoActual['formulario']['secciones'][$keySeccion]['campos'][$keyCampo]['longitudMax'] = $this->reemplazarValoresSalida($allFields, $flujoActual['formulario']['secciones'][$keySeccion]['campos'][$keyCampo]['longitudMax'] ?? '');
                    $flujoActual['formulario']['secciones'][$keySeccion]['campos'][$keyCampo]['longitudMin'] = $this->reemplazarValoresSalida($allFields, $flujoActual['formulario']['secciones'][$keySeccion]['campos'][$keyCampo]['longitudMin'] ?? '');

                    // procesa los por defecto
                    $flujoActual['formulario']['secciones'][$keySeccion]['campos'][$keyCampo]['valor'] = $this->reemplazarValoresSalida($allFields, $flujoActual['formulario']['secciones'][$keySeccion]['campos'][$keyCampo]['valor'] ?? '');

                    if (!empty($campoTmp) && !empty($campoTmp->valorLong)) {
                        if($campoTmp->tipo === 'encrypt'){
                            $campoTmp->valorLong = $this->desencriptar($campoTmp->valorLong);
                        }
                        $tmpJson = @json_decode($campoTmp->valorLong, true);

                        if (!empty($flujoActual['formulario']['secciones'][$keySeccion]['campos'][$keyCampo]['forceReplaceDef'])) {
                            $flujoActual['formulario']['secciones'][$keySeccion]['campos'][$keyCampo]['valor'] = $this->reemplazarValoresSalida($allFields, $flujoActual['formulario']['secciones'][$keySeccion]['campos'][$keyCampo]['valor'] ?? '');
                        }
                        else {
                            //var_dump($campoTmp->valorLong);
                            $flujoActual['formulario']['secciones'][$keySeccion]['campos'][$keyCampo]['valor'] = ((!empty($tmpJson) && (is_array($tmpJson) || (!is_infinite($tmpJson) && !is_nan($tmpJson)))) ? $tmpJson : $campoTmp->valorLong);
                        }

                        // si es array, reviso los valores ya seleccionados
                        /*if ($tmpJson) {
                            if (!empty($flujoActual['formulario']['secciones'][$keySeccion]['campos'][$keyCampo]['catalogoId']['items']) ) {

                                foreach ($flujoActual['formulario']['secciones'][$keySeccion]['campos'][$keyCampo]['catalogoId']['items'] as $keyItem => $itemTmp) {

                                    //dd($itemTmp);
                                    if (!empty($itemTmp[$flujoActual['formulario']['secciones'][$keySeccion]['campos'][$keyCampo]['catalogoValue']])){
                                        if (is_array($tmpJson) && in_array($itemTmp[$flujoActual['formulario']['secciones'][$keySeccion]['campos'][$keyCampo]['catalogoValue']], $tmpJson)) {
                                            $flujoActual['formulario']['secciones'][$keySeccion]['campos'][$keyCampo]['catalogoId']['items'][$keyItem]['selected'] = true;
                                        }
                                    }
                                }
                            }
                        }*/
                        if ($campoTmp->tipo === 'currency') {
                            if ($campoTmp->valorLong === null || $campoTmp->valorLong === '.00' || $campoTmp->valorLong === '') {
                                $flujoActual['formulario']['secciones'][$keySeccion]['campos'][$keyCampo]['valor'] = 0;
                            }
                        }

                    }
                }
            }
        }
        //die('here');
        // dd($flujoActual);

        // Si es una salida, hay que procesar la salida con la data ya guardada
        if ($flujoActual['typeObject'] === 'output') {
            $dataToSend = $this->reemplazarValoresSalida($allFields, $flujoActual['salidas']);

            $flujoActual['salidaReplaced'] = $dataToSend;

            if(!empty($flujoActual['saltoAutomatico']) && !empty($flujoActual['salidaIsHTML']) && !$toggle){
                $request->merge(['paso' => 'next']);
                $producto = $this->CambiarEstadoCotizacion($request, false, false, false, false);
            }

        }

        $flujoTmp = Flujos::where('productoId', $cotizacion->productoId)->where('activo', 1)->first('modoPruebas');
        if (empty($flujoTmp)) {
            return $this->ResponseError('COT-254', 'No existe ningún flujo activo para este producto');
        }

        $bitacoraView = [];
        if ($usuarioLogueadoId) {
            $bitacora = CotizacionBitacora::where('cotizacionId', $cotizacion->id)->with('usuario')->orderBy('id', 'DESC')->get();

            foreach ($bitacora as $bit) {
                if (!$flujoTmp['modoPruebas'] || !$AC->CheckAccess(['tareas/modo-pruebas'])) {
                    $bit->makeHidden(['dataInfo']);
                }

                $bit->usuarioNombre = $bit->usuario->name ?? 'Sin usuario';
                $bit->usuarioCorporativo = $bit->usuario->corporativo ?? 'Sin usuario';
                $bit->makeHidden(['usuario']);

                $bitacoraView[] = $bit;
            }
        }

        $bitacoraViewRecapitulation = [];
        $typesNode = [
            "start" => "Inicio",
            "input" => "Entradas",
            "condition" => "Condición",
            "process" => "Proceso",
            "setuser" => "Usuario",
            "review" => "Revisión",
            "output" => "Salida",
            "finish" => "Finalizar"
        ];

        if ($usuarioLogueadoId) {
            $bitacoraReca = CotizacionesUserNodo::
                where('cotizacionId', $cotizacion->id)
                ->orderBy('cotizacionesUserNodo.id', 'DESC')
                ->get();

            foreach ($bitacoraReca as $bitReca) {
                $bitReca->nodoName = $flujoOrientado[$bitReca->nodoId]['nodoName'] ?? 'Nodo sin Nombre';
                // $bitReca->nodoNameId = $flujoOrientado[$bitReca->nodoId]['nodoNameId'] ?? 'Nodo sin Identificador';
                $bitReca->typeObject = $typesNode[$flujoOrientado[$bitReca->nodoId]['typeObject'] ?? 'default'] ?? 'Nodo sin tipo';
                $bitReca->usuarioNombre = $bitReca->usuario->name ?? 'Sin usuario';
                $bitReca->usuarioCorporativo = $bitReca->usuario->corporativo ?? 'Sin Corporativo';
                $bitReca->createdAt = Carbon::parse($bitReca->createdAt)->setTimezone('America/Guatemala')->format('d/m/Y H:i');
                $bitacoraViewRecapitulation[] = $bitReca;
            }
        }

        // Salto el nodo ya que no corresponde a mi usuario
        $rolUsuarioLogueado = ($usuarioLogueado) ? $usuarioLogueado->rolAsignacion->rol : 0;
        $calcularVisibilidad = function ($flujo) use ($usuarioLogueadoId, $rolUsuarioLogueado, $public) {

            $hasConfigUsers = false;
            $usersDetalle = [];

            if (($public && $flujo['formulario']['tipo'] === 'publico') || ($public && $flujo['formulario']['tipo'] === 'mixto')) {
                // var_dump('asdfasdfsda');
                return true;
            };

            // evalua canales
            if (!empty($flujo['userAssign']['canal']) && is_array($flujo['userAssign']['canal']) && count($flujo['userAssign']['canal']) > 0) {

                $hasConfigUsers = true;

                $canales = UserCanalGrupo::whereIn('userCanalId', $flujo['userAssign']['canal'])->get();
                $flujo['userAssign']['group'] = [];
                foreach ($canales as $canal) {

                    $gruposUsuarios = $canal->canal->grupos;

                    foreach ($gruposUsuarios as $grupoU) {
                        if ($grupo = $grupoU->grupo) {
                            $users = $grupo->users;

                            // por usuario del grupo
                            foreach ($users as $userAsig) {
                                $usersDetalle[$userAsig->userId] = $userAsig->userId;
                            }
                            // por rol
                            if ($rol = $grupo->roles) {
                                foreach ($rol as $r) {
                                    if ($gruposRol = $r->rol) {
                                        $roles = $gruposRol->usersAsig;
                                        foreach ($roles as $userAsig) {
                                            $usersDetalle[$userAsig->userId] = $userAsig->userId;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // usuarios específicos del grupo
            if (!empty($flujo['userAssign']['group']) && is_array($flujo['userAssign']['group']) && count($flujo['userAssign']['group']) > 0) {
                $hasConfigUsers = true;

                // verifico usuarios específicos
                $usersGroup = UserGrupoUsuario::whereIn('userGroupId', $flujo['userAssign']['group'])->get();
                foreach ($usersGroup as $grupoUser) {
                    $gruposUsuarios = $grupoUser->grupo->users;
                    foreach ($gruposUsuarios as $userAsig) {
                        $usersDetalle[$userAsig->userId] = $userAsig->userId;
                    }
                }

                // por rol
                $usersGroupR = UserGrupoRol::whereIn('userGroupId', $flujo['userAssign']['group'])->get();

                foreach ($usersGroupR as $gruposRol) {
                    $userA = $gruposRol->rol->usersAsig;
                    foreach ($userA as $userAsig) {
                        $usersDetalle[$userAsig->userId] = $userAsig->userId;
                    }
                }
            }

            // verifico roles específicos
            if (!empty($flujo['roles_assign']) && is_array($flujo['roles_assign']) && count($flujo['roles_assign']) > 0) {
                $hasConfigUsers = true;
                if (in_array($rolUsuarioLogueado->id ?? 0, $flujo['roles_assign'])) {
                    $usersDetalle[] = $usuarioLogueadoId;
                }
            }

            return (in_array($usuarioLogueadoId, $usersDetalle));
        };

        $expiraDate = '';
        $expiro = false;

        if (!empty($cotizacion->dateExpire)) {
            $fechaHoy = Carbon::now();
            $fechaExpira = Carbon::parse($cotizacion->dateExpire);
            if ($fechaHoy->gt($fechaExpira)) {
                $expiro = true;
            }
            $expiraDate = $fechaExpira->format('d-m-Y');

            if ($AC->CheckAccess(['tareas/admin/operar-expirado']) && $expiro) {
                $cotizacion->estado = 'expirada_opt';
                $expiro = false;
                $expiraDate = '';
            }
        }

        $codigoAgente = $cotizacion->codigoAgente;
        if (!$AC->CheckAccess(['tareas/show-cod-ag'])) {
            $codigoAgente = 'N/D';
        }

        // Extra data
        $cotizacionData = [
            'ca' => (!$public ? ($codigoAgente ?? 'N/D') : 'N/D'),
            'acc' => true,
            'ed' => '',
            'ex' => $expiro,
            'exd' => $expiraDate,
            'no' => $cotizacion->id,
            'identificador' => $cotizacion->identificador,
            'rve' => $flujoActual['procesoEmail']['reenvio']??  false,
            'rvw' => $flujoActual['procesoWhatsapp']['reenvio']?? false,
        ];

        // Actual
        $userHandler = new AuthController();
        $CalculateAccess = $userHandler->CalculateAccess();
        $usuarioAsigID = (!empty($cotizacion->usuarioAsignado) ? $cotizacion->usuarioAsignado->id : 0);

        // si es supervisor
        $visibilidad = false;
        if (in_array($usuarioAsigID, $CalculateAccess['sup'])) {
            $visibilidad = in_array($usuarioAsigID, $CalculateAccess['all']);
        }
        else {
            $visibilidad = in_array($usuarioAsigID, $CalculateAccess['det']);
        }

        // si no tiene jerarquia, valida visibilidad de nodo
        if (!empty($flujoActual['formulario']['tipo']) && !$visibilidad) {
            $visibilidad = $calcularVisibilidad($flujoActual);
        }

        // acceso
        $cotizacionData['acc'] = $visibilidad;


        // si no es público
        if (!$public) {
            $usuarioAsig = $cotizacion->usuarioAsignado;

            if (!empty($usuarioAsig)) {
                $rolAsignado = $usuarioAsig->rolAsignacion->rol->name ?? 'N/D';
                $usuarioDesc = "";

                if ($AC->CheckAccess(['users/listar'])) {
                    $usuarioDesc = ", usuario: {$usuarioAsig->nombreUsuario} ({$usuarioAsig->name})";
                }
                $cotizacionData['ed'] = "Formulario asociado al rol: {$rolAsignado}{$usuarioDesc}";
            }

            if (!$cotizacionData['acc']) {
                $cotizacionData['ed'] .= ', no posees acceso a este formulario.';
            }
        }
        else {
            $usuarioAsig = $cotizacion->usuarioAsignado;

            if (!empty($usuarioAsig)) {
                $rolAsignado = $usuarioAsig->rolAsignacion->rol->name ?? 'N/D';
                $usuarioDesc = "";

                if ($AC->CheckAccess(['users/listar'])) {
                    $usuarioDesc = ", usuario: {$usuarioAsig->nombreUsuario} ({$usuarioAsig->name})";
                }
                $cotizacionData['ed'] = "Formulario asociado al rol: {$rolAsignado}{$usuarioDesc}";
            }

            if (!$cotizacionData['acc']) {
                $cotizacionData['ed'] .= ', no posees acceso a este formulario.';
            }

            else {
                $cotizacionData['ed'] = "Formulario publico";
            }
        }

        // Valido el usuario asignado
        /*if ($cotizacion->usuarioIdAsignado !== $usuarioLogueadoId) {

            $usuarioAsig = $cotizacion->usuarioAsignado;
            $cotizacionData['acc'] = false; // Acceso

            if (!$public) {
                if (!$usuarioAsig) {
                    $cotizacionData['ed'] = "El formulario no posee un usuario asignado";
                    $cotizacionData['acc'] = true; // Acceso
                }
                else {
                    $cotizacionData['ed'] = "Formulario no disponible, se encuentra asociada al usuario: {$usuarioAsig->nombreUsuario} ({$usuarioAsig->name})";
                }
            }
        }*/

        if (!$AC->CheckAccess(['admin/show-assi-usr'])) {
            $cotizacionData['ed'] = '';
        }

        //var_dump($cotizacion->rechazoData);
        $rechazoComments = [];
        $rechazoDataTmp = @json_decode($cotizacion->rechazoData, true);
        if (is_array($rechazoDataTmp)) {

            $camposActual = [];
            foreach ($flujoActual['formulario']['secciones'] as $seccion) {
                foreach ($seccion['campos'] as $campo) {
                    $camposActual[$campo['id']] = true;
                }
            }

            foreach ($rechazoDataTmp as $rechazoNodo) {
                foreach ($rechazoNodo as $rechazo) {
                    $hasField = false;
                    foreach ($rechazo['f'] as $campoKey => $campoVal) {
                        // si el rechazo tiene algún campo de mi nodo
                        if (isset($camposActual[$campoKey])) {
                            $hasField = true;
                            break;
                        }
                    }
                    if ($hasField) {
                        $rechazoComments[] = $rechazo;
                    }
                }
            }
        }
        //var_dump($flujoActual);

        // valido si es nodo de salida
        if ($onlyArray) {
            return ['actual' => $flujoActual, 'next' => $flujoNext, 'prev' => $flujoPrev, 'bit' => $bitacoraView, 'd' => $allFields, 'c' => $cotizacionData];
        }
        else {

            if ($public && $cotizacionData['acc']) {
                unset($flujoActual['nodosEntrada']);
                unset($flujoActual['userAssign']);
                unset($flujoActual['nodosEntrada']);
                unset($flujoActual['nodosSalida']);
                unset($flujoActual['nodosSalidaDecision']);
                unset($flujoActual['expiracionNodo']);
                unset($flujoActual['salidas']);
                unset($flujoActual['salidaIsPDF']);
                unset($flujoActual['salidaIsHTML']);
                unset($flujoActual['salidaIsEmail']);
                unset($flujoActual['salidaIsWhatsapp']);
                unset($flujoActual['procesoWhatsapp']);
                unset($flujoActual['procesoEmail']);
                unset($flujoActual['roles_assign']);
                unset($flujoActual['tareas_programadas']);
                unset($flujoActual['pdfTpl']);
                unset($flujoActual['salidaPDFId']);
                unset($flujoActual['salidaPDFGroup']);
                unset($flujoActual['salidaPDFDp']);
                unset($flujoActual['salidaPDFLabel']);
                unset($flujoActual['decisionesL']);
                unset($flujoActual['decisiones']);
                unset($flujoActual['procesos']);
                unset($flujoActual['saltoAutomatico']);
                unset($flujoActual['addcvv']);
                unset($flujoActual['afiliacion']);
            }

            if (!$cotizacionData['acc']) {
                $flujoActual = false;
            }

            $estadoCoti = $cotizacion->estado;
            if (!$AC->CheckAccess(['tareas/show-status'])) {
                $estadoCoti = 'S/E';
            }

            return $this->ResponseSuccess('Flujo calculado con éxito', ['estado' => $estadoCoti, 'actual' => $flujoActual, 'next' => (count($flujoNext) > 0), 'prev' => (count($flujoPrev) > 0), 'bit' => $bitacoraView, 'bitReca' => $bitacoraViewRecapitulation, 'd' => $allFieldsSecure, 'c' => $cotizacionData, 'e' => $estados, 'cG' => $rechazoComments, 'inspeccion' => $inspeccion ?? '']);
        }
    }

    public function CalcularPasosPublic(Request $request) {
        return $this->CalcularPasos($request, false, true);
    }

    public function CalcularCatalogo(Request $request) {

        $depends = $request->get('depends');
        $valor = $request->get('value');
        $cotizacionId = $request->get('token');

        return Cache::remember("cat_{$depends}_{$valor}_{$cotizacionId}", env('CACHE_SECONDS', 600), function() use ($depends, $valor, $cotizacionId) {

            $cotizacion = Cotizacion::where([['token', '=', $cotizacionId]])->first();
            $producto = $cotizacion->producto;
            $allcampos = $cotizacion->campos;

            $flujo = $producto->flujo->first();
            if (empty($flujo)) {
                return $this->ResponseError('COT-608', 'Flujo no válido');
            }

            $flujoConfig = @json_decode($flujo->flujo_config, true);
            if (!is_array($flujoConfig)) {
                return $this->ResponseError('COT-610', 'Error al interpretar flujo, por favor, contacte a su administrador');
            }

            $GetSyncCatalogoSlugs = new CatalogosController();
            $slugs = $GetSyncCatalogoSlugs->GetSyncCatalogoSlugs();

            $arrNodosCatalogo = [];
            foreach ($flujoConfig['nodes'] as $nodo) {

                if (empty($nodo['typeObject'])) continue;

                // todos los campos
                foreach ($nodo['formulario']['secciones'] as $keySeccion => $seccion) {
                    //$allFields[$keySeccion]['nombre'] = $seccion['nombre'];
                    foreach ($seccion['campos'] as $keyCampo => $campo) {
                        if ($campo['tipoCampo'] === 'add') {
                            $cantCampo = $this->reemplazarValoresSalida($allcampos, $campo['longitudMax']);
                            $cant = (is_int(intval($cantCampo)) && intval($cantCampo)) >= 1? intval($cantCampo) : 20;
                            $fieldsGroupAll = array_map(function($idF){ return $idF['id'];},
                                array_filter($seccion['campos'],
                                    function($camp) use($campo) {
                                        return $camp['group'] === $campo['id'];
                            }));
                            $fieldsGroupFilter = array_filter($seccion['campos'],
                                function($camp) use($campo) {
                                    return ($camp['group'] === $campo['id']) && (!empty($camp['id']) && !empty($camp['catalogoId']));
                            });
                            for ($x = 1; $x <= $cant; $x++) {
                                $fieldsGroup =
                                    array_map(
                                        function($campGroup) use($campo, $x, $fieldsGroupFilter, $fieldsGroupAll){
                                            if(in_array($campGroup['catFId'], $fieldsGroupAll)){
                                                $campGroup['catFId'] = "{$campo['id']}_{$campGroup['catFId']}_{$x}";
                                            }
                                            $campGroup['id'] = "{$campo['id']}_{$campGroup['id']}_{$x}";
                                            $campGroup['group'] = '';
                                            return $campGroup;
                                        }, $fieldsGroupFilter);

                                array_splice($seccion['campos'], $keyCampo + count($fieldsGroup)*($x-1), 0, $fieldsGroup);
                            }
                        }
                    }
                    foreach ($seccion['campos'] as $campo) {
                        if (empty($campo['id']) || empty($campo['catalogoId'])) continue;
                        $arrNodosCatalogo[$campo['id']] = $campo;
                    }
                }
            }

            if (count($arrNodosCatalogo) === 0) {
                return $this->ResponseSuccess('Catalogos obtenidos con éxito', []);
            }

            $tmpData = [];
            if (isset($producto->extraData) && $producto->extraData !== '') {
                $tmpData = json_decode($producto->extraData, true);
                $tmpData = $tmpData['planes'] ?? [];
            }
            //die('cat here');

            foreach ($slugs as $catId => $cat) {
                $class = "App\Models\\{$cat['class']}";
                if (class_exists($class)) {
                    $tmpClass = new $class();
                    $catTmpData = $tmpClass->where('activo', 1)->get();

                    $items = [];

                    foreach ($catTmpData as $item) {
                        $items[] = $item;
                    }

                    $tmpData[$catId] = [
                        'slug' => $catId,
                        'show' => false,
                        'items' => $items,
                        'nombreCatalogo' => $cat['nombre'],
                    ];
                }
            }
            //var_dump($tmpData);

            //dd($tmpData);

            $arrResponse = [];

            $fieldWithCatalogDepenps = [];
            foreach ($arrNodosCatalogo as $campo) {
                if (is_string($campo['catalogoId'])) {

                    // var_dump($campo['catalogoId']);

                    if (isset($tmpData[$campo['catalogoId']])) {
                        //var_dump($tmpData[$campo['catalogoId']]);

                        $itemsCatalog = [];

                        if (!empty($campo['catFId'])) {
                            $fieldWithCatalogDepenps[] = $campo['id'];

                            if (!empty($depends) && $campo['catFId'] === $depends) {
                                foreach ($tmpData[$campo['catalogoId']]['items'] as $item) {
                                    if (isset($item[$campo['catFValue']]) && strval($item[$campo['catFValue']]) === strval($valor)) {
                                        $itemsCatalog[] = $item;
                                    }
                                }
                                $arrResponse[$campo['id']] = $itemsCatalog;
                            } else {
                                $valorDetalle = CotizacionDetalle::where('cotizacionId', $cotizacion->id)->where('campo', $campo['catFId'])->first();
                                if(empty($valorDetalle)) continue;
                                foreach ($tmpData[$campo['catalogoId']]['items'] as $item) {
                                    if (isset($item[$campo['catFValue']]) && $item[$campo['catFValue']] === strval($valorDetalle->valorLong)) {
                                        $itemsCatalog[] = $item;
                                    }
                                }
                                $arrResponse[$campo['id']] = $itemsCatalog;

                            }
                            if(in_array($campo['catFId'], $fieldWithCatalogDepenps)){
                                $arrResponse[$campo['id']] = $itemsCatalog;
                            }
                        }
                        else {
                            //var_dump($campo['catFValue']);
                            //dd($campo['catalogoId']);
                            if (empty($depends)) {
                                $itemsCatalog = $tmpData[$campo['catalogoId']]['items'];
                                $arrResponse[$campo['id']] = $itemsCatalog;
                            }
                        }
                    }
                }
            }
            //dd($arrResponse);

            return $this->ResponseSuccess('Catalogos obtenidos con éxito', $arrResponse);
        });
    }

    public function consumirServicio($proceso = [], $data = [], $nodoId = '', $cotizacion = false) {
        ini_set('max_execution_time', 400);

        $isRoble = ($proceso['authType'] === 'elroble');

        $cotizacionId = $cotizacion->id ?? false;
        //$timingStart = microtime(true);

        // manejo de errores
        $manejoErroresPersonalizado = $proceso['manErrP'] ?? false;
        $manejoErroresPConf = (!empty($proceso['manErrC'])) ? @json_decode($proceso['manErrC'], true) : [];
        /*var_dump($manejoErroresPConf);
        die;*/

        //dd($proceso);
        $arrResponse = [];
        $arrResponse['status'] = false;
        $arrResponse['msg'] = 'El servicio no ha respondido adecuadamente o ha devuelto un error';
        $arrResponse['msgErrP'] = $arrResponse['msg'];
        $arrResponse['log'] = [];
        $arrResponse['data'] = [];

        // Log de proceso
        $dataResponse = [];
        $dataResponse['enviado'] = [];
        $dataResponse['enviadoH'] = [];
        $dataResponse['recibidoProcesado'] = [];
        $dataResponse['recibido'] = [];

        if (empty($proceso['authType'])) {
            $arrResponse['msg'] = 'Error, la configuración del servicio no tiene tipo de autenticación definida';
            return $arrResponse;
        }

        if (is_object($data)) {
            $data = $data->toArray();
        }

        // ahora se reemplazan los pre formatos
        if (!empty($proceso['pf'])) {
            //dd($data);
            foreach ($proceso['pf'] as $pf) {

                $condicion = $this->reemplazarValoresSalida($data, $pf['con']);
                $valores = $this->reemplazarValoresSalida($data, $pf['c']);

                $smpl = new \Le\SMPLang\SMPLang();
                $result = @$smpl->evaluate($condicion);

                $data[] = [
                    'id' => $pf['va'],
                    'campo' => $pf['va'],
                    'valorLong' => ((!empty($result)) ? $valores : ''),
                ];
            }
        }

        $dataToSend = $this->reemplazarValoresSalida($data, $proceso['entrada'], $isRoble); // En realidad es salida pero lo guardan como entrada
        $dataToSend = trim($dataToSend);
        $url = $this->reemplazarValoresSalida($data, $proceso['url']);
        $headers = $this->reemplazarValoresSalida($data, $proceso['header']);
        $hadersSend = [];
        // dd($proceso['header']);

        // Reemplazo bien los headers
        if (!empty($headers)) {
            $hadersSend = @json_decode($headers, true);

            if (!is_array($hadersSend)) {
                $arrResponse['msg'] = 'Error, las cabeceras no se encuentran bien configuradas';
                return $arrResponse;
            }
        }

        $respuestaXml = (!empty($proceso['respuestaXML']));

        $dataSend = false;

        if (empty($proceso['method'])) {
            $arrResponse['msg'] = 'Debe configurar el tipo de servicio (POST, GET, etc)';
            return $arrResponse;
        }

        if ($proceso['authType'] === 'elroble') {

            $urlAuth = $proceso['authUrl'] ?? '';
            $authPayload = $proceso['authPayload'] ?? '';

            if (empty($urlAuth)) {
                $arrResponse['msg'] = 'Debe configurar la url de autenticación del servicio';
                return $arrResponse;
            }

            if (empty($authPayload)) {
                $arrResponse['msg'] = 'Debe configurar los datos de autenticación del servicio';
                return $arrResponse;
            }

            $acsel = new \ACSEL_WS(false, true); // Siempre el servicio de gestiones de momento
            $acsel->setAuthData($urlAuth, $authPayload);


            $dataResponse['enviado'] = $dataToSend;

            //var_dump('Tiempo en preparar servicio: ' . microtime(true) - $timingStart);

            if ($proceso['method'] == 'get') {
                $dataSend = $acsel->get($url, false);
            }
            else if ($proceso['method'] == 'post') {
                $dataSend = $acsel->post($url, $dataToSend ?? [], false, $respuestaXml);
            }

            //var_dump('Tiempo en ejecutar servicio: ' . microtime(true) - $timingStart);

            if (!empty($dataSend)) {
                $arrResponse['status'] = true;
                $arrResponse['msg'] = 'Petición realizada con éxito';
            }
            else {
                $arrResponse['msg'] = 'Error al consumir servicio, el servicio no ha devuelto respuesta';
            }

            $dataResponse['enviadoH'] = (!empty($acsel->rawHeaders) ? $acsel->rawHeaders : $headers);
            $dataResponse['recibidoProcesado'] = $dataSend;
            $dataResponse['recibido'] = $acsel->rawResponse;
        }
        else {

            // Autenticación cualquiera
            if ($proceso['authType'] === 'bearer') {
                $hadersSend['Authorization'] = "Bearer {$proceso['bearerToken']}";
            }

            $headers = [];
            foreach ($hadersSend as $key => $value) {
                $headers[] = "{$key}:{$value}";
            }

            $dataResponse['enviadoH'] = print_r($headers, true);
            $dataResponse['enviado'] = $dataToSend;

            // PHP cURL  for https connection with auth
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_ENCODING , "");
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            //curl_setopt($ch, CURLOPT_USERPWD, $soapUser.":".$soapPassword); // username and password - declared at the top of the doc
            //curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            if ($proceso['method'] == 'get') {
                curl_setopt($ch, CURLOPT_POST, false);
            }
            else if ($proceso['method'] == 'post') {
                curl_setopt($ch, CURLOPT_POST, true);
            }

            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataToSend); // the SOAP request

            //dd($hadersSend);

            // converting
            $dataSend = curl_exec($ch);
            $dataResponse['recibido'] = print_r($dataSend, true);

            curl_close($ch);

            if ($respuestaXml) {

                $dataSend = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $dataSend);
                libxml_use_internal_errors(true);
                $xml = simplexml_load_string($dataSend);

                if (!$xml) {
                    libxml_clear_errors();
                    $arrResponse['msg'] = 'Error al parsear XML de respuesta';
                    return $arrResponse;
                }
                else {
                    $dataSend = json_decode(json_encode((array)simplexml_load_string($dataSend)), true);
                }
            }
            else {
                $dataSend = @json_decode($dataSend, true);
            }

            $dataResponse['recibidoProcesado'] = print_r($dataSend, true);

        }

        $result = array();
        if (is_array($dataSend)) {
            $ritit = new RecursiveIteratorIterator(new RecursiveArrayIterator($dataSend));

            foreach ($ritit as $leafValue) {
                $keys = array();
                foreach (range(0, $ritit->getDepth()) as $depth) {
                    $keys[] = $ritit->getSubIterator($depth)->key();
                }

                $result[join('.', $keys)] = $leafValue;
            }
        }

        $resultFull = [];
        foreach ($result as $key => $value) {
            $resultFull[$proceso['identificadorWs'] . '.' . $key] = $value;
        }

        $errorPGen = '';

        // errores personalizados
        if ($manejoErroresPersonalizado) {

            $flujoErroresPer = $cotizacion->producto->manErr ?? 0;

            if (is_array($manejoErroresPConf) && !empty($flujoErroresPer)) {

                $errorPData = [];

                foreach ($manejoErroresPConf as $errorP) {
                    // si es satisfactorio
                    /*var_dump($resultFull[$errorP['campo_ws']]);
                    var_dump($errorP['tomarSatisfactorio']);*/

                    if (isset($resultFull[$errorP['campo_ws']]) && $resultFull[$errorP['campo_ws']] == $errorP['tomarSatisfactorio']) {
                        $varKey = "{$proceso['identificadorWs']}.WSEXEC";
                        $errorPData["{$varKey}.nodo"] = $nodoId;
                        $errorPData["{$varKey}.status"] = 1;
                        $errorPData["{$varKey}.codigoHttp"] = $resultFull["{$proceso['identificadorWs']}.httpcode"] ?? 'N/D';
                        $errorPData["{$varKey}.msg"] = $errorP['textoSatisfactorio'];
                        $errorPData["{$varKey}.wkToken"] = $errorP['wkToken'] ?? false;
                    }
                    else {
                        $varKey = "{$proceso['identificadorWs']}.WSEXEC";
                        $errorPData["{$varKey}.nodo"] = $nodoId;
                        $errorPData["{$varKey}.status"] = 0;
                        $errorPData["{$varKey}.codigoHttp"] = $resultFull["{$proceso['identificadorWs']}.httpcode"] ?? 'N/D';
                        $errorPData["{$varKey}.msg"] = $errorP['textoError'];
                        $errorPData["{$varKey}.wkToken"] = $errorP['wkToken'] ?? false;
                    }

                    $errorPGen = $errorPData["{$varKey}.msg"];
                }

                foreach ($errorPData as $key => $value) {
                    $campo = CotizacionDetalle::where('campo', $key)->where('cotizacionId', $cotizacionId)->first();
                    if (empty($campo)) {
                        $campo = new CotizacionDetalle();
                    }
                    $campo->cotizacionId = $cotizacionId;
                    $campo->campo = $key;
                    $campo->valorLong = $value;
                    $campo->save();
                }

                $tokenId = '';
                $token = '';
                if (!empty($errorPData["{$varKey}.wkToken"]) && empty($errorPData["{$varKey}.status"])) {

                    $apiKey = env('WK_API').':'.env('WK_KEY');
                    $headers = [
                        'Authorization: Bearer '.$apiKey,
                        'Content-Type:application/json',
                    ];

                    // ejecuta workflow
                    $arrSend = [
                        'token' => '',
                        'flujo' => $errorPData["{$varKey}.wkToken"],
                        'campos' => [
                            'mensaje' => [
                                't' => 'text',
                                'v' => $errorPData["{$varKey}.msg"],
                            ],
                            'status' => [
                                't' => 'text',
                                'v' => $errorPData["{$varKey}.status"],
                            ],
                        ],
                    ];

                    $link = env('WK_URL') . "/tareas/operacion";
                    $tmpEnviado = json_encode($arrSend);
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $link);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $tmpEnviado);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    $server_output = curl_exec($ch);
                    $server_output = @json_decode($server_output, true);

                    // var_dump($server_output);

                    $tokenId = $server_output['data']['id'] ?? '';

                    $error_msg = '';
                    if (curl_errno($ch)) {
                        $error_msg = curl_error($ch);
                    }
                    curl_close($ch);
                }

                $bitacoraCoti = new CotizacionBitacora();
                $bitacoraCoti->cotizacionId = $cotizacionId;
                $bitacoraCoti->usuarioId = null;
                $bitacoraCoti->onlyPruebas = 1;
                $bitacoraCoti->dataInfo = json_encode($errorPData) . "WK, Tarea Id: {$tokenId}, Wk MSG: ".($server_output['msg'] ?? '');
                $bitacoraCoti->log = 'Excepción personalizada WS';
                $bitacoraCoti->save();
            }
        }

        $arrResponse['data'] = $resultFull;
        $arrResponse['log'] = $dataResponse;
        $arrResponse['errP'] = $dataResponse;
        $arrResponse['msgErrP'] = (!empty ($errorPGen) ? $errorPGen : $arrResponse['msg']);

        if (!empty($dataSend)) {
            $arrResponse['status'] = true;
            $arrResponse['msg'] = 'Petición realizada con éxito';
        }

        //var_dump('Tiempo de aplanamiento de respuesta: ' . microtime(true) - $timingStart);
        //die();

        return $arrResponse;
    }

    public function reemplazarValoresSalida($arrayValores, $texto, $convertirMayuscula = false, $verifyIsReplace = false, $ignoreFiles = false) {

        $tmpUserGrupo = Cache::remember("replace_vars_system_vars", env('CACHE_SECONDS', 600), function() {
            return SistemaVariable::all();
        });

        foreach ($tmpUserGrupo as $varTmp) {
            $varTmp->slug = trim($varTmp->slug);
            $arrayValores[$varTmp->slug] = ['id' => $varTmp->slug, 'nombre' => '', 'valorLong' => $varTmp->contenido, 'sysvar' => true];
        }

        $AC = new AuthController();
        $usuarioLogueado = auth('sanctum')->user();
        if(!empty($usuarioLogueado)) $arrayValores['CINTILLO_TIENDA'] = ['id' => 'CINTILLO_TIENDA', 'campo' => 'CINTILLO_TIENDA', 'nombre' => '', 'valorLong' => $AC->GetCintillo()];

        $result = $texto;
        foreach ($arrayValores as $dataItem) {

            if (empty($dataItem['id'])) continue;

            if (!isset($dataItem['valorLong'])) {
                $dataItem['valorLong'] = $dataItem['valor'] ?? '';
            }

            if(!empty($dataItem['tipo']) && $dataItem['tipo'] === 'encrypt'){
                $dataItem['valorLong'] = $this->desencriptar($dataItem['valorLong']);
            }

            if (!$ignoreFiles && !empty($dataItem['isFile']) && !empty($dataItem['valorLong']) && is_string($dataItem['valorLong'])) {
                //$dataItem['valorLong'] = Storage::disk('s3')->temporaryUrl($dataItem['valorLong'], now()->addMinutes(10));

                //var_dump($dataItem['valorLong']);
                //die();

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $dataItem['valorLong']);
                curl_setopt($ch, CURLOPT_HEADER, TRUE);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                $a = curl_exec($ch);
                if(preg_match('#Location: (.*)#', $a, $r)) {
                    $tmpPath = trim($r[1]);
                    $dataItem['valorLong'] = $tmpPath;

                    /*var_dump($tmpPath);
                    die();*/
                }
            }

            $stringData = $dataItem['valorLong'];

            if (empty($dataItem['noDecodeJson'])) {

                if ($dataItem['valorLong'] === '{}') $stringData = '';
                $jsonTmp = is_array($dataItem['valorLong']) ? $dataItem['valorLong'] : @json_decode($dataItem['valorLong'], true);

                if ($jsonTmp && is_array($jsonTmp)) {
                    if (empty($dataItem['sysvar'])) {
                        if (count($jsonTmp) > 0) {
                            $stringData = implode(', ', $jsonTmp);
                        }
                        else {
                            $stringData = '';
                        }
                    }
                    else {
                        $stringData = $dataItem['valorLong'];
                    }

                }
            }


            /*if (!empty($dataItem['maskOutput'])) {
                $stringDataTmp = @Carbon::parse($stringData)->format($dataItem['maskOutput']);

                if ($stringDataTmp) {
                    $stringData = $stringDataTmp;
                }
            }*/


            /*if ( $convertirMayuscula ) {
                $dataItem['campo'] = strtoupper($dataItem['campo']);
            }*/
            $idField = (!empty($dataItem['campo']) ? $dataItem['campo'] : $dataItem['id']);
            $idField = trim($idField);
            $token = "{{" . $idField . "}}";

            if ($verifyIsReplace && strpos($result, $token) && empty($stringData)) return false;
            $result = preg_replace("/" . preg_quote($token) . "/", $stringData, $result);
        }

        // remueve todas las variables que no existan
        //$result = preg_replace('/\{\{...*}}/s', '', $result);

        //$result = strtr($result, array('{{' => '', '}}' => ''));
        if ($verifyIsReplace && preg_match('/{{.*?}}/', $result)) return false;
        $result = preg_replace('#\s*\{\{.+}}\s*#U', '', $result);
        //dd($result);

        return $result;
    }

    public function validateUserInGroup($user, $userGroups = [], $roles = []) {

        $rolesGroupArr = [];

        $userRol = $user->rolAsignacion->first();
        $userRol = $userRol->rolId ?? 0;

        if (count($userGroups) > 0) {
            $rolesGroup = UserGrupoRol::whereIn('userGroupId', $userGroups)->get();

            foreach ($rolesGroup as $rolG) {
                $rolesGroupArr[] = $rolG->rolId;
            }

            if (!in_array($userRol, $rolesGroupArr)) {
                return false;
            }
        }

        if (count($roles) > 0) {
            if (!in_array($userRol, $roles)) {
                return false;
            }
        }

        return true;
    }

    public function uploadFileAttachPublic(Request $request) {
        return $this->uploadFileAttach($request, true);
    }

    // Subida de archivos
    public function uploadFileAttach(Request $request, $public = false) {

        $archivo = $request->file('file');
        $cotizacionId = $request->get('token');
        $seccionKey = $request->get('seccionKey');
        $campoId = $request->get('campoId');
        $isOCR = $request->get('isOCR');
        $tpl = $request->get('tp');
        $vehiculoIdAgrupadorNodo = $request->get('vehiGroup');
        $vehicleNumber = $request->get('vehicleNumber');

        $usuarioLogueado = auth('sanctum')->user();
        $cotizacion = Cotizacion::where([['token', '=', $cotizacionId]])->first();

        if (!empty($usuarioLogueado) && !$public) {
            $AC = new AuthController();
            if (!$AC->CheckAccess(['tareas/admin/uploadfiles'])) return $AC->NoAccess();
        }

        if (empty($cotizacion)) {
            return $this->ResponseError('COT-632', 'La tarea no existe o está asociada a otro usuario');
        }

        $flujoActual = false;
        if ($isOCR) {
            $flujoActual = $this->CalcularPasos($request, true);
        }

        $flujoConfig = $this->getFlujoFromCotizacion($cotizacion);
        //dd($flujoConfig);

        if (!$flujoConfig['status']) {
            return $this->ResponseError($flujoConfig['error-code'], $flujoConfig['msg']);
        }
        else {
            $flujoConfig = $flujoConfig['data'];
        }

        // Recorro campos para hacer resumen
        $expedientesNew = [];
        $campos = [];
        $camposAllTmp = $cotizacion->campos;
        $camposAll = [];
        foreach ($camposAllTmp as $field) {
            $camposAll[$field->campo] = $field;
        }

        foreach ($flujoConfig['nodes'] as $nodo) {
            //$resumen
            if (!empty($nodo['formulario']['secciones']) && count($nodo['formulario']['secciones']) > 0) {
                foreach ($nodo['formulario']['secciones'] as $keySeccion => $seccion) {
                    foreach ($seccion['campos'] as $keyCampo => $campo) {
                        $campos[$campo['id']] = $campo;
                        if ($campo['tipoCampo'] === 'add') {
                            $campoTmp = $camposAll[$campo['id']] ?? false;
                            $longMin = $this->reemplazarValoresSalida($camposAll, $campo['longitudMin'] ?? '');
                            $cant = (!empty($campoTmp) && !empty($campoTmp->valorLong))? intval($campoTmp->valorLong) : 1;
                            if($cant < intval($longMin)) $cant = intval($longMin);
                            $fieldsGroupFilter = array_filter($seccion['campos'],
                                function($camp) use($campo) {
                                    return $camp['group'] === $campo['id'];
                            });

                            for ($x = 1; $x <= $cant; $x++) {
                                foreach($fieldsGroupFilter as $campGroup){
                                    $campGroup['id'] = "{$campo['id']}_{$campGroup['id']}_{$x}";
                                    $campGroup['group'] = '';
                                    $campos[$campGroup['id']] = $campGroup;
                                }
                            }
                        }
                    }
                }
            }
        }

        $expedientesNew = $campos[$campoId]['expNewConf'] ?? [];

        $dir = '';
        $tipoArchivo = '';
        $arrMimeTypes = [];
        $arrMimeTypesTmp = [];

        if (!empty($campos[$campoId]['filePath'])) {
            $dir = $campos[$campoId]['filePath'];
            $tipoArchivo = $campos[$campoId]['tipoCampo'];
        }
        $dir = trim($dir, '/');

        // Variables por defecto si no existen
        $data = $cotizacion->campos;

        if (empty($data->where('campo', 'ID_SOLICITUD')->first())) {

            $camposTmp = [];
            $tmpUser = User::where('id', $cotizacion->usuarioId)->first();
            $rolUser = UserRol::where('userId', $cotizacion->usuarioId)->first();
            $rol = null;
            if(!empty($rolUser)) $rol = Rol::where('id',  $rolUser -> rolId)->first();

            // Tiendas
            $tiendas = $tmpUser->tiendas ?? [];
            $tiendasNombre = [];
            $tiendasId = [];
            foreach($tiendas as $tienda){
                $dataTienda = $tienda->tienda;
                if(!empty($dataTienda)) {
                    $tiendasNombre[] = $dataTienda->nombre ?? '';
                    $tiendasId[] = $dataTienda->id;
                }
            }

            // Distribuidor y canales
            $gruposNombre = [];
            $gruposForUser = $tmpUser->grupos ?? [];
            $gruposForRol = $rol->grupos ?? [];

            $canalesNombre = [];
            $gruposId = [];
            $canalesNombreCod = [];
            foreach($gruposForUser as $group){
                $dataGrupo = $group->grupo;
                if(!empty($dataGrupo)) {
                    $gruposNombre[] = $dataGrupo->nombre ?? '';
                    $gruposId[] = $dataGrupo->id;
                };
            }

            foreach($gruposForRol as $group){
                $dataGrupo = $group->grupo;
                if(!empty($dataGrupo)) {
                    $gruposNombre[] = $dataGrupo->nombre ?? '';
                    $gruposId[] = $dataGrupo->id;
                };

                $canales = $dataGrupo->canales ?? [];
                foreach($canales as $canal){
                    $dataCanal = $canal->canal;
                    if(!empty($dataCanal)) {
                        $canalesNombre[$dataCanal->codigoInterno] = $dataCanal->nombre ?? '';
                        $canalesNombreCod[$dataCanal->codigoInterno] = $dataCanal->codigoInterno ?? '';
                    }
                }
            }

            $ejecutivos = $this->CalculateEjecutivo($tmpUser->id ?? 0, $rol->id ?? 0, $gruposId, $tiendasId);


            $camposTmp['FECHA_COTIZACION']['v'] = $cotizacion->dateCreated;
            $camposTmp['FECHA_HOY']['v'] = Carbon::now()->toDateTimeString();
            $camposTmp['ID_COTIZACION']['v'] = $cotizacion->id;
            $camposTmp['HOY_SUM_1_YEAR']['v'] = Carbon::now()->addYear()->toDateTimeString();
            $camposTmp['HOY_SUM_1_YEAR_F1']['v'] = Carbon::now()->addYear()->format('d/m/Y');
            $camposTmp['CREADOR_NOMBRE']['v'] = (!empty($tmpUser) ? $tmpUser->name : 'Sin nombre');
            $camposTmp['CREADOR_CORP']['v'] = (!empty($tmpUser) ? $tmpUser->corporativo : 'Sin corporativo');
            $camposTmp['CREADOR_NOMBRE_USUARIO']['v'] = (!empty($tmpUser) ? $tmpUser->nombreUsuario : 'Sin nombre');
            $camposTmp['CREADOR_ROL']['v'] = (!empty($rol) ? $rol->name : 'Sin rol');
            $camposTmp['CREADOR_CANAL']['v'] = (count($canalesNombre) > 0 ? implode(', ', $canalesNombre) : 'Sin canal');
            $camposTmp['CREADOR_CANAL_CODIGO_INTERNO']['v'] = (count($canalesNombreCod) > 0 ? implode(', ', $canalesNombreCod) : '');
            $camposTmp['CREADOR_DISTRIBUIDOR']['v'] = (count($gruposNombre) > 0 ? implode(', ', $gruposNombre) : 'Sin distribuidor');
            $camposTmp['CREADOR_TIENDA']['v'] = (count($tiendasNombre) > 0 ? implode(', ', $tiendasNombre) : 'Sin tienda');
            $camposTmp['CREADOR_EJECUTIVO']['v'] = (count($ejecutivos) > 0 ? implode(', ', $ejecutivos) : 'Sin Ejecutivo');


            // producto
            $productoTk = $cotizacion->producto->token ?? '';
            $camposTmp['LINK_FORM']['v'] = $this->getCotizacionLink($productoTk, $cotizacion->token);

            foreach ($camposTmp as $campoKey => $valor) {
                $campoTmp = new CotizacionDetalle();
                $campoTmp->cotizacionId = $cotizacion->id;
                $campoTmp->seccionKey = 0;
                $campoTmp->campo = $campoKey;
                $campoTmp->label = '';
                $campoTmp->useForSearch = 0;
                $campoTmp->tipo = 'default';
                $campoTmp->valorLong = $valor['v'];
                $campoTmp->save();
            }

            // se vuelve a traer la data
            $cotizacion = $cotizacion->refresh();
            $data = $cotizacion->campos;
        }

        if (!empty($campos[$campoId]['mime'])) {
            $campos[$campoId]['mime'] = $this->reemplazarValoresSalida($camposAll, $campos[$campoId]['mime']);
            $arrMimeTypesTmp = explode(',', $campos[$campoId]['mime']);
        }
        //wdd($campos[$campoId]);

        $resultMimes = array_map('trim', $arrMimeTypesTmp);

        foreach ($resultMimes as $mime) {
            $peso = explode('|', $mime);
            if (!empty($peso[0])) {
                $arrMimeTypes[$peso[0]] = $peso[1] ?? 0;
            }
        }
        //dd($arrMimeTypes);

        // Valido los mime
        $fileType = $archivo->getMimeType();
        $fileType = trim($fileType);
        $fileSize = $archivo->getSize();
        $fileSize = $fileSize/1000000;

        if (!isset($arrMimeTypes[$fileType])) {
            return $this->ResponseError('T-12', 'Tipo de archivo no permitido para subida', ['mime' => $fileType, 'size' => $fileSize]);
        }

        // valido peso
        if (floatval($arrMimeTypes[$fileType]) < floatval($fileSize)) {
            return $this->ResponseError('T-13', "Peso de archivo excedido, máximo ".number_format($arrMimeTypes[$fileType], 2)." mb");
        }

        $hashName = md5($archivo->getClientOriginalName()); // Obtiene el nombre generado por Laravel
        $extension = $archivo->extension();
        $filenameWithExtension = $hashName . '.' . $extension; // Concatena el nombre generado por Laravel con la extensión


        if ($tipoArchivo === 'file') {
            try {
                //dd($archivo);
                $extensions = [
                    'png', 'jpg', 'jpeg',
                ];

                // guardo en local
                $disk = Storage::disk('local');
                $fileTmp = $dir.'/'.$filenameWithExtension;
                $disk->putFileAs($dir, $archivo, $filenameWithExtension);
                $diskPath = Storage::disk('local')->path($dir).'/'.$filenameWithExtension;

                if (in_array($extension, $extensions)) {
                    $image = new ImageManager(['driver' => 'imagick']);
                    $image = $image->make($diskPath);
                    $imagick = $image->getCore();
                    $imagick->setImageResolution(72, 72);
                    $imagick->resampleImage(72, 72, \Imagick::FILTER_UNDEFINED, 1);
                    $image->resize(1920, 1800, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                    $image->save($diskPath);
                }
                // subida al s3
                $disk = Storage::disk('s3');
                $path = $disk->put($fileTmp, file_get_contents($diskPath));
                $temporarySignedUrl = Storage::disk('s3')->temporaryUrl($fileTmp, now()->addMinutes(10));

                $campo = null;
                if(!empty($vehiculoIdAgrupadorNodo)) {
                    if (!empty($vehicleNumber)) {
                        $campoId = "{$campoId}_{$vehicleNumber}";
                    }
                    $campo = CotizacionDetalle::where('campo', $campoId)->where('cotizacionId', $cotizacion->id)->where('cotizacionVehiculoId', $vehiculoIdAgrupadorNodo)->first();
                }
                else {
                    $campo = CotizacionDetalle::where('campo', $campoId)->where('cotizacionId', $cotizacion->id)->first();
                }

                if (empty($campo)) {
                    $campo = new CotizacionDetalle();
                }
                $campo->cotizacionId = $cotizacion->id;
                $campo->seccionKey = $seccionKey;
                $campo->campo = $campoId;
                $campo->valorLong = $fileTmp;
                $campo->isFile = 1;
                if(!empty($vehiculoIdAgrupadorNodo)) $campo->cotizacionVehiculoId = $vehiculoIdAgrupadorNodo;
                $campo->save();

                // procesa ocr
                $headers = array(
                    'Content-Type: application/json',
                    'Authorization: Bearer '. env('ANY_SUBSCRIPTIONS_TOKEN')
                );

                $dataSend = [
                    "process"=>"auto",
                    "removePages"=> 1,
                    "htmlEndlines"=> 0,
                    "noReturnEndlines"=> 1,
                    "includeText"=> 1,
                    "detectQRBar"=> 0,
                    "encodingFrom"=> 0,
                    "encodingTo"=> 0
                ];

                $dataSend['templateToken'] =  $flujoActual['actual']['ocrTpl'] ?? '';
                $dataSend['fileLink'] = $temporarySignedUrl;

                $urlOcr = env('ANY_SUBSCRIPTIONS_URL', '').'/formularios/docs-plus/ocr-process/gen3';
                $ch = curl_init($urlOcr);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dataSend));
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                $data = curl_exec($ch);
                $info = curl_getinfo($ch);
                curl_close($ch);
                $resultado = @json_decode($data, true);

                if (!empty($resultado['status'])) {

                    foreach ($flujoActual['actual']['formulario']['secciones'] as $seccion) {
                        foreach ($seccion['campos'] as $campoTmp) {

                            if (!empty($campoTmp['ocrConfig'])) {
                                $desdeVariables = explode(',', $campoTmp['ocrConfig']);

                                foreach ($desdeVariables as $var) {

                                    if (isset($resultado['data']['tokens'][$var])) {

                                        $campo = null;
                                        if(!empty($vehiculoIdAgrupadorNodo)) {
                                            $campo = CotizacionDetalle::where('campo', $campoTmp['id'])->where('cotizacionId', $cotizacion->id)->where('cotizacionVehiculoId', $vehiculoIdAgrupadorNodo)->first();
                                        }
                                        else {
                                            $campo = CotizacionDetalle::where('campo', $campoTmp['id'])->where('cotizacionId', $cotizacion->id)->first();
                                        }

                                        if (empty($campo)) {
                                            $campo = new CotizacionDetalle();
                                        }
                                        $campo->cotizacionId = $cotizacion->id;
                                        $campo->seccionKey = $seccionKey;
                                        $campo->campo = $campoTmp['id']."_{$vehicleNumber}";
                                        $campo->valorLong = $resultado['data']['tokens']['pages'][0][$var][0];
                                        $campo->isFile = 0;
                                        if(!empty($vehiculoIdAgrupadorNodo)) $campo->cotizacionVehiculoId = $vehiculoIdAgrupadorNodo;
                                        $campo->save();
                                    }
                                }
                            }
                        }
                    }
                }
                /*var_dump($resultado);
                die();*/

                return $this->ResponseSuccess('Archivo subido con éxito', [
                    'key' => $temporarySignedUrl
                ]);

            } catch (\Exception $e) {
                var_dump($e->getMessage());
                //dd($e->getMessage());
                //$response['msg'] = 'Error en subida, por favor intente de nuevo '.$e;
                return $this->ResponseError('T-121', 'Error al cargar archivo ');
            }
        }
        else {

            if (!empty($campos[$campoId]['filePath'])) {
                $dir = $campos[$campoId]['filePath'];
                $tipoArchivo = $campos[$campoId]['tipoCampo'];
            }

            $ch = curl_init();

            // Se mandan indexados
            $arrArchivo = [];
            $urlExp = env('EXPEDIENTES_URL') . '/?api=true&opt=upload';

            // Si usará nueva estructura de expedientes
            if (!empty($expedientesNew['label'])) {

                $urlExp = env('EXPEDIENTES_NEW_URL') . '/?api=true&opt=upload';
                $expedientesNew['label'] = $expedientesNew['label'] ?? '';

                $arrArchivo['folderPath'] = trim(trim($dir), '/');
                $arrArchivo['ramo'] = $expedientesNew['ramo'] ?? '';
                $arrArchivo['label'] = (!empty($vehiculoIdAgrupadorNodo)) ? "{$expedientesNew['label']}_{$vehicleNumber}" : $expedientesNew['label'];
                $arrArchivo['filetype'] = $expedientesNew['tipo'] ?? '';
                $arrArchivo['sourceaplication'] = 'Gestor Comercial Automovil';
                $arrArchivo['bucket'] = 'EXPEDIENTES';
                $arrArchivo['overwrite'] = $expedientesNew['sobreescribir'] ?? 'N';

                foreach ($expedientesNew['attr'] as $attr) {
                    $arrArchivo[$attr['attr']] = $attr['value'];
                }
            }
            else {
                $arrArchivo['folderPath'] = trim(trim($dir), '/');
                $arrArchivo['ramo'] = $campos[$campoId]['fileRamo'] ?? '';
                $arrArchivo['producto'] = $campos[$campoId]['fileProducto'] ?? '';
                $arrArchivo['fechaCaducidad'] = $campos[$campoId]['fileFechaExp'] ?? '';
                $arrArchivo['reclamo'] = $campos[$campoId]['fileReclamo'] ?? '';
                $arrArchivo['poliza'] = $campos[$campoId]['filePoliza'] ?? '';
                $arrArchivo['estadoPoliza'] = $campos[$campoId]['fileEstadoPoliza'] ?? '';
                $arrArchivo['nit'] = $campos[$campoId]['fileNit'] ?? '';
                $arrArchivo['dpi'] = $campos[$campoId]['fileDPI'] ?? '';
                $arrArchivo['cif'] = $campos[$campoId]['fileCIF'] ?? '';
                $arrArchivo['label'] = $campos[$campoId]['fileLabel'] ?? '';
                $arrArchivo['filetype'] = $campos[$campoId]['fileTipo'] ?? '';
                $arrArchivo['filetypeSecondary'] = $campos[$campoId]['fileTipo2'] ?? '';
                $arrArchivo['source'] = 'Gestor Comercial Automovil';
            }

            $arrSend = [];
            foreach ($arrArchivo as $key => $item) {
                $arrSend[$key] = $this->reemplazarValoresSalida($camposAll, $item, false, $key === 'folderPath'); // En realidad es salida pero lo guardan como entrada
            }

            if (empty($arrSend['folderPath'])) {
                return $this->ResponseError('T-223', 'Uno o más campos son requeridos previo a la subida de este archivo');
            }

            //dd($archivo);
            $extensions = [
                'png', 'jpg', 'jpeg',
            ];

            // guardo en local
            $disk = Storage::disk('local');
            $fileTmp = $dir.'/'.$filenameWithExtension;
            $disk->putFileAs($dir, $archivo, $filenameWithExtension);
            $diskPath = Storage::disk('local')->path($dir).'/'.$filenameWithExtension;

            if (in_array($extension, $extensions)) {
                $image = new ImageManager(['driver' => 'imagick']);
                $image = $image->make($diskPath);
                $imagick = $image->getCore();
                $imagick->setImageResolution(72, 72);
                $imagick->resampleImage(72, 72, \Imagick::FILTER_UNDEFINED, 1);
                $image->resize(1920, 1800, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                $image->save($diskPath);
            }

            $arrSend['file'] = new \CurlFile($diskPath, $fileType, $filenameWithExtension);

            $headers = [
                'Authorization: Bearer 1TnwxbcvSesYkiqzl2nsmPgULTlYZFgSrcb3hSb383Tkv0ZzyaBz0sjD7LM2ymh',
            ];
            //dd($arrArchivo);

            curl_setopt($ch, CURLOPT_URL, $urlExp);
            curl_setopt($ch, CURLOPT_POST,1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $arrSend);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $server_output = curl_exec($ch);
            $server_output = @json_decode($server_output, true);
            curl_close($ch);

            //dd($server_output);

            if (!empty($server_output['status'])) {

                $campo = null;

                if (!empty($vehiculoIdAgrupadorNodo)){
                    $campoId = "{$campoId}_{$vehicleNumber}";
                }

                if(!empty($vehiculoIdAgrupadorNodo)) {
                    $campo = CotizacionDetalle::where('campo', $campoId)->where('cotizacionId', $cotizacion->id)->where('cotizacionVehiculoId', $vehiculoIdAgrupadorNodo)->first();
                }
                else {
                    $campo = CotizacionDetalle::where('campo', $campoId)->where('cotizacionId', $cotizacion->id)->first();
                }

                if (empty($campo)) {
                    $campo = new CotizacionDetalle();
                }
                $campo->cotizacionId = $cotizacion->id;
                $campo->seccionKey = $seccionKey;
                $campo->campo = $campoId;
                $campo->label = $campoId;
                $campo->valorLong = $server_output['data']['exp-url'];
                $campo->isFile = 1;
                if(!empty($vehiculoIdAgrupadorNodo)) $campo->cotizacionVehiculoId = $vehiculoIdAgrupadorNodo;
                $campo->save();


                // procesa ocr
                $headers = array(
                    'Content-Type: application/json',
                    'Authorization: Bearer '. env('ANY_SUBSCRIPTIONS_TOKEN')
                );

                $dataSend = [
                    "process"=>"auto",
                    "removePages"=> 1,
                    "htmlEndlines"=> 0,
                    "noReturnEndlines"=> 1,
                    "includeText"=> 1,
                    "detectQRBar"=> 0,
                    "encodingFrom"=> 0,
                    "encodingTo"=> 0,
                    "engine"=> 'AI_2',
                ];

                $dataSend['templateToken'] =  $flujoActual['actual']['ocrTpl'] ?? '';
                $dataSend['fileLink'] = $server_output['data']['exp-url'];

                $link = env('ANY_SUBSCRIPTIONS_URL', '').'/formularios/docs-plus/ocr-process/gen3';
                $ch = curl_init($link);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dataSend));
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                $data = curl_exec($ch);
                $info = curl_getinfo($ch);
                curl_close($ch);
                $resultado = @json_decode($data, true);

                $ocrBitacora = (string) $data;
                $ocrBitacoraSend = json_encode($dataSend);
                $bitacoraCoti = new CotizacionBitacora();
                $bitacoraCoti->cotizacionId = $cotizacion->id;
                $bitacoraCoti->usuarioId = $usuarioLogueado->id ?? null;
                $bitacoraCoti->onlyPruebas = 1;
                $bitacoraCoti->dataInfo = "<b>Link</b>: {$link}, <b>Enviado:</b> {$ocrBitacoraSend}, <b>Recibido:</b> {$ocrBitacora}";
                $bitacoraCoti->log = "OCR procesado";
                $bitacoraCoti->save();

                if (!empty($resultado['status'])) {

                    foreach ($flujoActual['actual']['formulario']['secciones'] as $seccion) {
                        foreach ($seccion['campos'] as $campoTmp) {

                            if (!empty($campoTmp['ocrConfig'])) {
                                $desdeVariables = explode(',', $campoTmp['ocrConfig']);

                                foreach ($desdeVariables as $var) {

                                    /*var_dump($var);
                                    var_dump($resultado['data']);*/

                                    if (isset($resultado['data']['tokens'][$var])) {

                                        $campo = null;
                                        if(!empty($vehiculoIdAgrupadorNodo)) {
                                            $campo = CotizacionDetalle::where('campo', $campoTmp['id'])->where('cotizacionId', $cotizacion->id)->where('cotizacionVehiculoId', $vehiculoIdAgrupadorNodo)->first();
                                        }
                                        else {
                                            $campo = CotizacionDetalle::where('campo', $campoTmp['id'])->where('cotizacionId', $cotizacion->id)->first();
                                        }

                                        if (empty($campo)) {
                                            $campo = new CotizacionDetalle();
                                        }
                                        $campo->cotizacionId = $cotizacion->id;
                                        $campo->seccionKey = $seccionKey;
                                        $campo->campo = $campoTmp['id'];
                                        $campo->valorLong = $resultado['data']['tokens'][$var] ?? '';
                                        $campo->isFile = 0;
                                        if(!empty($vehiculoIdAgrupadorNodo)) $campo->cotizacionVehiculoId = $vehiculoIdAgrupadorNodo;
                                        $campo->save();
                                    }
                                }
                            }
                        }
                    }
                }

                return $this->ResponseSuccess('Archivo subido con éxito', [
                    'key' => $server_output['data']['s3-url-tmp']
                ]);
            }
            else {
                $errorMsg = 'Error al cargar archivo, por favor intente de nuevo';
                if(!empty($server_output['msg'])) $errorMsg = $server_output['msg'];
                return $this->ResponseError('T-222', $errorMsg);
            }
        }
    }

    public function GetFilePreview(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['tareas/mis-tareas'])) return $AC->NoAccess();

        $token = $request->get('token');
        $seccionKey = $request->get('seccionKey');

        $usuarioLogueado = $usuario = auth('sanctum')->user();
        $cotizacion = Cotizacion::where([['token', '=', $token]])->first();

        if (empty($cotizacion)) {
            return $this->ResponseSuccess('Cotización sin adjuntos');
        }

        $producto = $cotizacion->producto;
        if (empty($producto)) {
            return $this->ResponseError('COT-700', 'Producto no válido');
        }

        $flujo = $producto->flujo->first();
        if (empty($flujo)) {
            return $this->ResponseError('COT-701', 'Flujo no válido');
        }

        $flujoConfig = @json_decode($flujo->flujo_config, true);
        if (!is_array($flujoConfig)) {
            return $this->ResponseError('COT-701', 'Error al interpretar flujo, por favor, contacte a su administrador');
        }

        $camposList = CotizacionDetalle::where('cotizacionId', $cotizacion->id)->where('isFile', 1)->get();

        // Recorro campos para hacer resumen
        $campos = [];

        foreach ($camposList as $campo) {

            $tmpPath = '';
            if (!empty($campo->valorLong)) {
                $temporarySignedUrl = $campo->valorLong;

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $temporarySignedUrl);
                curl_setopt($ch, CURLOPT_HEADER, TRUE);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                $a = curl_exec($ch);
                if(preg_match('#Location: (.*)#', $a, $r)) {
                    $tmpPath = trim($r[1]);
                    $tmpPath = parse_url($tmpPath);
                }

                $dataPDF = '';

                $type = '';
                $ext = pathinfo($tmpPath['path'] ?? '', PATHINFO_EXTENSION);
                //dd($ext);

                if ($ext == 'jpg' || $ext == 'jpeg' || $ext == 'png' || $ext == 'tiff' || $ext == 'gif') {
                    $type = 'image';
                }
                else if ($ext == 'pdf') {

                    $arrContextOptions=array(
                        "ssl"=>array(
                            "verify_peer"=>false,
                            "verify_peer_name"=>false,
                        ),
                    );
                    $response = file_get_contents($temporarySignedUrl, false, stream_context_create($arrContextOptions));
                    $type = 'pdf';
                    $dataPDF = 'data:application/pdf;base64,' . base64_encode($response);
                }

                $campos[$campo['id']] = [
                    'label' => $campo->label ?? 'Sin etiqueta',
                    'name' => $campo->label ?? 'Sin nombre',
                    'valor' => $campo->valorLong,
                    'url' => ($AC->CheckAccess(['adj/force/proxy'])) ? $this->MakeProxyLink($temporarySignedUrl) : $temporarySignedUrl,
                    'type' => $type,
                    'salida' => false,
                    'basePDF' => $dataPDF,
                ];
            }

        }

        // Salidas
        foreach ($camposList as $campo) {
            if ($campo->fromSalida) {

                // dd($campo);

                if (!empty($campo['valorLong'])) {

                    //$temporarySignedUrl = Storage::disk('s3')->temporaryUrl($campo['valorLong'], now()->addMinutes(10));
                    $temporarySignedUrl = $campo['valorLong'];

                    //$ext = pathinfo($campo['valorLong'], PATHINFO_EXTENSION);
                    $ext = 'pdf';

                    $arrContextOptions=array(
                        "ssl"=>array(
                            "verify_peer"=>false,
                            "verify_peer_name"=>false,
                        ),
                    );
                    $response = file_get_contents($temporarySignedUrl, false, stream_context_create($arrContextOptions));
                    $dataPDF = 'data:application/pdf;base64,' . base64_encode($response);

                    $campos[$campo['id']] = [
                        'label' => $campo['label'],
                        'name' => $campo['nombre'],
                        'valor' => $campo['valorLong'],
                        'url' => ($AC->CheckAccess(['adj/force/proxy'])) ? $this->MakeProxyLink($temporarySignedUrl) : $temporarySignedUrl,
                        'type' => $ext,
                        'salida' => $campo['fromSalida'],
                        'basePDF' => $dataPDF,
                    ];
                }
            }
        }

        return $this->ResponseSuccess('Adjuntos actualizados con éxito', $campos);
    }

    public function MakeProxyLink($link) {
        $link = base64_encode($link);
        return env('APP_URL')."/api/tareas/pxfile?f={$link}";
    }

    public function ProxyFile() {
        $url = $_GET['f'];
        $urlFile = base64_decode($url);

        // Obtener el contenido del archivo
        $arrContextOptions=array(
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ),
        );
        $s3_file = file_get_contents($urlFile, false, stream_context_create($arrContextOptions));

        // Escribir el archivo en un directorio temporal
        $fileTmp = uniqid();
        $tmpFilePath = storage_path("tmp/".$fileTmp);
        file_put_contents($tmpFilePath, $s3_file);
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $tmpFilePath);
        finfo_close($finfo);
        $ext = $this->verifyFileExtension($mimeType); // 'bin' si no se reconoce el tipo
        $fileTmpName = "{$fileTmp}.{$ext}";
        if (file_exists($tmpFilePath)) unlink($tmpFilePath);

        header("Content-type: {$mimeType}");
        header("Content-Disposition: inline; filename=$fileTmpName");
        header('Content-Transfer-Encoding: binary');
        header("Cache-Control: private, must-revalidate, post-check=0, pre-check=0, public");
        header("Pragma: public");
        header("Accept-Ranges: bytes");
        echo file_get_contents($urlFile);
        die;
    }

    public function GetProgression(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['tareas/mis-tareas'])) return $AC->NoAccess();

        $usuarioLogueado = $usuario = auth('sanctum')->user();
        $cotizacionId = $request->get('token');

        $cotizacion = Cotizacion::where([['token', '=', $cotizacionId]])->first();

        if (empty($cotizacion)) {
            return $this->ResponseError('COT-632', 'Cotización no válida');
        }

        $flujoConfig = $this->getFlujoFromCotizacion($cotizacion);

        if (!$flujoConfig['status']) {
            return $this->ResponseError($flujoConfig['error-code'], $flujoConfig['msg']);
        }
        else {
            $flujoConfig = $flujoConfig['data'];
        }

        $camposCoti = $cotizacion->campos;

        $arrResponse = [
            'percent' => 0,
            'total' => 0,
            'llenos' => 0,
            'nodos' => [],
        ];

        $totalCampos = 0;
        $totalLlenos = 0;

        // Recorro campos para hacer resumen
        foreach ($flujoConfig['nodes'] as $nodo) {

            $totalCamposN = 0;
            $totalLlenosN = 0;

            //$resumen
            if (!empty($nodo['formulario']['secciones']) && count($nodo['formulario']['secciones']) > 0) {

                foreach ($nodo['formulario']['secciones'] as $keySeccion => $seccion) {

                    $totalCamposS = 0;
                    $totalLlenosS = 0;

                    foreach ($seccion['campos'] as $keyCampo => $campo) {
                        $totalCamposN++;
                        $totalCamposS++;
                        $totalCampos++;

                        $campoTmp = $camposCoti->where('campo', $campo['id'])->first();

                        if (!empty($campoTmp->valorLong)) {
                            $totalLlenosN++;
                            $totalLlenosS++;
                            $totalLlenos++;
                        }
                    }

                    if ($totalCamposS > 0) {
                        $arrResponse['nodos'][$nodo['id']]['secciones'][$keySeccion]['nombre'] = $seccion['nombre'];
                        $arrResponse['nodos'][$nodo['id']]['secciones'][$keySeccion]['percent'] = number_format(($totalLlenosS * 100) / $totalCamposS, 2);
                        $arrResponse['nodos'][$nodo['id']]['secciones'][$keySeccion]['total'] = $totalCamposS;
                        $arrResponse['nodos'][$nodo['id']]['secciones'][$keySeccion]['llenos'] = $totalLlenosS;
                    }
                }
            }

            if ($totalCamposN) {
                $arrResponse['nodos'][$nodo['id']]['info']['nombre'] = $nodo['label'];
                $arrResponse['nodos'][$nodo['id']]['info']['percent'] = number_format(($totalLlenosN * 100) / $totalCamposN, 2);
                $arrResponse['nodos'][$nodo['id']]['info']['total'] = $totalCamposN;
                $arrResponse['nodos'][$nodo['id']]['info']['llenos'] = $totalLlenosN;
            }
        }

        if ($totalCampos) {
            $arrResponse['total'] = $totalCampos;
            $arrResponse['percent'] = number_format(($totalLlenos * 100) / $totalCampos, 2);
        }

        return $this->ResponseSuccess('Preview configurada con éxito', $arrResponse);
    }

    public function CalcularCampos(Request $request) {

        $campos = $request->get('campos');

        // dd($campos);

        $flujo = $producto->flujo->first();
        if (empty($flujo)) {
            return $this->ResponseError('COT-601', 'Flujo no válido');
        }

        $flujoConfig = @json_decode($flujo->flujo_config, true);
        if (!is_array($flujoConfig)) {
            return $this->ResponseError('COT-601', 'Error al interpretar flujo, por favor, contacte a su administrador');
        }

        $camposCoti = $cotizacion->campos;

        $arrResponse = [
            'percent' => 0,
            'total' => 0,
            'llenos' => 0,
            'nodos' => [],
        ];

        $totalCampos = 0;
        $totalLlenos = 0;

        // Recorro campos para hacer resumen
        foreach ($flujoConfig['nodes'] as $nodo) {

            $totalCamposN = 0;
            $totalLlenosN = 0;

            //$resumen
            if (!empty($nodo['formulario']['secciones']) && count($nodo['formulario']['secciones']) > 0) {

                foreach ($nodo['formulario']['secciones'] as $keySeccion => $seccion) {

                    $totalCamposS = 0;
                    $totalLlenosS = 0;

                    foreach ($seccion['campos'] as $keyCampo => $campo) {
                        $totalCamposN++;
                        $totalCamposS++;
                        $totalCampos++;

                        $campoTmp = $camposCoti->where('campo', $campo['id'])->first();

                        if (!empty($campoTmp->valorLong)) {
                            $totalLlenosN++;
                            $totalLlenosS++;
                            $totalLlenos++;
                        }
                    }

                    if ($totalCamposS > 0) {
                        $arrResponse['nodos'][$nodo['id']]['secciones'][$keySeccion]['nombre'] = $seccion['nombre'];
                        $arrResponse['nodos'][$nodo['id']]['secciones'][$keySeccion]['percent'] = number_format(($totalLlenosS * 100) / $totalCamposS, 2);
                        $arrResponse['nodos'][$nodo['id']]['secciones'][$keySeccion]['total'] = $totalCamposS;
                        $arrResponse['nodos'][$nodo['id']]['secciones'][$keySeccion]['llenos'] = $totalLlenosS;
                    }
                }
            }

            if ($totalCamposN) {
                $arrResponse['nodos'][$nodo['id']]['info']['nombre'] = $nodo['label'];
                $arrResponse['nodos'][$nodo['id']]['info']['percent'] = number_format(($totalLlenosN * 100) / $totalCamposN, 2);
                $arrResponse['nodos'][$nodo['id']]['info']['total'] = $totalCamposN;
                $arrResponse['nodos'][$nodo['id']]['info']['llenos'] = $totalLlenosN;
            }
        }

        if ($totalCampos) {
            $arrResponse['total'] = $totalCampos;
            $arrResponse['percent'] = number_format(($totalLlenos * 100) / $totalCampos, 2);
        }

        return $this->ResponseSuccess('Preview configurada con éxito', $arrResponse);
    }

    public function GetCatalogo(Request $request) {

        $campos = $request->get('campos');

        // dd($campos);

        $flujo = $producto->flujo->first();
        if (empty($flujo)) {
            return $this->ResponseError('COT-601', 'Flujo no válido');
        }

        $flujoConfig = @json_decode($flujo->flujo_config, true);
        if (!is_array($flujoConfig)) {
            return $this->ResponseError('COT-601', 'Error al interpretar flujo, por favor, contacte a su administrador');
        }

        $camposCoti = $cotizacion->campos;

        $arrResponse = [
            'percent' => 0,
            'total' => 0,
            'llenos' => 0,
            'nodos' => [],
        ];

        $totalCampos = 0;
        $totalLlenos = 0;

        // Recorro campos para hacer resumen
        foreach ($flujoConfig['nodes'] as $nodo) {

            $totalCamposN = 0;
            $totalLlenosN = 0;

            //$resumen
            if (!empty($nodo['formulario']['secciones']) && count($nodo['formulario']['secciones']) > 0) {

                foreach ($nodo['formulario']['secciones'] as $keySeccion => $seccion) {

                    $totalCamposS = 0;
                    $totalLlenosS = 0;

                    foreach ($seccion['campos'] as $keyCampo => $campo) {
                        $totalCamposN++;
                        $totalCamposS++;
                        $totalCampos++;

                        $campoTmp = $camposCoti->where('campo', $campo['id'])->first();

                        if (!empty($campoTmp->valorLong)) {
                            $totalLlenosN++;
                            $totalLlenosS++;
                            $totalLlenos++;
                        }
                    }

                    if ($totalCamposS > 0) {
                        $arrResponse['nodos'][$nodo['id']]['secciones'][$keySeccion]['nombre'] = $seccion['nombre'];
                        $arrResponse['nodos'][$nodo['id']]['secciones'][$keySeccion]['percent'] = number_format(($totalLlenosS * 100) / $totalCamposS, 2);
                        $arrResponse['nodos'][$nodo['id']]['secciones'][$keySeccion]['total'] = $totalCamposS;
                        $arrResponse['nodos'][$nodo['id']]['secciones'][$keySeccion]['llenos'] = $totalLlenosS;
                    }
                }
            }

            if ($totalCamposN) {
                $arrResponse['nodos'][$nodo['id']]['info']['nombre'] = $nodo['label'];
                $arrResponse['nodos'][$nodo['id']]['info']['percent'] = number_format(($totalLlenosN * 100) / $totalCamposN, 2);
                $arrResponse['nodos'][$nodo['id']]['info']['total'] = $totalCamposN;
                $arrResponse['nodos'][$nodo['id']]['info']['llenos'] = $totalLlenosN;
            }
        }

        if ($totalCampos) {
            $arrResponse['total'] = $totalCampos;
            $arrResponse['percent'] = number_format(($totalLlenos * 100) / $totalCampos, 2);
        }

        return $this->ResponseSuccess('Preview configurada con éxito', $arrResponse);
    }


    // plantillas pdf
    public function uploadPdfTemplate(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['admin/plantillas-pdf'])) return $AC->NoAccess();

        $archivo = $request->file('file');
        $id = $request->get('id');
        $nombre = $request->get('nombre');
        $activo = $request->get('activo');

        $item = PdfTemplate::where('id', $id)->first();
        $fileNameHash = md5(uniqid());

        if (empty($item)) {
            $item = new PdfTemplate();
        }
        else{
            $pattern = '/tpl_([a-f\d]+)\.docx/i';
            if (preg_match($pattern, $item->urlTemplate, $matches) && !ctype_digit($matches[1])) {
                $fileNameHash = $matches[1];
            }
        }
        $item->id = $id;
        $item->nombre = $nombre;
        $item->activo = intval($activo);
        $item->save();

        if (!empty($archivo)) {
            $disk = Storage::disk('s3');
            $path = $disk->putFileAs("/system-templates", $archivo, "tpl_{$fileNameHash}.docx");

            if (empty($path)) {
                return $this->ResponseError('TPL-6254', 'Error al subir plantilla');
            }

            $item->urlTemplate = $path;
            $item->save();
        }

        return $this->ResponseSuccess('Plantilla guardada con éxito', ['id' => $item->id]);
    }

    public function getPdfTemplateList(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['admin/plantillas-pdf'])) return $AC->NoAccess();

        $item = PdfTemplate::all();

        return $this->ResponseSuccess('Plantillas obtenidas con éxito', $item);
    }

    public function getPdfTemplate(Request $request, $id) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['admin/plantillas-pdf'])) return $AC->NoAccess();
        $item = PdfTemplate::where('id', $id)->first();

        if (empty($item)) {
            return $this->ResponseError('TPL-145', 'Error al obtener plantilla');
        }

        $item->urlShow = (!empty($item->urlTemplate)) ? Storage::disk('s3')->temporaryUrl($item->urlTemplate, now()->addMinutes(30)) : false;

        return $this->ResponseSuccess('Plantilla obtenida con éxito', $item);
    }

    public function deletePdfTemplate(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['admin/plantillas-pdf'])) return $AC->NoAccess();

        $id = $request->get('id');
        $item = PdfTemplate::where('id', $id)->first();

        if (empty($item)) {
            return $this->ResponseError('TPL-145', 'Plantilla inválida');
        }

        $item->delete();

        return $this->ResponseSuccess('Plantilla eliminada con éxito', $item);
    }

    // Comentarios
    public function CrearComentario(Request $request) {

        $AC = new AuthController();
        //if (!$AC->CheckAccess(['users/role/admin'])) return $AC->NoAccess();

        $token = $request->get('token');
        $comment = $request->get('comment');
        $comentarioAcceso = $request->get('comentarioAcceso');
        $usuarioLogueado = auth('sanctum')->user();
        $usuarioLogueadoId = (!empty($usuarioLogueado) ? $usuarioLogueado->id : 0);
        $cotizacion = Cotizacion::where([['token', '=', $token]])->first();

        if (empty($cotizacion)) {
            return $this->ResponseError('CM-002', 'Cotización inválida');
        }

        if (!empty($comment)) {
            $commentario = new CotizacionComentario();
            $commentario->cotizacionId = $cotizacion->id;
            $commentario->userId = $usuarioLogueadoId;
            $commentario->comentario = strip_tags($comment);
            $commentario->acceso = $comentarioAcceso;
            $commentario->deleted = null;
            $commentario->save();

            return $this->ResponseSuccess('Comentario enviado con éxito');
        }
        else {
            return $this->ResponseError('CM-003A', 'El comentario no puede estar vacío');
        }
    }

    public function GetComentarios(Request $request) {

        $AC = new AuthController();
        //if (!$AC->CheckAccess(['users/role/admin'])) return $AC->NoAccess();

        $token = $request->get('token');
        $usuarioLogueado = auth('sanctum')->user();
        $usuarioLogueadoId = (!empty($usuarioLogueado) ? $usuarioLogueado->id : 0);

        $cotizacion = Cotizacion::where([['token', '=', $token]])->first();

        if (empty($cotizacion)) {
            return $this->ResponseError('CM-001', 'Cotización inválida');
        }

        $arrResult = [];

        $comentariosTmp = CotizacionComentario::where([['cotizacionId', '=', $cotizacion->id], ['deleted', '=', null]]);

        if(!$usuarioLogueadoId) {
            $comentariosTmp->where('acceso', 'publico');
        }

        $comentarios = $comentariosTmp->get();

        foreach ($comentarios as $comment) {
            $arrResult[$comment->id]['date'] = Carbon::parse($comment->createdAt)->format('d/m/Y H:i');
            $arrResult[$comment->id]['usuario'] = $arrResult[$comment->id]['date'].' - '.($usuarioLogueadoId ? ($comment->usuario->name ?? 'Usuario sin nombre') : 'Cliente');
            $arrResult[$comment->id]['comentario'] = $comment->comentario;
            $arrResult[$comment->id]['a'] = $comment->acceso;
        }

        return $this->ResponseSuccess('Comentarios obtenidos con éxito', $arrResult);
    }

    // Soporte
    public function SoporteCrearComentario(Request $request) {

        $AC = new AuthController();
        //if (!$AC->CheckAccess(['users/role/admin'])) return $AC->NoAccess();

        $token = $request->get('token');
        $comment = $request->get('comment');
        $soporteTipoSelected = $request->get('soporteTipoSelected');
        $usuarioLogueado = auth('sanctum')->user();
        $usuarioLogueadoId = (!empty($usuarioLogueado) ? $usuarioLogueado->id : 0);

        $cotizacion = Cotizacion::where([['token', '=', $token]])->first();

        if (empty($cotizacion)) {
            return $this->ResponseError('CM-002', 'Cotización inválida');
        }

        // get vars
        $variablesFlujo = CotizacionDetalle::where('cotizacionId', $cotizacion->id)->get();

        if (!empty($comment)) {

            // envío de ticket a workflow
            $apiKey = env('WK_API').':'.env('WK_KEY');
            $headers = [
                'Authorization: Bearer '.$apiKey,
                'Content-Type:application/json',
            ];
            //dd($arrArchivo);

            // usuario creador
            $usuarioCreador = User::where('id', $cotizacion->usuarioId)->first();

            $arrSend = [
                'token' => '',
                'flujo' => $soporteTipoSelected ?? env('WK_SOPORTE_FLOW'),
                'campos' => [
                    'comentario_solicitud' => [
                        't' => 'text',
                        'v' => $comment,
                    ],
                    'auto_usuario_id' => [
                        't' => 'text',
                        'v' => $usuarioLogueadoId,
                    ],
                    'auto_usuario_email' => [
                        't' => 'text',
                        'v' => $usuarioCreador->email ?? '',
                    ],
                    'auto_token_tarea' => [
                        't' => 'text',
                        'v' => $cotizacion->token,
                    ],
                    'Cod_Agente' => [
                        't' => 'text',
                        'v' => $variablesFlujo->where('campo', 'CODIGO_AGENTE')->first()->valorLong ?? '',
                    ],
                    'corr_de_Solciitud' => [
                        't' => 'text',
                        'v' => $variablesFlujo->where('campo', 'correo_electronico')->first()->valorLong ?? '',
                    ],
                    'Desc_Siniestr' => [
                        't' => 'text',
                        'v' => $comment,
                    ],
                    'val_recargo_orig' => [
                        't' => 'text',
                        'v' => $variablesFlujo->where('campo', 'SYS_COT_REC')->first()->valorLong ?? '',
                    ],
                ],
            ];

            $link = env('WK_URL') . "/tareas/operacion";
            $tmpEnviado = json_encode($arrSend);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $link);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $tmpEnviado);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $server_output = curl_exec($ch);
            $server_output = @json_decode($server_output, true);
            $error_msg = '';
            if (curl_errno($ch)) {
                $error_msg = curl_error($ch);
            }
            curl_close($ch);

            $tmpRecibido = print_r($server_output, true);
            $tmpHeaders = print_r($headers, true);

            $bitacoraCoti = new CotizacionBitacora();
            $bitacoraCoti->cotizacionId = $cotizacion->id;
            $bitacoraCoti->usuarioId = $usuarioLogueadoId;
            $bitacoraCoti->onlyPruebas = 1;
            $bitacoraCoti->dataInfo = "<b>Link:</b>{$link}, <b>Headers:</b>{$tmpHeaders}<b>, Enviado:</b> {$tmpEnviado}, <b>Recibido:</b> {$tmpRecibido}, <b>Error:</b>{$error_msg}";
            $bitacoraCoti->log = "Enviado servicio Workflow";
            $bitacoraCoti->save();

            //var_dump($server_output);
            if (!empty($server_output['status']) && !empty($server_output['data']['id'])) {

                $ticket = new CotizacionSoporte();
                $ticket->cotizacionId = $cotizacion->id;
                $ticket->userId = $usuarioLogueadoId;
                $ticket->comentario = strip_tags($comment);
                $ticket->workflowFlujoId = $server_output['data']['id'] ?? 0;
                $ticket->workflowFlujoToken = $server_output['data']['token'] ?? 0;
                $ticket->link = env('WK_URL_WINDOW').'#/solicitar/producto/'.env('WK_SOPORTE_FLOW')."/".$ticket->workflowFlujoToken;
                $ticket->deleted = null;
                $ticket->save();

                return $this->ResponseSuccess('Ticket creado con éxito');
            }
            else {
                return $this->ResponseError('CM-003', 'Error al crear ticket, por favor intente de nuevo');
            }
        }
        else {
            return $this->ResponseError('CM-003', 'El comentario no puede estar vacío');
        }
    }

    public function SoporteCrearRetroalimentacion(Request $request) {

        $AC = new AuthController();
        //if (!$AC->CheckAccess(['users/role/admin'])) return $AC->NoAccess();

        $token = $request->get('workflowToken');
        $comment = $request->get('comment');

        $cotizacionSop = CotizacionSoporte::where([['workflowFlujoToken', '=', $token]])->first();

        if (empty($cotizacionSop)) {
            return $this->ResponseError('CM-00fA2', 'Flujo no existe en Gestor de automóvil');
        }
        else {
            $ticket = new CotizacionSoporte();
            $ticket->cotizacionId = $cotizacionSop->cotizacionId;
            $ticket->userId = null;
            $ticket->comentario = strip_tags($comment);
            $ticket->workflowFlujoId = $cotizacionSop->workflowFlujoId;
            $ticket->workflowFlujoToken = $token;
            $ticket->link = env('WK_URL_WINDOW').'#/solicitar/producto/'.env('WK_SOPORTE_FLOW')."/".$ticket->workflowFlujoToken;
            $ticket->deleted = null;
            $ticket->wkResponse = 1;
            $ticket->save();
        }

        return $this->ResponseSuccess('Grabado con éxito');
    }

    public function SoporteGetComentarios(Request $request) {

        $AC = new AuthController();
        //if (!$AC->CheckAccess(['users/role/admin'])) return $AC->NoAccess();

        $token = $request->get('token');
        $usuarioLogueado = auth('sanctum')->user();
        $usuarioLogueadoId = (!empty($usuarioLogueado) ? $usuarioLogueado->id : 0);

        $cotizacion = Cotizacion::where([['token', '=', $token]])->first();

        if (empty($cotizacion)) {
            return $this->ResponseError('CM-001', 'Cotización inválida');
        }

        $arrResult = [];

        $comentariosTmp = CotizacionSoporte::where([['cotizacionId', '=', $cotizacion->id], ['deleted', '=', null]])->orderBy('createdAt', 'ASC')->orderBy('workflowFlujoToken', 'DESC');

        $comentarios = $comentariosTmp->get();

        foreach ($comentarios as $comment) {
            $arrItem = [];
            $arrItem['date'] = Carbon::parse($comment->createdAt)->format('d/m/Y H:i');
            $arrItem['usuario'] = (!empty($comment->wkResponse)? 'Agente de soporte' : ($arrItem['date'].' - '.($usuarioLogueadoId ? ($comment->usuario->name ?? 'Usuario sin nombre') : 'N/D')));
            $arrItem['comentario'] = $comment->comentario;
            $arrItem['wkId'] = $comment->workflowFlujoId;
            $arrItem['wkT'] = $comment->workflowFlujoToken;
            $arrItem['link'] = $comment->link;
            $arrItem['isR'] = $comment->wkResponse;
            $arrResult[] = $arrItem;
        }

        return $this->ResponseSuccess('Soporte obtenido con éxito', $arrResult);
    }

    public function VarTest(Request $request) {

        $AC = new AuthController();
        //if (!$AC->CheckAccess(['users/role/admin'])) return $AC->NoAccess();

        $token = $request->get('token');
        $usuarioLogueado = auth('sanctum')->user();
        $usuarioLogueadoId = (!empty($usuarioLogueado) ? $usuarioLogueado->id : 0);

        $cotizacion = Cotizacion::where([['token', '=', $token]])->first();
        $detalle = CotizacionDetalle::where('cotizacionId', $cotizacion->id)->orderBy('campo')->get();

        $vars = [];
        foreach ($detalle as $item) {
            $vars[$item->campo] = $item->valorLong;
        }

        return $this->ResponseSuccess('Variables obtenidas con éxito', $vars);
    }

    public function SoporteDetalleComentario(Request $request) {

        $AC = new AuthController();
        //if (!$AC->CheckAccess(['users/role/admin'])) return $AC->NoAccess();

        $token = $request->get('token');
        $usuarioLogueado = auth('sanctum')->user();
        $usuarioLogueadoId = (!empty($usuarioLogueado) ? $usuarioLogueado->id : 0);

        // envío de ticket a workflow
        $apiKey = env('WK_API').':'.env('WK_KEY');
        $headers = [
            'Authorization: Bearer '.$apiKey,
            'Content-Type:application/json',
        ];
        //dd($arrArchivo);

        $arrSend = [
            'token' => $token,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, env('WK_URL') . "/tareas/get-resumen");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($arrSend));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $server_output = curl_exec($ch);

        $server_output = @json_decode($server_output, true);
        curl_close($ch);

        if (!empty($server_output['status'])) {
            return $this->ResponseSuccess('Detalle obtenido con éxito', $server_output);
        }
        else {
            return $this->ResponseError('CM-003', 'Error al consultar detalle de ticket, por favor intente de nuevo');
        }
    }

    // reenvío
    public function reenviarSalida(Request $request) {

        $token = $request->get('token');
        $tipo = $request->get('tipo');
        $cotizacionId = $request->get('token');
        $cotizacion = Cotizacion::where([['token', '=', $cotizacionId]])->first();
        $newEmailReenvio = $request->get('newEmailReenvio');
        $newWspReenvio = $request->get('newWspReenvio');

        $usuarioLogueado = auth('sanctum')->user();
        $usuarioLogueadoId = ($usuarioLogueado) ? $usuarioLogueado->id : 0;

        if (!empty($usuarioLogueadoId)) {
            $AC = new AuthController();
            if (!$AC->CheckAccess(['tareas/admin/cambio-paso'])) return $AC->NoAccess();
        }

        $item = Cotizacion::where([['token', '=', $token]])->first();

        $flujo = $this->CalcularPasos($request, true, false, false);

        $camposAll = $item->campos->toArray();
        if (!empty($flujo['actual']['salidaIsWhatsapp']) && $tipo === 'whatsapp') {
            $whatsappToken = $flujo['actual']['procesoWhatsapp']['token'] ?? '';
            $whatsappUrl = $flujo['actual']['procesoWhatsapp']['url'] ?? '';
            $whatsappAttachments = $flujo['actual']['procesoWhatsapp']['attachments'] ?? '';

            $whatsappData = (!empty($flujo['actual']['procesoWhatsapp']['data'])) ? $this->reemplazarValoresSalida($camposAll, $flujo['actual']['procesoWhatsapp']['data']) : false;

            // chapus para yalo
            $tmpData = json_decode($whatsappData, true);
            if (isset($tmpData['users'][0]['params']['document']['link'])) {
                $tmpData['users'][0]['params']['document']['link'] = $this->getWhatsappUrl($tmpData['users'][0]['params']['document']['link']);
                $whatsappData = json_encode($tmpData, JSON_UNESCAPED_SLASHES);
            }

            $headers = [
                'Authorization: Bearer ' . $whatsappToken ?? '',
                'Content-Type: application/json',
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $whatsappUrl ?? '');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $whatsappData);  //Post Fields
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $server_output = curl_exec($ch);
            $yaloTmp = $server_output;
            $server_output = @json_decode($server_output, true);
            // dd($server_output);
            curl_close($ch);

            $bitacoraCoti = new CotizacionBitacora();
            $bitacoraCoti->cotizacionId = $item->id;
            $bitacoraCoti->usuarioId = $usuarioLogueadoId;
            $bitacoraCoti->onlyPruebas = 1;
            $bitacoraCoti->dataInfo = "<b>Enviado:</b> {$whatsappData}, <b>Recibido:</b> {$yaloTmp}";
            $bitacoraCoti->log = "Enviado Whatsapp";
            $bitacoraCoti->save();

            if (empty($server_output['success'])) {
                // Guardo la bitácora
                $bitacoraCoti = new CotizacionBitacora();
                $bitacoraCoti->cotizacionId = $item->id;
                $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                $bitacoraCoti->onlyPruebas = 1;
                $bitacoraCoti->log = "Error al enviar WhatsApp";
                $bitacoraCoti->save();
            }
            else {
                $bitacoraCoti = new CotizacionBitacora();
                $bitacoraCoti->cotizacionId = $item->id;
                $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                $bitacoraCoti->log = "Enviado WhatsApp con éxito";
                $bitacoraCoti->save();
            }
        }

        if (!empty($flujo['actual']['salidaIsEmail']) && $tipo === 'email') {

            // dd($flujo['actual']);

            $destino = (!empty($flujo['actual']['procesoEmail']['destino'])) ? $this->reemplazarValoresSalida($camposAll, $flujo['actual']['procesoEmail']['destino']) : false;
            $asunto = (!empty($flujo['actual']['procesoEmail']['asunto'])) ? $this->reemplazarValoresSalida($camposAll, $flujo['actual']['procesoEmail']['asunto']) : false;
            $config = $flujo['actual']['procesoEmail']['mailgun'] ?? [];

            // reemplazo plantilla
            $contenido = $flujo['actual']['procesoEmail']['salidasEmail'];
            $contenido = $this->reemplazarValoresSalida($camposAll, $contenido);

            $attachments = $flujo['actual']['procesoEmail']['attachments'] ?? false;

            $attachmentsSend = [];
            if ($attachments) {
                $attachments = explode(',', $attachments);

                foreach ($attachments as $attach) {
                    $campoTmp = CotizacionDetalle::where('campo', $attach)->where('cotizacionId', $item->id)->first();

                    if (!empty($campoTmp)){
                        $ext = pathinfo($campoTmp['valorLong'] ?? '', PATHINFO_EXTENSION);
                        $s3_file = Storage::disk('s3')->get($campoTmp['valorLong']);
                        $attachmentsSend[] = ['fileContent'=>$s3_file, 'filename'=>($campoTmp['label'] ?? 'Sin nombre').'.'.$ext];
                    }
                }
            }

            $config['domain'] = $config['domain'] ?? 'N/D';
            if(!empty($newEmailReenvio)) $destino = $newEmailReenvio;
            try {
                $mg = Mailgun::create($config['apiKey'] ?? ''); // For US servers
                $email = $mg->messages()->send($config['domain'] ?? '', [
                    'from'    => $config['from'] ?? '',
                    'to'      => $destino ?? '',
                    'subject' => $asunto ?? '',
                    'html'    => $contenido,
                    'attachment' => $attachmentsSend
                ]);

                // Guardo la bitácora
                $bitacoraCoti = new CotizacionBitacora();
                $bitacoraCoti->cotizacionId = $item->id;
                $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                $bitacoraCoti->log = "Enviado correo electrónico \"{$destino}\" desde \"{$config['from']}\"";
                $bitacoraCoti->save();
                // return $this->ResponseSuccess( 'Si tu cuenta existe, llegará un enlace de recuperación');
            }
            catch (HttpClientException $e) {
                // Guardo la bitácora
                $bitacoraCoti = new CotizacionBitacora();
                $bitacoraCoti->cotizacionId = $item->id;
                $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                $bitacoraCoti->log = "Error al enviar correo electrónico \"{$destino}\" desde \"{$config['from']}\", dominio de salida: {$config['domain']}";
                $bitacoraCoti->save();
                // return $this->ResponseError('AUTH-RA94', 'Error al enviar notificación, verifique el correo o la configuración del sistema');
            }
        }

        return $this->ResponseSuccess('Reenvío solicitado con éxito');

    }

    public function reenviarAdjuntos(Request $request) {

        $token = $request->get('token');
        $tipo = $request->get('tipo');
        $cotizacionId = $request->get('token');
        $cotizacion = Cotizacion::where([['token', '=', $cotizacionId]])->first();
        $newEmailReenvio = $request->get('newEmailReenvio');
        $newWspReenvio = $request->get('newWspReenvio');
        $attachments = $request->get('attachments');

        $usuarioLogueado = auth('sanctum')->user();
        $usuarioLogueadoId = ($usuarioLogueado) ? $usuarioLogueado->id : 0;

        if (!empty($usuarioLogueadoId)) {
            $AC = new AuthController();
            if (!$AC->CheckAccess(['tareas/admin/cambio-paso'])) return $AC->NoAccess();
        }

        $item = Cotizacion::where([['token', '=', $token]])->first();

        $wsp = [
            'token' => '',
            'url' => '',
            'attachments' => '',
            'data' => '',
        ];

        if ($tipo === 'whatsapp') {
            $whatsappToken = $wsp['token'] ?? '';
            $whatsappUrl = $wsp['url'] ?? '';
            $whatsappAttachments = $wsp['attachments'] ?? '';

            $whatsappData = (!empty($wsp['data'])) ? $this->reemplazarValoresSalida($item->campos->toArray(), $wsp['data']) : false;

            // chapus para yalo
            $tmpData = json_decode($whatsappData, true);
            if (isset($tmpData['users'][0]['params']['document']['link'])) {
                $tmpData['users'][0]['params']['document']['link'] = $this->getWhatsappUrl($tmpData['users'][0]['params']['document']['link']);
                $whatsappData = json_encode($tmpData, JSON_UNESCAPED_SLASHES);
            }

            $headers = [
                'Authorization: Bearer ' . $whatsappToken ?? '',
                'Content-Type: application/json',
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $whatsappUrl ?? '');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $whatsappData);  //Post Fields
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $server_output = curl_exec($ch);
            $yaloTmp = $server_output;
            $server_output = @json_decode($server_output, true);
            // dd($server_output);
            curl_close($ch);

            $bitacoraCoti = new CotizacionBitacora();
            $bitacoraCoti->cotizacionId = $item->id;
            $bitacoraCoti->usuarioId = $usuarioLogueadoId;
            $bitacoraCoti->onlyPruebas = 1;
            $bitacoraCoti->dataInfo = "<b>Enviado:</b> {$whatsappData}, <b>Recibido:</b> {$yaloTmp}";
            $bitacoraCoti->log = "Enviado Whatsapp";
            $bitacoraCoti->save();

            if (empty($server_output['success'])) {
                // Guardo la bitácora
                $bitacoraCoti = new CotizacionBitacora();
                $bitacoraCoti->cotizacionId = $item->id;
                $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                $bitacoraCoti->onlyPruebas = 1;
                $bitacoraCoti->log = "Error al enviar WhatsApp";
                $bitacoraCoti->save();
            }
            else {
                $bitacoraCoti = new CotizacionBitacora();
                $bitacoraCoti->cotizacionId = $item->id;
                $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                $bitacoraCoti->log = "Enviado WhatsApp con éxito";
                $bitacoraCoti->save();
            }
        }

        if ($tipo === 'email') {

            // dd($flujo['actual']);
            $asunto = SistemaVariable::where('slug', 'REENVIO_ADJUNTOS_ASUNTO')->first();
            $contenido = SistemaVariable::where('slug', 'REENVIO_ADJUNTOS_CONTENIDO')->first();

            $asunto = (!empty($asunto->contenido)) ? $asunto->contenido : 'GESTOR AUTO';
            $contenido = (!empty($contenido->contenido)) ? $contenido->contenido : 'Estimado cliente, enviamos la documentación requerida';

            $data = $item->campos->all();
            $asunto = $this->reemplazarValoresSalida($data, $asunto);
            $contenido = $this->reemplazarValoresSalida($data, $contenido);


            $destino = false;
            $config = [
                'apiKey' => env('MAILGUN_APIKEY'),
                'domain' => env('MAILGUN_DOMINIO'),
                'from' => env('MAILGUN_FROM'),
            ];

            $attachmentsSend = [];
            if (!empty($attachments) && count($attachments) > 0) {
                foreach ($attachments as $attach) {
                    $campoTmp = CotizacionDetalle::where('id', $attach)->where('cotizacionId', $item->id)->first();
                    if (!empty($campoTmp)){
                        if ($campoTmp->tipo === 'fileER') {
                            $temporarySignedUrl = $campoTmp['valorLong'];
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $temporarySignedUrl);
                            curl_setopt($ch, CURLOPT_HEADER, TRUE);
                            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                            $a = curl_exec($ch);
                            if(preg_match('#Location: (.*)#', $a, $r)) {
                                $tmpPath = trim($r[1]);
                                $tmpPath = parse_url($tmpPath);
                            }
                        }
                        else {
                            $temporarySignedUrl = Storage::disk('s3')->temporaryUrl($campoTmp['valorLong'], now()->addMinutes(10));
                            $tmpPath = parse_url($temporarySignedUrl);
                        }

                        $dataPDF = '';

                        $type = '';
                        $ext = pathinfo($tmpPath['path'] ?? '', PATHINFO_EXTENSION);
                        $s3_file = Storage::disk('s3')->get($campoTmp['valorLong']);
                        if(empty($s3_file)) $s3_file = file_get_contents($campoTmp['valorLong']);
                        //dd($ext);
                        $attachmentsSend[] = ['fileContent'=>$s3_file, 'filename'=>($campoTmp['label'] ?? 'Sin nombre').'.'.$ext];
                    }
                }
            }

            $config['domain'] = $config['domain'] ?? 'N/D';
            if(!empty($newEmailReenvio)) $destino = $newEmailReenvio;
            try {
                $mg = Mailgun::create($config['apiKey'] ?? ''); // For US servers
                $send =  [
                    'from'    => $config['from'] ?? '',
                    'to'      => $destino ?? '',
                    'subject' => $asunto ?? '',
                    'html'    => $contenido,
                    'attachment' => $attachmentsSend
                ];

                $email = $mg->messages()->send($config['domain'] ?? '', $send);

                // Guardo la bitácora
                $bitacoraCoti = new CotizacionBitacora();
                $bitacoraCoti->cotizacionId = $item->id;
                $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                $bitacoraCoti->log = "Enviado correo electrónico \"{$destino}\" desde \"{$config['from']}\"";
                $bitacoraCoti->save();
                // return $this->ResponseSuccess( 'Si tu cuenta existe, llegará un enlace de recuperación');
            }
            catch (HttpClientException $e) {
                // Guardo la bitácora
                $bitacoraCoti = new CotizacionBitacora();
                $bitacoraCoti->cotizacionId = $item->id;
                $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                $bitacoraCoti->log = "Error al enviar correo electrónico \"{$destino}\" desde \"{$config['from']}\", dominio de salida: {$config['domain']}";
                $bitacoraCoti->save();
                // return $this->ResponseError('AUTH-RA94', 'Error al enviar notificación, verifique el correo o la configuración del sistema');
            }
        }

        return $this->ResponseSuccess('Reenvío solicitado con éxito');

    }

    public function saveFieldOnBlur(Request $request){
        $valor = $request->get('campo'); // solo seria un campo
        $token = $request->get('token');
        $seccionKey = $request->get('seccionKey');
        $campoKey = $request->get('campoKey');
        $vehiculoIdAgrupadorNodo = $request->get('vehiculoIdAgrupadorNodo');
        $showInReports = $request->get('showInReports');
        $nombre = $request->get('nombre');

        $usuarioLogueado = auth('sanctum')->user();
        $usuarioLogueadoId = ($usuarioLogueado) ? $usuarioLogueado->id : 0;
        if (!empty($usuarioLogueadoId)) {
            $AC = new AuthController();
            if (!$AC->CheckAccess(['tareas/admin/cambio-paso'])) return $AC->NoAccess();
        }
        $userHandler = new AuthController();

        $item = Cotizacion::where([['token', '=', $token]])->first();
        // verificar que campokey exista en el flujo

        if (empty($item)) {
            return $this->ResponseError('COT-015', 'Tarea inválida');
        }

        if ($item->siniesBlock === 1) {
            return $this->ResponseError('COT-SINIESPEN', 'La aprobación por parte de soporte aún se encuentra pendiente');
        }

        if ($valor['v'] === '__SKIP__FILE__') return $this->ResponseError('COT-016', 'No se guarda');

            // tipos de archivo que no se guardan
        if (!empty($valor['t']) && ($valor['t'] === 'txtlabel' || $valor['t'] === 'subtitle' || $valor['t'] === 'title')) {
            return $this->ResponseError('COT-016', 'No se guarda');
        }

        if($valor['t'] === 'encrypt'){
            $valor['v'] = $this->encriptar($valor['v']);
        }

        if (!empty($vehiculoIdAgrupadorNodo)) {
            $campo = CotizacionDetalle::where('campo', $campoKey)->where('cotizacionId', $item->id)->where('cotizacionVehiculoId', $vehiculoIdAgrupadorNodo)->first();
        }
        else {
            $campo = CotizacionDetalle::where('campo', $campoKey)->where('cotizacionId', $item->id)->first();
        }

        if (empty($campo)){
            $campo = new CotizacionDetalle();
        }
        $campo->cotizacionId = $item->id;
        $campo->seccionKey = $seccionKey;
        $campo->campo = $campoKey;
        $campo->useForSearch = $showInReports ? 1 : 0;
        $campo->cotizacionVehiculoId = $vehiculoIdAgrupadorNodo ?? null;
        $campo->label = $nombre;

        $campo->tipo = $valor['t'] ?? 'default';

        if ($campo->tipo === 'signature') {
            // solo se guarda la firma si viene en base 64, quiere decir que cambió
            if (str_contains($valor['v'], 'data:image/')) {
                $marcaToken = $item->marca->token ?? false;
                $name = md5(uniqid()) . '.png';
                $dir = "{$marcaToken}/{$item->token}/{$name}";
                $image = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $valor['v']));
                $disk = Storage::disk('s3');
                $path = $disk->put($dir, $image);
                $campo->isFile = 1;
                $campo->valorLong = $dir;
            }
        }
        else if ($campo->tipo === 'dateMask') {
            // solo se guarda la firma si viene en base 64, quiere decir que cambió
            $date = Carbon::parse($valor['v']);
            $campo->valorLong = $date->format('Y-m-d');

            // guarda variable adicional
            $this->saveExtraVar("{$campoKey}_fecha_as", $item->id, $date->format('d-m-Y'), $vehiculoIdAgrupadorNodo ?? null);
            $this->saveExtraVar("{$campoKey}_fecha_D", $item->id, $date->format('d'), $vehiculoIdAgrupadorNodo ?? null);
            $this->saveExtraVar("{$campoKey}_fecha_M", $item->id, $date->format('m'), $vehiculoIdAgrupadorNodo ?? null);
            $this->saveExtraVar("{$campoKey}_fecha_Y", $item->id, $date->format('Y'), $vehiculoIdAgrupadorNodo ?? null);

        }
        else {
            if (is_array($valor['v'])) {
                $campo->valorLong = json_encode($valor['v'], JSON_FORCE_OBJECT);
            }
            else {
                $campo->valorLong = $valor['v'];
            }
        }
        $campo->valorShow = (!empty($valor['vs']) ? $valor['vs'] : null);
        $campo->save();

        return $this->ResponseSuccess('Cambios ejecutados con exito', $campoKey);
    }

    public function saveExtraVar($campo, $cotizacionId, $value, $vehiculoIdAgrupadorNodo = false) {
        $campoTmp = $campo;
        $campoNew = CotizacionDetalle::where('campo', $campoTmp)->where('cotizacionId', $cotizacionId)->first();
        if (empty($campoNew)){
            $campoNew = new CotizacionDetalle();
        }
        $campoNew->cotizacionId = $cotizacionId;
        $campoNew->seccionKey = null;
        $campoNew->campo = $campoTmp;
        $campoNew->cotizacionVehiculoId = $vehiculoIdAgrupadorNodo ?? null;
        $campoNew->tipo = $valor['t'] ?? 'default';
        $campoNew->valorLong = $value;
        $campoNew->save();
    }

    public function addVehiculo(Request $request){
        $vehiculos = $request->get('vehiculos'); // solo seria un campo
        $token = $request->get('token');

        $item = Cotizacion::where([['token', '=', $token]])->first();
        // verificar que campokey exista en el flujo

        if (empty($item)) {
            return $this->ResponseError('COT-015', 'Tarea inválida');
        }

        $cotizacionVehiculo = new CotizacionDetalleVehiculo();
        $cotizacionVehiculo->cotizacionId = $item->id;
        $cotizacionVehiculo->save();


        // se crea una cotización
        $tmpSubCoti = new CotizacionDetalleVehiculoCotizacion();
        $tmpSubCoti->tarifaId = 0;
        $tmpSubCoti->cotizacionId = $item->id;
        $tmpSubCoti->cotizacionDetalleVehiculoId = $cotizacionVehiculo->id;
        $tmpSubCoti->formaPagoId = 0;
        $tmpSubCoti->numeroPagos = 0;
        $tmpSubCoti->descuentoPorcentaje = 0;
        $tmpSubCoti->save();

        return $this->ResponseSuccess('Vehículo agregado con éxito', $cotizacionVehiculo->id);
    }

    public function deleteVehiculo(Request $request){
        $vehiculoId = $request->get('vehiculoId'); // solo seria un campo
        $vehiculos = $request->get('vehiculos'); // solo seria un campo
        $token = $request->get('token');

        $item = Cotizacion::where([['token', '=', $token]])->first();
        // verificar que campokey exista en el flujo

        if (empty($item)) {
            return $this->ResponseError('COT-015', 'Tarea inválida');
        }

        $cotizacionVehiculo = CotizacionDetalleVehiculo::where('id', $vehiculoId)->first();
        $cotizacionVehiculo->delete();
        return $this->ResponseSuccess('Vehículo eliminado con éxito');
    }

    public function deleteCotizacion(Request $request){
        $cotizacionId = $request->get('cotizacionId'); // solo seria un campo
        $token = $request->get('token');

        $item = Cotizacion::where([['token', '=', $token]])->first();
        // verificar que campokey exista en el flujo

        if (empty($item)) {
            return $this->ResponseError('COT-015', 'Tarea inválida');
        }

        $cotizacionVehiculo = CotizacionDetalleVehiculoCotizacion::where('id', $cotizacionId)->first();
        if (!empty($cotizacionVehiculo)) {
            $cotizacionVehiculo->delete();
            return $this->ResponseSuccess('Cotización eliminado con éxito');
        }
        else {
            return $this->ResponseError('ERR-DEL', 'Error al eliminar cotización, intente nuevamente');
        }
    }

    public function setEmitirPoliza(Request $request){
        $token = $request->get('token'); // solo seria un campo
        $cotizacionId = $request->get('cotizacionId'); // solo seria un campo
        $status = $request->get('status');

        $item = Cotizacion::where([['token', '=', $token]])->first();
        // verificar que campokey exista en el flujo

        if (empty($item)) {
            return $this->ResponseError('COT-015', 'Tarea inválida');
        }

        $cotizacionVehiculo = CotizacionDetalleVehiculoCotizacion::where('id', $cotizacionId)->first();
        $cotizacionVehiculo->emitirPoliza = $status;
        $cotizacionVehiculo->save();

        $conteo = [];
        $detalle = CotizacionDetalleVehiculoCotizacion::where('cotizacionId', $item->id)->where('emitirPoliza', '1')->get();

        $aEmitir = CotizacionDetalle::where('cotizacionId', $item->id)->where('campo', 'VEHICULOS_A_EMITIR_CONTEO')->first();
        if (empty($aEmitir)) {
            $aEmitir = new CotizacionDetalle();
        }
        $aEmitir->campo = 'VEHICULOS_A_EMITIR_CONTEO';
        $aEmitir->cotizacionId = $item->id;
        $aEmitir->valorLong = $detalle->count();
        $aEmitir->save();

        // conteo cotizaciones
        $cotizacionVehiculosCoti = CotizacionDetalleVehiculoCotizacion::where('cotizacionId', $cotizacionVehiculo->cotizacionId)->where('emitirPoliza', 1)->get();

        $count = 1;
        foreach ($cotizacionVehiculosCoti as $value) {
            $this->saveReplaceCustomVar($item->id, "veh{$count}|emitido|producto", $value->producto->nombre ?? '');
            $count++;
        }

        return $this->ResponseSuccess('Cotización actualizada con éxito');
    }

    public function saveVehiculosOnBlur(Request $request){
        $vehiculoId = $request->get('vehiculoId'); // solo seria un campo
        $vehiculoNumber = $request->get('vehiculoN'); // solo seria un campo
        $vehiculo = $request->get('vehiculo'); // solo seria un campo
        $vehicleChange = $request->get('vehicleCh');
        $token = $request->get('token');
        $descuentoAdicional = $request->get('descuentoAdicional');
        $refreshDiscounts = $request->get('refreshDiscounts');
        $node = $request->get('node');

        $item = Cotizacion::where([['token', '=', $token]])->first();

        // guardado de variable para conteo de vehículos
        $vehiculos = CotizacionDetalleVehiculo::where('cotizacionId', $item->id)->get();
        $vehiCount = $vehiculos->count();
        $this->saveReplaceCustomVar($item->id, "SYS_CONTEO_VEHICULOS", $vehiCount);

        if (!empty($vehiculoId)) {
            $cotizacionVehiculo = $vehiculos->where('id', $vehiculoId)->first();
        }
        else {
            $cotizacionVehiculo = new CotizacionDetalleVehiculo();
            $cotizacionVehiculo->cotizacionId = $item->id;
        }

        $vehiculo['marcaId'] = ($vehiculo['marcaId'] ?? null);
        $vehiculo['lineaId'] = ($vehiculo['lineaId'] ?? null);
        $vehiculo['modelo'] = ($vehiculo['modelo'] ?? null);
        $vehiculo['lineaId'] = ($vehiculo['lineaId'] ?? null);
        $vehiculo['tipoId'] = ($vehiculo['tipoId'] ?? null);
        $vehiculo['noPasajeros'] = ($vehiculo['noPasajeros'] ?? null);
        $vehiculo['noChasis'] = ($vehiculo['noChasis'] ?? null);
        $vehiculo['noMotor'] = ($vehiculo['noMotor'] ?? null);
        $vehiculo['modelo'] = ($vehiculo['modelo'] ?? null);

        $cotizacionVehiculo->marcaId = $vehiculo['marcaId'] ?? null;

        if ($node === 'vehiculo_comp' && !empty($descuentoAdicional) && count($descuentoAdicional) > 0) {
            foreach ($descuentoAdicional as $desc) {
                $this->saveReplaceCustomVar($item->id, "desc_adicional", $desc['descAdi'], $vehiculoId, $vehiculoNumber);
                $this->saveReplaceCustomVar($item->id, "desc_adicional_comm", $desc['comm'], $vehiculoId, $vehiculoNumber);
            }

            return $this->ResponseSuccess('Guardado con éxito', $cotizacionVehiculo->id);
        }


        // verificar que campokey exista en el flujo

        if (empty($item)) {
            return $this->ResponseError('COT-015', 'Tarea inválida');
        }

        if ($item->siniesBlock === 1) {
            return $this->ResponseError('COT-SINIESPEN', 'La aprobación por parte de soporte aún se encuentra pendiente');
        }

        // traigo los vehiculos actuales de la cotizacion
        //$vehiculos = CotizacionDetalleVehiculo::where('cotizacionId', $item->id)->get();

        $vehiculosResponse = [];

        // se limpia el detalle de los vehículos
        // CotizacionDetalleVehiculo::where('cotizacionId', $item->id)->delete();

        // recorre los vehículos
        // si viene con id se borra

        $changeVehiculo = false;
        if ($vehiculo['marcaId'] !== $cotizacionVehiculo->marcaId || $vehiculo['lineaId'] !== $cotizacionVehiculo->lineaId || $vehiculo['modelo'] !== $cotizacionVehiculo->modelo || $vehicleChange) {
            $changeVehiculo = true;
        }


        $cotizacionVehiculo->lineaId = $vehiculo['lineaId'];
        $cotizacionVehiculo->tipoId = $vehiculo['tipoId'];
        $cotizacionVehiculo->noPasajeros = $vehiculo['noPasajeros'];
        $cotizacionVehiculo->noChasis = $vehiculo['noChasis'];
        $cotizacionVehiculo->noMotor = $vehiculo['noMotor'];
        $cotizacionVehiculo->modelo = $vehiculo['modelo'];
        $cotizacionVehiculo->valorProm = $this->clearMoney($vehiculo['valorProm'] ?? $vehiculo['valorPromDef']);
        $cotizacionVehiculo->valorPromDef = $this->clearMoney($vehiculo['valorPromDef']);
        $cotizacionVehiculo->placa = $vehiculo['placa'];
        $cotizacionVehiculo->vehiculoNuevo = $vehiculo['vehiculoNuevo'];
        $cotizacionVehiculo->altoRiesgoDisp = $vehiculo['altoRiesgoDisp'];
        $cotizacionVehiculo->validarVeh = intval($vehiculo['validarVeh']);
        $cotizacionVehiculo->save();

        // guardado de variables para uso posterior
        $this->saveReplaceCustomVar($item->id, "id", $vehiculoId, $vehiculoId, $vehiculoNumber);
        $this->saveReplaceCustomVar($item->id, "marca", $cotizacionVehiculo->marca->nombre ?? '', $vehiculoId, $vehiculoNumber);
        $this->saveReplaceCustomVar($item->id, "linea", $cotizacionVehiculo->linea->nombre ?? '', $vehiculoId, $vehiculoNumber);
        $this->saveReplaceCustomVar($item->id, "tipo", $cotizacionVehiculo->tipo->nombre ?? '', $vehiculoId, $vehiculoNumber);
        $this->saveReplaceCustomVar($item->id, "modelo", $cotizacionVehiculo->modelo ?? '', $vehiculoId, $vehiculoNumber);
        $this->saveReplaceCustomVar($item->id, "noPasajeros", $cotizacionVehiculo->noPasajeros ?? '', $vehiculoId, $vehiculoNumber);
        $this->saveReplaceCustomVar($item->id, "noChasis", $cotizacionVehiculo->noChasis ?? '', $vehiculoId, $vehiculoNumber);
        $this->saveReplaceCustomVar($item->id, "noMotor", $cotizacionVehiculo->noMotor ?? '', $vehiculoId, $vehiculoNumber);
        $this->saveReplaceCustomVar($item->id, "valorProm", $cotizacionVehiculo->valorProm ?? '', $vehiculoId, $vehiculoNumber);
        $this->saveReplaceCustomVar($item->id, "valorPromDef", $cotizacionVehiculo->valorPromDef ?? '', $vehiculoId, $vehiculoNumber);
        $this->saveReplaceCustomVar($item->id, "placa", $cotizacionVehiculo->placa ?? '', $vehiculoId, $vehiculoNumber);
        $this->saveReplaceCustomVar($item->id, "vehiculoNuevo", $cotizacionVehiculo->vehiculoNuevo ?? '', $vehiculoId, $vehiculoNumber);
        $this->saveReplaceCustomVar($item->id, "altoRiesgoDisp", $cotizacionVehiculo->altoRiesgoDisp ?? '', $vehiculoId, $vehiculoNumber);
        $this->saveReplaceCustomVar($item->id, "validarVeh", $cotizacionVehiculo->validarVeh ?? '', $vehiculoId, $vehiculoNumber);
        $this->saveReplaceCustomVar($item->id, "clasificacion", $cotizacionVehiculo->linea->clasificacion ?? '', $vehiculoId, $vehiculoNumber);

        // canal
        $AC = new AuthController();
        $canal = $AC->getFirstCanal();

        if (!empty($refreshDiscounts) || $changeVehiculo) {
            // se reinicia el comentario de descuento
            $this->saveReplaceCustomVar($item->id, "desc_adicional", 0, $vehiculoId);
            $this->saveReplaceCustomVar($item->id, "desc_adicional_comm", '', $vehiculoId);
        }

        if(!empty($canal) && $vehiculoNumber){
            $tipoLinea = $canal->tipoLinea ?? '';
            $tipoCartera = $canal->tipoCartera ?? '';
            $tipoProduccion = $canal->tipoProduccion ?? '';
            $tipoMovimiento = $canal->tipoMovimiento ?? '';
            $subtipoMovimiento = $canal->subtipoMovimiento ?? '';
            $tipoDocumento = $canal->tipoDocumento ?? '';
            $tipoUsuario = $canal->tipoUsuario ?? '';
            $tipoAsignacion = $canal->tipoAsignacion ?? '';

            $this->saveReplaceCustomVar($item->id, 'CANAL_TIPO_LINEA', $tipoLinea, $vehiculoId, $vehiculoNumber);
            $this->saveReplaceCustomVar($item->id, 'CANAL_TIPO_CARTERA', $tipoCartera, $vehiculoId, $vehiculoNumber);
            $this->saveReplaceCustomVar($item->id, 'CANAL_TIPO_PRODUCCION', $tipoProduccion, $vehiculoId, $vehiculoNumber);
            $this->saveReplaceCustomVar($item->id, 'CANAL_TIPO_MOVIMIENTO', $tipoMovimiento, $vehiculoId, $vehiculoNumber);
            $this->saveReplaceCustomVar($item->id, 'CANAL_SUB_TIPO_MOVIMIENTO', $subtipoMovimiento, $vehiculoId, $vehiculoNumber);
            $this->saveReplaceCustomVar($item->id, 'CANAL_TIPO_DOCUMENTO', $tipoDocumento, $vehiculoId, $vehiculoNumber);
            $this->saveReplaceCustomVar($item->id, 'CANAL_TIPO_USUARIO', $tipoUsuario, $vehiculoId, $vehiculoNumber);
            $this->saveReplaceCustomVar($item->id, 'CANAL_TIPO_ASIGNACION', $tipoAsignacion, $vehiculoId, $vehiculoNumber);
        }

        // TRAE LA LINEA
        $linea = catLinea::where('id', $vehiculo['lineaId'])->first();
        if(!empty($linea)){
            $this->saveReplaceCustomVar($item->id, "CLASIFICACION", $linea->clasificacion, $vehiculoId, $vehiculoNumber);
        }

        $tarifasArr = [];
        $tarifasTmp = catTarifas::where('activo', 1)->get();
        foreach ($tarifasTmp as $tarifa) {
            $clasificacion = explode(',', $tarifa->clasificacion);

            foreach ($clasificacion as $clasi) {
                $clasi = trim($clasi);
                $tarifasArr[$clasi][$tarifa->idTarifa] = $tarifa;
            }
        }

        $arrTarifasId= [];
        if (isset($linea->clasificacion) && isset($tarifasArr[$linea->clasificacion])) {
            foreach ($tarifasArr[$linea->clasificacion] as $tmp) {
                $arrTarifasId[] = $tmp->id;
            }
        }

        // sub cotizaciones
        if (is_array($vehiculo['cotizaciones'])) {

            // valor promedio
            if ($changeVehiculo) {
                $catController = new CatalogosController();
                $valorPromedio = $catController->getValorPromedio($vehiculo['marcaId'], $vehiculo['lineaId'], $vehiculo['modelo'], 'Q', $item->id);
                $cotizacionVehiculo->valorPromDef = (!empty($valorPromedio) ? $valorPromedio : 0);
                $cotizacionVehiculo->valorProm = (!empty($valorPromedio) ? $valorPromedio : 0);
                $cotizacionVehiculo->save();
            }

            $countCotizacion = 1;
            foreach ($vehiculo['cotizaciones'] as $subCoti) {

                $tarifaId = 0;
                $tarifaCod = "";
                $tarifaName = "";
                $productosTarifaId = [];
                $productosTarifa = catProductoTarifa::where('idProducto', $subCoti['productoId'])->whereIn('idTarifa', $arrTarifasId)->where('activo', 1)->get();
                foreach ($productosTarifa as $tmp) {
                    $productosTarifaId[] = $tmp->id;
                    $tarifaId = $tmp->idTarifa;
                    $tarifaCod = $tmp->tarifa->idTarifa;
                    $tarifaName = $tmp->tarifa->descripcion;
                }

                /*if (empty($tarifaId)) {
                    return $this->ResponseError('CLASS-004', 'El vehículo no posee tarifa asociada a línea y clasificación, no es posible asociar la tarifa');
                }*/

                $tmpSubCoti = false;
                if (!empty($subCoti['ID'])) {
                    $tmpSubCoti = CotizacionDetalleVehiculoCotizacion::where('cotizacionDetalleVehiculoId', $cotizacionVehiculo->id)->where('id', $subCoti['ID'])->first();
                }
                else {
                    $tmpSubCoti = new CotizacionDetalleVehiculoCotizacion();
                }

                $producto = catProductos::where('id', $subCoti['productoId'])->first();
                $porcentajeDescuento = 0;

                if(!empty($subCoti['productoId'])){
                    $currentYear = Carbon::now()->year;
                    $currentYearInt = (int) $currentYear;
                    $maxAnios = $producto->maxAnios;
                    if($maxAnios > 0 && abs($currentYearInt - (int)$vehiculo['modelo']) > $maxAnios){
                        return $this->ResponseError('CVMA-001', 'El vehículo excede los años máximo del producto, elija otro producto');
                    }
                }

                $tmpSubCoti->productoId = $subCoti['productoId'];
                $tmpSubCoti->cotizacionId = $item->id;
                $tmpSubCoti->cotizacionDetalleVehiculoId = $cotizacionVehiculo->id;
                $tmpSubCoti->formaPagoId = $subCoti['formaPago'];
                $tmpSubCoti->numeroPagos = $subCoti['numeroPagos'];
                $tmpSubCoti->descuentoPorcentaje = $subCoti['descuento'] ?? 0;
                $tmpSubCoti->descuentoId = $subCoti['descuentoSelect'] ?? 0;
                $tmpSubCoti->frecuenciaPagos = json_encode($subCoti['frecuenciaPagos']) ?? '[]';
                $tmpSubCoti->tarifaId = $tarifaId ?? null;



                //var_dump($tarifaId);
                //$subCoti->save();

                if ($changeVehiculo) {
                    $suma = floatval($cotizacionVehiculo->valorPromDef);
                }
                else {
                    $sumTmp = $this->clearMoney($subCoti['sumAseg'] ?? 0);
                    $suma = ($sumTmp > 0) ? $sumTmp : $cotizacionVehiculo->valorPromDef;
                }

                $tmpSubCoti->sumaAsegurada = $suma;
                $tmpSubCoti->save();

                // coberturas
                //var_dump($subCoti['coberturas']);
                CotizacionDetalleVehiculoCotizacionCobertura::where('cotizacionDetalleVehiculoCotId', $tmpSubCoti->id)->delete();
                if (!empty($subCoti['coberturas'])) {
                    $count = 1;
                    foreach ($subCoti['coberturas'] as $cober) {

                        $invisible = ($cober['tipoVisibilidad'] === 'nada' || empty($cober['selected'])) ? 1 : 0;

                        if (!empty($cober['selected']) || !empty($cober['obligatorio'])) {
                            $tmpSubCotiCober = new CotizacionDetalleVehiculoCotizacionCobertura();
                            $tmpSubCotiCober->coberturaId = $cober['value'];
                            $tmpSubCotiCober->cotizacionDetalleVehiculoCotId = $tmpSubCoti->id;
                            $tmpSubCotiCober->monto = $this->clearMoney($cober['monto'] ?? 0);
                            $tmpSubCotiCober->codigoCobertura = trim($cober['codigo']);
                            $tmpSubCotiCober->invisible = $invisible;
                            $tmpSubCotiCober->save();

                            // variables por cobertura
                            $this->saveReplaceCustomVar($item->id, "COBERTURA_{$cober['codigo']}", 1, $vehiculoId, $vehiculoNumber, $countCotizacion, $tmpSubCoti->id);
                        }
                    }
                }

                // var_dump($tmpSubCoti);

                // Variables para cotización
                $this->saveReplaceCustomVar($item->id, 'DESCUENTO', $tmpSubCoti->descuentoPorcentaje, $vehiculoId, $vehiculoNumber, $countCotizacion, $tmpSubCoti->id);
                $this->saveReplaceCustomVar($item->id, "tarifa", $tmpSubCoti->tarifa->nombre ?? '', $vehiculoId, $vehiculoNumber, $countCotizacion, $tmpSubCoti->id);
                $this->saveReplaceCustomVar($item->id, "descuentoPorcentaje", $tmpSubCoti->descuentoPorcentaje ?? '', $vehiculoId, $vehiculoNumber, $countCotizacion, $tmpSubCoti->id);
                $this->saveReplaceCustomVar($item->id, "sumaAsegurada", $tmpSubCoti->sumaAsegurada ?? '', $vehiculoId, $vehiculoNumber, $countCotizacion);
                $this->saveReplaceCustomVar($item->id, "formaPago", ($tmpSubCoti->formaPago ? $tmpSubCoti->formaPago->descripcion : 'Sin forma de pago'), $vehiculoId, $vehiculoNumber, $countCotizacion, $tmpSubCoti->id);
                $this->saveReplaceCustomVar($item->id, "sumaAsegurada", $tmpSubCoti->sumaAsegurada ?? '', $vehiculoId, $vehiculoNumber, $countCotizacion, $tmpSubCoti->id);
                $this->saveReplaceCustomVar($item->id, "numeroPagos", $tmpSubCoti->numeroPagos ?? '', $vehiculoId, $vehiculoNumber, $countCotizacion, $tmpSubCoti->id);
                $this->saveReplaceCustomVar($item->id, "primaNeta", $tmpSubCoti->primaNeta ?? '', $vehiculoId, $vehiculoNumber, $countCotizacion, $tmpSubCoti->id);
                $this->saveReplaceCustomVar($item->id, "primaTotal", $tmpSubCoti->primaTotal ?? '', $vehiculoId, $vehiculoNumber, $countCotizacion, $tmpSubCoti->id);
                $this->saveReplaceCustomVar($item->id, "recargoPorcentaje", $tmpSubCoti->primaTotal ?? '', $vehiculoId, $vehiculoNumber, $countCotizacion, $tmpSubCoti->id);
                $this->saveReplaceCustomVar($item->id, "emitirPoliza", $tmpSubCoti->emitirPoliza ?? 0, $vehiculoId, $vehiculoNumber, $countCotizacion, $tmpSubCoti->id);
                $this->saveReplaceCustomVar($item->id, "numeroCotizacionAS400", $tmpSubCoti->numeroCotizacionAS400 ?? '', $vehiculoId, $vehiculoNumber, $countCotizacion, $tmpSubCoti->id);
                $this->saveReplaceCustomVar($item->id, 'TARIFA_CODIGO', $tmpSubCoti->tarifa->idTarifa ?? '', $vehiculoId, $vehiculoNumber, $countCotizacion, $tmpSubCoti->id);
                $this->saveReplaceCustomVar($item->id, 'TARIFA_NOMBRE', $tmpSubCoti->tarifa->descripcion ?? '', $vehiculoId, $vehiculoNumber, $countCotizacion, $tmpSubCoti->id);
                $this->saveReplaceCustomVar($item->id, "producto_nombre", $tmpSubCoti->producto->nombre ?? 'N/D', $vehiculoId, $vehiculoNumber, $countCotizacion, $tmpSubCoti->id);

                $countCotizacion++;
            }
        }

        return $this->ResponseSuccess('Guardado con éxito', $cotizacionVehiculo->id);
    }

    public function saveReplaceCustomVar($cotizacionId, $key, $value = '', $vehiculoId = 0, $vehiculoNumber = 1, $cotNumber = false, $cotCotId = null) {

        $vehiPrefix = '';
        if (!empty($vehiculoId)) {
            // si el vehiculo id viene pero el vehiculo number está vacío, no se guardan
            if (empty($vehiculoNumber)) {
                return true;
            }
            $vehiPrefix = "veh{$vehiculoNumber}|";

            // prefijo de coti
            if (!empty($cotNumber)) {
                $vehiPrefix = "{$vehiPrefix}cot{$cotNumber}|";
            }
        }

        $campo = "{$vehiPrefix}{$key}";

        $cotizacionDetalle= CotizacionDetalle::where('cotizacionId', $cotizacionId)
            ->where('cotizacionVehiculoId', $vehiculoId)
            ->where('campo', $campo)
            ->first();

        if(empty($cotizacionDetalle)) $cotizacionDetalle = new CotizacionDetalle();
        $cotizacionDetalle->cotizacionId = $cotizacionId;
        $cotizacionDetalle->cotizacionVehiculoId = $vehiculoId;
        $cotizacionDetalle->campo = $campo;
        $cotizacionDetalle->valorLong = $value;

        if ($cotCotId) {
            $cotizacionDetalle->cotizacionDetalleVehiculoCotId = $cotCotId;
        }

        $cotizacionDetalle->save();
    }

    public function getVehiculos(Request $request){

        $token = $request->get('token');

        $item = Cotizacion::where([['token', '=', $token]])->first();
        // verificar que campokey exista en el flujo

        if (empty($item)) {
            return $this->ResponseError('COT-015', 'Tarea inválida');
        }

        $productos = catProductos::where('activo', 1)->get();

        // se limpia el detalle de los vehículos
        $cotizacionVehiculo = CotizacionDetalleVehiculo::where('cotizacionId', $item->id)->get();

        $vehiculos = [];

        foreach ($cotizacionVehiculo as $vehi) {

            // traigo cotizaciones y coberturas
            $coti = CotizacionDetalleVehiculoCotizacion::where('cotizacionDetalleVehiculoId', $vehi->id)->get();

            foreach ($coti as $cotiTmp) {
                if(empty($cotiTmp->frecuenciaPagos)){
                    $cotiTmp->frecuenciaPagos = [['f' => '', 'p' => []]];
                }else{
                    $cotiTmp->frecuenciaPagos = json_decode($cotiTmp->frecuenciaPagos, true);
                }

                $coberturaTmp = CotizacionDetalleVehiculoCotizacionCobertura::where('cotizacionDetalleVehiculoCotId', $cotiTmp->id)->get();
                $cotiTmp->coberturas = $coberturaTmp;

                $productoTmp = $productos->where('id', $cotiTmp->productoId)->first();
                $cotiTmp->producto = $productoTmp->nombre ?? '';
            }
            //$vehi = $vehi->toArray();
            $vehi->cotizaciones = $coti;

            $vehiculos[] = $vehi;
        }

        return $this->ResponseSuccess('Vehículos obtenidos con éxito', $vehiculos);
    }


    public function getVehiculosCotizaciones(Request $request){

        $token = $request->get('token');
        $onlyEmitidos = $request->get('onlyE');

        $item = Cotizacion::where([['token', '=', $token]])->first();
        // verificar que campokey exista en el flujo

        if (empty($item)) {
            return $this->ResponseError('COT-015', 'Tarea inválida');
        }

        // se limpia el detalle de los vehículos
        $cotizacionVehiculo = CotizacionDetalleVehiculo::where('cotizacionId', $item->id)->orderBy('id', 'ASC')->get();

        $arrayData = [];

        $countVehi = 1;
        foreach ($cotizacionVehiculo as $vehi) {

            // traigo cotizaciones y coberturas
            $output = array();
            $coti = CotizacionDetalleVehiculoCotizacion::where('cotizacionDetalleVehiculoId', $vehi->id)->with('coberturas', 'vehiculo')->get();

            // vars vehi
            $detalleVehi = CotizacionDetalle::where('cotizacionId', $item->id)->where('cotizacionVehiculoId', $vehi->id)->get();

            // procesa todo
            $arrayFinalVehi = [];
            foreach ($detalleVehi as $tmp) {
                $arrayFinalVehi[$tmp->campo] = $tmp->valorLong;
            }

            $emitir = 0;

            foreach ($coti as $cotiTmp) {
                if (empty($cotiTmp->producto)) continue;

                $producto = $cotiTmp->producto->nombre ?? '';
                $arrCoberturas = [];
                $detalle = CotizacionDetalle::where('cotizacionId', $item->id)->where('cotizacionDetalleVehiculoCotId', $cotiTmp->id)->get();

                // coberturas
                foreach ($cotiTmp->coberturas as $tmp) {
                    $arrCoberturas[] = $tmp->codigoCobertura;
                }

                // procesa todo
                $arrayFinal = [];
                foreach ($detalle as $tmp) {
                    $arrayFinal[$tmp->campo] = $tmp->valorLong;
                }

                $convertDotToArray = function($array) {
                    $newArray = array();
                    foreach($array as $key => $value) {
                        $dots = explode(".", $key);
                        if(count($dots) > 1) {
                            $last = &$newArray[ $dots[0] ];
                            foreach($dots as $k => $dot) {
                                if($k == 0) continue;
                                $last = &$last[$dot];
                            }

                            $last = $value;
                        } else {
                            $newArray[$key] = $value;
                        }
                    }
                    return $newArray;
                };
                $procesoDataCotizacion = $convertDotToArray($arrayFinal);
                $opcionesDePago = [];
                $datosCotizacionGestorComercial = $procesoDataCotizacion['COTIZACION_AS400']['datosIdEmpresaGC']['datos03']['datosCotizacionGestorComercial2'] ?? [];
                $listaOpcionesPago = $datosCotizacionGestorComercial['listaOpcionesPago']['opcionPago']['listaOpcion'] ?? [];
                $opcionesDePago['headers'] = [
                    ['text' => 'Opciones de pago', 'value' => 'descripcionPago'],
                    ['text' => 'Pagos', 'value' => 'idPago'],
                    ['text' => 'Prima', 'value' => 'primaPago'],
                    ['text' => 'Monto de pago', 'value' => 'montoPago']
                ];

                if (is_array($listaOpcionesPago)) {
                    foreach ($listaOpcionesPago as $key => $value) {
                        if (isset($value['primaPago'])) {

                            $montoPago = number_format($value['primaPago'] / $value['idPago'], 2, ',');
                            $value['primaPago'] = number_format($value['primaPago'], 2, ',');
                            $listaOpcionesPago[$key]['primaPago'] = "Q. {$value['primaPago']}";
                            $listaOpcionesPago[$key]['montoPago'] = "Q. {$montoPago}";
                        }
                    }
                }

                $opcionesDePago['data'] = [];
                if(empty($listaOpcionesPago[0])) {
                    $opcionesDePago['data'] = [$listaOpcionesPago];
                }
                else {
                    $opcionesDePago['data'] = $listaOpcionesPago;
                };

                $listaDesglosePrima = $datosCotizacionGestorComercial['listaDesglosePrima']['listaDesglose']['desglosePrima'] ?? [];
                $opcionesDesglosePago = [];
                $grupoCoberturas = [];

                if ($datosCotizacionGestorComercial) {
                    $listaOpcionesPago = $datosCotizacionGestorComercial['listaOpcionesPago']['opcionPago']['listaOpcion'];
                    $opcionesDePago['headers'] = [
                        ['text' => 'Opciones de pago', 'value' => 'descripcionPago'],
                        ['text' => 'Pagos', 'value' => 'idPago'],
                        ['text' => 'Prima', 'value' => 'primaPago'],
                        ['text' => 'Monto pago', 'value' => 'montoPago'],
                    ];


                    if(empty($listaOpcionesPago[0]))  $opcionesDePago['data'] = [$listaOpcionesPago];
                    else $opcionesDePago['data'] = $listaOpcionesPago;

                    // agrega moneda
                    foreach ($opcionesDePago['data'] as $key => $value) {
                        $montoPago = number_format($value['primaPago'] / $value['idPago'], 2, ',');
                        $value['primaPago'] = number_format($value['primaPago'], 2, ',');
                        $opcionesDePago['data'][$key]['primaPago'] = "Q. {$value['primaPago']}";
                        $opcionesDePago['data'][$key]['montoPago'] = "Q. {$montoPago}";
                    }

                    $listaDesglosePrima = $datosCotizacionGestorComercial['listaDesglosePrima']['listaDesglose']['desglosePrima'];


                    if(!empty($listaDesglosePrima['tipoPrima'])) $listaDesglosePrima = [$listaDesglosePrima];
                    $dataFila = [];
                    $dataHeadersIni = [['text' => 'Desglose de prima', 'value' => 'descripcion']];
                    $dataHeadersFin = [['text' => 'Desglose de prima', 'value' => 'descripcion']];
                    foreach($listaDesglosePrima as $desglose){
                        $dataHeadersFin[] = ['text' => $desglose['tipoPrima'], 'value' => $desglose['tipoPrima']];
                        foreach($desglose['listaPrima'] as $listaPrima){
                            $dataFila[$listaPrima['descripcionPrima']]['descripcion'] = $listaPrima['descripcionPrima'];
                            $listaPrima['valorPrima'] = number_format($listaPrima['valorPrima'], 2, ',');
                            $dataFila[$listaPrima['descripcionPrima']][$desglose['tipoPrima']] = "Q. {$listaPrima['valorPrima']}";
                        }
                        if(count($dataHeadersFin) > 3){
                            $opcionesDesglosePago['headers'][] = $dataHeadersFin;
                            $dataHeadersFin = $dataHeadersIni;
                        }
                    }
                    if(count($dataHeadersFin) > 1)$opcionesDesglosePago['headers'][] = $dataHeadersFin;
                    $opcionesDesglosePago['data'] = array_values($dataFila);

                    $listaCoberturas = $datosCotizacionGestorComercial['listaCoberturas']['coberturas'];
                    $grupoCoberturas = [];

                    /*var_dump($arrCoberturas);
                    var_dump($listaCoberturas);*/

                    foreach($listaCoberturas as $listCob){
                        $idCobertura = $listCob['idCobertura'];

                        if (!in_array($idCobertura, $arrCoberturas)) {
                            continue;
                        }

                        $catGroupCob = catCoberturas::where('codigo', $idCobertura)->first();
                        if(!empty($catGroupCob->grupo)){
                            $grupoCoberturas[$catGroupCob->grupo]['lista'][] = $listCob;
                        }
                    }

                    foreach($grupoCoberturas as $idGroup => $groupCob){
                        $groupcat = catGrupoCoberturas::where('id', $idGroup)->first();
                        $grupoCoberturas[$idGroup]['nombre'] = $groupcat->nombre;
                        $grupoCoberturas[$idGroup]['descripcion'] = $groupcat->descripcion;
                    }
                }

                if (!empty($cotiTmp->emitirPoliza) || empty($onlyEmitidos)) {
                    $arrayData[$vehi->id]['c'][$cotiTmp->id] = $procesoDataCotizacion;
                    $arrayData[$vehi->id]['c'][$cotiTmp->id]['emitirPoliza'] = $cotiTmp->emitirPoliza;
                    $arrayData[$vehi->id]['c'][$cotiTmp->id]['idCorrelativo'] = $cotiTmp->idCorrelativo ?? 1;
                    $arrayData[$vehi->id]['c'][$cotiTmp->id]['noCot'] = $cotiTmp->numeroCotizacionAS400;
                    $arrayData[$vehi->id]['c'][$cotiTmp->id]['cs'] = $arrCoberturas;
                    $arrayData[$vehi->id]['c'][$cotiTmp->id]['opcionesPago'] = $opcionesDePago;
                    $arrayData[$vehi->id]['c'][$cotiTmp->id]['desglosePago'] = $opcionesDesglosePago;
                    $arrayData[$vehi->id]['c'][$cotiTmp->id]['grupoCoberturas'] = $grupoCoberturas;
                    $arrayData[$vehi->id]['c'][$cotiTmp->id]['producto'] = $producto;
                    $arrayData[$vehi->id]['c'][$cotiTmp->id]['msgAs'] = ucfirst($arrayFinal['COTIZACION_AS400.datosIdEmpresaGC.datos03.datosCotizacionGestorComercial2.msgRespuesta'] ?? '');

                    $nombreTmp = $cotiTmp->vehiculo->marca->nombre ?? '';
                    $lnombreTmp = $cotiTmp->vehiculo->linea->nombre ?? '';
                    $vehiculoMod = $cotiTmp->vehiculo->modelo ?? '';
                    $arrayData[$vehi->id]['v'] = "{$nombreTmp} - {$lnombreTmp} - año {$vehiculoMod}, {$producto}";
                    $arrayData[$vehi->id]['n'] = $countVehi;
                    $arrayData[$vehi->id]['descAdi'] = $arrayFinalVehi["desc_adicional"] ?? 0;
                    $arrayData[$vehi->id]['descAdiC'] = $arrayFinalVehi["desc_adicional_comm"] ?? '';

                    /*if (!$emitir && !empty($cotiTmp->emitirPoliza)) {
                        $emitir = 1;
                    }*/
                }


            }

            //$arrayData[$vehi->id]['emitir'] = $emitir;

            $countVehi++;
        }

        return $this->ResponseSuccess('Vehículos C obtenidos con éxito', $arrayData);
    }

    public function getVehiculosCotizacionesTables($cotizacionId){

        $item = Cotizacion::where([['id', '=', $cotizacionId]])->first();

        // se limpia el detalle de los vehículos
        $cotizacionVehiculo = CotizacionDetalleVehiculo::where('cotizacionId', $item->id)->get();

        $arrayData = [];

        $countVehi = 1;
        foreach ($cotizacionVehiculo as $vehi) {

            // traigo cotizaciones y coberturas
            $coti = CotizacionDetalleVehiculoCotizacion::where('cotizacionDetalleVehiculoId', $vehi->id)->with('coberturas', 'vehiculo')->get();

            foreach ($coti as $cotiTmp) {
                if (empty($cotiTmp->producto)) continue;

                $producto = $cotiTmp->producto->nombre ?? '';
                $arrCoberturas = [];
                $detalle = CotizacionDetalle::where('cotizacionId', $item->id)->where('cotizacionDetalleVehiculoCotId', $cotiTmp->id)->get();

                // coberturas
                foreach ($cotiTmp->coberturas as $tmp) {
                    $arrCoberturas[] = $tmp->codigoCobertura;
                }

                // procesa todo
                $arrayFinal = [];
                foreach ($detalle as $tmp) {
                    $arrayFinal[$tmp->campo] = $tmp->valorLong;
                }

                $convertDotToArray = function($array) {
                    $newArray = array();
                    foreach($array as $key => $value) {
                        $dots = explode(".", $key);
                        if(count($dots) > 1) {
                            $last = &$newArray[ $dots[0] ];
                            foreach($dots as $k => $dot) {
                                if($k == 0) continue;
                                $last = &$last[$dot];
                            }

                            $last = $value;
                        } else {
                            $newArray[$key] = $value;
                        }
                    }
                    return $newArray;
                };
                $procesoDataCotizacion = $convertDotToArray($arrayFinal);
                $datosCotizacionGestorComercial = $procesoDataCotizacion['COTIZACION_AS400']['datosIdEmpresaGC']['datos03']['datosCotizacionGestorComercial2'] ?? [];
                $listaOpcionesPagoTmp = $datosCotizacionGestorComercial['listaOpcionesPago']['opcionPago']['listaOpcion'] ?? [];

                $opcionesDePago = [];
                $opcionesDePago['headers'] = [
                    'Opciones de pago',
                    'Pagos',
                    'Prima',
                    'Monto de pago'
                ];
                $opcionesDePago['rows'] = [];

                if (!isset($listaOpcionesPagoTmp[0])) {
                    $listaOpcionesPagoTmp = [$listaOpcionesPagoTmp];
                }

                if (is_array($listaOpcionesPagoTmp)) {
                    foreach ($listaOpcionesPagoTmp as $key => $value) {
                        if (isset($value['primaPago'])) {

                            $montoPago = number_format($value['primaPago'] / $value['idPago'], 2, ',');
                            $value['primaPago'] = number_format($value['primaPago'], 2, ',');

                            $opcionesDePago['rows'][$key][] = $value['descripcionPago'];
                            $opcionesDePago['rows'][$key][] = $value['idPago'];
                            $opcionesDePago['rows'][$key][] = "Q. {$value['primaPago']}";
                            $opcionesDePago['rows'][$key][] = "Q. {$montoPago}";
                        }
                    }
                }

                $opcionesDesglosePago = [];
                $grupoCoberturas = [];

                if ($datosCotizacionGestorComercial) {

                    $listaDesglosePrima = $datosCotizacionGestorComercial['listaDesglosePrima']['listaDesglose']['desglosePrima'];

                    if(!empty($listaDesglosePrima['tipoPrima'])) $listaDesglosePrima = [$listaDesglosePrima];
                    $dataFila = [];
                    $dataHeadersIni = [['text' => 'Desglose de prima', 'value' => 'descripcion']];
                    $dataHeadersFin = [['text' => 'Desglose de prima', 'value' => 'descripcion']];
                    foreach($listaDesglosePrima as $desglose){
                        $dataHeadersFin[] = ['text' => $desglose['tipoPrima'], 'value' => $desglose['tipoPrima']];
                        foreach($desglose['listaPrima'] as $listaPrima){
                            $dataFila[$listaPrima['descripcionPrima']]['descripcion'] = $listaPrima['descripcionPrima'];
                            $listaPrima['valorPrima'] = number_format($listaPrima['valorPrima'], 2, ',');
                            $dataFila[$listaPrima['descripcionPrima']][$desglose['tipoPrima']] = "Q. {$listaPrima['valorPrima']}";
                        }
                        if(count($dataHeadersFin) > 3){
                            $opcionesDesglosePago['headers'][] = $dataHeadersFin;
                            $dataHeadersFin = $dataHeadersIni;
                        }
                    }
                    if(count($dataHeadersFin) > 1)$opcionesDesglosePago['headers'][] = $dataHeadersFin;
                    $opcionesDesglosePago['data'] = array_values($dataFila);

                    $listaCoberturas = $datosCotizacionGestorComercial['listaCoberturas']['coberturas'];

                    /*var_dump($arrCoberturas);
                    var_dump($listaCoberturas);*/

                    foreach($listaCoberturas as $listCob){
                        $idCobertura = $listCob['idCobertura'];

                        if (!in_array($idCobertura, $arrCoberturas)) {
                            continue;
                        }
                        else {
                            $bitacoraCoti = new CotizacionBitacora();
                            $bitacoraCoti->cotizacionId = $item->id;
                            $bitacoraCoti->usuarioId = 0;
                            $bitacoraCoti->log = "La cobertura {$idCobertura} no existe en catálogo";
                            $bitacoraCoti->save();
                        }

                        $catGroupCob = catCoberturas::where('codigo', $idCobertura)->first();
                        if(!empty($catGroupCob->grupo)){
                            $groupcat = catGrupoCoberturas::where('id', $catGroupCob->grupo)->first();

                            $keyGrupo = "COBERTURA_GRUPO_{$catGroupCob->grupo}";
                            if (!isset($grupoCoberturas[$keyGrupo]['headers'])) {
                                $grupoCoberturas[$keyGrupo]['headers'] = [
                                    'Cobertura',
                                    'Suma asegurada',
                                    '%',
                                    'Deducible',
                                ];
                            }

                            $grupoCoberturas[$keyGrupo]['rows'][] = [
                                $listCob['descripcion'],
                                "Q. ".number_format($listCob['sumaAsegurada'], 2),
                                floatval($listCob['porcentajeDeducible']),
                                "Q. ".number_format($listCob['deducible'], 2),
                            ];
                        }
                    }

                    /*foreach($grupoCoberturas as $idGroup => $groupCob){
                        $groupcat = catGrupoCoberturas::where('id', $idGroup)->first();
                        $grupoCoberturas[$groupcat->nombre]['nombre'] = $groupcat->nombre;
                        $grupoCoberturas[$groupcat->nombre]['descripcion'] = $groupcat->descripcion;
                    }*/
                }

                $arrayData[$vehi->id][$cotiTmp->id]['PDF_PAGOS_TABLE'] = json_encode($opcionesDePago);
                //$arrayData[$vehi->id][$cotiTmp->id]['desglosePago'] = $opcionesDesglosePago;

                foreach ($grupoCoberturas as $grupo => $cobertura) {
                    $arrayData[$vehi->id][$cotiTmp->id][$grupo] = json_encode($cobertura);
                }
            }

            $countVehi++;
        }

        /*var_dump($arrayData);
        die();*/
        return $arrayData;
    }

    public function getVehiculosCotizacionesComp(Request $request){

        $token = $request->get('token');

        $cotizacion = Cotizacion::where([['token', '=', $token]])->first();
        // verificar que campokey exista en el flujo

        if (empty($cotizacion)) {
            return $this->ResponseError('COT-015', 'Tarea inválida');
        }

        // grupos de coberturas
        $groupcat = catGrupoCoberturas::where('activo', 1)->get();
        $grupoCoberturas = [];
        foreach ($groupcat as $item) {
            $grupoCoberturas[$item->id] = $item;
        }

        // coberturas
        $coberturasAll = [];
        $coberturasTmp = catCoberturas::where('activo', 1)->get();
        foreach ($coberturasTmp as $item) {
            $coberturasAll[$item->codigo] = $item;
        }

        // se limpia el detalle de los vehículos
        $cotizacionVehiculo = CotizacionDetalleVehiculo::where('cotizacionId', $cotizacion->id)->get();

        $productosTitle = [];
        $coberturasByCoti = [];

        foreach ($cotizacionVehiculo as $vehi) {

            $marca = $vehi->marca->nombre;
            $linea = $vehi->linea->nombre;
            $coti = CotizacionDetalleVehiculoCotizacion::where('cotizacionDetalleVehiculoId', $vehi->id)->with('coberturas', 'vehiculo')->get();

            $countCoti = 1;
            foreach ($coti as $cotiTmp) {
                /*var_dump($cotiTmp);
                die();*/

                if (empty($cotiTmp->producto)) continue;

                $moneda = $cotiTmp->producto->idMoneda ?? 'Q';
                $producto = $cotiTmp->producto->nombre ?? '';
                $arrCoberturas = [];
                $detalle = CotizacionDetalle::where('cotizacionId', $cotizacion->id)->where('cotizacionDetalleVehiculoCotId', $cotiTmp->id)->get();

                // coberturas
                foreach ($cotiTmp->coberturas as $tmp) {
                    $arrCoberturas[] = $tmp->codigoCobertura;
                }

                // procesa todo
                $arrayFinal = [];
                foreach ($detalle as $tmp) {
                    $arrayFinal[$tmp->campo] = $tmp->valorLong;
                }

                $convertDotToArray = function($array) {
                    $newArray = array();
                    foreach($array as $key => $value) {
                        $dots = explode(".", $key);
                        if(count($dots) > 1) {
                            $last = &$newArray[ $dots[0] ];
                            foreach($dots as $k => $dot) {
                                if($k == 0) continue;
                                $last = &$last[$dot];
                            }

                            $last = $value;
                        } else {
                            $newArray[$key] = $value;
                        }
                    }
                    return $newArray;
                };
                $procesoDataCotizacion = $convertDotToArray($arrayFinal);
                $opcionesDePago = [];
                $datosCotizacionGestorComercial = $procesoDataCotizacion['COTIZACION_AS400']['datosIdEmpresaGC']['datos03']['datosCotizacionGestorComercial2']['listaCoberturas']['coberturas'] ?? [];
                //var_dump($datosCotizacionGestorComercial);

                foreach ($datosCotizacionGestorComercial as $coberturas) {



                    if (!empty($coberturasAll[$coberturas['idCobertura']])) {

                        // busco su grupo
                        $grupo = false;
                        if (!empty($coberturasAll[$coberturas['idCobertura']]->grupo)) {
                            $grupo = $grupoCoberturas[$coberturasAll[$coberturas['idCobertura']]->grupo];
                        }

                        $deducible = floatval($coberturas['deducible'] ?? 0);

                        // formateado
                        $coberturas['deducible'] = ($deducible > 0) ? $moneda." ".number_format($deducible, 2) : '';
                        $coberturas['sumaAsegurada'] = $moneda." ".number_format($coberturas['sumaAsegurada'], 2);
                        $coberturas['porcentajeDeducible'] = number_format($coberturas['porcentajeDeducible'], 2)." %";

                        $coberturasByCoti["{$marca} - {$linea} - {$vehi->modelo}"][$grupo->nombre ?? 'Otras'][$coberturas['descripcion']][$producto][$countCoti] = $coberturas;
                        $productosTitle["{$marca} - {$linea} - {$vehi->modelo}"][$producto] = $producto;
                    }
                }

                // rellenar vacías
                foreach ($coberturasByCoti as $keyVehi => $vehiTmp) {

                    $maxCols = 1;
                    foreach ($vehiTmp as $keyGrupo => $cober) {

                        foreach ($cober as $keyProd => $prods) {
                            $total = count($prods);
                            if ($total > $maxCols) {
                                $maxCols = $total;
                            }
                        }
                    }

                    foreach ($vehiTmp as $keyGrupo => $cober) {
                        foreach ($cober as $keyProd => $prods) {
                            if ($maxCols > count($prods)) {
                                for ($i = 1; $i<$maxCols; $i++) {
                                    $coberturasByCoti[$keyVehi][$keyGrupo][$keyProd]["vacia_{$i}"] = [[]];
                                }
                            }
                        }
                    }
                    /*foreach ($vehiTmp as $keyGrupo => $cober) {

                        if ($maxCols > count($cober)) {
                            for ($i = 0; $i<$maxCols; $i++) {
                                $coberturasByCoti[$keyVehi][$keyGrupo]["vacia_{$i}"] = [[]];
                            }
                        }
                    }*/
                }

                /*var_dump($coberturasByCoti);
                die();

                $listaOpcionesPago = $datosCotizacionGestorComercial['listaOpcionesPago']['opcionPago']['listaOpcion'] ?? [];
                $opcionesDePago['headers'] = [
                    ['text' => 'Opciones de pago', 'value' => 'descripcionPago'],
                    ['text' => 'Pagos', 'value' => 'idPago'],
                    ['text' => 'Prima', 'value' => 'primaTotal'],
                    ['text' => 'Monto de pago', 'value' => 'montoPago']
                ];

                if (is_array($listaOpcionesPago)) {
                    foreach ($listaOpcionesPago as $key => $value) {
                        if (isset($value['primaTotal'])) {

                            $montoPago = number_format($value['primaTotal'] / $value['idPago'], 2, ',');
                            $value['primaTotal'] = number_format($value['primaTotal'], 2, ',');
                            $listaOpcionesPago[$key]['primaTotal'] = "Q. {$value['primaTotal']}";
                            $listaOpcionesPago[$key]['montoPago'] = "Q. {$montoPago}";
                        }
                    }
                }

                $opcionesDePago['data'] = [];
                if(empty($listaOpcionesPago[0])) {
                    $opcionesDePago['data'] = [$listaOpcionesPago];
                }
                else {
                    $opcionesDePago['data'] = $listaOpcionesPago;
                };

                $listaDesglosePrima = $datosCotizacionGestorComercial['listaDesglosePrima']['listaDesglose']['desglosePrima'] ?? [];
                $opcionesDesglosePago = [];
                $grupoCoberturas = [];

                if ($datosCotizacionGestorComercial) {
                    $listaOpcionesPago = $datosCotizacionGestorComercial['listaOpcionesPago']['opcionPago']['listaOpcion'];
                    $opcionesDePago['headers'] = [
                        ['text' => 'Opciones de pago', 'value' => 'descripcionPago'],
                        ['text' => 'Pagos', 'value' => 'idPago'],
                        ['text' => 'Prima', 'value' => 'primaTotal'],
                        ['text' => 'Monto pago', 'value' => 'montoPago'],
                    ];


                    if(empty($listaOpcionesPago[0]))  $opcionesDePago['data'] = [$listaOpcionesPago];
                    else $opcionesDePago['data'] = $listaOpcionesPago;

                    // agrega moneda
                    foreach ($opcionesDePago['data'] as $key => $value) {
                        $montoPago = number_format($value['primaTotal'] / $value['idPago'], 2, ',');
                        $value['primaTotal'] = number_format($value['primaTotal'], 2, ',');
                        $opcionesDePago['data'][$key]['primaTotal'] = "Q. {$value['primaTotal']}";
                        $opcionesDePago['data'][$key]['montoPago'] = "Q. {$montoPago}";
                    }

                    $listaDesglosePrima = $datosCotizacionGestorComercial['listaDesglosePrima']['listaDesglose']['desglosePrima'];


                    if(!empty($listaDesglosePrima['tipoPrima'])) $listaDesglosePrima = [$listaDesglosePrima];
                    $dataFila = [];
                    $dataHeadersIni = [['text' => 'Desglose de prima', 'value' => 'descripcion']];
                    $dataHeadersFin = [['text' => 'Desglose de prima', 'value' => 'descripcion']];
                    foreach($listaDesglosePrima as $desglose){
                        $dataHeadersFin[] = ['text' => $desglose['tipoPrima'], 'value' => $desglose['tipoPrima']];
                        foreach($desglose['listaPrima'] as $listaPrima){
                            $dataFila[$listaPrima['descripcionPrima']]['descripcion'] = $listaPrima['descripcionPrima'];
                            $listaPrima['valorPrima'] = number_format($listaPrima['valorPrima'], 2, ',');
                            $dataFila[$listaPrima['descripcionPrima']][$desglose['tipoPrima']] = "Q. {$listaPrima['valorPrima']}";
                        }
                        if(count($dataHeadersFin) > 3){
                            $opcionesDesglosePago['headers'][] = $dataHeadersFin;
                            $dataHeadersFin = $dataHeadersIni;
                        }
                    }
                    if(count($dataHeadersFin) > 1)$opcionesDesglosePago['headers'][] = $dataHeadersFin;
                    $opcionesDesglosePago['data'] = array_values($dataFila);

                    $listaCoberturas = $datosCotizacionGestorComercial['listaCoberturas']['coberturas'];
                    $grupoCoberturas = [];

                    foreach($listaCoberturas as $listCob){
                        $idCobertura = $listCob['idCobertura'];

                        if (!in_array($idCobertura, $arrCoberturas)) {
                            continue;
                        }

                        $catGroupCob = catCoberturas::where('codigo', $idCobertura)->first();
                        if(!empty($catGroupCob->grupo)){
                            $grupoCoberturas[$catGroupCob->grupo]['lista'][] = $listCob;
                        }
                    }

                    foreach($grupoCoberturas as $idGroup => $groupCob){
                        $groupcat = catGrupoCoberturas::where('id', $idGroup)->first();
                        $grupoCoberturas[$idGroup]['nombre'] = $groupcat->nombre;
                        $grupoCoberturas[$idGroup]['descripcion'] = $groupcat->descripcion;
                    }
                }

                $arrayData[$vehi->id]['c'][$cotiTmp->id] = $procesoDataCotizacion;
                $arrayData[$vehi->id]['c'][$cotiTmp->id]['emitirPoliza'] = $cotiTmp->emitirPoliza;
                $arrayData[$vehi->id]['c'][$cotiTmp->id]['idCorrelativo'] = $cotiTmp->idCorrelativo ?? 1;
                $arrayData[$vehi->id]['c'][$cotiTmp->id]['noCot'] = $cotiTmp->numeroCotizacionAS400;
                $arrayData[$vehi->id]['c'][$cotiTmp->id]['cs'] = $arrCoberturas;
                $arrayData[$vehi->id]['c'][$cotiTmp->id]['opcionesPago'] = $opcionesDePago;
                $arrayData[$vehi->id]['c'][$cotiTmp->id]['desglosePago'] = $opcionesDesglosePago;
                $arrayData[$vehi->id]['c'][$cotiTmp->id]['grupoCoberturas'] = $grupoCoberturas;
                $arrayData[$vehi->id]['c'][$cotiTmp->id]['producto'] = $producto;
                $arrayData[$vehi->id]['c'][$cotiTmp->id]['msgAs'] = ucfirst($arrayFinal['COTIZACION_AS400.datosIdEmpresaGC.datos03.datosCotizacionGestorComercial2.msgRespuesta'] ?? '');

                $nombreTmp = $cotiTmp->vehiculo->marca->nombre ?? '';
                $lnombreTmp = $cotiTmp->vehiculo->linea->nombre ?? '';
                $vehiculoMod = $cotiTmp->vehiculo->modelo ?? '';
                $arrayData[$vehi->id]['v'] = "{$nombreTmp} - {$lnombreTmp} - año {$vehiculoMod}, {$producto}";
                $arrayData[$vehi->id]['n'] = $countVehi;
                $arrayData[$vehi->id]['descAdi'] = $arrayFinalVehi["desc_adicional"] ?? 0;
                $arrayData[$vehi->id]['descAdiC'] = $arrayFinalVehi["desc_adicional_comm"] ?? '';*/
                $countCoti++;
            }


        }

        $arrData = [
            'p' => $productosTitle,
            'c' => $coberturasByCoti,
        ];
        return $this->ResponseSuccess('Comparativa obtenida con éxito', $arrData);
    }

    public function changeStateExpired(){
        $todayDate = Carbon::now()->setTimezone('America/Guatemala')->toDateString();
        $updatedRows = Cotizacion::whereRaw("DATE(dateExpire) < ?", [$todayDate])
            ->whereNotIn('estado', ['expirada', 'finalizada', 'cancelada'])
            ->update(['estado'=> 'expirado']);
        return $this->ResponseSuccess('Actualizacion de estado exitosa', $updatedRows);
    }

    public function linkingCotizacionesPublic(Request $request){
        return $this->linkingCotizaciones($request, true);
    }

    public function linkingCotizaciones(Request $request, $public = false){
        $token = $request->get('token');
        $lToken = $request->get('lToken');

        $usuarioLogueado = auth('sanctum')->user();
        $usuarioLogueadoId = ($usuarioLogueado) ? $usuarioLogueado->id : 0;
        if (!empty($usuarioLogueadoId)) {
            $AC = new AuthController();
            if (!$AC->CheckAccess(['tareas/admin/cambio-paso'])) return $AC->NoAccess();
        }
        $userHandler = new AuthController();

        $cotizacionLToken = Cotizacion::where([['token', '=', $lToken]])->first();

        $cotizacion = Cotizacion::where([['token', '=', $token]])->first();

        if (empty($cotizacionLToken)) {
            return $this->ResponseError('COT-731', 'Tarea no válida');
        }

        if (empty($cotizacion)) {
            return $this->ResponseError('COT-732', 'Tarea no válida');
        }

        $producto = $cotizacion->producto;
        if (empty($producto)) {
            return $this->ResponseError('COT-700', 'Flujo no válido');
        }

        $flujo = $producto->flujo->first();
        if (empty($flujo)) {
            return $this->ResponseError('COT-701', 'Flujo no válido');
        }

        $flujoConfig = @json_decode($flujo->flujo_config, true);
        if (!is_array($flujoConfig)) {
            return $this->ResponseError('COT-701', 'Error al interpretar flujo, por favor, contacte a su administrador');
        }

        $cotizacion->codigoAgente = $cotizacionLToken->codigoAgente;
        $cotizacion->save();

        $detalle = CotizacionDetalle::where('cotizacionId', $cotizacionLToken->id)->first();
        //$campoExistenteP = CotizacionDetalle::where('cotizacionId', $cotizacion->id)->first();
        foreach ($detalle as $campoLToken) {
            $newCampo = new CotizacionDetalle();
            $newCampo->cotizacionId = $cotizacion->id;
            $newCampo->seccionKey = $campoLToken->seccionKey;
            $newCampo->campo = $campoLToken->campo;
            $newCampo->useForSearch = $campoLToken->useForSearch;
            $newCampo->tipo = $campoLToken->tipo;
            $newCampo->valorLong = $campoLToken->valorLong;
            $newCampo->expToken = $campoLToken->expToken;
            $newCampo->valorShow = $campoLToken->valorShow;
            $newCampo->isFile = $campoLToken->isFile;
            $newCampo->save();
        }


        /*foreach ($flujoConfig['nodes'] as $nodo) {
            if (!empty($nodo['formulario']['secciones']) && count($nodo['formulario']['secciones']) > 0) {
                foreach ($nodo['formulario']['secciones'] as $keySeccion => $seccion) {
                    foreach ($seccion['campos'] as $campo) {

                    }
                }
            }
        }*/

        // duplica los vehiculos y cotizaciones
        $cotizaciones = CotizacionDetalleVehiculo::where('cotizacionId', $cotizacionLToken->id)->get();

        foreach ($cotizaciones as $item) {
            $vehiculoNew = new CotizacionDetalleVehiculo();

            $vehiculoNew->cotizacionId = $cotizacion->id;
            $vehiculoNew->marcaId = $item->marcaId;
            $vehiculoNew->lineaId = $item->lineaId;
            $vehiculoNew->tipoId = $item->tipoId;
            $vehiculoNew->noPasajeros = $item->noPasajeros;
            $vehiculoNew->noChasis = $item->noChasis;
            $vehiculoNew->noMotor = $item->noMotor;
            $vehiculoNew->modelo = $item->modelo;
            $vehiculoNew->valorProm = $item->valorProm;
            $vehiculoNew->valorPromDef = $item->valorPromDef;
            $vehiculoNew->placa = $item->placa;
            $vehiculoNew->vehiculoNuevo = $item->vehiculoNuevo;
            $vehiculoNew->altoRiesgoDisp = $item->altoRiesgoDisp;
            $vehiculoNew->autoInspeccionLink = $item->autoInspeccionLink;
            $vehiculoNew->inspeccionId = $item->inspeccionId;
            $vehiculoNew->save();
            //var_dump($vehiculoNew->save());

            // copiar cotizaciones
            $cotizacionesTmp = CotizacionDetalleVehiculoCotizacion::where('cotizacionDetalleVehiculoId', $item->id)->get();
            foreach ($cotizacionesTmp as $tmpCot) {
                $newCot = new CotizacionDetalleVehiculoCotizacion();
                $newCot->cotizacionDetalleVehiculoId = $vehiculoNew->id;
                $newCot->cotizacionId = $cotizacion->id;
                $newCot->productoId = $tmpCot->productoId;
                $newCot->tarifaId = $tmpCot->tarifaId;
                $newCot->formaPagoId = $tmpCot->formaPagoId;
                $newCot->numeroPagos = $tmpCot->numeroPagos;
                $newCot->descuentoPorcentaje = $tmpCot->descuentoPorcentaje;
                $newCot->catProductoTarifaDescRecargoId = $tmpCot->catProductoTarifaDescRecargoId;
                $newCot->recargoPorcentaje = $tmpCot->recargoPorcentaje;
                $newCot->emitirPoliza = $tmpCot->emitirPoliza;
                $newCot->numeroCotizacionAS400 = $tmpCot->numeroCotizacionAS400;
                $newCot->descuentoId = $tmpCot->descuentoId;
                $newCot->frecuenciaPagos = $tmpCot->frecuenciaPagos;
                $newCot->idCorrelativo = $tmpCot->idCorrelativo;
                $newCot->primaNeta = $tmpCot->primaNeta;
                $newCot->primaTotal = $tmpCot->primaTotal;
                $newCot->save();

                // copia las coberturas
                $cotizacionesTmp = CotizacionDetalleVehiculoCotizacionCobertura::where('cotizacionDetalleVehiculoCotId', $tmpCot->id)->get();

                foreach ($cotizacionesTmp as $tmpCober) {
                    $newCober = new CotizacionDetalleVehiculoCotizacionCobertura();
                    $newCober->cotizacionDetalleVehiculoCotId = $newCot->id;
                    $newCober->coberturaId = $tmpCober->coberturaId;
                    $newCober->codigoCobertura = $tmpCober->codigoCobertura;
                    $newCober->monto = $tmpCober->monto;
                    $newCober->save();
                }
            }

        }

        return $this->ResponseSuccess('Cotización copiada con exito');

    }

    public function reemplazarValorGroup($array, $text, $n){
        $result = $text;
        foreach($array as $a){
            $idField = trim($a['id']);
            $groupField = trim($a['group']);
            $token = "{{" . $idField . "}}";
            $stringData = "{{" . $groupField.'_'. $idField .'_'. $n . "}}";
            $result = preg_replace("/" . preg_quote($token) . "/", $stringData, $result);
        }
        return $result;
    }

    function encriptar($data/*, $key, $iv */) {
        $key = hex2bin(env('ENCRYPTION_KEY'));
        $iv = hex2bin(env('ENCRYPTION_IV'));
        return base64_encode(@openssl_encrypt($data, 'AES-128-ECB', $key, 0, $iv));
    }

    function desencriptar($data) {
        $key = hex2bin(env('ENCRYPTION_KEY'));
        $iv = hex2bin(env('ENCRYPTION_IV'));
        return @openssl_decrypt(base64_decode($data), 'AES-128-ECB', $key, 0, $iv);
    }

    public function calculateAccidentRate(Request $request) {

        $token = $request->get('token');
        $usuarioLogueado = auth('sanctum')->user();
        $usuarioLogueadoId = ($usuarioLogueado) ? $usuarioLogueado->id : 0;

        if (!empty($usuarioLogueadoId)) {
            $AC = new AuthController();
            if (!$AC->CheckAccess(['tareas/admin/cambio-paso'])) return $AC->NoAccess();
        }

        // Actual
        $userHandler = new AuthController();
        $CalculateAccess = $userHandler->CalculateAccess();

        $siniestralidadCliente = 0;

        // si es supervisor
        $arrUsers = false;
        if (in_array($usuarioLogueadoId, $CalculateAccess['sup'])) {
            $arrUsers = $CalculateAccess['all'];
        }
        else {
            $arrUsers = $CalculateAccess['det'];
        }

        $item = Cotizacion::where([['token', '=', $token]])->first();

        if (empty($item)) {
            return $this->ResponseError('COT-015', 'Tarea inválida');
        }

        $wsData = $this->wsCustomAuto('SINIESTRALIDAD_AS400', $item->id, null, $item->campos);

        if (count($wsData['errors']) > 0) {
            return $this->ResponseError('COTW-004', $wsData['errors'][0]);
        }

        /*var_dump($wsData);
        die;*/

        $procesos = [
          "pf" => [],
          "url" => getenv('URL_COTIZADOR')."/automoviles/cotizador/api/gestor_comercial",
          "type" => null,
          "isXML" => false,
          "header" => "",
          "method" => "post",
          "salida" => [],
          "authUrl" => getenv('URL_COTIZADOR')."/session/login",
          "entrada" => "",
          "authType" => "elroble",
          "authPayload" => "{\n    \"usuario\": \"".getenv('ACSEL_AUTO_USER')."\",\n    \"contrasenia\": \"".getenv('ACSEL_AUTO_PASS')."\",\n    \"origen\": \"services\"\n}",
          "respuestaXML" => true,
          "tipoRecibido" => false,
          "configuracionS" => [],
          "identificadorWs" => "SINIESTRALIDAD_AS400"
        ];

        // var_dump($procesos);

        // OVERRIDES A XML
        if (count($wsData['list']) > 0) {
            $response = ['cliente' => [], 'vehiculo' => [], 'siniesBlock' => 0];

            foreach ($wsData['list'] as $tmpWsData) {
                $procesos['entrada'] = $tmpWsData['entrada'];
                $resultado = $this->consumirServicio($procesos, $tmpWsData['data'] ?? $item->campos, '', $item);

                $dataLog = "<h5>Data enviada</h5> <br> " . htmlentities($resultado['log']['enviado'] ?? '') . " <br><br> <h5>Headers enviados</h5> <br> ".($resultado['log']['enviadoH'] ?? '')." <br><br> <h5>Data recibida</h5> <br> " . htmlentities($resultado['log']['recibido'] ?? '') . " <br><br> <h5>Data procesada</h5> <br> " . htmlentities(print_r($resultado['data'] ?? '', true));

                $bitacoraCoti = new CotizacionBitacora();
                $bitacoraCoti->cotizacionId = $item->id;
                $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                $bitacoraCoti->onlyPruebas = 1;
                $bitacoraCoti->dataInfo = $dataLog;
                $bitacoraCoti->log = "Cálculo de siniestralidad";
                $bitacoraCoti->save();


                if (empty($resultado['status']) || (empty($resultado['data'])
                    || (
                        empty($resultado['data']['SINIESTRALIDAD_AS400.datosIdEmpresaGC.mensajeRespuesta']) ||
                        ($resultado['data']['SINIESTRALIDAD_AS400.datosIdEmpresaGC.mensajeRespuesta'] !== 'SATISFACTORIO' &&
                        strpos($resultado['data']['SINIESTRALIDAD_AS400.datosIdEmpresaGC.mensajeRespuesta'], 'no existe codigo') !== false
                        ))
                    )
                ) {
                    if($tmpWsData['tipo'] === 'cliente') $response[$tmpWsData['tipo']]['errors'][] = $resultado['data'];
                    else {
                        $dataToShow = [
                            'orden' => count($response[$tmpWsData['tipo']]) + 1,
                            'placa' => $tmpWsData['placa'],
                            'motor' => $tmpWsData['motor'],
                            'chasis' => $tmpWsData['chasis'],
                            'color' => '',
                            'siniestralidad' => !empty($resultado['data']) ? ($resultado['data']['SINIESTRALIDAD_AS400.datosIdEmpresaGC.mensajeRespuesta']?? '') : 'Sin Respuesta',
                        ];
                        $response[$tmpWsData['tipo']][] = $dataToShow;
                    };
                }
                else {
                    if($tmpWsData['tipo'] === 'cliente') {

                        $response['poliza'] = [];
                        //$response[$tmpWsData['tipo']]['success'][] = $resultado['data'];

                        $twoYears = Carbon::now()->subYears(2);
                        $conteoPolizas = 0;
                        //$porcentajeSiniestralidad = 0;
                        $porcentajeFinal = 0;

                        // var_dump($resultado['log']['recibidoProcesado']);
                        /*var_dump($resultado['log']['recibidoProcesado']['datosIdEmpresaGC']['datos03']['datosConsultaPolizasGestorComercial']);
                        die;*/

                        if (!empty($resultado['log']['recibidoProcesado']['datosIdEmpresaGC']['datos03']['datosConsultaPolizasGestorComercial']['listaPolizas']['lista'])) {

                            if (isset($resultado['log']['recibidoProcesado']['datosIdEmpresaGC']['datos03']['datosConsultaPolizasGestorComercial']['listaPolizas']['lista']['poliza'])) {
                                $arrRecorrer = $resultado['log']['recibidoProcesado']['datosIdEmpresaGC']['datos03']['datosConsultaPolizasGestorComercial']['listaPolizas'];
                            }
                            else {
                                $arrRecorrer = $resultado['log']['recibidoProcesado']['datosIdEmpresaGC']['datos03']['datosConsultaPolizasGestorComercial']['listaPolizas']['lista'];
                            }

                            $montoMayor = 0;
                            foreach ($arrRecorrer as $poliza) {
                                $dataToShow = [
                                    'orden' => count($response['poliza']) + 1,
                                    'poliza' => $poliza['poliza'],
                                    'certificado' => $poliza['certificado'],
                                    'vigenciaDesde' => $poliza['vigenciaDesde'],
                                    'vigenciaHasta' => $poliza['vigenciaHasta'],
                                    'pagosPendientes' => $poliza['pagosPendientes'],
                                    'primaTotal' => $poliza['primaTotal'],
                                    'estado' => $poliza['estado'],
                                    'porcentajeSiniestralidad' => $poliza['porcentajeSiniestralidad'],
                                    'diasMorosidad' => $poliza['diasMorosidad'],
                                ];
                                $response['poliza'][] = $dataToShow;
                                $vigenciaHasta = Carbon::createFromFormat('d/m/Y', $poliza['vigenciaHasta']);
                                if ($vigenciaHasta->gt($twoYears)) {
                                    $tmpPorcent = floatval($poliza['porcentajeSiniestralidad']);
                                    if ($tmpPorcent > $montoMayor) {
                                        $montoMayor = $tmpPorcent;
                                    }
                                    // $conteoPolizas++;
                                }
                            }

                            $porcentajeFinal = $montoMayor;
                        }
                        // var_dump($porcentajeFinal);
                        // $porcentajeFinal = ($conteoPolizas > 0 ? $porcentajeSiniestralidad / $conteoPolizas: 0);
                        $cotiDetalelSin = CotizacionDetalle::where('campo', 'SYS_COT_SINIESTRALIDAD')->where('cotizacionId', $item->id)->first();

                        if (empty($cotiDetalelSin)) {
                            $cotiDetalelSin = new CotizacionDetalle();
                        }

                        $cotiDetalelSin->cotizacionId = $item->id;
                        $cotiDetalelSin->seccionKey = 0;
                        $cotiDetalelSin->campo = 'SYS_COT_SINIESTRALIDAD';
                        $cotiDetalelSin->label = '';
                        $cotiDetalelSin->useForSearch = 0;
                        $cotiDetalelSin->tipo = 'default';
                        $cotiDetalelSin->valorLong = $porcentajeFinal;
                        $cotiDetalelSin->save();


                        $recargoSiniestralidad = RecargaSiniestralidad::where([['valorMin', '<=', $porcentajeFinal], ['valorMax', '>=', $porcentajeFinal]])->first();

                        /*var_dump($porcentajeFinal);
                        var_dump($recargoSiniestralidad);
                        die();*/

                        $recargoFinal = 0;
                        if (!empty($recargoSiniestralidad)) {
                            $recargoFinal = $recargoSiniestralidad->recargo;
                        }

                        $cotiDetalelRec = CotizacionDetalle::where('campo', 'SYS_COT_REC')->where('cotizacionId', $item->id)->first();

                        if (empty($cotiDetalelRec)) {
                            $cotiDetalelRec = new CotizacionDetalle();
                        }

                        $cotiDetalelRec->cotizacionId = $item->id;
                        $cotiDetalelRec->seccionKey = 0;
                        $cotiDetalelRec->campo = 'SYS_COT_REC';
                        $cotiDetalelRec->label = '';
                        $cotiDetalelRec->useForSearch = 0;
                        $cotiDetalelRec->tipo = 'default';
                        $cotiDetalelRec->valorLong = $recargoFinal;
                        $cotiDetalelRec->save();

                        $siniestralidadCliente = $recargoFinal;

                        // Si el cliente tiene siniestralidad
                        if ($item->siniesBlock !== 2 && $recargoFinal > 0) {
                            $item->siniesBlock = 1; // 1 bloqueado, 2 aprobado, 0 sin bloqueo
                            $item->save();
                        }

                        $response['siniesBlock'] = $item->siniesBlock;
                        $response['cliente'] = $siniestralidadCliente;

                    }
                    else {
                        $dataToShow = [
                            'orden' => count($response[$tmpWsData['tipo']]) + 1,
                            'placa' => $resultado['data']['SINIESTRALIDAD_AS400.datosIdEmpresaGC.datos03.consultaDatosVehiculoGestorComercial.placa'] ?? $tmpWsData['placa'],
                            'motor' => $resultado['data']['SINIESTRALIDAD_AS400.datosIdEmpresaGC.datos03.consultaDatosVehiculoGestorComercial.motor'] ?? $tmpWsData['motor'],
                            'chasis' => $resultado['data']['SINIESTRALIDAD_AS400.datosIdEmpresaGC.datos03.consultaDatosVehiculoGestorComercial.chasis'] ?? $tmpWsData['chasis'],
                            'color' => $resultado['data']['SINIESTRALIDAD_AS400.datosIdEmpresaGC.datos03.consultaDatosVehiculoGestorComercial.color'] ?? '',
                            'siniestralidad' => $resultado['data']['SINIESTRALIDAD_AS400.datosIdEmpresaGC.datos03.consultaDatosVehiculoGestorComercial.msgSiniestralidad'] ?? 'Sin siniestralidad',
                        ];
                        $response[$tmpWsData['tipo']][] = $dataToShow;
                    };
                }
            }
            return $this->ResponseSuccess('SIN-10', $response);
        }
        else {
            return $this->ResponseError('SIN-20', "Ha ocurrido realizando el proceso");
        }
    }

    public function verInspecciones(Request $request) {

        $token = $request->get('token');
        $usuarioLogueado = auth('sanctum')->user();
        $usuarioLogueadoId = ($usuarioLogueado) ? $usuarioLogueado->id : 0;

        if (!empty($usuarioLogueadoId)) {
            $AC = new AuthController();
            if (!$AC->CheckAccess(['inpecciones/agendar-en-flujo'])) return $AC->NoAccess();
        }

        // Actual
        $userHandler = new AuthController();
        $CalculateAccess = $userHandler->CalculateAccess();

        $cotizacion = Cotizacion::where([['token', '=', $token]])->first();

        if (empty($cotizacion)) {
            return $this->ResponseError('COT-015', 'Tarea inválida');
        }


        $detalleVehi = $cotizacion->vehiculos;

        $arrResponse = [];
        foreach ($detalleVehi as $vehi) {

            $arrResponse[$vehi->id] = [
                'autoId' => $vehi->id,
                'autoInspeccion' => $vehi->autoInspeccionLink,
                'inspeccionId' => $vehi->inspeccionId,
                'chasis' => $vehi->noChasis,
                'motor' => $vehi->noMotor,
                'modelo' => $vehi->modelo,
                'placa' => $vehi->placa,
                'direccion' => $vehi->direccion ?? '',
                'marca' => $vehi->marca->nombre ?? 'Marca no disponible',
                'linea' => $vehi->linea->nombre ?? 'Línea no disponible',
            ];
        }
        return $this->ResponseSuccess('Inspecciones inválida', $arrResponse);
    }

    public function execProcess(Request $request) {
        $token = $request->get('token');
        $nodoId = $request->get('nodoId');
        $usuarioLogueado = auth('sanctum')->user();
        $usuarioLogueadoId = ($usuarioLogueado) ? $usuarioLogueado->id : 0;

        if (!empty($usuarioLogueadoId)) {
            $AC = new AuthController();
            if (!$AC->CheckAccess(['tareas/admin/cambio-paso'])) return $AC->NoAccess();
        }

        $item = Cotizacion::where([['token', '=', $token]])->first();

        if (empty($item)) {
            return $this->ResponseError('COT-015', 'Tarea inválida');
        }

        // Recorro campos para tener sus datos de configuración
        $flujoConfig = $this->getFlujoFromCotizacion($item);
        $fieldsData = [];
        $nodoProcess = null;
        if (!empty($flujoConfig['data']['nodes'])) {
            foreach ($flujoConfig['data']['nodes'] as $nodo) {
                //$resumen
                if($nodoId === $nodo['id'])  $nodoProcess = $nodo;
            }
        }

        if (empty($nodoProcess)) {
            return $this->ResponseError('PRC-010', 'No encontro el ejecutable, por favor intente de nuevo');
        }

        $flujo['next'] = $nodoProcess;

        // valida si no hay un ws custom
        $wsData = $this->wsCustomAuto($flujo['next']['procesos'][0]['identificadorWs'] ?? false, $item->id, $flujo['next']['procesos'][0]['entrada'], $item->campos);

        // si es cotización
        if ($wsData['type'] === 'COTIZACION_AS400') {
            // se borran las normales
            CotizacionDetalle::where('cotizacionid', $item->id)->whereNotNull('cotizacionDetalleVehiculoCotId')->delete();
        }
        if(!empty($flujo['next']['procesos'][0]['identificadorWs'])){
            CotizacionDetalle::where('cotizacionid', $item->id)->where('campo', 'like', $flujo['next']['procesos'][0]['identificadorWs'].'.%')->delete();
        }

        if (count($wsData['errors']) > 0) {
            return $this->ResponseError('COTW-004', $wsData['errors'][0]);
        }

        // si se tiene que ejecutar por los vehículos
        if (!empty($flujo['next']['procesos'][0]['execVehi'])) {

            $cotizacionVehiculo = CotizacionDetalleVehiculo::where('cotizacionId', $item->id)->with('linea')->get();

            $wsData = [
                'type' => '',
                'list' => [],
                'errors' => [],
            ];

            $dataSendTmp = $item->campos->toArray();

            foreach ($cotizacionVehiculo as $vehi) {

                $dataSendTmp[] = ['id' => 'VEHI_NO_CHASIS', 'campo' => 'VEHI_NO_CHASIS', 'valorLong' => $vehi->noChasis ?? ''];
                $dataSendTmp[] = ['id' => 'VEHI_NO_MOTOR', 'campo' => 'VEHI_NO_MOTOR', 'valorLong' => $vehi->noMotor ?? ''];
                $dataSendTmp[] = ['id' => 'VEHI_MODELO', 'campo' => 'VEHI_MODELO', 'valorLong' => $vehi->modelo ?? ''];
                $dataSendTmp[] = ['id' => 'VEHI_PLACA', 'campo' => 'VEHI_PLACA', 'valorLong' => $vehi->placa ?? ''];
                $dataSendTmp[] = ['id' => 'VEHI_VALOR', 'campo' => 'VEHI_VALOR', 'valorLong' => $vehi->valorProm ?? ''];
                $dataSendTmp[] = ['id' => 'VEHI_MARCA', 'campo' => 'VEHI_MARCA', 'valorLong' => $vehi->marca->codigo ?? ''];
                $dataSendTmp[] = ['id' => 'VEHI_LINEA', 'campo' => 'VEHI_LINEA', 'valorLong' => $vehi->linea->codigo ?? ''];

                $wsData['list'][] = [
                    'entrada' => $flujo['next']['procesos'][0]['entrada'],
                    'vehiculoId' => $vehi->id,
                    'data' => $dataSendTmp,
                ];
            }
        }

        // OVERRIDES A XML
        if (count($wsData['list']) > 0) {
            foreach ($wsData['list'] as $tmpWsData) {
                $flujo['next']['procesos'][0]['entrada'] = $tmpWsData['entrada'];

                $resultado = $this->consumirServicio($flujo['next']['procesos'][0], $tmpWsData['data'] ?? $item->campos, $flujo['next']['id'] ?? '', $item);

                /*var_dump($resultado);
                die;*/

                $dataLog = "<h5>Data enviada</h5> <br> " . htmlentities($resultado['log']['enviado'] ?? '') . " <br><br> <h5>Headers enviados</h5> <br> ".($resultado['log']['enviadoH'] ?? '')." <br><br> <h5>Data recibida</h5> <br> " . htmlentities($resultado['log']['recibido'] ?? '') . " <br><br> <h5>Data procesada</h5> <br> " . htmlentities(print_r($resultado['data'] ?? '', true));
                $identificadorForWs = $flujo['next']['procesos'][0]['identificadorWs'];

                // si es menores con cobertura
                if (!empty($resultado['data'][$identificadorForWs .'.datosIdEmpresaGC.datos03.menoresEdadConCoberturasGestorComercial.msgRespuesta']) && trim($resultado['data'][$identificadorForWs .'.datosIdEmpresaGC.datos03.menoresEdadConCoberturasGestorComercial.msgRespuesta']) === 'TRANSACCION NO CORRESPONDE') {
                    $resultado['data'][$identificadorForWs .'.datosIdEmpresaGC.mensajeRespuesta'] = 'SATISFACTORIO';
                }

                if (empty($resultado['status'])
                    || empty($resultado['data'])
                    ||(in_array($identificadorForWs, ['EMISION_AS400', 'SINIESTRALIDAD_AS400', 'EMISION_DATOS_CLIENTE_AS400', 'COTIZACION_AS400'])
                    && (empty($resultado['data'][$identificadorForWs .'.datosIdEmpresaGC.mensajeRespuesta']) || $resultado['data'][$identificadorForWs .'.datosIdEmpresaGC.mensajeRespuesta'] !== 'SATISFACTORIO'))) {
                    $bitacoraCoti = new CotizacionBitacora();
                    $bitacoraCoti->cotizacionId = $item->id;
                    $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                    $bitacoraCoti->onlyPruebas = 1;
                    $bitacoraCoti->dataInfo = $dataLog;
                    $bitacoraCoti->log = "Error ejecutando proceso. Saliendo de Campo ejecutable, URL: {$flujo['next']['procesos'][0]['url']}";
                    $bitacoraCoti->save();

                    //dd($resultado);
                    $mensajeResult = $resultado['msg'];
                    if(!empty($resultado['data'][$identificadorForWs .'.datosIdEmpresaGC.mensajeRespuesta'])) $mensajeResult = $resultado['data'][$identificadorForWs .'.datosIdEmpresaGC.mensajeRespuesta'];
                    if(!empty($resultado['data'][$identificadorForWs . '.0']) && strpos($resultado['data'][$identificadorForWs . '.0'], "500 :") === 0) $mensajeResult = 'Error en servicio';

                    if (!empty($resultado['msgErrP'])) {
                        return $this->ResponseError('COTW-001', $resultado['msgErrP']);
                    }
                    else {
                        return $this->ResponseError('COTW-001', "Ha ocurrido realizando el proceso de envío de datos. {$mensajeResult}");
                    }
                    // return $this->ResponseError('COTW-001', "Ha ocurrido realizando el proceso de envío de datos. {$mensajeResult}");
                }
                else {
                    if(!empty($tmpWsData['process']) && $tmpWsData['process'] === '7'){
                        $dataMetodoPago = DataMetodoPago::where('cotizacionesDetalleVehiculoCotId', $tmpWsData['cotizacionDetalleVehiculoCotId'])->first();

                        if(!empty($dataMetodoPago)){
                            $pattern = "/<numeroCuentaTarjeta>[\d-]+<\/numeroCuentaTarjeta>/";
                            $replace = "<numeroCuentaTarjeta>XXXX-XXXX-XXXX-{$dataMetodoPago->lastDigits}</numeroCuentaTarjeta>";
                            $dataLog = preg_replace($pattern, $replace, $dataLog);
                        }
                        //$dataMetodoPago->datac = null;
                        //$dataMetodoPago->save();
                    }

                    // Si tiene identificador de WS, se guardan los campos de una
                    if (($wsData['type'] === 'COTIZACION_AS400' || $wsData['type'] === 'EMISION_DATOS_CLIENTE_AS400' || !empty($flujo['next']['procesos'][0]['execVehi'])) && !empty($flujo['next']['procesos'][0]['identificadorWs'])) {
                        foreach ($resultado['data'] as $campoKey => $campoValue) {

                            // data si es cotización de vehículo
                            $campo = null;
                            if (!empty($tmpWsData['cotizacionDetalleVehiculoCotId'])) {
                                // si es cotización de vehículo se borran las anteriores

                                $campo = CotizacionDetalle::where('campo', $campoKey)->where('cotizacionId', $item->id)->where('cotizacionDetalleVehiculoCotId', $tmpWsData['cotizacionDetalleVehiculoCotId'])->first();
                                if (empty($campo)) {
                                    $campo = new CotizacionDetalle();
                                }
                                $campo->cotizacionDetalleVehiculoCotId = $tmpWsData['cotizacionDetalleVehiculoCotId'];
                            }
                            else {
                                $campo = CotizacionDetalle::where('campo', $campoKey)->where('cotizacionId', $item->id)->first();
                                if (empty($campo)) {
                                    $campo = new CotizacionDetalle();
                                }
                            }

                            if (!empty($tmpWsData['vehiculoId'])) {
                                $campo->cotizacionVehiculoId = $tmpWsData['vehiculoId'];
                            }

                            $campo->cotizacionId = $item->id;
                            $campo->campo = $campoKey;
                            if (is_array($campoValue)) {
                                $campo->valorLong = json_encode($campoValue, JSON_FORCE_OBJECT);
                            }
                            else {
                                $campo->valorLong = $campoValue;
                            }
                            $campo->save();

                            // guarda el número de cotización
                            if ($campoKey === 'COTIZACION_AS400.datosIdEmpresaGC.datos03.datosCotizacionGestorComercial2.numeroCotizacion') {
                                $cotTmp = CotizacionDetalleVehiculoCotizacion::where('id', $tmpWsData['cotizacionDetalleVehiculoCotId'])->first();
                                $cotTmp->numeroCotizacionAS400 = $campoValue;
                                $cotTmp->save();
                            }
                        }
                    }

                    $bitacoraCoti = new CotizacionBitacora();
                    $bitacoraCoti->cotizacionId = $item->id;
                    $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                    $bitacoraCoti->onlyPruebas = 1;
                    $bitacoraCoti->dataInfo = "<h5>URL:</h5> {$flujo['next']['procesos'][0]['url']} <br/><br/>" . $dataLog;
                    $bitacoraCoti->log = "Ejecutado proceso saliendo de Campo Ejecutable";
                    $bitacoraCoti->save();
                }
            }

        }
        else {

            $resultado = $this->consumirServicio($flujo['next']['procesos'][0], $item->campos, $flujo['next']['id'] ?? '', $item);

            /*var_dump($resultado);
            die;*/
            //dd($resultado);
            $identificadorForWs = $flujo['next']['procesos'][0]['identificadorWs'];
            $dataLog = "<h5>Data enviada</h5> <br> " . htmlentities($resultado['log']['enviado'] ?? '') . " <br><br> <h5>Headers enviados</h5> <br> ".($resultado['log']['enviadoH'] ?? '')." <br><br> <h5>Data recibida</h5> <br> " . htmlentities($resultado['log']['recibido'] ?? '') . " <br><br> <h5>Data procesada</h5> <br> " . htmlentities(print_r($resultado['data'] ?? '', true));

            if (empty($resultado['status'])
            || empty($resultado['data'])
            ||(in_array($identificadorForWs, ['EMISION_AS400', 'SINIESTRALIDAD_AS400', 'EMISION_DATOS_CLIENTE_AS400', 'COTIZACION_AS400', 'WS_CLIENTE'])
            && (empty($resultado['data'][$identificadorForWs .'.datosIdEmpresaGC.mensajeRespuesta']) || $resultado['data'][$identificadorForWs .'.datosIdEmpresaGC.mensajeRespuesta'] !== 'SATISFACTORIO'))
            ) {
                $bitacoraCoti = new CotizacionBitacora();
                $bitacoraCoti->cotizacionId = $item->id;
                $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                $bitacoraCoti->onlyPruebas = 1;
                $bitacoraCoti->dataInfo = $dataLog;
                $bitacoraCoti->log = "Error ejecutando proceso. Saliendo de \"Campo ejecutable\", URL: {$flujo['next']['procesos'][0]['url']}";
                $bitacoraCoti->save();

                //dd($resultado);
                $mensajeResult = $resultado['msg'];
                    if(!empty($resultado['data'][$identificadorForWs .'.datosIdEmpresaGC.mensajeRespuesta'])) $mensajeResult = $resultado['data'][$identificadorForWs .'.datosIdEmpresaGC.mensajeRespuesta'];
                    if(!empty($resultado['data'][$identificadorForWs . '.0']) && strpos($resultado['data'][$identificadorForWs . '.0'], "500 :") === 0) $mensajeResult = 'Error en servicio';

                    if (!empty($resultado['msgErrP'])) {
                        return $this->ResponseError('COTW-001', $resultado['msgErrP']);
                    }
                    else {
                        return $this->ResponseError('COTW-001', "Ha ocurrido realizando el proceso de envío de datos. {$mensajeResult}");
                    }

                $manejoErroresPConf = (!empty($flujo['next']['procesos'][0]['manErrC'])) ? @json_decode($flujo['next']['procesos'][0]['manErrC'], true) : [];
                $manejoErroresPConf = $manejoErroresPConf[0] ?? [];
                if (!empty($manejoErroresPConf['procesoOnError'])) {

                    $requestTmpReverse = new \Illuminate\Http\Request();
                    $requestTmpReverse->replace(['token' => $item->token, 'nodoId' => $manejoErroresPConf['procesoOnError']]);
                    $this->execProcess($requestTmpReverse);
                }
            }
            else {

                // Si tiene identificador de WS, se guardan los campos de una
                if (!empty($flujo['next']['procesos'][0]['identificadorWs'])) {

                    foreach ($resultado['data'] as $campoKey => $campoValue) {
                        $campo = CotizacionDetalle::where('campo', $campoKey)->where('cotizacionId', $item->id)->first();
                        if (empty($campo)) {
                            $campo = new CotizacionDetalle();
                        }
                        $campo->cotizacionId = $item->id;
                        $campo->campo = $campoKey;
                        if (is_array($campoValue)) {
                            $campo->valorLong = json_encode($campoValue, JSON_FORCE_OBJECT);
                        }
                        else {
                            $campo->valorLong = $campoValue;
                        }
                        $campo->save();
                    }
                }

                $bitacoraCoti = new CotizacionBitacora();
                $bitacoraCoti->cotizacionId = $item->id;
                $bitacoraCoti->usuarioId = $usuarioLogueadoId;
                $bitacoraCoti->onlyPruebas = 1;
                $bitacoraCoti->dataInfo = "<h5>URL:</h5> {$flujo['next']['procesos'][0]['url']} <br/><br/>" . $dataLog;
                $bitacoraCoti->log = "Ejecutado proceso saliendo de \"Campo ejecutable\"";
                $bitacoraCoti->save();
            }

        }

        return $this->ResponseSuccess('Proceso ejecutado con exito');

    }

    function calculateDataVehicule($cotId) {

        $cacheH = ClassCache::getInstance();

        $response = $cacheH->get("VEHI_CALCULATED_DATA_{$cotId}");
        if (empty($response)) {

            $response = [];
            $dataVehicle = CotizacionDetalleVehiculo::where('cotizacionId', $cotId)
                ->orderBy('id', 'asc')
                ->get();
            foreach($dataVehicle as $keyveh => $dataveh){
                $sub =  strval($keyveh + 1);
                $sub = 'veh' . $sub . '|';

                $response[] = ['tipo'=> 'default', 'campo' => $sub . 'id', 'valorLong' => $dataveh->id];
                $response[] = ['tipo'=> 'default', 'campo' => $sub . 'marca', 'valorLong' => $dataveh->marca->nombre ?? ''];
                $response[] = ['tipo'=> 'default', 'campo' => $sub . 'linea', 'valorLong' => $dataveh->linea->nombre ?? ''];
                $response[] = ['tipo'=> 'default', 'campo' => $sub . 'tipo', 'valorLong' => $dataveh->tipo->nombre ?? ''];
                $response[] = ['tipo'=> 'default', 'campo' => $sub . 'noPasajeros', 'valorLong' => $dataveh->noPasajeros ?? ''];
                $response[] = ['tipo'=> 'default', 'campo' => $sub . 'noChasis', 'valorLong' => $dataveh->noChasis ?? ''];
                $response[] = ['tipo'=> 'default', 'campo' => $sub . 'noMotor', 'valorLong' => $dataveh->noMotor ?? ''];
                $response[] = ['tipo'=> 'default', 'campo' => $sub . 'modelo', 'valorLong' => $dataveh->modelo ?? ''];
                $response[] = ['tipo'=> 'default', 'campo' => $sub . 'valorProm', 'valorLong' => $dataveh->valorProm ?? ''];
                $response[] = ['tipo'=> 'default', 'campo' => $sub . 'valorPromDef', 'valorLong' => $dataveh->valorPromDef ?? ''];
                $response[] = ['tipo'=> 'default', 'campo' => $sub . 'placa', 'valorLong' => $dataveh->placa ?? ''];
                $response[] = ['tipo'=> 'default', 'campo' => $sub . 'vehiculoNuevo', 'valorLong' => $dataveh->vehiculoNuevo? 'Nuevo' : 'Usado'];
                $response[] = ['tipo'=> 'default', 'campo' => $sub . 'altoRiesgoDisp', 'valorLong' => $dataveh->altoRiesgoDisp? 'SI' : 'NO'];

                $dataCotizacion = $dataveh->subCotizacion;

                foreach($dataCotizacion as $keycot => $datacot){
                    $cotSub =  strval($keycot + 1);
                    $cotSub =  $sub . 'cot' . $cotSub . '|';

                    $response[] = ['tipo'=> 'default', 'campo' => $cotSub . 'id', 'valorLong' => $datacot->id];
                    $response[] = ['tipo'=> 'default', 'campo' => $cotSub . 'producto', 'valorLong' => $datacot->producto->nombre ?? ''];
                    $response[] = ['tipo'=> 'default', 'campo' => $cotSub . 'tarifa', 'valorLong' => $datacot->tarifa->nombre ?? ''];
                    $response[] = ['tipo'=> 'default', 'campo' => $cotSub . 'descuentoPorcentaje', 'valorLong' => $datacot->descuentoPorcentaje ?? ''];
                    //catProductoTarigaDescRecargoId
                    $response[] = ['tipo'=> 'default', 'campo' => $cotSub . 'formaPago', 'valorLong' => $datacot->formaPago ? $datacot->formaPago->descripcion : 'Sin forma de pago'];
                    $response[] = ['tipo'=> 'default', 'campo' => $cotSub . 'numeroPagos', 'valorLong' => $datacot->numeroPagos ?? 0];
                    $response[] = ['tipo'=> 'default', 'campo' => $cotSub . 'primaNeta', 'valorLong' => $datacot->primaNeta ?? 0];
                    $response[] = ['tipo'=> 'default', 'campo' => $cotSub . 'primaTotal', 'valorLong' => $datacot->primaTotal ?? 0];

                    $response[] = ['tipo'=> 'default', 'campo' => $cotSub . 'recargoPorcentaje', 'valorLong' => $datacot->recargoPorcentaje ?? ''];
                    $response[] = ['tipo'=> 'default', 'campo' => $cotSub . 'emitirPoliza', 'valorLong' => $datacot->emitirPoliza ? 'Emitida' : 'No emitida'];
                    $response[] = ['tipo'=> 'default', 'campo' => $cotSub . 'numeroCotizacionAS400', 'valorLong' => $datacot->numeroCotizacionAS400 ?? ''];
                    //descuentoId
                    //frecuenciaPagos
                    $response[] = ['tipo'=> 'default', 'campo' => $cotSub . 'idCorrelativo', 'valorLong' => $datacot->idCorrelativo];

                    $dataCoberturas = $datacot->coberturas;

                    foreach($dataCoberturas as $keycob => $datacob){
                        $cobSub =  strval($keycob + 1);
                        $cobSub = $cotSub  . 'cob' . $cobSub . '|';

                        $response[] = ['tipo'=> 'default', 'campo' => $cobSub . 'cobertura', 'valorLong' => $datacob->cobertura->nombre ?? ''];
                        $response[] = ['tipo'=> 'default', 'campo' => $cobSub . 'monto', 'valorLong' => $datacob->monto ?? ''];
                        $response[] = ['tipo'=> 'default', 'campo' => $cobSub . 'codigoCobertura', 'valorLong' => $datacob->codigoCobertura ?? ''];
                    }
                }
            }

            $cacheH->set("VEHI_CALCULATED_DATA_{$cotId}", $response);
        }

        return $response;
    }

    function calculateDataVehiculeForCot($cotId){

        $arrVehi = [];
        $vehiculos = CotizacionDetalleVehiculo::where('cotizacionId', $cotId)->get();

        foreach ($vehiculos as $item) {
            $arrVehi[] = $item->id;
        }

        $detalle = CotizacionDetalle::where('cotizacionId', $cotId)->whereIn('cotizacionVehiculoId', $arrVehi)
                                            ->orderBy('id', 'asc')
                                            ->get();


        $arrFinal = [];

        foreach ($detalle as $campo) {

            // var_dump($campo->subCotizacion->cotizacionDetalleVehiculoId);
            /*var_dump($campo->subCotizacion);
            die;*/
            // var_dump($campo->subCotizacion);
            $vehiculoId = $campo->cotizacionVehiculoId;

            if (!preg_match('/veh\d*\|/', $campo->campo)) continue;
            $campoKey = explode('|', $campo->campo);

            if (empty($campoKey[0])) continue;

            for ($i = 1; $i <= 10; $i++) {

                if ($campoKey[0] === "veh{$i}") {

                    if ((isset($campoKey[1]) && !preg_match('/cot\d*\|/', $campo->campo))) {
                        $campoNewTmp = str_replace("veh{$i}", 'veh', $campo->campo);

                        $arrFinal[$campoKey[0]]['v'][$campoNewTmp] = $campo->valorLong;
                        $arrFinal[$campoKey[0]]['v']['vehId'] = $vehiculoId; // para guardar la cotizacion id
                    }

                    // verificacion de cotización
                    if (!empty($campoKey[1])) {
                        for ($j = 1; $j <= 10; $j++) {
                            if ($campoKey[1] === "cot{$j}") {
                                $campoNewVeh = str_replace("veh{$i}", 'veh', $campo->campo);
                                $campoNew = str_replace("cot{$j}", 'cot', $campoNewVeh);
                                $arrFinal[$campoKey[0]]['c']["cot_{$j}"][$campoNew] = $campo->valorLong;
                                $arrFinal[$campoKey[0]]['c']["cot_{$j}"]['cotId'] = $campo->cotizacionDetalleVehiculoCotId;
                            }
                        }
                    }
                }
            }
        }

        // array finalisimo
        $arrFinalisimo = [];
        foreach ($arrFinal as $vehi => $item) {
            if (isset($item['c'])) {
                foreach ($item['c'] as $coti) {
                    $arrFinalisimo[$vehi][] = array_merge($item['v'] ?? [], $coti);
                }
            }
        }

        return $arrFinalisimo;
    }

    function calculateDataVehiculeForVeh($cotId){
        $responseFormat = [];
        $dataVehicle = CotizacionDetalleVehiculo::where('cotizacionId', $cotId)
            ->orderBy('id', 'asc')
            ->get();
        foreach($dataVehicle as $keyveh => $dataveh){
            $response = [];
            $sub = 'veh|';

            $response[] = ['tipo'=> 'default', 'campo' => $sub . 'id', 'valorLong' => $dataveh->id];
            $response[] = ['tipo'=> 'default', 'campo' => $sub . 'marca', 'valorLong' => $dataveh->marca->nombre ?? ''];
            $response[] = ['tipo'=> 'default', 'campo' => $sub . 'linea', 'valorLong' => $dataveh->linea->nombre ?? ''];
            $response[] = ['tipo'=> 'default', 'campo' => $sub . 'tipo', 'valorLong' => $dataveh->tipo->nombre ?? ''];
            $response[] = ['tipo'=> 'default', 'campo' => $sub . 'noPasajeros', 'valorLong' => $dataveh->noPasajeros ?? ''];
            $response[] = ['tipo'=> 'default', 'campo' => $sub . 'noChasis', 'valorLong' => $dataveh->noChasis ?? ''];
            $response[] = ['tipo'=> 'default', 'campo' => $sub . 'noMotor', 'valorLong' => $dataveh->noMotor ?? ''];
            $response[] = ['tipo'=> 'default', 'campo' => $sub . 'modelo', 'valorLong' => $dataveh->modelo ?? ''];
            $response[] = ['tipo'=> 'default', 'campo' => $sub . 'valorProm', 'valorLong' => $dataveh->valorProm ?? 0];
            $response[] = ['tipo'=> 'default', 'campo' => $sub . 'valorPromDef', 'valorLong' => $dataveh->valorPromDef ?? 0];
            $response[] = ['tipo'=> 'default', 'campo' => $sub . 'placa', 'valorLong' => $dataveh->placa ?? ''];
            $response[] = ['tipo'=> 'default', 'campo' => $sub . 'vehiculoNuevo', 'valorLong' => $dataveh->vehiculoNuevo? 'Nuevo' : 'Usado'];
            $response[] = ['tipo'=> 'default', 'campo' => $sub . 'altoRiesgoDisp', 'valorLong' => $dataveh->altoRiesgoDisp? 'SI' : 'NO'];

            $dataCotizacion = $dataveh->subCotizacion;

            foreach($dataCotizacion as $keycot => $datacot){
                $cotSub =  strval($keycot + 1);
                $cotSub =  $sub . 'cot' . $cotSub . '|';

                $response[] = ['tipo'=> 'default', 'campo' => $cotSub . 'id', 'valorLong' => $datacot->id];
                $response[] = ['tipo'=> 'default', 'campo' => $cotSub . 'producto', 'valorLong' => $datacot->producto->nombre ?? ''];
                $response[] = ['tipo'=> 'default', 'campo' => $cotSub . 'tarifa', 'valorLong' => $datacot->tarifa->nombre ?? ''];
                $response[] = ['tipo'=> 'default', 'campo' => $cotSub . 'descuentoPorcentaje', 'valorLong' => $datacot->descuentoPorcentaje ?? ''];

                $response[] = ['tipo'=> 'default', 'campo' => $cotSub . 'formaPago', 'valorLong' => $datacot->formaPago ? $datacot->formaPago->descripcion : 'Sin forma de pago'];
                $response[] = ['tipo'=> 'default', 'campo' => $cotSub . 'numeroPagos', 'valorLong' => $datacot->numeroPagos ?? 0];
                $response[] = ['tipo'=> 'default', 'campo' => $cotSub . 'primaNeta', 'valorLong' => $datacot->primaNeta ?? 0];
                $response[] = ['tipo'=> 'default', 'campo' => $cotSub . 'primaTotal', 'valorLong' => $datacot->primaTotal ?? 0];
                //catProductoTarigaDescRecargoId
                $response[] = ['tipo'=> 'default', 'campo' => $cotSub . 'recargoPorcentaje', 'valorLong' => $datacot->recargoPorcentaje ?? ''];
                $response[] = ['tipo'=> 'default', 'campo' => $cotSub . 'emitirPoliza', 'valorLong' => $datacot->emitirPoliza ? 'Emitida' : 'No emitida'];
                $response[] = ['tipo'=> 'default', 'campo' => $cotSub . 'numeroCotizacionAS400', 'valorLong' => $datacot->numeroCotizacionAS400 ?? ''];
                //descuentoId
                //frecuenciaPagos
                $response[] = ['tipo'=> 'default', 'campo' => $cotSub . 'idCorrelativo', 'valorLong' => $datacot->idCorrelativo ?? ''];

                $dataCoberturas = $datacot->coberturas;

                foreach($dataCoberturas as $keycob => $datacob){
                    $cobSub =  strval($keycob + 1);
                    $cobSub = $cotSub  . 'cob' . $cobSub . '|';

                    $response[] = ['tipo'=> 'default', 'campo' => $cobSub . 'cobertura', 'valorLong' => $datacob->cobertura->nombre ?? ''];
                    $response[] = ['tipo'=> 'default', 'campo' => $cobSub . 'monto', 'valorLong' => $datacob->monto ?? ''];
                    $response[] = ['tipo'=> 'default', 'campo' => $cobSub . 'codigoCobertura', 'valorLong' => $datacob->codigoCobertura ?? ''];
                }
            }

            $responseFormat[] = ['dataTables' => $response, 'vehId' => $dataveh->id];
        }

        return $responseFormat;
    }

    public function CalculateEjecutivo($usuarioId, $rol, $grupos, $tiendas) {

        $jerarquias = UserJerarquiaDetail::where('userId', $usuarioId)
            ->orWhere('rolId', $rol)
            ->orWhereIn('canalId', $tiendas)
            ->orWhereIn('userGroupId', $grupos)
            ->get();

        $ejecutivos = [];
        $usersId = [];
        foreach($jerarquias as $jerar){
            $jerarquiaData = $jerar->jerarquia;
            if(!empty($jerarquiaData)){
                $supervisores = $jerarquiaData->supervisor->whereNotNull('userId');
                foreach($supervisores as $sup) {
                    $user = $sup->user;
                    if(!empty($user) && !in_array($user->id, $usersId)) {
                        $ejecutivos[] = $user->name ?? '';
                        $usersId[] = $user->id;
                    };
                }
            }
        }

        return $ejecutivos;
    }

    public function processAutoInspeccionAS400(Request $request) {

        $token = $request->get('token');
        $vehiculoId = $request->get('vehiculoId');
        $usuarioLogueado = auth('sanctum')->user();
        $usuarioLogueadoId = ($usuarioLogueado) ? $usuarioLogueado->id : 0;
        $direccion = $request->get('direccion');

        if (empty($direccion)) {
            return $this->ResponseError('COT-015', 'Error, Falta dirección');
        }

        if (!empty($usuarioLogueadoId)) {
            $AC = new AuthController();
            if (!$AC->CheckAccess(['tareas/admin/cambio-paso'])) return $AC->NoAccess();
        }

        // Actual
        $userHandler = new AuthController();
        $CalculateAccess = $userHandler->CalculateAccess();

        $item = Cotizacion::where([['token', '=', $token]])->first();

        if (empty($item)) {
            return $this->ResponseError('COT-015', 'Tarea inválida');
        }

        $vehiculo = CotizacionDetalleVehiculo::where('id', $vehiculoId)->first();

        if (empty($vehiculo)) {
            return $this->ResponseError('INSP-015', 'Vehiculo no valido');
        }

        $wsData = $this->wsCustomAuto('AUTOINSPECCION_AS400', $item->id, null, $item->campos, $vehiculoId);

        if (count($wsData['errors']) > 0) {
            return $this->ResponseError('COTW-004', $wsData['errors'][0]);
        }

        /*var_dump($wsData);
        die;*/

        $procesos = [
          "pf" => [],
          "url" => "https://0qitpcca1g.execute-api.us-east-1.amazonaws.com/default/SelfInspectionTest",
          "type" => null,
          "isXML" => false,
          "header" => "",
          "method" => "post",
          "salida" => [],
          "authUrl" => "",
          "entrada" => "",
          "authType" => "ninguna",
          "authPayload" => "",
          "respuestaXML" => false,
          "tipoRecibido" => false,
          "configuracionS" => [],
          "identificadorWs" => "AUTOINSPECCION_AS400"
        ];

        // OVERRIDES A XML
        if (count($wsData['list']) > 0) {
            $tmpWsData = $wsData['list'][0];
            $procesos['entrada'] = $tmpWsData['entrada'];
            $resultado = $this->consumirServicio($procesos, $tmpWsData['data'] ?? $item->campos, '', $item);

            //$dataLog = "<h5>Data enviada</h5> <br> " . htmlentities($resultado['log']['enviado'] ?? '') . " <br><br> <h5>Headers enviados</h5> <br> ".($resultado['log']['enviadoH'] ?? '')." <br><br> <h5>Data recibida</h5> <br> " . htmlentities($resultado['log']['recibido'] ?? '') . " <br><br> <h5>Data procesada</h5> <br> " . htmlentities(print_r($resultado['data'] ?? '', true));

            if (empty($resultado['status'])) {
                return $this->ResponseError('INSP-20', "Error al generar inspeccion: PLACA {$tmpWsData['placa']}");
            }
            else {
                $urlInspeccion = $resultado['data']['AUTOINSPECCION_AS400.url'];
                $vehiculo->autoInspeccionLink = $urlInspeccion;
                $vehiculo->direccion = $direccion;
                $vehiculo->save();
                $dataToShow = [
                    'vehiculoId' => $vehiculoId,
                    'placa' => $tmpWsData['placa'],
                    'url' => $urlInspeccion
                ];
                return $this->ResponseSuccess('INSP-10', $dataToShow);
            }
        }
        else {
            return $this->ResponseError('INSP-30', "Ha ocurrido realizando el proceso");
        }
    }

    public function importDataInspecciones(Request $request) {
        $token = $request->get('token');

        $usuarioLogueado = auth('sanctum')->user();
        $usuarioLogueadoId = ($usuarioLogueado) ? $usuarioLogueado->id : 0;
        if (!empty($usuarioLogueadoId)) {
            $AC = new AuthController();
            if (!$AC->CheckAccess(['tareas/admin/cambio-paso'])) return $AC->NoAccess();
        }
        $userHandler = new AuthController();

        $cotizacion = Cotizacion::where([['token', '=', $token]])->first();

        if (empty($cotizacion)) {
            return $this->ResponseError('COT-732', 'Tarea no válida');
        }

        $vehiculos = $cotizacion->vehiculos;

        foreach($vehiculos as $veh){
            if(empty($veh->inspeccionId)) continue;
            $cotizacionInspeccion = Cotizacion::where('id', $veh->inspeccionId)->first();
            if (empty($cotizacionInspeccion)) continue;
            $producto = $cotizacionInspeccion->producto;
            if (empty($producto)) continue;
            $flujo = $producto->flujo->first();
            if (empty($flujo)) continue;

            /*verificar que no tenga cotizaciones emitidas esos vehiculos
            $cotizaciones = $veh->cotizaciones;
            $poliza = CotizacionDetalle::where('campo', 'EMISION_AS400.datosIdEmpresaGC.datos03.datosdePolizaGestorComercial.poliza')
                ->where('cotizacionId', $cotizacion->id)
                ->where('cotizacionDetalleVehiculoCotId', $cot->id)
                ->first();*/

            $flujoConfig = @json_decode($flujo->flujo_config, true);
            if (!is_array($flujoConfig)) continue;

            foreach ($flujoConfig['nodes'] as $nodo) {
                if (!empty($nodo['formulario']['secciones']) && count($nodo['formulario']['secciones']) > 0) {
                    foreach ($nodo['formulario']['secciones'] as $keySeccion => $seccion) {
                        foreach ($seccion['campos'] as $campo) {
                            $campoInspeccion = CotizacionDetalle::where('campo', $campo['id'])->where('cotizacionId', $veh->inspeccionId)->first();
                            $campoVeh = CotizacionDetalle::where('campo', $campo['id'])->where('cotizacionId', $cotizacion->id)->where('cotizacionVehiculoId', $veh->id)->first();
                            if(!empty($campoInspeccion) && empty($campoInspeccion->isFile)){
                                if(empty($campoVeh)) $campoVeh = new CotizacionDetalle();
                                $campoVeh->cotizacionId = $cotizacion->id;
                                $campoVeh->seccionKey = $keySeccion;
                                $campoVeh->campo = $campo['id'];
                                $campoVeh->cotizacionVehiculoId = $veh->id;
                                $campoVeh->useForSearch = 0;
                                $campoVeh->tipo = $campo['tipoCampo'] ?? 'default';
                                $campoVeh->valorLong = $campoInspeccion->valorLong;
                                $campoVeh->valorShow = $campoInspeccion->valorShow;
                                $campoVeh->save();
                            }
                        }
                    }
                }
            }
        }

        return $this->ResponseSuccess('Cambios ejecutados con exito');
    }

    public function getOrdenCotizacionesVehiculos($cotId){
        $ordenCotizacionVehiculos = [];
        $ordenCotizacionVehiculosCotizaciones = [];

        // guardamos el vehículo en cache
        $cacheH = ClassCache::getInstance();

        if(!empty($cotId)){

            // verifica en caché en memoria
            $ordenCotizacionVehiculos = $cacheH->get("VEHICULOS_BY_COTI_{$cotId}");
            if (empty($ordenCotizacionVehiculos)) {
                $cotizacionVehiculos = CotizacionDetalleVehiculo::where('cotizacionId', $cotId)->get()->toArray();
                foreach($cotizacionVehiculos as $keycot => $cotveh){
                    $ordenCotizacionVehiculos[$cotveh['id']] = $keycot;
                }
                $cacheH->set("VEHICULOS_BY_COTI_{$cotId}", $ordenCotizacionVehiculos);
            }


            // GUARDADO EN CACHÉ EN MEMORIA
            $ordenCotizacionVehiculosCotizaciones = $cacheH->get("VEHICULOS_BY_COTI_COTI_DETAIL_{$cotId}");
            if (empty($ordenCotizacionVehiculosCotizaciones)) {

                $cotizacionVehiculoCotizaciones =  CotizacionDetalleVehiculoCotizacion::where('cotizacionId', $cotId)->get()->toArray();
                foreach($cotizacionVehiculoCotizaciones as $keycot => $cotveh){
                    $ordenCotizacionVehiculosCotizaciones[$cotveh['id']] = $keycot;
                }

                $cacheH->set("VEHICULOS_BY_COTI_COTI_DETAIL_{$cotId}", $ordenCotizacionVehiculosCotizaciones);
            }

           /* $calculateDataVehicule = array_map(function($e){
                $e['id'] = $e['campo'];
                return $e;
            }, $this->calculateDataVehicule($cotId));

            foreach($calculateDataVehicule as $dataVehicle){
                $arrayValores[] = $dataVehicle;
            }*/
        }

        return [
            'ordenCotizacionVehiculos' => $ordenCotizacionVehiculos,
            'ordenCotizacionVehiculosCotizaciones' => $ordenCotizacionVehiculosCotizaciones
        ];
    }

    // whatsapp chapus archivo
    public function getWhatsappUrl($urlFile) {
        // guarda el archivo
        $time = date('Ymd');
        $urlInfo = parse_url($urlFile);
        $fileName = uniqid().'.pdf';
        $tmpFilePath = storage_path("tmp/".$fileName);
        file_put_contents($tmpFilePath, fopen($urlFile, 'r'));
        $diskTmp = Storage::disk('s3Temporal');
        $pathTmp = $diskTmp->putFileAs("{$time}", $tmpFilePath, $fileName);
        $tmpUrl = $diskTmp->url($pathTmp);
        if (file_exists($tmpFilePath)) unlink($tmpFilePath);
        return $tmpUrl;
    }
    // whatsapp chapus archivo

    public function customVarsSave(Request $request) {
        $cotiToken = $request->get('ct');
        $vehiId = $request->get('vehiId');
        $cotiK = $request->get('cotiK');
        $cotiId = $request->get('cotiId');
        $vehiNumber = $request->get('vehiNumber');
        $valorG = $request->get('valorG');
        $valorA1 = $request->get('valorA1');
        $valorA2 = $request->get('valorA2');

        $cotizacion = Cotizacion::where('token', $cotiToken)->first();

        if (!empty($cotizacion)) {
            $this->saveReplaceCustomVar($cotizacion->id, "COTI_VALOR_GARANTIZADO", $valorG, $vehiId, $vehiNumber, $cotiK, $cotiId);
            $this->saveReplaceCustomVar($cotizacion->id, "COTI_ALERTA_1", $valorA1, $vehiId, $vehiNumber, $cotiK, $cotiId);
            $this->saveReplaceCustomVar($cotizacion->id, "COTI_ALERTA_2", $valorA2, $vehiId, $vehiNumber, $cotiK, $cotiId);
        }
        return $this->ResponseSuccess('Guardado correcto');
    }

    public function SiniesBlock(Request $request) {

        $cotiToken = $request->get('ct');
        $siniesBlock = $request->get('sb');
        $recargo = $request->get('recargo');
        $nodoNuevo = $request->get('nodoNuevo');
        $cotizacion = Cotizacion::where('token', $cotiToken)->first();

        if (!empty($cotizacion)) {
            $cotizacion->siniesBlock = intval($siniesBlock);
            $cotizacion->siniesBlockRecargo = floatval($recargo);
            if (!empty($nodoNuevo)) {
                $cotizacion->nodoActual = $nodoNuevo ?? '';
            }
            $cotizacion->save();

            return $this->ResponseSuccess('Operación realizada con éxito, aplicado recargo: '.floatval($recargo));
        }
        else {
            return $this->ResponseError('COT-SINIESERR', 'Cotización inválida');
        }
    }

    public function verifyFileExtension($mimeType) {
        // Determinar la extensión según el MIME type
        $extensions = [
            'video/3gpp2' => '3g2',
            'video/3gp' => '3gp',
            'video/3gpp' => '3gp',
            'application/x-compressed' => '7zip',
            'audio/x-acc' => 'aac',
            'audio/ac3' => 'ac3',
            'application/postscript' => 'ai',
            'audio/x-aiff' => 'aif',
            'audio/aiff' => 'aif',
            'audio/x-au' => 'au',
            'video/x-msvideo' => 'avi',
            'video/msvideo' => 'avi',
            'video/avi' => 'avi',
            'application/x-troff-msvideo' => 'avi',
            'application/macbinary' => 'bin',
            'application/mac-binary' => 'bin',
            'application/x-binary' => 'bin',
            'application/x-macbinary' => 'bin',
            'image/bmp' => 'bmp',
            'image/x-bmp' => 'bmp',
            'image/x-bitmap' => 'bmp',
            'image/x-xbitmap' => 'bmp',
            'image/x-win-bitmap' => 'bmp',
            'image/x-windows-bmp' => 'bmp',
            'image/ms-bmp' => 'bmp',
            'image/x-ms-bmp' => 'bmp',
            'application/bmp' => 'bmp',
            'application/x-bmp' => 'bmp',
            'application/x-win-bitmap' => 'bmp',
            'application/cdr' => 'cdr',
            'application/coreldraw' => 'cdr',
            'application/x-cdr' => 'cdr',
            'application/x-coreldraw' => 'cdr',
            'image/cdr' => 'cdr',
            'image/x-cdr' => 'cdr',
            'zz-application/zz-winassoc-cdr' => 'cdr',
            'application/mac-compactpro' => 'cpt',
            'application/pkix-crl' => 'crl',
            'application/pkcs-crl' => 'crl',
            'application/x-x509-ca-cert' => 'crt',
            'application/pkix-cert' => 'crt',
            'text/css' => 'css',
            'text/x-comma-separated-values' => 'csv',
            'text/comma-separated-values' => 'csv',
            'application/vnd.msexcel' => 'csv',
            'application/x-director' => 'dcr',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/x-dvi' => 'dvi',
            'message/rfc822' => 'eml',
            'application/x-msdownload' => 'exe',
            'video/x-f4v' => 'f4v',
            'audio/x-flac' => 'flac',
            'video/x-flv' => 'flv',
            'image/gif' => 'gif',
            'application/gpg-keys' => 'gpg',
            'application/x-gtar' => 'gtar',
            'application/x-gzip' => 'gzip',
            'application/mac-binhex40' => 'hqx',
            'application/mac-binhex' => 'hqx',
            'application/x-binhex40' => 'hqx',
            'application/x-mac-binhex40' => 'hqx',
            'text/html' => 'html',
            'image/x-icon' => 'ico',
            'image/x-ico' => 'ico',
            'image/vnd.microsoft.icon' => 'ico',
            'text/calendar' => 'ics',
            'application/java-archive' => 'jar',
            'application/x-java-application' => 'jar',
            'application/x-jar' => 'jar',
            'image/jp2' => 'jp2',
            'video/mj2' => 'jp2',
            'image/jpx' => 'jp2',
            'image/jpm' => 'jp2',
            'image/jpeg' => 'jpeg',
            'image/pjpeg' => 'jpeg',
            'application/x-javascript' => 'js',
            'application/json' => 'json',
            'text/json' => 'json',
            'application/vnd.google-earth.kml+xml' => 'kml',
            'application/vnd.google-earth.kmz' => 'kmz',
            'text/x-log' => 'log',
            'audio/x-m4a' => 'm4a',
            'audio/mp4' => 'm4a',
            'application/vnd.mpegurl' => 'm4u',
            'audio/midi' => 'mid',
            'application/vnd.mif' => 'mif',
            'video/quicktime' => 'mov',
            'video/x-sgi-movie' => 'movie',
            'audio/mpeg' => 'mp3',
            'audio/mpg' => 'mp3',
            'audio/mpeg3' => 'mp3',
            'audio/mp3' => 'mp3',
            'video/mp4' => 'mp4',
            'video/mpeg' => 'mpeg',
            'application/oda' => 'oda',
            'audio/ogg' => 'ogg',
            'video/ogg' => 'ogg',
            'application/ogg' => 'ogg',
            'font/otf' => 'otf',
            'application/x-pkcs10' => 'p10',
            'application/pkcs10' => 'p10',
            'application/x-pkcs12' => 'p12',
            'application/x-pkcs7-signature' => 'p7a',
            'application/pkcs7-mime' => 'p7c',
            'application/x-pkcs7-mime' => 'p7c',
            'application/x-pkcs7-certreqresp' => 'p7r',
            'application/pkcs7-signature' => 'p7s',
            'application/pdf' => 'pdf',
            'application/octet-stream' => 'pdf',
            'application/x-x509-user-cert' => 'pem',
            'application/x-pem-file' => 'pem',
            'application/pgp' => 'pgp',
            'application/x-httpd-php' => 'php',
            'application/php' => 'php',
            'application/x-php' => 'php',
            'text/php' => 'php',
            'text/x-php' => 'php',
            'application/x-httpd-php-source' => 'php',
            'image/png' => 'png',
            'image/x-png' => 'png',
            'application/powerpoint' => 'ppt',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.ms-office' => 'ppt',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'application/x-photoshop' => 'psd',
            'image/vnd.adobe.photoshop' => 'psd',
            'audio/x-realaudio' => 'ra',
            'audio/x-pn-realaudio' => 'ram',
            'application/x-rar' => 'rar',
            'application/rar' => 'rar',
            'application/x-rar-compressed' => 'rar',
            'audio/x-pn-realaudio-plugin' => 'rpm',
            'application/x-pkcs7' => 'rsa',
            'text/rtf' => 'rtf',
            'text/richtext' => 'rtx',
            'video/vnd.rn-realvideo' => 'rv',
            'application/x-stuffit' => 'sit',
            'application/smil' => 'smil',
            'text/srt' => 'srt',
            'image/svg+xml' => 'svg',
            'application/x-shockwave-flash' => 'swf',
            'application/x-tar' => 'tar',
            'application/x-gzip-compressed' => 'tgz',
            'image/tiff' => 'tiff',
            'font/ttf' => 'ttf',
            'text/plain' => 'txt',
            'text/x-vcard' => 'vcf',
            'application/videolan' => 'vlc',
            'text/vtt' => 'vtt',
            'audio/x-wav' => 'wav',
            'audio/wave' => 'wav',
            'audio/wav' => 'wav',
            'application/wbxml' => 'wbxml',
            'video/webm' => 'webm',
            'image/webp' => 'webp',
            'audio/x-ms-wma' => 'wma',
            'application/wmlc' => 'wmlc',
            'video/x-ms-wmv' => 'wmv',
            'video/x-ms-asf' => 'wmv',
            'font/woff' => 'woff',
            'font/woff2' => 'woff2',
            'application/xhtml+xml' => 'xhtml',
            'application/excel' => 'xl',
            'application/msexcel' => 'xls',
            'application/x-msexcel' => 'xls',
            'application/x-ms-excel' => 'xls',
            'application/x-excel' => 'xls',
            'application/x-dos_ms_excel' => 'xls',
            'application/xls' => 'xls',
            'application/x-xls' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/xml' => 'xml',
            'text/xml' => 'xml',
            'text/xsl' => 'xsl',
            'application/xspf+xml' => 'xspf',
            'application/x-compress' => 'z',
            'application/x-zip' => 'zip',
            'application/zip' => 'zip',
            'application/x-zip-compressed' => 'zip',
            'application/s-compressed' => 'zip',
            'multipart/x-zip' => 'zip',
            'text/x-scriptzsh' => 'zsh'
        ];

        return $extensions[$mimeType] ?? 'bin'; // 'bin' si no se reconoce el tipo
    }

    public function getFileContent($urlFile) {

        // Obtener el contenido del archivo
        $arrContextOptions=array(
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ),
        );
        $s3_file = file_get_contents($urlFile, false, stream_context_create($arrContextOptions));

        // Escribir el archivo en un directorio temporal
        $fileTmp = uniqid();
        $tmpFilePath = storage_path("tmp/".$fileTmp);
        file_put_contents($tmpFilePath, $s3_file);
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $tmpFilePath);
        finfo_close($finfo);
        $ext = $this->verifyFileExtension($mimeType); // 'bin' si no se reconoce el tipo
        $fileTmpName = "{$fileTmp}.{$ext}";
        if (file_exists($tmpFilePath)) unlink($tmpFilePath);

        // crea link temporal
        /*$time = time();
        $diskTmp = Storage::disk('s3Temporal');
        $pathTmp = $diskTmp->putFileAs("{$time}", $tmpFilePath, $fileTmpName);
        $tmpUrl = $diskTmp->url($pathTmp);
        if (file_exists($tmpFilePath)) unlink($tmpFilePath);
        return $tmpUrl;*/
    }
}
