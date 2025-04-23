<?php

namespace App\Http\Controllers;

use app\core\Response;
use app\models\Clientes;
use App\Models\Cotizacion;
use App\Models\CotizacionDetalle;
use App\Models\CotizacionBitacora;
use App\Models\Productos;
use App\Models\Reporte;
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


class ReportesController extends Controller {

    use Response;

    // plantillas pdf
    public function Save(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['reportes/admin'])) return $AC->NoAccess();

        $id = $request->get('id');
        $nombre = $request->get('nombre');
        $activo = $request->get('activo');
        $producto = $request->get('flujos');
        $tipo = $request->get('tipo');
        $campos = $request->get('campos');
        $docsTpl = $request->get('docsTpl');
        $agrupacion = $request->get('agrupacion');
        $visibilidad = $request->get('visibilidad');
        $variablesDefault = $request->get('variablesDefault');
        $ordenVariables = $request->get('ordenVariables');
        $allowSendReport = $request->get('allowSendReport');
        $dateStart = $request->get('dateStart');
        $period_assign = $request->get('period_assign');
        $mailConfig = $request->get('mailConfig');
        $system = $request->get('system');
        //var_dump($agrupacion);

        $item = Reporte::where('id', $id)->first();

        if (empty($item)) {
            $item = new Reporte();
        }

        $arrConfig = [];
        foreach ($campos as $campo) {
            $tmp = explode('||', $campo);
            $arrConfig['c'][] = [
                'id' => $campo,
                'p' => $tmp[0],
                'c' => $tmp[1],
            ];
        }
        foreach ($agrupacion as $campo) {
            $tmp = explode('||', $campo['id']);
            $arrConfig['ag'][] = [
                'id' => $campo['id'],
                'opt' => $campo['campoOpt'],
                'p' => $tmp[0],
                'c' => $tmp[1],
            ];
        }

        $arrConfig['p'] = $producto;
        $arrConfig['tpl'] = $docsTpl;
        $arrConfig['visibilidad'] = $visibilidad;
        $arrConfig['variablesDefault'] = $variablesDefault;
        $arrConfig['ordenVariables'] = $ordenVariables;
        $arrConfig['system'] = $system;

        $item->id = intval($id);
        $item->nombre = strip_tags($nombre);
        $item->productoId = intval($producto);
        $item->activo = intval($activo);
        $item->config = @json_encode($arrConfig) ?? null;
        $item->tipo = $tipo;
        /*
        $item->sendReport=$allowSendReport;
        $item->mailconfig=@json_encode($mailConfig) ?? null;
        $item->dateToSend=$dateStart;
        $item->period=$period_assign;
        */
        $item->save();

        return $this->ResponseSuccess('Plantilla guardada con éxito', ['id' => $item->id]);
    }

    public function ListadoAll(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['reportes/listar'])) return $AC->NoAccess();

        $item = Reporte::all();
        return $this->ResponseSuccess('Reportes obtenidos con éxito', $item);
    }

    public function Listado(Request $request) {
        $AC = new AuthController();
        if (!$AC->CheckAccess(['reportes/listar'])) return $AC->NoAccess();

        $usuarioLogueado = auth('sanctum')->user();
        $rolUsuarioLogueado = ($usuarioLogueado) ? $usuarioLogueado->rolAsignacion->rol : 0;

        $reports = Reporte::all();
        $data = [];

        foreach($reports as $item){
            $visibilidad = json_decode($item->config, true)['visibilidad']?? [];
            $access = $AC->CalculateVisibility($usuarioLogueado->id, $rolUsuarioLogueado->id ?? 0, false, $visibilidad['roles'] ?? [], $visibilidad['grupos'] ?? [], $visibilidad['canales'] ?? []);
            if (!$access &&  !in_array($usuarioLogueado->id, $visibilidad['users']?? [])) continue;
            $data[] = $item;
        }
        return $this->ResponseSuccess('Reportes obtenidos con éxito', $data);
    }

    public function ListadoMasivos(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['reportes/listar'])) return $AC->NoAccess();

        $item = Reporte::where('tipo', 'm')->where('activo', 1)->get();
        return $this->ResponseSuccess('Reportes obtenidos con éxito', $item);
    }

    public function ListadoFlujos(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['reportes/admin'])) return $AC->NoAccess();

        $item = Productos::where('status', 1)->get();
        $item->makeHidden(['descripcion', 'token', 'extraData', 'imagenData']);
        return $this->ResponseSuccess('Reportes obtenidos con éxito', $item);
    }

    public function GetDocsPlusTpl(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['reportes/admin'])) return $AC->NoAccess();

        $headers = array(
            'Content-Type: application/json',
            'Authorization: Bearer '.env('ANY_SUBSCRIPTIONS_TOKEN')
        );
        $ch = curl_init(env('ANY_SUBSCRIPTIONS_URL', '').'/formularios/all');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dataSend));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $data = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        $dataResponse = @json_decode($data, true);

        $templates = [];
        if (!empty($dataResponse['status'])) {
            foreach ($dataResponse['data'] as $data) {
                $templates[$data['id']] = [
                    'n' => $data['descripcion']." ({$data['token']})",
                    't' => $data['token'],
                ];
            }
        }

        return $this->ResponseSuccess('Plantillas obtenidas con éxito', $templates);
    }

    public function NodosCampos(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['reportes/admin'])) return $AC->NoAccess();

        $productosTmp = $request->get('productos');
        // voy a traer los productos
        $productos = Productos::whereIn('id', $productosTmp)->get();

        $allFields = [];
        $arrResponse = [];
        foreach ($productos as $producto) {

            $flujo = $producto->flujo->where('activo', 1)->first();
            if (empty($flujo)) {
                return $this->ResponseError('RPT-001', 'Flujo no válido');
            }

            $flujoConfig = @json_decode($flujo->flujo_config, true);
            if (!is_array($flujoConfig)) {
                return $this->ResponseError('RPT-002', 'Error al interpretar flujo, por favor, contacte a su administrador');
            }

            foreach ($flujoConfig['nodes'] as $nodo) {

                //$resumen
                if (!empty($nodo['formulario']['secciones']) && count($nodo['formulario']['secciones']) > 0) {

                    foreach ($nodo['formulario']['secciones'] as $keySeccion => $seccion) {

                        foreach ($seccion['campos'] as $keyCampoTmp => $campo) {

                            $keyCampo = $producto->id.'||'.$campo['id'] ;
                            $allFields[$keyCampo]['id'] = $keyCampo;
                            $allFields[$keyCampo]['r'] = $campo['id'];
                            $allFields[$keyCampo]['label'] = $campo['nombre'];
                            $allFields[$keyCampo]['pr'] = $producto->nombreProducto;
                            $allFields[$keyCampo]['nodo'] = strip_tags($nodo['label']);

                            if(!empty($campo['catalogoId'])){
                                $campoIdDesc = $campo['id'] . '_DESC';
                                $keyCampo = $producto->id.'||'.$campoIdDesc ;
                                $allFields[$keyCampo]['id'] = $keyCampo;
                                $allFields[$keyCampo]['r'] = $campoIdDesc;
                                $allFields[$keyCampo]['label'] = $campo['nombre'] . ' (Descripción)';
                                $allFields[$keyCampo]['pr'] = $producto->nombreProducto;
                                $allFields[$keyCampo]['nodo'] = strip_tags($nodo['label']);
                            }
                        }
                    }
                }
            }
            //dd($flujoConfig);
        }

        return $this->ResponseSuccess('Campos obtenidos con éxito', $allFields);
    }

    public function GetReporte(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['reportes/generar'])) return $AC->NoAccess();

        $id = $request->get('id');

        $item = Reporte::where('id', $id)->first();

        if (empty($item)) {
            return $this->ResponseError('RPT-014', 'Error al obtener reporte');
        }

        $item->c = @json_decode($item->config);
        $item->mail = @json_decode($item->mailconfig);
        $item->makeHidden(['config']);
        $item->makeHidden(['mailconfig']);

        return $this->ResponseSuccess('Reporte obtenido con éxito', $item);
    }

    public function Generar_bk(Request $request, $public = false) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['reportes/generar']) && !$public) return $AC->NoAccess();
        $CalculateAccess = $AC->CalculateAccess();

        $id = $request->get('reporteId');
        $fechaIni = $request->get('fechaIni');
        $fechaFin = $request->get('fechaFin');

        $fechaIni = Carbon::parse($fechaIni);
        $fechaFin = Carbon::parse($fechaFin);

        $item = Reporte::where('id', $id)->first();

        if (empty($item)) {
            return $this->ResponseError('RPT-015', 'Error al obtener reporte');
        }

        // $item->nombre
        $config = @json_decode($item->config, true);

        /*if($item->tipo === 's') {
            $data = [
                'system' => $config['system'],
                'fechaIni' => $fechaIni,
                'fechaFin' => $fechaFin,
                'reportName' => $item->nombre,
            ];
            return $this->GenerarSistema($data);
        }*/


        /*$strQueryFull = "SELECT C.
                        FROM cotizaciones AS C
                        JOIN M_servicios S on D.siniestro = S.siniestro
                        WHERE
                            D.fecha >= '".$fechaIni->toDateString()."'
                        AND D.fecha <= '".$fechaFin->toDateString()."'
                        GROUP BY D.codigoDiagnostico, D.diagnosticoDesc, D.fecha
                        ORDER BY ConteoSiniestro DESC";
        */

        // var_dump($config);

        $campos = '';
        $ordenVariables = $config['ordenVariables']?? [];
        $camposOri = [];
        foreach ($config['c'] as $conf) {
            // $campos .= ($campos !== '') ? ", '{$conf['c']}'" : "'{$conf['c']}'";
            $camposOri[] = $conf['c'];
        }

        $variablesDefault = $config['variablesDefault']?? [];

        // var_dump($variablesDefault);

        $prod = '';
        $prods = [];
        foreach ($config['p'] as $conf) {
            $conf = intval($conf);
            $prods[] = $conf;
            $prod .= ($prod !== '') ? ", {$conf}" : "{$conf}";
        }

        if(count($ordenVariables) > 0) {
            $camposOri = array_map(function($e){
                $tmp = explode('||', $e);
                return !empty($tmp[1])? $tmp[1] : $tmp[0];
            },$ordenVariables);
        };
        $usersToFind = implode(', ', $CalculateAccess['all']);
        $datosFinal = $this->calculateDatosFinal($usersToFind, $prod, $campos, $item->tipo, $fechaIni->format('Y-m-d'), $fechaFin->format('Y-m-d'), $camposOri, $variablesDefault);
        var_dump($datosFinal);
        die;

        /*

        $strQueryFull = "SELECT C.id, C.dateCreated, C.dateExpire, C.productoId, C.usuarioId, C.usuarioIdAsignado, CD.campo, CD.valorLong, P.nombreProducto
                        FROM cotizaciones AS C
                        JOIN cotizacionesDetalle AS CD ON C.id = CD.cotizacionId
                        JOIN productos AS P ON C.productoId = P.id
                        WHERE
                            C.productoId IN ($prod)
                            AND C.usuarioIdAsignado IN ({$usersToFind})
                            AND CD.campo IN ({$campos})
                            AND C.dateCreated >= '".$fechaIni->format('Y-m-d')." 00:00:00'
                            AND C.dateCreated <= '".$fechaFin->format('Y-m-d')." 23:59:59'
                        ";


        $queryTmp = DB::select(DB::raw($strQueryFull));

        $datosFinal = [];
        $datosFinal[] = [
            'No.',
            'Fecha creación',
            'Fecha expiración',
            'Producto',
        ];

        $campos = [];
        $data = [];

        foreach ($queryTmp as $tmp) {
            $valorLong = $tmp->valorLong;
            if($tmp->campo === 'FECHA_HOY')  $valorLong = Carbon::now()->setTimezone('America/Guatemala')->toDateTimeString();
            $campos[$tmp->campo] = $tmp->campo;
            $data[$tmp->id][$tmp->campo] = $valorLong;
        }

        foreach ($camposOri as $campo) {
            //for pero consultando si ya existe
            if(!empty($campos[$campo])) $datosFinal[0][] = $campo;
        }

        foreach ($queryTmp as $tmp) {
            //for
            $datosFinal[$tmp->id]['id'] = $tmp->id;
            $datosFinal[$tmp->id]['dateCreated'] = $tmp->dateCreated;
            $datosFinal[$tmp->id]['dateExpire'] = $tmp->dateExpire;
            $datosFinal[$tmp->id]['nombreProducto'] = $tmp->nombreProducto;

            foreach ($camposOri as $campo) {
                if(!empty($campos[$campo])) $datosFinal[$tmp->id][$campo] = (!empty($data[$tmp->id][$campo]) ? $data[$tmp->id][$campo] : '');
            }
        }*/

        $spreadsheet = new Spreadsheet();

        $spreadsheet
            ->getProperties()
            ->setCreator("GastosMedicos-ElRoble")
            ->setLastModifiedBy('Automator') // última vez modificado por
            ->setTitle('Reporte de '.$item->nombre)
            ->setDescription('Reporte');

        // first sheet
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle("Hoja 1");
        $sheet->fromArray($datosFinal, NULL, 'A1');

        foreach ($sheet->getRowIterator() as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $cell->setValueExplicit($cell->getValue(), DataType::TYPE_STRING);
            }
        }

        $writer = new Xlsx($spreadsheet);
        $fileNameHash = md5(uniqid());
        $tmpPath = storage_path("tmp/{$fileNameHash}.xlsx");
        $writer->save($tmpPath);

        $disk = Storage::disk('s3');
        $path = $disk->putFileAs("/tmp/files", $tmpPath, "{$fileNameHash}.xlsx");
        $temporarySignedUrl = Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(10));

        return $this->ResponseSuccess('Reporte generado con éxito', ['url' => $temporarySignedUrl]);
    }

    public function Generar(Request $request, $public = false) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['reportes/generar']) && !$public) return $AC->NoAccess();
        $CalculateAccess = $AC->CalculateAccess();
        $usersToFind = implode(', ', $CalculateAccess['all']);

        $id = $request->get('reporteId');
        $fechaIni = $request->get('fechaIni');
        $fechaFin = $request->get('fechaFin');

        $fechaIni = Carbon::parse($fechaIni);
        $fechaFin = Carbon::parse($fechaFin);

        $item = Reporte::where('id', $id)->first();

        if (empty($item)) {
            return $this->ResponseError('RPT-015', 'Error al obtener reporte');
        }

        // $item->nombre
        $config = @json_decode($item->config, true);

        $campos = '';
        $camposOri = [];
        foreach ($config['c'] as $conf) {
            $campos .= ($campos !== '') ? ", '{$conf['c']}'" : "'{$conf['c']}'";
            $camposOri[] = $conf['c'];
        }

        $variablesDefault = $config['variablesDefault']?? [];
        foreach ($variablesDefault as $conf) {
            $campos .= ($campos !== '') ? ", '{$conf}'" : "'{$conf}'";
            $camposOri[] = $conf;
        }

        $prod = '';
        foreach ($config['p'] as $conf) {
            $conf = intval($conf);
            $prod .= ($prod !== '') ? ", {$conf}" : "{$conf}";
        }

        $strQueryFull = "SELECT C.id, C.dateCreated, C.dateExpire, C.productoId, C.usuarioId, C.usuarioIdAsignado, CD.campo, CD.valorLong, P.nombreProducto
                        FROM cotizaciones AS C
                        JOIN cotizacionesDetalle AS CD ON C.id = CD.cotizacionId
                        JOIN productos AS P ON C.productoId = P.id
                        WHERE 
                            C.productoId IN ($prod)
                            AND CD.campo IN ({$campos})
                            AND C.usuarioIdAsignado IN ({$usersToFind})
                            AND C.dateCreated >= '".$fechaIni->format('Y-m-d')." 00:00:00'
                            AND C.dateCreated <= '".$fechaFin->format('Y-m-d')." 23:59:59'
                        ";


        $queryTmp = DB::select(DB::raw($strQueryFull));

        $datosFinal = [];
        $datosFinal[] = [
            'No.',
            'Fecha creación',
            'Fecha expiración',
            'Producto',
            /*'Usuario Asignado',
            'Usuario Creador',*/
        ];

        $campos = [];
        $data = [];

        foreach ($queryTmp as $tmp) {
            $valorLong = $tmp->valorLong;
            if($tmp->campo === 'FECHA_HOY')  $valorLong = Carbon::now()->setTimezone('America/Guatemala')->toDateTimeString();
            $campos[$tmp->campo] = $tmp->campo;
            $data[$tmp->id][$tmp->campo] = $valorLong;
        }


        $ordenVariablesTmp = $config['ordenVariables']?? [];
        $ordenVariables = array_map(function($e){
            $tmp = explode('||', $e);
            return !empty($tmp[1])? $tmp[1] : $tmp[0];
        }, $ordenVariablesTmp);

        // reemplaza el orden
        $camposOri = $ordenVariables;

        // Eliminación de duplicados y valores vacíos en camposOri
        $camposOri = array_filter(array_unique($camposOri));
        // Reindexar el array para evitar índices no secuenciales
        $camposOri = array_values($camposOri);

        foreach ($camposOri as $campo) {
            // Agregar siempre la columna al encabezado, independientemente de si tiene datos
            $datosFinal[0][] = $campo;

        }

        foreach ($queryTmp as $tmp) {
            $datosFinal[$tmp->id]['id'] = $tmp->id;
            $datosFinal[$tmp->id]['dateCreated'] = $tmp->dateCreated;
            $datosFinal[$tmp->id]['dateExpire'] = $tmp->dateExpire;
            $datosFinal[$tmp->id]['nombreProducto'] = $tmp->nombreProducto;

            foreach ($camposOri as $campo) {
                // Asignar el valor correspondiente o una cadena vacía si no existe
                $datosFinal[$tmp->id][$campo] = $data[$tmp->id][$campo] ?? '';
            }
        }

        $spreadsheet = new Spreadsheet();

        $spreadsheet
            ->getProperties()
            ->setCreator("GastosMedicos-ElRoble")
            ->setLastModifiedBy('Automator') // última vez modificado por
            ->setTitle('Reporte de '.$item->nombre)
            ->setDescription('Reporte');

        // first sheet
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle("Hoja 1");
        $sheet->fromArray($datosFinal, NULL, 'A1');

        foreach ($sheet->getRowIterator() as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $cell->setValueExplicit($cell->getValue(), DataType::TYPE_STRING);
            }
        }

        $writer = new Xlsx($spreadsheet);
        $fileNameHash = md5(uniqid());
        $tmpPath = storage_path("tmp/{$fileNameHash}.xlsx");
        $writer->save($tmpPath);

        $disk = Storage::disk('s3');
        $path = $disk->putFileAs("/tmp/files", $tmpPath, "{$fileNameHash}.xlsx");
        $temporarySignedUrl = Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(10));

        return $this->ResponseSuccess('Reporte generado con éxito', ['url' => $temporarySignedUrl]);
    }

    public function GenerarSistema($data) {
        $system = $data['system'];
        $fechaIni = $data['fechaIni'];
        $fechaFin = $data['fechaFin'];
        $reportName = $data['reportName'];

        $options = [
            'R1' => [
                'datos' => [
                    ['value' => 'flujo', 'text' => 'Flujo'],
                    ['value' => 'parcial', 'text' => 'Parcial']
                ],
                'strQueryFull' => "SELECT P.nombreProducto as flujo, COUNT(C.id) as parcial
                FROM cotizaciones AS C
                JOIN productos AS P ON C.productoId = P.id
                WHERE C.dateCreated >= '".$fechaIni->format('Y-m-d')." 00:00:00'
                AND C.dateCreated <= '".$fechaFin->format('Y-m-d')." 23:59:59'
                GROUP BY P.id
                ORDER BY P.nombreProducto",
            ],
            'R2' => [
                'datos' => [
                    ['value' => 'flujo', 'text' => 'Flujo'],
                    ['value' => 'nodoName', 'text' => 'Nombre del Nodo'],
                    ['value' => 'nodoNameId', 'text' => 'Identificador de Nodo'],
                    ['value' => 'parcial', 'text' => 'Parcial']
                ],
                'strQueryFull' => "SELECT P.nombreProducto as flujo, C.productoId, C.nodoActual, COUNT(C.id) as parcial
                FROM cotizaciones AS C
                JOIN productos AS P ON C.productoId = P.id
                WHERE C.dateCreated >= '".$fechaIni->format('Y-m-d')." 00:00:00'
                AND C.dateCreated <= '".$fechaFin->format('Y-m-d')." 23:59:59'
                GROUP BY P.nombreProducto, C.productoId, C.nodoActual
                ORDER BY P.nombreProducto",
            ],
            'R3' => [
                'datos' => [
                    ['value' => 'canal', 'text' => 'CANAL'],
                    ['value' => 'distribuidor', 'text' => 'DISTRIBUIDOR'],
                    ['value' => 'tienda', 'text' => 'TIENDA'],
                    ['value' => 'ejecutivo', 'text' => 'EJECUTIVO'],
                    ['value' => 'producto', 'text' => 'PRODUCTO'],
                    ['value' => 'emisiones', 'text' => 'EMISIONES'],
                    ['value' => 'cotizaciones', 'text' => 'COTIZACIONES'],
                    ['value' => 'aceptacion', 'text' => 'ACEPTACIÓN'],
                    ['value' => 'primaneta', 'text' => 'PRIMA NETA PROMEDIO'],
                    ['value' => 'factorcomercial', 'text' => 'FACTOR COMERCIAL'],
                ],
                'strQueryFull' => "SELECT
                CA.nombre as canal,
                G.nombre as distribuidor, 
                T.nombre as tienda, 
                UJ.name as ejecutivo,
                C.productoId,
                P.nombre as producto,
                SUM(C.emitirPoliza) as emisiones,
                COUNT(C.id) as cotizaciones,
                (SUM(C.emitirPoliza)/COUNT(C.id)) as aceptacion,
                AVG(C.primaNeta) as primaneta,
                (SUM(C.emitirPoliza)/COUNT(C.id)*AVG(C.primaNeta)) as factorcomercial
                FROM cotizacionesDetalleVehiculosCot AS C
                JOIN catProductos AS P ON C.productoId = P.id
                JOIN cotizaciones AS Z ON C.cotizacionId = Z.id
                LEFT JOIN user_rol ON user_rol.userId = Z.usuarioId

                LEFT JOIN usersGroupUsuarios ON usersGroupUsuarios.userId = Z.usuarioId
                LEFT JOIN usersGroupRoles ON usersGroupRoles.rolId = user_rol.rolId
                LEFT JOIN usersGroup AS G ON G.id = usersGroupRoles.userGroupId OR G.id = usersGroupUsuarios.userGroupId

                LEFT JOIN usersCanalGrupos ON usersCanalGrupos.userGroupId = G.id
                LEFT JOIN usersCanal AS CA ON CA.id = usersCanalGrupos.userCanalId

                LEFT JOIN userTienda AS TD ON TD.userId = Z.usuarioId
                LEFT JOIN usersTiendas AS T ON TD.tiendaId = T.id

                LEFT JOIN usersJerarquiaDetail ON T.id = usersJerarquiaDetail.canalId
                LEFT JOIN usersJerarquiaSup ON usersJerarquiaSup.jerarquiaId = usersJerarquiaDetail.jerarquiaId
                LEFT JOIN users as UJ ON UJ.id = usersJerarquiaSup.userId

                WHERE Z.dateCreated >= '".$fechaIni->format('Y-m-d')." 00:00:00'
                AND Z.dateCreated <= '".$fechaFin->format('Y-m-d')." 23:59:59'
                AND usersJerarquiaDetail.canalId IS NOT NULL
                AND usersJerarquiaSup.userId IS NOT NULL
                GROUP BY CA.id, G.id, T.id, UJ.id, C.productoId
                ORDER BY CA.id, G.id, T.id, UJ.id, C.productoId",
                'withoutFooter' => true,
            ],
        ];

        //userstiendas
        if(empty($options[$system])) return $this->ResponseError('RPT-0110', 'No existe reporte de sistema');

        $datosFinal = [];
        $queryTmp = DB::select(DB::raw($options[$system]['strQueryFull']));
        $foot = [];
        $total = 0;

        $camposOri = $options[$system]['datos'];

        foreach ($camposOri as $campo) {
            $datosFinal[0][] = $campo['text'];
            $foot[] = '';
        }

        $flujosFromCotizacion = [];

        foreach ($queryTmp as $tmp) {
            $datosFinal[] = [];
            $total += $tmp->parcial?? 0;
            if(!empty($tmp->nodoActual) && !empty($tmp->productoId)){
                if(empty($flujosFromCotizacion[$tmp->productoId])){
                    $producto = Productos::where('id', $tmp->productoId)->first();
                    $flujo = $producto->flujo->first();
                    if (!empty($flujo)) {
                        $flujoConfig = @json_decode($flujo->flujo_config, true);
                        $flujosFromCotizacion[$tmp->productoId] = $flujoConfig;
                    }
                }
                $flujoConfig = $flujosFromCotizacion[$tmp->productoId];
                if(!empty($flujoConfig)){
                    $nodo = array_values(array_filter($flujoConfig['nodes'], function($nodo) use ($tmp) {
                        return $nodo['id'] === $tmp->nodoActual;
                    }))[0]?? [];

                    $tmp->nodoName = $nodo['nodoName']?? '';
                    $tmp->nodoNameId = $nodo['nodoId']?? '';
                }
            }
            foreach ($camposOri as $campo) {
                $value = $campo['value'];
                $datosFinal[count($datosFinal)-1][$campo['value']] = $tmp->$value ?? '';
            }
        }

        $foot[count($foot)-2] = 'Total';
        $foot[count($foot)-1] = $total;
        if(empty($options[$system]['withoutFooter'])) $datosFinal[] = $foot;
        $spreadsheet = new Spreadsheet();

        $spreadsheet
            ->getProperties()
            ->setCreator("Auto-ElRoble")
            ->setLastModifiedBy('Automator') // última vez modificado por
            ->setTitle('Reporte de '.$reportName)
            ->setDescription('Reporte');

        // first sheet
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle("Hoja 1");
        $sheet->fromArray($datosFinal, NULL, 'A1');

        foreach ($sheet->getRowIterator() as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $cell->setValueExplicit($cell->getValue(), DataType::TYPE_STRING);
            }
        }

        $writer = new Xlsx($spreadsheet);
        $fileNameHash = md5(uniqid());
        $tmpPath = storage_path("tmp/{$fileNameHash}.xlsx");
        $writer->save($tmpPath);

        $disk = Storage::disk('s3');
        $path = $disk->putFileAs("/tmp/files", $tmpPath, "{$fileNameHash}.xlsx");
        $temporarySignedUrl = Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(10));

        return $this->ResponseSuccess('Reporte generado con éxito', ['url' => $temporarySignedUrl]);
    }

    public function ReporteByNodoBitacora(Request $request) {
        $cotizacionId = $request->get('token');
        $cotizacion = Cotizacion::where([['token', '=', $cotizacionId]])->first();
        if (empty($cotizacion)) {
            return $this->ResponseError('COT-632', 'Tarea no válida');
        }

        $camposAll = CotizacionDetalleBitacora::
            where('cotizacionId', $cotizacion->id)
            ->orderBy('id', 'DESC')
            ->get();

        $producto = Productos::where('id', $cotizacion->productoId)->first();
        $flujo = Flujos::Where('productoId', '=', $producto->id)->Where('activo', '=', 1)->first();
        if (!empty($producto)) $data['producto'] = $producto->toArray();
        if (!empty($flujo)) $data['flujo'] = $flujo->toArray();

        $typesNode = [
            "start" => "Inicio",
            "input" => "Entradas",
            "condition" => "Condición",
            "process" => "Proceso",
            "setuser" => "Usuario",
            "review" => "Revisión",
            "output" => "Salida",
        ];

        $flujoConfig = @json_decode($flujo->flujo_config, true);
        $allNodes = [];
        $nodoStart = '';
        foreach($flujoConfig['nodes'] as $node){
            $allNodes[$node['id']] = $node;
            if ($node['typeObject'] === 'start'){
                $nodoStart  = $node['id'];
            }
        }
        //cotizacionId, nodo, etapa, campo, valor, fecha,

        $datosFinal = [];
        $datosFinal[0] = ['TAREA', 'NODO', 'ETAPA', 'CAMPO','CAMPOID', 'VALOR', 'FECHA', 'USUARIO'];

        foreach($camposAll as $campo){
             $usuario = $campo->usuario ?? null;
            $datosFinal[] = [
                'TAREA' => $campo->cotizacionId,
                'NODO' => $allNodes[$campo->nodoId?? $nodoStart]['nodoName'],
                'ETAPA' =>$typesNode[$allNodes[$campo->nodoId?? $nodoStart]['typeObject'] ?? 'default'] ?? 'Nodo sin etapa',
                'CAMPO' => $campo->label,
                'CAMPOID' => $campo->campo,
                'VALOR' => $campo->valorLong,
                'FECHA' => Carbon::parse($campo->createdAt)->setTimezone('America/Guatemala')->format('d/m/Y H:i'),
                'USUARIO'=> $usuario->name?? 'Sin Usuario',
            ];
        }

        $spreadsheet = new Spreadsheet();

        $spreadsheet
            ->getProperties()
            ->setCreator("GastosMedicos-ElRoble")
            ->setLastModifiedBy('Automator') // última vez modificado por
            ->setTitle('Reporte de Logs Bitacora')
            ->setDescription('Reporte');

        // first sheet
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle("Hoja 1");
        $sheet->fromArray($datosFinal, NULL, 'A1');

        foreach ($sheet->getRowIterator() as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $cell->setValueExplicit($cell->getValue(), DataType::TYPE_STRING);
            }
        }

        $writer = new Xlsx($spreadsheet);
        $fileNameHash = md5(uniqid());
        $tmpPath = storage_path("tmp/{$fileNameHash}.xlsx");
        $writer->save($tmpPath);

        $disk = Storage::disk('s3');
        $path = $disk->putFileAs("/tmp/files", $tmpPath, "{$fileNameHash}.xlsx");
        $temporarySignedUrl = Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(10));

        return $this->ResponseSuccess('Reporte generado con éxito', ['url' => $temporarySignedUrl]);
    }

    public function GenerarMasivo(Request $request) {
        ini_set('max_execution_time', 400);
        $AC = new AuthController();
        if (!$AC->CheckAccess(['reportes/generar'])) return $AC->NoAccess();

        $tareas = $request->get('tareas');
        $reporte = $request->get('reporte');
        $fechaIni = $request->get('fechaIni');
        $fechaFin = $request->get('fechaFin');

        $fechaIni = Carbon::parse($fechaIni);
        $fechaFin = Carbon::parse($fechaFin);

        $item = Reporte::where('id', $reporte)->first();
        if (empty($item)) {
            return $this->ResponseError('RPT-014', 'Reporte inválido');
        }

        $fieldsToGroup = [];
        $fieldsToSend = [];
        $fieldsToSendOrder = [
            0 // orden
        ];
        $reporteConfig = @json_decode($item->config, true);
        foreach ($reporteConfig['c'] as $item) {
            $fieldsToSend[$item['c']] = $item;
            $fieldsToSendOrder[] = $item['c'];
        }
        foreach ($reporteConfig['ag'] as $item) {
            //var_dump($item);;
            $fieldsToGroup[$item['c']] = $item['opt'];
        }

        $variablesDefault = $reporteConfig ['variablesDefault']?? [];
        foreach ($variablesDefault as $item) {
            $arrConfig = [
                'id' => $item,
                'p' => $item,
                'c' => $item,
            ];
            $fieldsToSend[$item] = $arrConfig;
            $fieldsToSendOrder[] = $item;
        }

        $cotizaciones = Cotizacion::whereIn('id', $tareas)->with('campos')->get();

        $arrDataSend = [];
        $dataSend['token'] = $reporteConfig['tpl'] ?? '';
        $dataSend['operation'] = 'generate';
        $dataSend['response'] = 'url';

        $usuarioLogueado = auth('sanctum')->user();
        // var_dump($usuarioLogueado);
        $arrDataSend['IMPRESO_POR'] = $usuarioLogueado->name ?? 'N/D';

        $fieldsTypes = [];

        $headersSet = false;
        $allPathGroup = [[]];
        if (!empty($cotizaciones)) {
            //$arrDataSend['tabla_masiva']['headers'][] = "No.";

            $contador = 1;
            if(empty($arrDataSend['file']))  $arrDataSend['file'] = [];
            foreach ($cotizaciones as $key => $coti) {

                // numeración automática
                $arrDataSend['tabla_masiva']['rows'][$key][0] = $key + 1;

                foreach ($coti->campos as $campo) {
                    if ($campo->tipo === 'text' ||
                        $campo->tipo === 'option' ||
                        $campo->tipo === 'select' ||
                        $campo->tipo === 'textArea' ||
                        $campo->tipo === 'default'||
                        $campo->tipo === 'number' ||
                        $campo->tipo === 'date' ||
                        $campo->tipo === 'currency'
                    ) {
                        $fieldsTypes[$campo->campo] = $campo->tipo;

                        // si se deben agrupar
                        if (isset($fieldsToGroup[$campo->campo])) {
                            if ($fieldsToGroup[$campo->campo] === 'sum') {
                                if (!isset($arrDataSend[$campo->campo])) {
                                    $arrDataSend[$campo->campo] = 0;
                                }
                                $arrDataSend[$campo->campo] += $campo->valorLong;
                            }
                            if ($fieldsToGroup[$campo->campo] === 'showg') {
                                if (!isset($arrDataSend[$campo->campo])) {
                                    $arrDataSend[$campo->campo] = $campo->valorLong;
                                }
                            }
                        }

                        if (isset($fieldsToSend[$campo->campo])) {
                            $keyOrder = array_search($campo->campo, $fieldsToSendOrder);
                            /*if (!$headersSet) {
                                $arrDataSend['tabla_masiva']['headers'][] = $campo->label;
                            }*/
                            $arrDataSend['tabla_masiva']['rows'][$key][$keyOrder] = $campo->valorLong ?? 'N/D';
                        }

                        if ($campo->campo === 'USUARIO_ACT_NODO_nodo_caja') {
                            $arrDataSend['autorizado_por']['rows'][] = [
                                $contador,
                                $campo->valorLong,
                            ];
                        }
                    }
                    if($campo->isFile &&  $campo->tipo !== 'signature' && $campo->campo !==  'SYSTEM_TEMPLATE' && !empty($campo->valorLong)){
                        if(count($allPathGroup[count($allPathGroup)-1]) > 20) $allPathGroup[] = [];
                        $allPathGroup[count($allPathGroup)-1][] = $campo->valorLong;
                    }
                }

                $headersSet = true;
                $contador++;
            }
        }

        // formateo
        foreach ($arrDataSend as $fieldKey => $value){
            if (!empty($fieldsTypes[$fieldKey])) {

                if (is_array($value)) {
                    foreach ($value as $tk => $tv) {
                        if (is_float($tv)) {
                            $arrDataSend[$fieldKey][$tk] = number_format($tv, 2);
                        }
                    }
                }
                else {
                    if ($fieldsTypes[$fieldKey] === 'currency') {
                        $arrDataSend[$fieldKey] = number_format($value, 2);
                    }
                }
            }
        }

        $allLocalFiles = [];
        foreach($allPathGroup as $group){
            $headers = array(
                'Content-Type: application/json',
                'Authorization: Bearer '.env('ANY_SUBSCRIPTIONS_TOKEN')
            );
            $dataMerge = ["responseMerge" => "local", "merge" => $group];

            $ch = curl_init(env('ANY_SUBSCRIPTIONS_URL', '').'/formularios/docs-plus/pdf-merge');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dataMerge));
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $data = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            $dataResponse = @json_decode($data, true);
            // var_dump($dataResponse);

            if (!empty($dataResponse['status'])) {
                $allLocalFiles[] = $dataResponse['data']['path'];

            }
            else {
                return $this->ResponseError('RPT-014', $dataResponse['msg'] ?? 'Error al unir adjuntos');
            }

        }

        $dataSend['localPath'] = $allLocalFiles;
        $dataSend['data'] = $arrDataSend;
        $headers = array(
            'Content-Type: application/json',
            'Authorization: Bearer '.env('ANY_SUBSCRIPTIONS_TOKEN')
        );

        $ch = curl_init(env('ANY_SUBSCRIPTIONS_URL', '').'/formularios/docs-plus/generate-join');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dataSend));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $data = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        $dataResponse = @json_decode($data, true);
        // var_dump($dataResponse);

        if (!empty($dataResponse['status'])) {
            /*$tmpFile = base_path().'/public/tmp'.md5(uniqid()).".pdf";
            file_put_contents($tmpFile, fopen($dataResponse['data']['url'], 'r'));*/
            //return response()->download($tmpFile, "Reporte masivo Workflow.pdf");
            $url = base64_encode($dataResponse['data']['url']);
            $newUrl = env('APP_URL')."/api/reportes/download/file/{$url}";

            return $this->ResponseSuccess('Reporte generado con éxito', ['url' => $newUrl]);
        }
        else {
            return $this->ResponseError('RPT-015', $dataResponse['msg'] ?? 'Error al generar reporte');
        }
    }

    public function DescargarForzado($url) {

        $AC = new AuthController();
        //$url = $request->get('url');
        //if (!$AC->CheckAccess(['reportes/listar'])) return $AC->NoAccess();

        $urlNew = base64_decode($url);

        header("Content-type:application/pdf");
        header("Content-Disposition:attachment;filename=\"Reporte masivo Workflow.pdf\"");
        /*header("Content-Transfer-Encoding: Binary");
        header("Content-disposition: attachment; filename=\"Reporte masivo Workflow.pdf\"");*/
        readfile($urlNew);
        exit;
    }

    public function DeleteReporte(Request $request) {
        $AC = new AuthController();
        if (!$AC->CheckAccess(['reportes/eliminar'])) return $AC->NoAccess();

        $id = $request->get('id');
        try {
            $item = Reporte::find($id);

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

    public function ReportProgram(Request $request) {
        $date = Carbon::now();

        $reports = Reporte::where([['sendReport', 1],['dateToSend', '<=', $date]])->get();
        foreach($reports as $report){
            $period = $report->period;
            $mailconfig = json_decode($report->mailconfig, true);
            if(empty($period) || empty($mailconfig)) continue;

            $dateToSend = Carbon::parse($report->dateToSend);
            $fechaIni =  Carbon::parse($report->dateToSend);
            $newDateToSend = Carbon::parse($report->dateToSend);

            if($period === 'week'){
                $diff = $date->diffInWeeks($dateToSend) + 1;
                $fechaIni->subWeeks(1);
                $newDateToSend->addWeeks($diff);
            }else if($period === 'month'){
                $diff = $date->diffInMonths($dateToSend) + 1;
                $fechaIni->subMonths(1);
                $newDateToSend->addMonths($diff);
            }else if($period === 'year'){
                $diff = $date->diffInYears($dateToSend) + 1;
                $fechaIni->subYears(1);
                $newDateToSend->addYears($diff);
            }else {
                $diff = $date->diffInDays($dateToSend) + 1;
                $fechaIni->subDays(1);
                $newDateToSend->addDays($diff);
            }

            $requestTmp = new \Illuminate\Http\Request();
            $requestTmp->replace(['reporteId' => $report->id,'fechaIni' => $fechaIni, 'fechaFin' => $date]);

            $tmp = $this->Generar($requestTmp, true);
            $tmp = json_decode($tmp, true);

            if (empty($tmp['status'])) {
                return $this->ResponseError('REP-421', 'Error generar');
            }

            $destino = $mailconfig['destino'] ?? '';
            if(empty($destino)) continue;
            $asunto = $mailconfig['asunto'] ?? '';
            $config = $mailconfig['mailgun'] ?? [];
            $contenido = $mailconfig['salidasEmail'] ?? '';
            $attachments = [0 => ['url' => $tmp['data']['url'], 'name' => $report->nombre, 'ext'=> 'xlsx']];

            $data = [
                'destino' => $destino,
                'asunto' => $asunto,
                'config' => $config,
                'attachments' => $attachments,
                'contenido' => $contenido,
            ];

            $email = $this->sendEmail($data);
            $report->dateToSend = $newDateToSend->toDateString();
            $report->save();
        }
        return $this->ResponseSuccess('Reportes programados generados con exito');
    }

    public function sendEmail($data) {
        //data
        $destino = $data['destino']?? false;
        $asunto = $data['asunto']?? false;
        $config = $data['config'] ?? [];
        $attachments = $data['attachments'] ?? false;
        $contenido = $data['contenido'] ?? '';

        $attachmentsSend = [];
        if ($attachments) {
            foreach ($attachments as $attach) {
                $s3_file = file_get_contents($attach['url']);
                $attachmentsSend[] = ['fileContent' => $s3_file, 'filename' => ($attach['name'] ?? 'Sin nombre') . '.' . $attach['ext']];
            }
        }

        try {
            $mg = Mailgun::create($config['apiKey'] ?? ''); // For US servers
            $email = $mg->messages()->send($config['domain'] ?? '', [
                'from' => $config['from'] ?? '',
                'to' => $destino ?? '',
                'subject' => $asunto ?? '',
                'html' => $contenido,
                'attachment' => $attachmentsSend
            ]);
            return $this->ResponseSuccess('Enviado con exito');
        } catch (HttpClientException $e) {
            return $this->ResponseError('AUTH-RA94', 'Error al enviar notificación, verifique el correo o la configuración del sistema');
        }
    }

    public function calculateDatosFinal ($usersToFind, $prod, $campos, $tipo, $fechaIni, $fechaFin, $camposOri, $variablesDefault){
        $data = [];
        $principleDataCot = [];

        $strQueryFull = "SELECT C.id, C.dateCreated, C.dateExpire, C.productoId, C.usuarioId, C.usuarioIdAsignado, CD.campo, CD.valorLong, P.nombreProducto
                        FROM cotizaciones AS C
                        JOIN cotizacionesDetalle AS CD ON C.id = CD.cotizacionId
                        JOIN productos AS P ON C.productoId = P.id
                        WHERE 
                            C.productoId IN ($prod)
                            AND C.usuarioIdAsignado IN ({$usersToFind})
                            AND CD.campo IN ({$campos})
                            AND C.dateCreated >= '".$fechaIni." 00:00:00'
                            AND C.dateCreated <= '".$fechaFin." 23:59:59'
                        ";

        $strQueryCot = "SELECT C.id, C.dateCreated, C.dateExpire, C.productoId, C.usuarioId, C.usuarioIdAsignado, P.nombreProducto
                        FROM cotizaciones AS C
                        JOIN productos AS P ON C.productoId = P.id
                        WHERE 
                            C.productoId IN ($prod)
                            AND C.usuarioIdAsignado IN ({$usersToFind})
                            AND C.dateCreated >= '".$fechaIni." 00:00:00'
                            AND C.dateCreated <= '".$fechaFin." 23:59:59'
                        ";

        // $queryTmp = DB::select(DB::raw($strQueryFull));
        $queryTmpCot = DB::select(DB::raw($strQueryCot));

        /*var_dump($queryTmp);
        die;*/

        $datosFinal = [];
        $datosFinal[] = [
            'No.',
            'Fecha creación',
            'Fecha expiración',
            'Producto',
            'Vehiculo No.',
            'Cotización No.',
            'Marca',
            'Línea',
            'Tipo',
            'Modelo',
            'No. Pasajeros',
            'No. Pasajeros',
            'No. Chasis',
            'No. Motor',
            'Valor promedio',
            'Placa',
            'Es nuevo',
            'Alto riesgo',
            'Clasificación',
            /*'Usuario Asignado',
            'Usuario Creador',*/
        ];

        $campos = [];
        $tareaController = new TareaController();

        if($tipo === 'c'){
            $allDataCotizacion = [];

            foreach ($queryTmpCot as $tmp) {
                $dataForCotOrder =  $tareaController->calculateDataVehiculeForCot($tmp->id);

                /*var_dump($dataForCotOrder);
                die;*/


                foreach ($dataForCotOrder as $item) {


                    foreach ($dataForCotOrder as $keyVehi => $vehi) {
                        // vehiculos
                        // $datosFinal[0][] = "Vehiculo {$keyVehi}";



                        foreach ($vehi as $keyCot => $cot) {

                            // var_dump($cot);
                            $tmpItem = [
                                $tmp->id,
                                $tmp->dateCreated,
                                $tmp->dateExpire,
                                $tmp->nombreProducto,
                            ];

                            $tmpItem[] = $keyVehi;
                            $tmpItem[] = $cot['veh|id'] ?? '';
                            $tmpItem[] = $cot['veh|marca'] ?? '';
                            $tmpItem[] = $cot['veh|marca'] ?? '';
                            $tmpItem[] = $cot['veh|linea'] ?? '';
                            $tmpItem[] = $cot['veh|tipo'] ?? '';
                            $tmpItem[] = $cot['veh|modelo'] ?? '';
                            $tmpItem[] = $cot['veh|noPasajeros'] ?? '';
                            $tmpItem[] = $cot['veh|noChasis'] ?? '';
                            $tmpItem[] = $cot['veh|noMotor'] ?? '';
                            $tmpItem[] = $cot['veh|valorProm'] ?? '';
                            $tmpItem[] = $cot['veh|placa'] ?? '';
                            $tmpItem[] = $cot['veh|vehiculoNuevo'] ?? '';
                            $tmpItem[] = $cot['veh|altoRiesgoDisp'] ?? '';
                            $tmpItem[] = $cot['veh|CLASIFICACION'] ?? '';
                            $datosFinal[] = $tmpItem;
                        }
                    }
                }

                /*if (!empty($dataForCotOrder) && count($dataForCotOrder) > 0) {
                    // var_dump($dataForCotOrder);

                    $allDataCotizacion[$tmp->id] = [];
                    foreach($dataForCotOrder as $keyDataCot => $dataForCot){
                        $vehId = $dataForCot['vehId'] ?? 0;
                        $cotId = $dataForCot['cotId'] ?? 0;
                        $allDataCotizacion[$tmp->id][$vehId][] = $cotId ;
                        if (!empty($dataForCot['dataTables'])) {
                            foreach($dataForCot['dataTables'] as $dfcot){
                                $data[$tmp->id][$vehId][$cotId][$dfcot['campo']] = $dfcot['valorLong'];
                                if(in_array($dfcot['campo'], $camposOri)) $campos[$dfcot['campo']] = $dfcot['campo'];
                            }
                        }
                    }

                    $principleDataCot[$tmp->id]['id'] = $tmp->id;
                    $principleDataCot[$tmp->id]['dateCreated'] = $tmp->dateCreated;
                    $principleDataCot[$tmp->id]['dateExpire'] = $tmp->dateExpire;
                    $principleDataCot[$tmp->id]['nombreProducto'] = $tmp->nombreProducto;
                }*/
            }

            /*foreach ($queryTmp as $tmp) {

                $valorLong = $tmp->valorLong;
                if($tmp->campo === 'FECHA_HOY')  $valorLong = Carbon::now()->setTimezone('America/Guatemala')->toDateTimeString();
                $campos[$tmp->campo] = $tmp->campo;
                if((empty($tmp->cotizacionDetalleVehiculoCotId) || $tmp->cotizacionDetalleVehiculoCotId === 0)
                    && (empty($tmp->cotizacionVehiculoId) || $tmp->cotizacionVehiculoId === 0)){
                    foreach($allDataCotizacion[$tmp->id] as $vehId => $veh){
                        foreach($veh as $cotId){
                            $data[$tmp->id][$vehId][$cotId][$tmp->campo] = $valorLong;
                        }
                    }
                } else if((empty($tmp->cotizacionDetalleVehiculoCotId) || $tmp->cotizacionDetalleVehiculoCotId === 0)
                    && (!empty($tmp->cotizacionVehiculoId) && $tmp->cotizacionVehiculoId !== 0)){
                    foreach($allDataCotizacion[$tmp->id][$tmp->cotizacionVehiculoId] as $cotId){
                        $data[$tmp->id][$tmp->cotizacionVehiculoId][$cotId][$tmp->campo] = $valorLong;
                    }
                } else $data[$tmp->id][$tmp->cotizacionVehiculoId][$tmp->cotizacionDetalleVehiculoCotId][$tmp->campo] = $valorLong;
            }

            foreach ($camposOri as $campo) {
                if(!empty($campos[$campo])) $datosFinal[0][] = $campo;
            }

            foreach ($data as $p => $productodata) {
                foreach($productodata as $vehId => $vehiculoData){
                    foreach($vehiculoData as $cotId => $cotizacionData){
                        $datosFinal[$cotId]['id'] = $cotId;
                        $datosFinal[$cotId]['dateCreated'] = $principleDataCot[$p]['dateCreated'];
                        $datosFinal[$cotId]['dateExpire'] = $principleDataCot[$p]['dateExpire'];
                        $datosFinal[$cotId]['nombreProducto'] = $principleDataCot[$p]['nombreProducto'];

                        foreach ($camposOri as $campo) {
                            if(!empty($campos[$campo]))
                            $datosFinal[$cotId][$campo] =
                                (!empty($data[$p][$vehId][$cotId][$campo])
                                ? $data[$p][$vehId][$cotId][$campo]
                                : '');
                        }
                    }
                }
            }*/

            /*var_dump($datosFinal);
            die;*/
            return $datosFinal;
        }

        //var_dump($tipo);

        /*if($tipo === 'v'){
            $allDataVehicle = [];

            foreach ($queryTmpCot as $tmp) {
                $dataForVehOrder =  $tareaController->calculateDataVehiculeForVeh($tmp->id);
                $allDataVehicle[$tmp->id] = [];
                foreach($dataForVehOrder as $keyDataVeh => $dataForVeh){
                    $allDataVehicle[$tmp->id][] = $dataForVeh['vehId'];
                    foreach($dataForVeh['dataTables'] as $dfveh){
                        $data[$tmp->id][$dataForVeh['vehId']][$dfveh['campo']] = $dfveh['valorLong'];
                        if(in_array($dfveh['campo'], $camposOri)) $campos[$dfveh['campo']] = $dfveh['campo'];
                    }
                };

                $principleDataCot[$tmp->id]['id'] = $tmp->id;
                $principleDataCot[$tmp->id]['dateCreated'] = $tmp->dateCreated;
                $principleDataCot[$tmp->id]['dateExpire'] = $tmp->dateExpire;
                $principleDataCot[$tmp->id]['nombreProducto'] = $tmp->nombreProducto;
            }

            foreach ($queryTmp as $tmp) {
                $valorLong = $tmp->valorLong;
                if($tmp->campo === 'FECHA_HOY')  $valorLong = Carbon::now()->setTimezone('America/Guatemala')->toDateTimeString();
                $campos[$tmp->campo] = $tmp->campo;
                if(empty($tmp->cotizacionVehiculoId) || $tmp->cotizacionVehiculoId === 0){
                    foreach($allDataVehicle[$tmp->id] as $veh){
                        $data[$tmp->id][$veh][$tmp->campo] = $valorLong;
                    }
                }
                else $data[$tmp->id][$tmp->cotizacionVehiculoId][$tmp->campo] = $valorLong;
            }

            foreach ($camposOri as $campo) {
                if(!empty($campos[$campo])) $datosFinal[0][] = $campo;
            }

            foreach ($data as $p => $productodata) {
                foreach($productodata as $vehId => $vehiculoData){
                    $datosFinal[$vehId]['id'] = $vehId;
                    $datosFinal[$vehId]['dateCreated'] = $principleDataCot[$p]['dateCreated'];
                    $datosFinal[$vehId]['dateExpire'] = $principleDataCot[$p]['dateExpire'];
                    $datosFinal[$vehId]['nombreProducto'] = $principleDataCot[$p]['nombreProducto'];

                    foreach ($camposOri as $campo) {
                        if(!empty($campos[$campo]))
                        $datosFinal[$vehId][$campo] =
                            (!empty($data[$p][$vehId][$campo])
                            ? $data[$p][$vehId][$campo]
                            : '');
                    }
                }
            }

            return $datosFinal;
        }*/

        /*else{

            foreach ($queryTmpCot as $tmp) {
                $dataForCotOrder =  $tareaController->calculateDataVehicule($tmp->id);
                foreach($dataForCotOrder as $dfcot){
                    $data[$tmp->id][$dfcot['campo']] = $dfcot['valorLong'];
                    if(in_array($dfcot['campo'], $camposOri)) $campos[$dfcot['campo']] = $dfcot['campo'];
                };

                $principleDataCot[$tmp->id]['id'] = $tmp->id;
                $principleDataCot[$tmp->id]['dateCreated'] = $tmp->dateCreated;
                $principleDataCot[$tmp->id]['dateExpire'] = $tmp->dateExpire;
                $principleDataCot[$tmp->id]['nombreProducto'] = $tmp->nombreProducto;
            }

            foreach ($queryTmp as $tmp) {
                $valorLong = $tmp->valorLong;
                if($tmp->campo === 'FECHA_HOY')  $valorLong = Carbon::now()->setTimezone('America/Guatemala')->toDateTimeString();
                $campos[$tmp->campo] = $tmp->campo;
                $data[$tmp->id][$tmp->campo] = $valorLong;
                //Logica para extraer la data de cotizaciones
            }

            foreach ($camposOri as $campo) {
                if(!empty($campos[$campo])) $datosFinal[0][] = $campo;
            }

            foreach ($data as $p => $productodata) {
                $datosFinal[$p]['id'] = $principleDataCot[$p]['id'];
                $datosFinal[$p]['dateCreated'] = $principleDataCot[$p]['dateCreated'];
                $datosFinal[$p]['dateExpire'] = $principleDataCot[$p]['dateExpire'];
                $datosFinal[$p]['nombreProducto'] = $principleDataCot[$p]['nombreProducto'];

                foreach ($camposOri as $campo) {
                    if(!empty($campos[$campo]))
                    $datosFinal[$p][$campo] =
                        (!empty($data[$p][$campo])
                        ? $data[$p][$campo]
                        : '');
                }
            }
            return $datosFinal;
        }*/

        /* foreach ($camposOri as $campo) {
            //for pero consultando si ya existe
            if(!empty($campos[$campo])) $datosFinal[0][] = $campo;
        }

        foreach ($queryTmp as $tmp) {
            //for
            $datosFinal[$tmp->id]['id'] = $tmp->id;
            $datosFinal[$tmp->id]['dateCreated'] = $tmp->dateCreated;
            $datosFinal[$tmp->id]['dateExpire'] = $tmp->dateExpire;
            $datosFinal[$tmp->id]['nombreProducto'] = $tmp->nombreProducto;

            foreach ($camposOri as $campo) {
                if(!empty($campos[$campo]))
                $datosFinal[$tmp->id][$campo] =
                    (!empty($data[$tmp->id][$campo])
                    ? $data[$tmp->id][$campo]
                    : '');
            }
        } */
    }

    public function getGraph(Request $request) {

        $AC = new AuthController();
        //if (!$AC->CheckAccess(['admin/flujos'])) return $AC->NoAccess();

        $fechaIni = $request->get('fechaIni');
        $fechaFin = $request->get('fechaFin');

        $fechaIni = Carbon::parse($fechaIni)->toDateString()." 00:00:00";
        $fechaFin = Carbon::parse($fechaFin)->toDateString()." 23:59:59";

        $strQueryFull = "SELECT
                        G.nombre as distribuidor, 
                        COUNT(C.id) as cotizaciones,
                        ROUND(SUM(C.primaNeta),2) as primaneta,
                        ROUND(AVG(C.primaNeta),2) as primapromedio,
                        (SELECT COUNT(*)
                            FROM cotizacionesDetalleVehiculosCot as C
                            JOIN cotizaciones AS Z ON C.cotizacionId = Z.id
                            JOIN cotizacionesDetalle AS ZD ON C.cotizacionId = ZD.cotizacionId
                            WHERE ZD.campo = 'EMISION_AS400.datosIdEmpresaGC.datos03.datosdePolizaGestorComercial.poliza'
                            AND Z.dateCreated >= '{$fechaIni} 00:00:00'
                            AND Z.dateCreated <= '{$fechaFin} 23:59:59'
                        ) AS polizas,
                        (ROUND((SELECT COUNT(*)
                            FROM cotizacionesDetalleVehiculosCot as C
                            JOIN cotizaciones AS Z ON C.cotizacionId = Z.id
                            JOIN cotizacionesDetalle AS ZD ON C.cotizacionId = ZD.cotizacionId
                            WHERE ZD.campo = 'EMISION_AS400.datosIdEmpresaGC.datos03.datosdePolizaGestorComercial.poliza'
                            AND Z.dateCreated >= '{$fechaIni} 00:00:00'
                            AND Z.dateCreated <= '{$fechaFin} 23:59:59'
                        )/COUNT(C.id),2)) as aceptacion,
                        (ROUND((SELECT COUNT(*)
                            FROM cotizacionesDetalleVehiculosCot as C
                            JOIN cotizaciones AS Z ON C.cotizacionId = Z.id
                            JOIN cotizacionesDetalle AS ZD ON C.cotizacionId = ZD.cotizacionId
                            WHERE ZD.campo = 'EMISION_AS400.datosIdEmpresaGC.datos03.datosdePolizaGestorComercial.poliza'
                            AND Z.dateCreated >= '{$fechaIni} 00:00:00'
                            AND Z.dateCreated <= '{$fechaFin} 23:59:59'
                        )/COUNT(C.id)*AVG(C.primaNeta),2)) as factorcomercial
                        FROM cotizacionesDetalleVehiculosCot AS C
                        JOIN catProductos AS P ON C.productoId = P.id
                        JOIN cotizaciones AS Z ON C.cotizacionId = Z.id
                        LEFT JOIN user_rol ON user_rol.userId = Z.usuarioId

                        LEFT JOIN usersGroupUsuarios ON usersGroupUsuarios.userId = Z.usuarioId
                        LEFT JOIN usersGroupRoles ON usersGroupRoles.rolId = user_rol.rolId
                        LEFT JOIN usersGroup AS G ON G.id = usersGroupRoles.userGroupId OR G.id = usersGroupUsuarios.userGroupId

                        WHERE Z.dateCreated >= '{$fechaIni} 00:00:00'
                        AND Z.dateCreated <= '{$fechaFin} 23:59:59'
                        GROUP BY  G.id
                        ORDER BY  G.id";

        $cotizacionesZ = DB::select(DB::raw($strQueryFull));

        $strQueryFull = "SELECT
                        P.nombre as producto,
                        COUNT(C.id) as cotizaciones,
                        ROUND(SUM(C.primaNeta),2) as primaneta,
                        ROUND(AVG(C.primaNeta),2) as primapromedio,
                        (SELECT COUNT(*)
                            FROM cotizacionesDetalleVehiculosCot as C
                            JOIN cotizaciones AS Z ON C.cotizacionId = Z.id
                            JOIN cotizacionesDetalle AS ZD ON C.cotizacionId = ZD.cotizacionId
                            WHERE ZD.campo = 'EMISION_AS400.datosIdEmpresaGC.datos03.datosdePolizaGestorComercial.poliza'
                            AND Z.dateCreated >= '{$fechaIni} 00:00:00'
                            AND Z.dateCreated <= '{$fechaFin} 23:59:59'
                        ) AS polizas,
                        (ROUND((SELECT COUNT(*)
                            FROM cotizacionesDetalleVehiculosCot as C
                            JOIN cotizaciones AS Z ON C.cotizacionId = Z.id
                            JOIN cotizacionesDetalle AS ZD ON C.cotizacionId = ZD.cotizacionId
                            WHERE ZD.campo = 'EMISION_AS400.datosIdEmpresaGC.datos03.datosdePolizaGestorComercial.poliza'
                            AND Z.dateCreated >= '{$fechaIni} 00:00:00'
                            AND Z.dateCreated <= '{$fechaFin} 23:59:59'
                        )/COUNT(C.id),2)) as aceptacion,
                        (ROUND((SELECT COUNT(*)
                            FROM cotizacionesDetalleVehiculosCot as C
                            JOIN cotizaciones AS Z ON C.cotizacionId = Z.id
                            JOIN cotizacionesDetalle AS ZD ON C.cotizacionId = ZD.cotizacionId
                            WHERE ZD.campo = 'EMISION_AS400.datosIdEmpresaGC.datos03.datosdePolizaGestorComercial.poliza'
                            AND Z.dateCreated >= '{$fechaIni} 00:00:00'
                            AND Z.dateCreated <= '{$fechaFin} 23:59:59'
                        )/COUNT(C.id)*AVG(C.primaNeta),2)) as factorcomercial
                        FROM cotizacionesDetalleVehiculosCot AS C
                        JOIN catProductos AS P ON C.productoId = P.id
                        JOIN cotizaciones AS Z ON C.cotizacionId = Z.id
                        WHERE Z.dateCreated >= '{$fechaIni} 00:00:00'
                        AND Z.dateCreated <= '{$fechaFin} 23:59:59'
                        GROUP BY C.productoId
                        ORDER BY C.productoId";


        $cotizacionesW = DB::select(DB::raw($strQueryFull));

        return $this->ResponseSuccess('Gráfica obtenida con éxito', [
             'z' => $cotizacionesZ, 'w' => $cotizacionesW
        ]);

    }
}
