<?php

namespace App\Http\Controllers;

use app\core\Response;
use App\Models\Cotizacion;
use App\Models\CotizacionDetalle;
use App\Models\CotizacionDetalleVehiculo;
use App\Models\Inspeccion;
use App\Models\Productos;
use App\Models\SistemaVariable;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InspeccionesController extends Controller {

    use Response;

    private function timeSteps($step, $start, $end, $isToday) {
        $stepHours = substr($step, 0, 2);
        $stepMinutes = substr($step, 3, 2);
        $stepSeconds = substr($step, 6, 2);

        $startTime = Carbon::createFromFormat('H:i:s', $start);
        $endTime = Carbon::createFromFormat('H:i:s', $end);
        $result = [];
        //dd($isToday);

        if ($isToday) {
            $mytime = Carbon::now();
        }

        $watchdog = 0;
        while ($startTime->lt($endTime)) {
            if ($isToday && $startTime->lt($mytime)) {
                $startTime->addHours($stepHours);
                $startTime->addMinutes($stepMinutes);
                $startTime->addSeconds($stepSeconds);
            }
            else {
                array_push($result, $startTime->format('H:i:s'));
                $startTime->addHours($stepHours);
                $startTime->addMinutes($stepMinutes);
                $startTime->addSeconds($stepSeconds);
            }
            $watchdog++;
            //dd($watchdog);
            //dd($result);
            if ($watchdog > 1000) break;
        }
        return $result;
    }


    public function getHorarios(Request $request) {

        set_time_limit(3600); //1 hora

        $AC = new AuthController();
        //if (!$AC->CheckAccess(['inpecciones/agendar'])) return $AC->NoAccess();

        $date = $request->get('date');
        $fechaConsulta = Carbon::parse($date)->format('Y-m-d');

        $users = User::where('active', 1)->with('horarios')->get();

        $dateTmp = Carbon::parse($date);
        $diaHorario = $dateTmp->dayOfWeek;

        $dateG = Carbon::parse($date);
        $isToday = $dateG->isToday();

        $horariosDisp = [];

        $tiempoReserva = "01:00:00";
        $tiempoTraslado = "00:30:00";
        $tempTiempoReserva = SistemaVariable::where('slug', 'INSPECCIONES_TIEMPO_RESERVA')->first() ?? '';
        if (!empty($tempTiempoReserva)) {
            $tiempoReserva = $tempTiempoReserva->contenido;
        }

        $tempTiempoTraslado = SistemaVariable::where('slug', 'INSPECCIONES_TIEMPO_TRASLADO')->first() ?? '';
        if (!empty($tempTiempoTraslado)) {
            $tiempoTraslado = $tempTiempoTraslado->contenido;
        }

        $secs = strtotime($tiempoTraslado)-strtotime("00:00:00");
        $tiempoReserva = date("H:i:s",strtotime($tiempoReserva)+$secs);

        // traigo las cotizaciones que ya tienen ese horario
        $producto = Productos::where('codigoInterno', 'AUTO_INSPECCIONES_FLOW')->first();

        $strQueryFull = "SELECT CTMP.*, CMTMP.usuarioIdAsignado
                         FROM
                            cotizacionesDetalle AS CTMP
                            JOIN cotizaciones AS CMTMP ON CTMP.cotizacionId = CMTMP.id
                            JOIN (SELECT CD.cotizacionId
                                FROM cotizacionesDetalle AS CD
                                JOIN cotizaciones AS C ON C.id = CD.cotizacionId
                                JOIN productos AS P ON P.id = C.productoId
                                WHERE P.codigoInterno = 'INSPECCIONES_FLOW'
                                AND CD.campo IN ('datos_cliente_fecha_cita', 'datos_cliente_hora_cita')
                                AND CD.valorLong = '{$fechaConsulta}'
                                GROUP BY CD.cotizacionId) AS CDTMP ON CTMP.cotizacionId = CDTMP.cotizacionId
                            AND CTMP.campo IN ('datos_cliente_fecha_cita', 'datos_cliente_hora_cita')";

        $fechasTmp = [];
        $dataAgendada = DB::select(DB::raw($strQueryFull));

        foreach ($dataAgendada as $tmp) {
            if ($tmp->campo === 'datos_cliente_fecha_cita') {
                $fechasTmp[$tmp->usuarioIdAsignado][$tmp->cotizacionId]['fecha'] = $tmp->valorLong;
            }
            else {
                $fechasTmp[$tmp->usuarioIdAsignado][$tmp->cotizacionId]['hora'] = $tmp->valorLong;
            }
        }

        // validación de fechas
        $fechasAgenda = [];
        foreach ($fechasTmp as $usuarioID => $tmp) {
            foreach ($tmp as $tmp2) {
                $fechasAgenda[$usuarioID][$tmp2['fecha']][] = $tmp2['hora'];
            }
        }

        foreach ($users as $user) {

            // var_dump($user->id);

            foreach ($user->horarios as $horario) {

                if ($diaHorario != $horario->diaSemana) continue;

                if (empty($horario->horaInicio)) continue;

                if (!empty($horario->horaDescansoInicio) && !empty($horario->horaDescansoFin) && !empty($horario->tieneDescanso)) {

                    $rangosDisponibesADC = $this->timeSteps($tiempoReserva, $horario->horaInicio, $horario->horaDescansoInicio, $isToday);
                    //dd($rangosDisponibesADC);
                    $rangosDisponibesDC = $this->timeSteps($tiempoReserva, $horario->horaDescansoFin, $horario->horaFin, $isToday);
                    //dd($rangosDisponibesDC);
                    $arrHorarioTotales = array_merge($rangosDisponibesADC, $rangosDisponibesDC);
                }
                else {
                    // var_dump('adsfasdf');
                    $arrHorarioTotales = $this->timeSteps($tiempoReserva, $horario->horaInicio, $horario->horaFin, $isToday);
                }

                foreach ($arrHorarioTotales as $horario) {

                    if (isset($fechasAgenda[$user->id][$fechaConsulta])) {
                        if (!in_array($horario, $fechasAgenda[$user->id][$fechaConsulta])) {
                            $horariosDisp['horario'][] = $horario;
                        }
                    }
                    else {
                        $horariosDisp['horario'][] = $horario;
                    }
                }
            }
        }

        return $this->ResponseSuccess('Horarios obtenidos con éxito', $horariosDisp);
    }

    public function getAgendadas(Request $request) {

        set_time_limit(3600); //1 hora

        $AC = new AuthController();
        //if (!$AC->CheckAccess(['inpecciones/agendar'])) return $AC->NoAccess();

        $date = $request->get('date');
        //$fecha = Carbon::parse($date)->toDateString();

        $inspec = Inspeccion::where('activo', 1)->with('usuario')->get();

        return $this->ResponseSuccess('Inspecciones obtenidas con éxito', $inspec);

        // return $this->ResponseError('INS-01', 'Fecha seleccionada inválida');
        /*
                if ($opt === 'get') {
                    $tmpData = $tmptable->get();
                    return $this->ResponseSuccess('Catálogo cargado con éxito', $tmpData);
                }
                else if ($opt === 'sync') {

                    if (empty($responseSync)) {
                        return $this->ResponseError('SYNC-01', 'Error de sincronización, es posible que los servicios no respondan');
                    }

                    return $this->ResponseSuccess('Catálogo sincronizado con éxito');
                }*/
    }

    public function startAgenda(Request $request) {

        set_time_limit(3600); //1 hora

        $AC = new AuthController();
        //if (!$AC->CheckAccess(['inpecciones/agendar'])) return $AC->NoAccess();

        $autoId = $request->get('autoId');
        $date = $request->get('date');
        $time = $request->get('time');
        $type = $request->get('type');
        $direccion = $request->get('direccion');

        $date = Carbon::parse($date)->format('Y-m-d');
        $usuarioLogueado = auth('sanctum')->user();

        if ($type === 'auto') {
            $producto = Productos::where('codigoInterno', 'AUTO_INSPECCIONES_FLOW')->first();
        }
        else {
            $producto = Productos::where('codigoInterno', 'INSPECCIONES_FLOW')->first();
        }

        // var_dump($producto);

        $flujo = $producto->flujo->first();
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
        $item->token = trim(bin2hex(random_bytes(18))).time();
        $item->estado = 'creada';
        $item->productoId = $producto->id;
        $item->codigoAgente = $codigoAgente ?? null;

        if ($item->save()) {

            // pre inserta detalle
            if ($type !== 'auto') {

                $arrData = [
                    'datos_cliente_fecha_cita' => [
                        'type' => 'text',
                        'value' => $date,
                    ],
                    'datos_cliente_hora_cita' => [
                        'type' => 'text',
                        'value' => $time,
                    ],
                ];

                foreach ($arrData as $keyCampo => $campo) {
                    $campoTmp = CotizacionDetalle::where('campo', $keyCampo)->where('cotizacionId', $item->id)->first();
                    if (empty($campoTmp)) {
                        $campoTmp = new CotizacionDetalle();
                    }
                    $campoTmp->cotizacionId = $item->id;
                    $campoTmp->seccionKey = 0;
                    $campoTmp->campo = $keyCampo;
                    $campoTmp->label = '';
                    $campoTmp->useForSearch = 1;
                    $campoTmp->tipo = $campo['type'];
                    $campoTmp->valorLong = $campo['value'];
                    $campoTmp->save();
                }
            }


            // guardo el id de inspeccion en la cotización
            if (!empty($autoId)) {
                if(empty($direccion)) return $this->ResponseError('COT-015', 'Error, Falta dirección');
                $vehiculo = CotizacionDetalleVehiculo::where('id', $autoId)->first();
                $vehiculo->inspeccionId = $item->id;
                $vehiculo->direccion = $direccion;
                $vehiculo->save();

                $marca = !empty($vehiculo->marca)? $vehiculo->marca->nombre : 'Sin marca';
                $linea = !empty($vehiculo->linea)? $vehiculo->linea->nombre : 'Sin linea';
                $tipo =!empty($vehiculo->tipo)? $vehiculo->tipo->nombre : 'Sin tipo';
                $noPasajeros = $vehiculo->noPasajeros;
                $noChasis = $vehiculo->noChasis;
                $noMotor = $vehiculo->noMotor;
                $modelo = $vehiculo->modelo;
                $placa = $vehiculo->placa;

                $arrData = [
                    'marca' => [
                        'type' => 'text',
                        'value' => $marca,
                        'label' => 'Marca'
                    ],
                    'linea' => [
                        'type' => 'text',
                        'value' => $linea,
                        'label' => 'Linea'
                    ],
                    'tipo' => [
                        'type' => 'text',
                        'value' => $tipo,
                        'label' => 'Tipo'
                    ],
                    'datos_vehiculo_no_pasajeros' => [
                        'type' => 'text',
                        'value' => $noPasajeros,
                        'label' => 'Numero de pasajeros'
                    ],
                    'datos_vehiculo_chasis' => [
                        'type' => 'text',
                        'value' => $noChasis,
                        'label' => 'Chasis'
                    ],
                    'datos_vehiculo_motor' => [
                        'type' => 'text',
                        'value' => $noMotor,
                        'label' => 'Motor'
                    ],
                    'modelo' => [
                        'type' => 'text',
                        'value' => $modelo,
                        'label' => 'Modelo'
                    ],
                    'datos_vehiculo_placa' => [
                        'type' => 'text',
                        'value' => $placa,
                        'label' => 'Placa'
                    ],
                    'datos_vehiculo_direccion' => [
                        'type' => 'text',
                        'value' => $direccion,
                        'label' => 'Dirección'
                    ]
                ];

                foreach ($arrData as $keyCampo => $campo) {
                    $campoTmp = CotizacionDetalle::where('campo', $keyCampo)->where('cotizacionId', $item->id)->first();
                    if (empty($campoTmp)) {
                        $campoTmp = new CotizacionDetalle();
                    }
                    $campoTmp->cotizacionId = $item->id;
                    $campoTmp->seccionKey = 0;
                    $campoTmp->campo = $keyCampo;
                    $campoTmp->label = $campo['label'];
                    $campoTmp->useForSearch = 1;
                    $campoTmp->tipo = $campo['type'];
                    $campoTmp->valorLong = $campo['value'];
                    $campoTmp->save();
                }

            }

            return $this->ResponseSuccess('Tarea iniciada con éxito', ['token' => $item->token, 'id' => $item->id, 'ptoken' => $producto->token]);
        }
        else {
            return $this->ResponseError('COT-014', 'Error al iniciar tarea, por favor intente de nuevo');
        }
    }
}
