<?php

namespace App\Http\Controllers;

use app\core\Response;
use App\Models\Canales;
use App\Models\catZona;
use App\Models\catAgenteTarifa;
use App\Models\catProfesion;
use App\Models\catBeneficiarios;
use App\Models\catCoberturas;
use App\Models\catCodigoAgente;
use App\Models\catFormaPago;
use App\Models\catLinea;
use App\Models\catMarca;
use App\Models\catEstadoCivil;
use App\Models\catProductoCobertura;
use App\Models\catProductoTarifaCobertura;
use App\Models\catProductoTarifaDescuentoRecargo;
use App\Models\catProductos;
use App\Models\catLineaIntermediario;
use App\Models\catProductoTarifa;
use App\Models\catProductoTarifaGrupoUsuario;
use App\Models\catTarifas;
use App\Models\catTipoCuentaTarjeta;
use App\Models\catTipoDocumento;
use App\Models\catTipoLicencia;
use App\Models\catTipoLinea;
use App\Models\catTipoMovimiento;
use App\Models\catTipoPlaca;
use App\Models\catTipoProductos;
use App\Models\catMedioCobro;
use App\Models\catTipoTarifas;
use App\Models\catTipoVehiculo;
use App\Models\catClaseTarjeta;
use App\Models\catTipoCuentaBancaria;
use App\Models\catBancoEmisor;
use App\Models\catSexo;
use App\Models\catZonaEmision;
use App\Models\catNacionalidad;
use App\Models\catTipoCliente;
use App\Models\catTipoSociedad;
use App\Models\catActividadEconomica;
use App\Models\catTipoUso;
use App\Models\catTipoCombustible;
use App\Models\catTipoCartera;
use App\Models\catSubtipoMovimiento;
use App\Models\catDepartamento;
use App\Models\catMunicipio;
use App\Models\catCodigoAlarma;
use App\Models\catPromociones;
use App\Models\catTipoTecnologia;
use App\Models\catTipoAsignacion;
use App\Models\catTipoUsuario;
use App\Models\catSeleccion;
use App\Models\catGrupoCoberturas;

use App\Models\CotizacionDetalleVehiculo;
use App\Models\CotizacionDetalleVehiculoCotizacion;
use App\Models\CotizacionDetalleVehiculoCotizacionCobertura;
use App\Models\SistemaVariable;
use App\Models\UserGrupoUsuario;
use App\Models\frecuenciaPago;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\CotizacionBitacora;

class CatalogosController extends Controller {

    use Response;

    // grupos de usuario
    public function Canales() {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['admin/canales'])) return $AC->NoAccess();

        $items = Canales::all();

        if (!empty($items)) {
            return $this->ResponseSuccess('Información obtenida con éxito', $items);
        }
        else {
            return $this->ResponseError('CAT-001', 'Error al listar información');
        }
    }

    public function sync($dataSend, $customUrl = '') {

        $url = env('ACSEL_AUTO_URL');
        $wsAcsel = new \ACSEL_WS();
        $wsAcsel->setAuthData($url . '/session/login', '{"usuario": "' . env('ACSEL_AUTO_USER') . '","contrasenia": "' . env('ACSEL_AUTO_PASS') . '","origen": "services"}');

        $response = false;
        $urlTmp = ($customUrl !== '') ? $customUrl : "{$url}/automoviles/cotizador/api/gestor_comercial";
        $data = $wsAcsel->post($urlTmp, $dataSend);

        if (!empty($data['dtaoutput'])) {
            $data = $wsAcsel->parseXml($data['dtaoutput']);
            // Chapus para convertir en array
            $json = json_encode($data);
            $array = json_decode($json, true);
            if (is_array($array)) {
                $response = $array;
            }
        }

        if ($response) {
            return $response;
        }
        else {
            return false;
        }
    }

    public function GetSyncCatalogoSlugs() {
        $arrCatalogos = [
            'productos' => [
                'nombre' => 'AS400 - Productos',
                'class' => 'catProductos',
                'campos' => [
                    'codigoProducto',
                    'nombre',
                    'descripcion',
                    'maxPolizas',
                    'maxCuotas',
                    'maxAnios',
                    'rangoPolizaDesde',
                    'rangoPolizaHasta',
                    'estado',
                    'idMoneda',
                    'rc',
                    'activo',
                    'flock',
                    'zonaEmision',
                    'tieneDescuento',
                ],
                'sendToAs400' => true,
            ],
            'tarifas' => [
                'nombre' => 'AS400 - Tarifas',
                'class' => 'catTarifas',
                'campos' => [
                    'idTarifa',
                    'clasificacion',
                    'descripcion',
                    'activo',
                    'flock',
                ],
                'sendToAs400' => true,
            ],
            'coberturas' => [
                'nombre' => 'AS400 - Coberturas',
                'class' => 'catCoberturas',
                'campos' => [
                    'codigo',
                    'nombre',
                    'tieneRango',
                    'activo',
                    'flock',
                    'grupo',
                    'tieneDescuento',
                ],
                'sendToAs400' => true,
            ],
            'marcas' => [
                'nombre' => 'AS400 - Marcas',
                'class' => 'catMarca',
                'campos' => [
                    'codigo',
                    'nombre',
                    'asegurable',
                ],
            ],
            'lineas' => [
                'nombre' => 'AS400 - Líneas',
                'class' => 'catLinea',
                'campos' => [
                    'marcaId',
                    'marcaCodigo',
                    'codigo',
                    'nombre',
                    'clasificacion',
                    'noAsegurable',
                    'altoRiesgo',
                    'altoRiesgoMin',
                    'altoRiesgoMax',
                ],
            ],
            'tipo_placa' => [
                'nombre' => 'AS400 - Tipo de placa',
                'class' => 'catTipoPlaca',
                'campos' => [
                    'codigo',
                    'nombre',
                ],
            ],
            'tipo_cuenta_tarjeta' => [
                'nombre' => 'AS400 - Tipo de placa',
                'class' => 'catTipoCuentaTarjeta',
                'campos' => [
                    'codigo',
                    'nombre',
                ],
            ],
            'filter_tarifas' => [
                'nombre' => 'AS400 - Tarifas por usuario',
                'class' => 'customTarifasPorUsuario',
                'custom' => true,
                'campos' => [
                    'idTarifa',
                    'clasificacion',
                    'descripcion',
                ],
            ],
            'formas_pago' => [
                'nombre' => 'AS400 - Formas de pago',
                'class' => 'catFormaPago',
                'custom' => true,
                'campos' => [
                    'codigo',
                    'descripcion',
                    'activo',
                    'flock',
                ],
            ],
            'estado_civil' => [
                'nombre' => 'AS400 - Estado civil',
                'class' => 'catEstadoCivil',
                'custom' => true,
                'campos' => [
                    'codigo',
                    'descripcion',
                    'activo',
                    'flock',
                ],
            ],
            'profesion' => [
                'nombre' => 'AS400 - Profesión',
                'class' => 'catProfesion',
                'custom' => true,
                'campos' => [
                    'codigo',
                    'descripcion',
                    'activo',
                    'flock',
                ],
            ],
            'zona' => [
                'nombre' => 'AS400 - Zona',
                'class' => 'catZona',
                'custom' => true,
                'campos' => [
                    'codigo',
                    'descripcion',
                    'isRoja',
                    'activo',
                    'flock',
                    'municipio',
                ],
            ],
            'tipo_licencia' => [
                'nombre' => 'AS400 - Tipo de licencia',
                'class' => 'catTipoLicencia',
                'custom' => true,
                'campos' => [
                    'codigo',
                    'nombre',
                    'activo',
                    'flock',
                ],
            ],
            'medio_cobro' => [
                'nombre' => 'AS400 - Medio de cobro',
                'class' => 'catMedioCobro',
                'custom' => true,
                'campos' => [
                    'codigo',
                    'nombre',
                    'activo',
                    'flock',
                ],
            ],
            'clase_tarjeta' => [
                'nombre' => 'AS400 - Clase Tarjeta',
                'class' => 'catClaseTarjeta',
                'custom' => true,
                'campos' => [
                    'codigo',
                    'descripcion',
                    'activo',
                    'flock',
                ],
                'ruta' => [
                    'datos03',
                    'catalogosGestorComercial',
                    'listaCatalogos',
                    'lista'
                ],
                'codigoTabla' => '12'
            ],
            'tipo_cuenta_bancaria' => [
                'nombre' => 'AS400 - Tipo Cuenta Bancaria',
                'class' => 'catTipoCuentaBancaria',
                'custom' => true,
                'campos' => [
                    'codigo',
                    'descripcion',
                    'activo',
                    'flock',
                ],
                'ruta' => [
                    'datos03',
                    'catalogosGestorComercial',
                    'listaCatalogos',
                    'lista'
                ],
                'codigoTabla' => '58'
            ],
            'banco_emisor' => [
                'nombre' => 'AS400 - Banco Emisor',
                'class' => 'catBancoEmisor',
                'custom' => true,
                'campos' => [
                    'codigo',
                    'descripcion',
                    'activo',
                    'flock',
                ],
                'ruta' => [
                    'datos03',
                    'catalogosGestorComercial',
                    'listaCatalogos',
                    'lista'
                ],
                'codigoTabla' => '13'
            ],
            'tipo_linea' => [
                'nombre' => 'AS400 - Tipo de Linea',
                'class' => 'catTipoLinea',
                'campos' => [
                    'codigo',
                    'nombre',
                    'activo',
                    'flock',
                ],
            ],
            'tipo_vehiculo' => [
                'nombre' => 'AS400 - Tipo de Vehículo',
                'class' => 'catTipoVehiculo',
                'campos' => [
                    'codigo',
                    'nombre',
                    'activo',
                    'flock',
                ],
            ],
            'tipo_movimiento' => [
                'nombre' => 'AS400 - Tipo de Movimiento',
                'class' => 'catTipoMovimiento',
                'campos' => [
                    'codigo',
                    'nombre',
                    'activo',
                    'flock',
                ],
            ],
            'tipo_documento' => [
                'nombre' => 'AS400 - Tipo de Documento',
                'class' => 'catTipoDocumento',
                'campos' => [
                    'codigo',
                    'nombre',
                    'activo',
                    'flock',
                ],
            ],
            'tipo_tarifas' => [
                'nombre' => 'AS400 - Tipo de Tarifas',
                'class' => 'catTipoTarifas',
                'campos' => [
                    'codigo',
                    'nombre',
                    'activo',
                    'flock',
                ],
            ],
            'tipo_productos' => [
                'nombre' => 'AS400 - Tipo de Productos (producción)',
                'class' => 'catTipoProductos',
                'campos' => [
                    'codigo',
                    'nombre',
                    'activo',
                    'flock',
                ],
            ],
            'beneficiarios' => [
                'nombre' => 'AS400 - Beneficiarios',
                'class' => 'catBeneficiarios',
                'campos' => [
                    'codigo',
                    'nombre',
                    'activo',
                    'flock',
                ],
            ],
            'sexo' => [
                'nombre' => 'AS400 - Sexo',
                'class' => 'catSexo',
                'custom' => true,
                'campos' => [
                    'codigo',
                    'descripcion',
                    'activo',
                    'flock',
                ],
                'ruta' => [
                    'datos03',
                    'catalogosGestorComercial',
                    'listaCatalogos',
                    'lista'
                ],
                'codigoTabla' => '6'
            ],
            'zona_emision' => [
                'nombre' => 'AS400 - Zona Emisión',
                'class' => 'catZonaEmision',
                'custom' => true,
                'campos' => [
                    'codigo',
                    'descripcion',
                    'activo',
                    'flock',
                ],
                'ruta' => [
                    'datos03',
                    'catalogosGestorComercial',
                    'listaCatalogos',
                    'lista'
                ],
                'codigoTabla' => '2'
            ],
            'nacionalidad' => [
                'nombre' => 'AS400 - Nacionalidad',
                'class' => 'catNacionalidad',
                'custom' => true,
                'campos' => [
                    'codigo',
                    'descripcion',
                    'activo',
                    'flock',
                ],
                'ruta' => [
                    'datos03',
                    'catalogosGestorComercial',
                    'listaCatalogos',
                    'lista'
                ],
                'codigoTabla' => '29'
            ],
            'tipo_cliente' => [
                'nombre' => 'AS400 - Tipo Cliente',
                'class' => 'catTipoCliente',
                'custom' => true,
                'campos' => [
                    'codigo',
                    'descripcion',
                    'activo',
                    'flock',
                ],
                'ruta' => [
                    'datos03',
                    'catalogosGestorComercial',
                    'listaCatalogos',
                    'lista'
                ],
                'codigoTabla' => '54'
            ],
            'tipo_sociedad' => [
                'nombre' => 'AS400 - Tipo Sociedad',
                'class' => 'catTipoSociedad',
                'custom' => true,
                'campos' => [
                    'codigo',
                    'descripcion',
                    'activo',
                    'flock',
                ],
                'ruta' => [
                    'datos03',
                    'catalogosGestorComercial',
                    'listaCatalogos',
                    'lista'
                ],
                'codigoTabla' => '22'
            ],
            'actividad_economica' => [
                'nombre' => 'AS400 - Actividad Económica',
                'class' => 'catActividadEconomica',
                'custom' => true,
                'campos' => [
                    'codigo',
                    'descripcion',
                    'activo',
                    'flock',
                ],
                'ruta' => [
                    'datos03',
                    'catalogosGestorComercial',
                    'listaCatalogos',
                    'lista'
                ],
                'codigoTabla' => '14'
            ],
            'tipo_uso' => [
                'nombre' => 'AS400 - Tipo Uso',
                'class' => 'catTipoUso',
                'custom' => true,
                'campos' => [
                    'codigo',
                    'descripcion',
                    'activo',
                    'flock',
                ],
                'ruta' => [
                    'datos03',
                    'catalogosGestorComercial',
                    'listaCatalogos',
                    'lista'
                ],
                'codigoTabla' => '55'
            ],
            'tipo_combustible' => [
                'nombre' => 'AS400 - Tipo Combustible',
                'class' => 'catTipoCombustible',
                'custom' => true,
                'campos' => [
                    'codigo',
                    'descripcion',
                    'activo',
                    'flock',
                ],
                'ruta' => [
                    'datos03',
                    'catalogosGestorComercial',
                    'listaCatalogos',
                    'lista'
                ],
                'codigoTabla' => '56'
            ],
            'tipo_tecnologia' => [
                'nombre' => 'AS400 - Tipo Tecnología',
                'class' => 'catTipoTecnologia',
                'custom' => true,
                'campos' => [
                    'codigo',
                    'descripcion',
                    'activo',
                    'flock',
                ],
                'ruta' => [
                    'datos03',
                    'catalogosGestorComercial',
                    'listaCatalogos',
                    'lista'
                ],
                'codigoTabla' => '57'
            ],
            'tipo_cartera' => [
                'nombre' => 'AS400 - Tipo Cartera',
                'class' => 'catTipoCartera',
                'custom' => true,
                'campos' => [
                    'codigo',
                    'descripcion',
                    'activo',
                    'flock',
                ],
                'ruta' => [
                    'datos03',
                    'catalogosGestorComercial',
                    'listaCatalogos',
                    'lista'
                ],
                'codigoTabla' => '23'
            ],
            'subtipo_movimiento' => [
                'nombre' => 'AS400 - Subipo Movimiento',
                'class' => 'catSubtipoMovimiento',
                'custom' => true,
                'campos' => [
                    'codigo',
                    'descripcion',
                    'activo',
                    'flock',
                ],
                'ruta' => [
                    'datos03',
                    'catalogosGestorComercial',
                    'listaCatalogos',
                    'lista'
                ],
                'codigoTabla' => '18'
            ],
            'departamento' => [
                'nombre' => 'AS400 - Departamento',
                'class' => 'catDepartamento',
                'custom' => true,
                'campos' => [
                    'codigo',
                    'descripcion',
                    'activo',
                    'flock',
                    'pais',
                ],
                'ruta' => [
                    'datos03',
                    'catalogosGestorComercial',
                    'listaCatalogos',
                    'lista'
                ],
                'codigoTabla' => '26'
            ],
            'municipio' => [
                'nombre' => 'AS400 - Municipio',
                'class' => 'catMunicipio',
                'custom' => true,
                'campos' => [
                    'codigo',
                    'descripcion',
                    'activo',
                    'flock',
                    'departamento',
                ],
                'ruta' => [
                    'datos03',
                    'catalogosGestorComercial',
                    'listaCatalogos',
                    'lista'
                ],
                'codigoTabla' => '27'
            ],
            'codigo_alarma' => [
                'nombre' => 'AS400 - Codigo Alarma',
                'class' => 'catCodigoAlarma',
                'custom' => true,
                'campos' => [
                    'codigo',
                    'descripcion',
                    'activo',
                    'flock',
                ],
                'ruta' => [
                    'datos03',
                    'catalogosGestorComercial',
                    'listaCatalogos',
                    'lista'
                ],
                'codigoTabla' => '10'
            ],
            'promociones' => [
                'nombre' => 'AS400 - Promociones',
                'class' => 'catPromociones',
                'custom' => true,
                'campos' => [
                    'codigo',
                    'descripcion',
                    'activo',
                    'flock',
                ],
                'ruta' => [
                    'datos03',
                    'catalogosGestorComercial',
                    'listaCatalogos',
                    'lista'
                ],
                'codigoTabla' => '21'
            ],
            'tipo_asignacion' => [
                'nombre' => 'AS400 - Tipo Asignación',
                'class' => 'catTipoAsignacion',
                'custom' => true,
                'campos' => [
                    'codigo',
                    'descripcion',
                    'activo',
                    'flock',
                ],
                'ruta' => [
                    'datos03',
                    'catalogosGestorComercial',
                    'listaCatalogos',
                    'lista'
                ],
                'codigoTabla' => '68'
            ],
            'tipo_usuario' => [
                'nombre' => 'AS400 - Tipo Usuario',
                'class' => 'catTipoUsuario',
                'custom' => true,
                'campos' => [
                    'codigo',
                    'descripcion',
                    'activo',
                    'flock',
                ],
                'ruta' => [
                    'datos03',
                    'catalogosGestorComercial',
                    'listaCatalogos',
                    'lista'
                ],
                'codigoTabla' => '69'
            ],
            'seleccion' => [
                'nombre' => 'AS400 - Selección',
                'class' => 'catSeleccion',
                'custom' => true,
                'campos' => [
                    'codigo',
                    'descripcion',
                    'activo',
                    'flock',
                ],
                'ruta' => [
                    'datos03',
                    'catalogosGestorComercial',
                    'listaCatalogos',
                    'lista'
                ],
                'codigoTabla' => '81'
            ],
            'grupo_coberturas' => [
                'nombre' => 'Grupo Cobertura',
                'class' => 'catGrupoCoberturas',
                'custom' => true,
                'campos' => [
                    'id',
                    'nombre',
                    'descripcion',
                ]
            ],
            'linea_por_intermediario' => [
                'nombre' => 'Línea por intermediario',
                'class' => 'catLineaIntermediario',
                'custom' => true,
                'campos' => [
                    'id',
                    'codigoIntermediario',
                    'codigoZonaEmision',
                ]
            ],

        ];
        return $arrCatalogos;
    }

    public function LoadCatalogo(Request $request) {

        set_time_limit(3600); //1 hora

        $AC = new AuthController();
        if (!$AC->CheckAccess(['admin/catalogo/sync'])) return $AC->NoAccess();

        $opt = $request->get('opt');
        $catalogo = $request->get('slug');
        $dataExtra = $request->get('data');

        /*var_dump($catalogo);
        die;*/

        $logSync = [
            'AS' => [],
            'Updated' => [],
            'Created' => [],
            'Error' => [],
        ];
        $responseSync = false;
        $tmptable = false;

        if ($catalogo === 'marcas') {

            $tmptable = catMarca::where('activo', 1);
            $dataSync = [
                'nprogram' => 'XXPD539',
                'dtainput' => '<datosIdEmpresaGC><idEmpresa>01</idEmpresa><datos01><catalogosGestorComercial><codigoTabla>33</codigoTabla><nombreTabla></nombreTabla><descripcionTabla></descripcionTabla></catalogosGestorComercial></datos01><datos02></datos02><moneda></moneda></datosIdEmpresaGC>',
            ];
            if ($opt === 'sync') $responseSync = $this->sync($dataSync);

            if (!empty($responseSync)) {
                //var_dump($responseSync);
                $items = $responseSync['datos03']['catalogosGestorComercial']['listaCatalogos']['lista'] ?? false;
                if (!empty($items)) {
                    foreach ($items as $item) {
                        $logSync['AS'][] = $item;

                        $tmp = catMarca::where('codigo', $item['codigo'])->first();
                        $item['idGestor'] = $tmp->id ?? 0;

                        if (empty($tmp)) {
                            $tmp = new catMarca();
                            $logSync['Created'][] = $item;
                        }
                        else {
                            if (!empty($tmp->flock)) continue;
                            $logSync['Updated'][] = $item;
                        }
                        $tmp->codigo = $item['codigo'];
                        $tmp->nombre = $item['descripcion'];

                        if (!empty($item['codigo'])) {
                            $tmp->save();
                        }
                        else {
                            $logSync['Error'][] = $item;
                        }
                    }
                }
            }
        }
        else if ($catalogo === 'lineas') {
            $tmptable = catLinea::where('activo', 1);

            if ($opt === 'sync') {

                if (empty($dataExtra)) {
                    return $this->ResponseError('La sincronización de líneas general ha sido desactivada, por favor sincronice por marca');
                }

                $marcas = catMarca::where('activo', 1)->where('id', $dataExtra)->get();

                foreach ($marcas as $m) {

                    $dataSync = [
                        'nprogram' => 'XXPD539',
                        'dtainput' => "<datosIdEmpresaGC><idEmpresa>01</idEmpresa><datos01><catalogosGestorComercial><codigoTabla>01</codigoTabla><nombreTabla></nombreTabla><descripcionTabla>{$m->codigo}</descripcionTabla></catalogosGestorComercial></datos01><datos02></datos02><moneda>Q/$</moneda></datosIdEmpresaGC>",
                    ];

                    $responseSync = $this->sync($dataSync);
                    $items = $responseSync['datos03']['lineasVehiculo']['listaLineas']['lista'] ?? false;

                    if (!empty($items)) {

                        foreach ($items as $item) {
                            if (empty($item['id'])) continue;
                            $logSync['AS'][] = $item;
                            $tmp = catLinea::where('codigo', $item['id'])->where('marcaId', $m->id)->first();
                            $item['idGestor'] = $tmp->id ?? 0;

                            if (empty($tmp)) {
                                $tmp = new catLinea();
                                $logSync['Created'][] = $item;
                            }
                            else {
                                if (!empty($tmp->flock)) continue;
                                $logSync['Updated'][] = $item;
                            }

                            if (!empty($m->codigo) && is_string($item['linea']) && is_string($item['clasificacion'])) {
                                $tmp->codigo = $item['id'];
                                $tmp->marcaId = $m->id;
                                $tmp->marcaCodigo = $m->codigo;
                                $tmp->nombre = $item['linea'] ?? 'N/D';
                                $tmp->clasificacion = $item['clasificacion'] ?? 'N/D';
                                $tmp->save();
                            }
                            else {
                                $logSync['Error'][] = $item;
                            }
                        }
                    }
                }
            }
        }
        else if ($catalogo === 'lineas_no_asegurables') {
            $tmptable = catLinea::where('activo', 1)->where('noAsegurable', 1);

            if ($opt === 'sync') {

                // no asegurables
                $dataSync = [
                    'nprogram' => 'XXPD539',
                    'dtainput' => '<datosIdEmpresaGC><idEmpresa>01</idEmpresa><datos01><catalogosGestorComercial><codigoTabla>32</codigoTabla><nombreTabla></nombreTabla><descripcionTabla></descripcionTabla></catalogosGestorComercial></datos01><datos02></datos02><moneda></moneda></datosIdEmpresaGC>',
                ];
                $noAsegurables = [];
                if ($opt === 'sync') $responseSync = $this->sync($dataSync);
                $items = $responseSync['datos03']['catalogosGestorComercial']['listaCatalogos']['lista'] ?? false;

                if (!empty($items)) {
                    foreach ($items as $tmp) {
                        $tmp = catLinea::where('codigo', $tmp['codigo'])->first();
                        if (!empty($tmp)) {
                            $tmp->noAsegurable = 1;
                            $tmp->save();
                        }
                        else {
                            if (!empty($tmp->flock)) continue;
                        }
                    }
                }
            }
        }
        else if ($catalogo === 'tipo_placa') {

            $tmptable = catTipoPlaca::where('activo', 1);
            $dataSync = [
                'nprogram' => 'XXPD539',
                'dtainput' => '<datosIdEmpresaGC><idEmpresa>01</idEmpresa><datos01><catalogosGestorComercial><codigoTabla>25</codigoTabla><nombreTabla></nombreTabla><descripcionTabla></descripcionTabla></catalogosGestorComercial></datos01><datos02></datos02><moneda></moneda></datosIdEmpresaGC>',
            ];
            if ($opt === 'sync') $responseSync = $this->sync($dataSync);

            if (!empty($responseSync)) {

                $items = $responseSync['datos03']['catalogosGestorComercial']['listaCatalogos']['lista'] ?? false;
                if (!empty($items)) {
                    foreach ($items as $item) {
                        $tmp = catTipoPlaca::where('codigo', $item['codigo'])->first();
                        if (empty($tmp)) {
                            $tmp = new catTipoPlaca();
                        }
                        else {
                            if (!empty($tmp->flock)) continue;
                        }
                        $tmp->codigo = $item['codigo'];
                        $tmp->nombre = $item['descripcion'];
                        $tmp->save();
                    }
                }
            }
        }
        else if ($catalogo === 'formas_pago') {

            $tmptable = catFormaPago::where('activo', 1);
            $dataSync = [
                'nprogram' => 'XXPD539',
                'dtainput' => '<datosIdEmpresaGC><idEmpresa>01</idEmpresa><datos01><catalogosGestorComercial><codigoTabla>53</codigoTabla><nombreTabla></nombreTabla><descripcionTabla></descripcionTabla></catalogosGestorComercial></datos01><datos02></datos02><moneda></moneda></datosIdEmpresaGC>',
            ];
            if ($opt === 'sync') $responseSync = $this->sync($dataSync);

            if (!empty($responseSync)) {

                $items = $responseSync['datos03']['catalogosGestorComercial']['frecuenciaPagos']['listaFrecuencia'] ?? false;
                if (!empty($items)) {
                    foreach ($items as $item) {
                        $tmp = catFormaPago::where('codigo', $item['frecuencia'])->first();
                        if (empty($tmp)) {
                            $tmp = new catFormaPago();
                        }
                        else {
                            if (!empty($tmp->flock)) continue;
                        }
                        $tmp->codigo = $item['frecuencia'];
                        $tmp->descripcion = $item['descripcionFrecuencia'];
                        $tmp->numeroPagos = json_encode(str_split($item['numerosPagos'], 2));
                        $tmp->save();
                    }
                }
            }
        }
        else if ($catalogo === 'tipo_linea') {

            $tmptable = catTipoLinea::where('activo', 1);
            $dataSync = [
                'nprogram' => 'XXPD539',
                'dtainput' => '<datosIdEmpresaGC><idEmpresa>01</idEmpresa><datos01><catalogosGestorComercial><codigoTabla>11</codigoTabla><nombreTabla></nombreTabla><descripcionTabla></descripcionTabla></catalogosGestorComercial></datos01><datos02></datos02><moneda></moneda></datosIdEmpresaGC>',
            ];
            if ($opt === 'sync') $responseSync = $this->sync($dataSync);

            if (!empty($responseSync)) {

                $items = $responseSync['datos03']['catalogosGestorComercial']['listaCatalogos']['lista'] ?? false;
                if (!empty($items)) {
                    foreach ($items as $item) {
                        $tmp = catTipoLinea::where('codigo', $item['codigo'])->first();
                        if (empty($tmp)) {
                            $tmp = new catTipoLinea();
                        }
                        else {
                            if (!empty($tmp->flock)) continue;
                        }
                        $tmp->codigo = $item['codigo'];
                        $tmp->nombre = $item['descripcion'];
                        $tmp->save();
                    }
                }
            }
        }
        else if ($catalogo === 'tipo_vehiculo') {

            $tmptable = catTipoVehiculo::where('activo', 1);
            $dataSync = [
                'nprogram' => 'XXPD539',
                'dtainput' => '<datosIdEmpresaGC><idEmpresa>01</idEmpresa><datos01><catalogosGestorComercial><codigoTabla>9</codigoTabla><nombreTabla></nombreTabla><descripcionTabla></descripcionTabla></catalogosGestorComercial></datos01><datos02></datos02><moneda></moneda></datosIdEmpresaGC>',
            ];
            if ($opt === 'sync') $responseSync = $this->sync($dataSync);

            if (!empty($responseSync)) {
                $items = $responseSync['datos03']['catalogosGestorComercial']['listaCatalogos']['lista'] ?? false;
                if (!empty($items)) {
                    foreach ($items as $item) {
                        if (!is_string($item['codigo'])) continue;
                        $tmp = catTipoVehiculo::where('codigo', $item['codigo'])->first();
                        if (empty($tmp)) {
                            $tmp = new catTipoVehiculo();
                        }
                        else {
                            if (!empty($tmp->flock)) continue;
                        }
                        $tmp->codigo = $item['codigo'];
                        $tmp->nombre = $item['descripcion'];
                        $tmp->save();
                    }
                }
            }
        }
        else if ($catalogo === 'tipo_cuenta_tarjeta') {

            $tmptable = catTipoCuentaTarjeta::where('activo', 1);
            $dataSync = [
                'nprogram' => 'XXPD539',
                'dtainput' => '<datosIdEmpresaGC><idEmpresa>01</idEmpresa><datos01><catalogosGestorComercial><codigoTabla>40</codigoTabla><nombreTabla></nombreTabla><descripcionTabla></descripcionTabla></catalogosGestorComercial></datos01><datos02></datos02><moneda></moneda></datosIdEmpresaGC>',
            ];
            if ($opt === 'sync') $responseSync = $this->sync($dataSync);

            if (!empty($responseSync)) {
                $items = $responseSync['datos03']['catalogosGestorComercial']['listaCatalogos']['lista'] ?? false;
                if (!empty($items)) {
                    foreach ($items as $item) {
                        if (!is_string($item['codigo'])) continue;
                        $tmp = catTipoCuentaTarjeta::where('codigo', $item['codigo'])->first();
                        if (empty($tmp)) {
                            $tmp = new catTipoCuentaTarjeta();
                        }
                        else {
                            if (!empty($tmp->flock)) continue;
                        }
                        $tmp->codigo = $item['codigo'];
                        $tmp->nombre = $item['descripcion'];
                        $tmp->save();
                    }
                }
            }
        }
        else if ($catalogo === 'tipo_licencia') {

            $tmptable = catTipoLicencia::where('activo', 1);
            $dataSync = [
                'nprogram' => 'XXPD539',
                'dtainput' => '<datosIdEmpresaGC><idEmpresa>01</idEmpresa><datos01><catalogosGestorComercial><codigoTabla>7</codigoTabla><nombreTabla></nombreTabla><descripcionTabla></descripcionTabla></catalogosGestorComercial></datos01><datos02></datos02><moneda></moneda></datosIdEmpresaGC>',
            ];
            if ($opt === 'sync') $responseSync = $this->sync($dataSync);

            if (!empty($responseSync)) {
                $items = $responseSync['datos03']['catalogosGestorComercial']['listaCatalogos']['lista'] ?? false;
                if (!empty($items)) {
                    foreach ($items as $item) {
                        if (!is_string($item['codigo'])) continue;
                        $tmp = catTipoLicencia::where('codigo', $item['codigo'])->first();
                        if (empty($tmp)) {
                            $tmp = new catTipoLicencia();
                        }
                        else {
                            if (!empty($tmp->flock)) continue;
                        }
                        $tmp->codigo = $item['codigo'];
                        $tmp->nombre = $item['descripcion'];
                        $tmp->save();
                    }
                }
            }
        }
        else if ($catalogo === 'tipo_movimiento') {

            $tmptable = catTipoMovimiento::where('activo', 1);
            $dataSync = [
                'nprogram' => 'XXPD539',
                'dtainput' => '<datosIdEmpresaGC><idEmpresa>01</idEmpresa><datos01><catalogosGestorComercial><codigoTabla>17</codigoTabla><nombreTabla></nombreTabla><descripcionTabla></descripcionTabla></catalogosGestorComercial></datos01><datos02></datos02><moneda></moneda></datosIdEmpresaGC>',
            ];
            if ($opt === 'sync') $responseSync = $this->sync($dataSync);

            if (!empty($responseSync)) {
                $items = $responseSync['datos03']['catalogosGestorComercial']['listaCatalogos']['lista'] ?? false;
                if (!empty($items)) {
                    foreach ($items as $item) {
                        if (!is_string($item['codigo'])) continue;
                        $tmp = catTipoMovimiento::where('codigo', $item['codigo'])->first();
                        if (empty($tmp)) {
                            $tmp = new catTipoMovimiento();
                        }
                        else {
                            if (!empty($tmp->flock)) continue;
                        }
                        $tmp->codigo = $item['codigo'];
                        $tmp->nombre = $item['descripcion'];
                        $tmp->save();
                    }
                }
            }
        }
        else if ($catalogo === 'tipo_documento') {

            $tmptable = catTipoDocumento::where('activo', 1);
            $dataSync = [
                'nprogram' => 'XXPD539',
                'dtainput' => '<datosIdEmpresaGC><idEmpresa>01</idEmpresa><datos01><catalogosGestorComercial><codigoTabla>20</codigoTabla><nombreTabla></nombreTabla><descripcionTabla></descripcionTabla></catalogosGestorComercial></datos01><datos02></datos02><moneda></moneda></datosIdEmpresaGC>',
            ];
            if ($opt === 'sync') $responseSync = $this->sync($dataSync);

            if (!empty($responseSync)) {
                $items = $responseSync['datos03']['catalogosGestorComercial']['listaCatalogos']['lista'] ?? false;
                if (!empty($items)) {
                    foreach ($items as $item) {
                        if (!is_string($item['codigo'])) continue;
                        $tmp = catTipoDocumento::where('codigo', $item['codigo'])->first();
                        if (empty($tmp)) {
                            $tmp = new catTipoDocumento();
                        }
                        else {
                            if (!empty($tmp->flock)) continue;
                        }
                        $tmp->codigo = $item['codigo'];
                        $tmp->nombre = $item['descripcion'];
                        $tmp->save();
                    }
                }
            }
        }
        else if ($catalogo === 'tipo_tarifas') {

            $tmptable = catTipoTarifas::where('activo', 1);
            $dataSync = [
                'nprogram' => 'XXPD539',
                'dtainput' => '<datosIdEmpresaGC><idEmpresa>01</idEmpresa><datos01><catalogosGestorComercial><codigoTabla>15</codigoTabla><nombreTabla></nombreTabla><descripcionTabla></descripcionTabla></catalogosGestorComercial></datos01><datos02></datos02><moneda></moneda></datosIdEmpresaGC>',
            ];
            if ($opt === 'sync') $responseSync = $this->sync($dataSync);

            if (!empty($responseSync)) {
                $items = $responseSync['datos03']['catalogosGestorComercial']['listaCatalogos']['lista'] ?? false;
                if (!empty($items)) {
                    foreach ($items as $item) {
                        if (!is_string($item['codigo'])) continue;
                        $tmp = catTipoTarifas::where('codigo', $item['codigo'])->first();
                        if (empty($tmp)) {
                            $tmp = new catTipoTarifas();
                        }
                        else {
                            if (!empty($tmp->flock)) continue;
                        }
                        $tmp->codigo = $item['codigo'];
                        $tmp->nombre = $item['descripcion'];
                        $tmp->save();
                    }
                }
            }
        }
        else if ($catalogo === 'tarifas') {

            $url = env('ACSEL_AUTO_URL');

            $tmptable = catTarifas::where('activo', 1);
            $dataSync = [
                'nprogram' => '539',
                'dtainput' => '<datosIdEmpresa><idEmpresa>02</idEmpresa><datos01><tarifas></tarifas></datos01><datos02></datos02></datosIdEmpresa>',
            ];
            if ($opt === 'sync') $responseSync = $this->sync($dataSync, "{$url}/automoviles/cotizador/api/gestor_comercial");
            //dd($responseSync);
            $responseSync = false;
            if (!empty($responseSync)) {

                $items = $responseSync['datos03']['tarifas']['tiposProductos']['producto'] ?? false;
                if (!empty($items)) {
                    foreach ($items as $item) {
                        if (!is_string($item['idProducto'])) continue;
                        $tmp = catTarifas::where('idProducto', $item['idProducto'])->first();
                        if (empty($tmp)) {
                            $tmp = new catTarifas();
                        }
                        else {
                            if (!empty($tmp->flock)) continue;
                        }
                        $tmp->idTarifa = $item['idMoneda'];
                        $tmp->idProducto = $item['idProducto'];
                        $tmp->nombre = $item['nombreProducto'];
                        $tmp->descripcion = $item['descripcionProducto'];
                        $tmp->estado = $item['estado'];
                        $tmp->rangoPolizaDesde = $item['rangoPolizaDesde'];
                        $tmp->rangoPolizaHasta = $item['rangoPolizaHasta'];
                        $tmp->activo = 1;
                        $tmp->save();
                    }
                }
            }
        }
        else if ($catalogo === 'coberturas') {

            $tmptable = catCoberturas::where('activo', 1);
            /*$dataSync = [
                'nprogram' => '532',
                'dtainput' => '<datosIdEmpresa><idEmpresa>02</idEmpresa><datos01><tarifas></tarifas></datos01><datos02></datos02></datosIdEmpresa>',
            ];
            if ($opt === 'sync') $responseSync = $this->sync($dataSync, "{$url}/automoviles/cotizador/api/gestor_comercial");
            //dd($responseSync);
            $responseSync = false;
            if (!empty($responseSync)) {

                $items = $responseSync['datos03']['tarifas']['tiposProductos']['producto'] ?? false;
                if (!empty($items)){
                    foreach ($items as $item) {
                        if (!is_string($item['idProducto'])) continue;
                        $tmp = catTarifas::where('idProducto', $item['idProducto'])->first();
                        if (empty($tmp)) {
                            $tmp = new catTarifas();
                        }
                        else {
                            if (!empty($tmp->flock)) continue;
                        }
                        $tmp->idTarifa = $item['idMoneda'];
                        $tmp->idProducto = $item['idProducto'];
                        $tmp->nombre = $item['nombreProducto'];
                        $tmp->descripcion = $item['descripcionProducto'];
                        $tmp->estado = $item['estado'];
                        $tmp->rangoPolizaDesde = $item['rangoPolizaDesde'];
                        $tmp->rangoPolizaHasta = $item['rangoPolizaHasta'];
                        $tmp->activo = 1;
                        $tmp->save();
                    }
                }
            }*/
        }
        else if ($catalogo === 'codigo_agente') {

            $tmptable = catCodigoAgente::where('activo', 1);
            $dataSync = [
                'nprogram' => 'XXPD539',
                'dtainput' => '<datosIdEmpresaGC><idEmpresa>01</idEmpresa><datos01><catalogosGestorComercial><codigoTabla>31</codigoTabla><nombreTabla></nombreTabla><descripcionTabla></descripcionTabla></catalogosGestorComercial></datos01><datos02></datos02><moneda></moneda></datosIdEmpresaGC>',
            ];
            if ($opt === 'sync') $responseSync = $this->sync($dataSync);

            if (!empty($responseSync)) {
                $items = $responseSync['datos03']['catalogosGestorComercial']['listaCatalogos']['lista'] ?? false;
                if (!empty($items)) {
                    foreach ($items as $item) {
                        if (!is_string($item['codigo'])) continue;
                        $tmp = catCodigoAgente::where('codigo', $item['codigo'])->first();
                        if (empty($tmp)) {
                            $tmp = new catCodigoAgente();
                        }
                        else {
                            if (!empty($tmp->flock)) continue;
                        }
                        $tmp->codigo = $item['codigo'];
                        $tmp->nombre = $item['descripcion'];
                        $tmp->save();
                    }
                }
            }
        }
        else if ($catalogo === 'productos') {

            $tmptable = catProductos::whereIn('activo', [0,1]);
            $dataSync = [
                'nprogram' => 'XXPD539',
                'dtainput' => '<datosIdEmpresaGC><idEmpresa>01</idEmpresa><datos01><catalogosGestorComercial><codigoTabla>49</codigoTabla><nombreTabla></nombreTabla><descripcionTabla></descripcionTabla></catalogosGestorComercial></datos01><datos02></datos02><moneda></moneda></datosIdEmpresaGC>',
            ];
            if ($opt === 'sync') $responseSync = $this->sync($dataSync);
            //dd($responseSync);
            if (!empty($responseSync)) {

                $items = $responseSync['datos03']['catalogosGestorComercial']['tiposProductos']['producto'] ?? false;
                if (!empty($items)) {
                    foreach ($items as $item) {
                        if (!is_string($item['idProducto'])) continue;
                        $tmp = catProductos::where('codigoProducto', $item['idProducto'])->first();
                        if (empty($tmp)) {
                            $tmp = new catProductos();
                        }
                        else {
                            if (!empty($tmp->flock)) continue;
                        }
                        $tmp->idMoneda = $item['idMoneda'];
                        $tmp->codigoProducto = $item['idProducto'];
                        $tmp->nombre = $item['nombreProducto'];
                        $tmp->descripcion = $item['descripcionProducto'];
                        $tmp->estado = $item['estado'];
                        $tmp->rangoPolizaDesde = $item['rangoPolizaDesde'];
                        $tmp->rangoPolizaHasta = $item['rangoPolizaHasta'];
                        $tmp->activo = 1;
                        $tmp->save();
                    }
                }
            }
        }
        else if ($catalogo === 'tipo_productos') {

            $tmptable = catTipoProductos::where('activo', 1);
            $dataSync = [
                'nprogram' => 'XXPD539',
                'dtainput' => '<datosIdEmpresaGC><idEmpresa>01</idEmpresa><datos01><catalogosGestorComercial><codigoTabla>3</codigoTabla><nombreTabla></nombreTabla><descripcionTabla></descripcionTabla></catalogosGestorComercial></datos01><datos02></datos02><moneda></moneda></datosIdEmpresaGC>',
            ];
            if ($opt === 'sync') $responseSync = $this->sync($dataSync);
            //dd($responseSync);
            if (!empty($responseSync)) {
                $items = $responseSync['datos03']['catalogosGestorComercial']['listaCatalogos']['lista'] ?? false;
                if (!empty($items)) {
                    foreach ($items as $item) {
                        if (!is_string($item['codigo'])) continue;
                        $tmp = catTipoProductos::where('codigo', $item['codigo'])->first();
                        if (empty($tmp)) {
                            $tmp = new catTipoProductos();
                        }
                        else {
                            if (!empty($tmp->flock)) continue;
                        }
                        $tmp->codigo = $item['codigo'];
                        $tmp->nombre = $item['descripcion'];
                        $tmp->save();
                    }
                }
            }
        }
        else if ($catalogo === 'beneficiarios') {

            $tmptable = catBeneficiarios::where('activo', 1);
            $dataSync = [
                'nprogram' => 'XXPD539',
                'dtainput' => '<datosIdEmpresaGC><idEmpresa>01</idEmpresa><datos01><catalogosGestorComercial><codigoTabla>34</codigoTabla><nombreTabla></nombreTabla><descripcionTabla></descripcionTabla></catalogosGestorComercial></datos01><datos02></datos02><moneda></moneda></datosIdEmpresaGC>',
            ];
            if ($opt === 'sync') $responseSync = $this->sync($dataSync);
            //dd($responseSync);
            if (!empty($responseSync)) {
                $items = $responseSync['datos03']['catalogosGestorComercial']['listaCatalogos']['lista'] ?? false;
                if (!empty($items)) {
                    foreach ($items as $item) {
                        if (!is_string($item['codigo'])) continue;
                        $tmp = catBeneficiarios::where('codigo', $item['codigo'])->first();
                        if (empty($tmp)) {
                            $tmp = new catBeneficiarios();
                        }
                        else {
                            if (!empty($tmp->flock)) continue;
                        }
                        $tmp->codigo = $item['codigo'];
                        $tmp->nombre = $item['descripcion'];
                        $tmp->save();
                    }
                }
            }
        }
        else if ($catalogo === 'agente_tarifas') {
            $tmptable = catAgenteTarifa::where('activo', 1);

            if ($opt === 'sync') {

                $itemsParent = catCodigoAgente::where('activo', 1)->get();

                foreach ($itemsParent as $m) {

                    $dataSync = [
                        'nprogram' => 'XXPD539',
                        'dtainput' => "<datosIdEmpresaGC><idEmpresa>01</idEmpresa><datos01><catalogosGestorComercial><codigoTabla>61</codigoTabla><nombreTabla></nombreTabla><descripcionTabla>{$m->codigo}</descripcionTabla></catalogosGestorComercial></datos01><datos02></datos02><moneda>Q</moneda></datosIdEmpresaGC>",
                        //'dtainput' => "<datosIdEmpresaGC><idEmpresa>01</idEmpresa><datos01><catalogosGestorComercial><codigoTabla>61</codigoTabla><nombreTabla></nombreTabla><descripcionTabla>9605</descripcionTabla></catalogosGestorComercial></datos01><datos02></datos02><moneda>Q</moneda></datosIdEmpresaGC>",
                    ];

                    $responseSync = $this->sync($dataSync);
                    //dd($responseSync);
                    $items = $responseSync['datos03']['listaTarifasAgentes']['listaTarifa']['lista'] ?? false;

                    if (!empty($items)) {

                        foreach ($items as $item) {
                            if (empty($item['codigoTarifa'])) continue;
                            $tmp = catAgenteTarifa::where('codigoAgente', $m->codigo)->where('codigo', $item['codigoTarifa'])->first();
                            if (empty($tmp)) {
                                $tmp = new catAgenteTarifa();
                            }
                            else {
                                if (!empty($tmp->flock)) continue;
                            }
                            $tmp->codigo = $item['codigoTarifa'];
                            $tmp->codigoAgente = $m->codigo;
                            $tmp->nombre = $item['descripcion'] ?? 'N/D';
                            $tmp->save();
                        }
                    }
                }
            }
        }
        else if ($catalogo === 'estado_civil') {

            $tmptable = catEstadoCivil::where('activo', 1);
            $dataSync = [
                'nprogram' => 'XXPD539',
                'dtainput' => '<datosIdEmpresaGC><idEmpresa>01</idEmpresa><datos01><catalogosGestorComercial><codigoTabla>8</codigoTabla><nombreTabla></nombreTabla><descripcionTabla></descripcionTabla></catalogosGestorComercial></datos01><datos02></datos02><moneda></moneda></datosIdEmpresaGC>',
            ];
            if ($opt === 'sync') $responseSync = $this->sync($dataSync);

            if (!empty($responseSync)) {
                $items = $responseSync['datos03']['catalogosGestorComercial']['listaCatalogos']['lista'] ?? false;
                if (!empty($items)) {
                    foreach ($items as $item) {
                        if (!is_string($item['codigo'])) continue;
                        $tmp = catEstadoCivil::where('codigo', $item['codigo'])->first();
                        if (empty($tmp)) {
                            $tmp = new catEstadoCivil();
                        }
                        else {
                            if (!empty($tmp->flock)) continue;
                        }
                        $tmp->codigo = $item['codigo'];
                        $tmp->descripcion = $item['descripcion'];
                        $tmp->save();
                    }
                }
            }
        }
        else if ($catalogo === 'profesion') {

            $tmptable = catProfesion::where('activo', 1);
            $dataSync = [
                'nprogram' => 'XXPD539',
                'dtainput' => '<datosIdEmpresaGC><idEmpresa>01</idEmpresa><datos01><catalogosGestorComercial><codigoTabla>5</codigoTabla><nombreTabla></nombreTabla><descripcionTabla></descripcionTabla></catalogosGestorComercial></datos01><datos02></datos02><moneda></moneda></datosIdEmpresaGC>',
            ];
            if ($opt === 'sync') $responseSync = $this->sync($dataSync);

            if (!empty($responseSync)) {
                $items = $responseSync['datos03']['catalogosGestorComercial']['listaCatalogos']['lista'] ?? false;
                if (!empty($items)) {
                    foreach ($items as $item) {
                        if (!is_string($item['codigo'])) continue;
                        $tmp = catProfesion::where('codigo', $item['codigo'])->first();
                        if (empty($tmp)) {
                            $tmp = new catProfesion();
                        }
                        else {
                            if (!empty($tmp->flock)) continue;
                        }
                        $tmp->codigo = $item['codigo'];
                        $tmp->descripcion = $item['descripcion'];
                        $tmp->save();
                    }
                }
            }
        }
        else if ($catalogo === 'zona') {

            $tmptable = catZona::where('activo', 1);
            $dataSync = [
                'nprogram' => 'XXPD539',
                'dtainput' => '<datosIdEmpresaGC><idEmpresa>01</idEmpresa><datos01><catalogosGestorComercial><codigoTabla>28</codigoTabla><nombreTabla></nombreTabla><descripcionTabla></descripcionTabla></catalogosGestorComercial></datos01><datos02></datos02><moneda></moneda></datosIdEmpresaGC>',
            ];
            if ($opt === 'sync') $responseSync = $this->sync($dataSync);

            if (!empty($responseSync)) {
                $items = $responseSync['datos03']['catalogos']['listaCatalogos']['lista'] ?? false;
                if (!empty($items)) {
                    foreach ($items as $item) {
                        if (!is_string($item['codigo'])) continue;
                        $tmp = catZona::where('codigo', $item['codigo'])->first();
                        if (empty($tmp)) {
                            $tmp = new catZona();
                        }
                        else {
                            if (!empty($tmp->flock)) continue;
                        }
                        $tmp->codigo = $item['codigo'];
                        $tmp->descripcion = $item['descripcion'];
                        $tmp->municipio = substr($item['codigo'], 0, -3);
                        $tmp->save();
                    }
                }
            }
        }
        else if ($catalogo === 'medio_cobro') {

            $tmptable = catMedioCobro::where('activo', 1);
            $dataSync = [
                'nprogram' => 'XXPD539',
                'dtainput' => '<datosIdEmpresaGC><idEmpresa>01</idEmpresa><datos01><catalogosGestorComercial><codigoTabla>39</codigoTabla><nombreTabla></nombreTabla><descripcionTabla></descripcionTabla></catalogosGestorComercial></datos01><datos02></datos02><moneda></moneda></datosIdEmpresaGC>',
            ];

            if ($opt === 'sync') $responseSync = $this->sync($dataSync);

            if (!empty($responseSync)) {
                $items = $responseSync['datos03']['catalogosGestorComercial']['listaCatalogos']['lista'] ?? false;
                if (!empty($items)) {
                    foreach ($items as $item) {
                        $tmp = catMedioCobro::where('codigo', $item['codigo'])->first();
                        if (empty($tmp)) {
                            $tmp = new catMedioCobro();
                        }
                        else {
                            if (!empty($tmp->flock)) continue;
                        }

                        $tmp->codigo = $item['codigo'];
                        $tmp->nombre = $item['descripcion'];
                        $tmp->save();
                    }
                }
            }

        }
        else if ($catalogo === 'clase_tarjeta') {
            $tmptable = catClaseTarjeta::where('activo', 1);
            $responseSync = $this->SyncCatalogo($catalogo, $opt);
        }
        else if ($catalogo === 'tipo_cuenta_bancaria') {
            $tmptable = catTipoCuentaBancaria::where('activo', 1);
            $responseSync = $this->SyncCatalogo($catalogo, $opt);
        }
        else if ($catalogo === 'banco_emisor') {
            $tmptable = catBancoEmisor::where('activo', 1);
            $responseSync = $this->SyncCatalogo($catalogo, $opt);
        }
        else if ($catalogo === 'sexo') {
            $tmptable = catSexo::where('activo', 1);
            $responseSync = $this->SyncCatalogo($catalogo, $opt);
        }
        else if ($catalogo === 'zona_emision') {
            $tmptable = catZonaEmision::where('activo', 1);
            $responseSync = $this->SyncCatalogo($catalogo, $opt);
        }
        else if ($catalogo === 'nacionalidad') {
            $tmptable = catNacionalidad::where('activo', 1);
            $responseSync = $this->SyncCatalogo($catalogo, $opt);
        }
        else if ($catalogo === 'tipo_cliente') {
            $tmptable = catTipoCliente::where('activo', 1);
            $responseSync = $this->SyncCatalogo($catalogo, $opt);
        }
        else if ($catalogo === 'tipo_sociedad') {
            $tmptable = catTipoSociedad::where('activo', 1);
            $responseSync = $this->SyncCatalogo($catalogo, $opt);
        }
        else if ($catalogo === 'actividad_economica') {
            $tmptable = catActividadEconomica::where('activo', 1);
            $responseSync = $this->SyncCatalogo($catalogo, $opt);
        }
        else if ($catalogo === 'tipo_uso') {
            $tmptable = catTipoUso::where('activo', 1);
            $responseSync = $this->SyncCatalogo($catalogo, $opt);
        }
        else if ($catalogo === 'tipo_combustible') {
            $tmptable = catTipoCombustible::where('activo', 1);
            $responseSync = $this->SyncCatalogo($catalogo, $opt);
        }
        else if ($catalogo === 'tipo_tecnologia') {
            $tmptable = catTipoTecnologia::where('activo', 1);
            $responseSync = $this->SyncCatalogo($catalogo, $opt);
        }
        else if ($catalogo === 'tipo_cartera') {
            $tmptable = catTipoCartera::where('activo', 1);
            $responseSync = $this->SyncCatalogo($catalogo, $opt);
        }
        else if ($catalogo === 'subtipo_movimiento') {
            $tmptable = catSubtipoMovimiento::where('activo', 1);
            $responseSync = $this->SyncCatalogo($catalogo, $opt);
        }
        else if ($catalogo === 'linea_por_intermediario') {
            $tmptable = catLineaIntermediario::where('activo', 1);
            //$responseSync = $this->SyncCatalogo($catalogo, $opt);
        }
        else if ($catalogo === 'departamento') {
            $tmptable = catDepartamento::where('activo', 1);
            $dataSync = [
                'nprogram' => 'XXPD539',
                'dtainput' => '<datosIdEmpresaGC><idEmpresa>01</idEmpresa><datos01><catalogosGestorComercial><codigoTabla>26</codigoTabla><nombreTabla></nombreTabla><descripcionTabla></descripcionTabla></catalogosGestorComercial></datos01><datos02></datos02><moneda></moneda></datosIdEmpresaGC>',
            ];

            if ($opt === 'sync') $responseSync = $this->sync($dataSync);

            if (!empty($responseSync)) {
                $items = $responseSync['datos03']['catalogosGestorComercial']['listaCatalogos']['lista'] ?? false;
                if (!empty($items)) {
                    foreach ($items as $item) {
                        $tmp = catDepartamento::where('codigo', $item['codigo'])->first();
                        if (empty($tmp)) {
                            $tmp = new catDepartamento();
                        }
                        else {
                            if (!empty($tmp->flock)) continue;
                        }

                        $tmp->codigo = $item['codigo'];
                        $tmp->descripcion = $item['descripcion'];
                        $tmp->pais = '001';
                        $tmp->save();
                    }
                }
            }
        }
        else if ($catalogo === 'municipio') {
            $tmptable = catMunicipio::where('activo', 1);
            $dataSync = [
                'nprogram' => 'XXPD539',
                'dtainput' => '<datosIdEmpresaGC><idEmpresa>01</idEmpresa><datos01><catalogosGestorComercial><codigoTabla>27</codigoTabla><nombreTabla></nombreTabla><descripcionTabla></descripcionTabla></catalogosGestorComercial></datos01><datos02></datos02><moneda></moneda></datosIdEmpresaGC>',
            ];

            if ($opt === 'sync') $responseSync = $this->sync($dataSync);

            if (!empty($responseSync)) {
                $items = $responseSync['datos03']['catalogosGestorComercial']['listaCatalogos']['lista'] ?? false;
                if (!empty($items)) {
                    foreach ($items as $item) {
                        $tmp = catMunicipio::where('codigo', $item['codigo'])->first();
                        if (empty($tmp)) {
                            $tmp = new catMunicipio();
                        }
                        else {
                            if (!empty($tmp->flock)) continue;
                        }

                        $tmp->codigo = $item['codigo'];
                        $tmp->descripcion = $item['descripcion'];
                        $departamento = substr($item['codigo'], 0, -3);
                        if(substr($departamento, -1) === '-') $departamento = substr($item['codigo'], 0, -4);
                        if(substr($departamento, -1) === '-') $departamento = substr($item['codigo'], 0, -5);
                        $tmp->departamento = $departamento;
                        $tmp->save();
                    }
                }
            }
        }
        else if ($catalogo === 'codigo_alarma') {
            $tmptable = catCodigoAlarma::where('activo', 1);
            $responseSync = $this->SyncCatalogo($catalogo, $opt);
        }
        else if ($catalogo === 'promociones') {
            $tmptable = catPromociones::where('activo', 1);
            $responseSync = $this->SyncCatalogo($catalogo, $opt);
        }
        else if ($catalogo === 'tipo_asignacion') {
            $tmptable = catTipoAsignacion::where('activo', 1);
            $responseSync = $this->SyncCatalogo($catalogo, $opt);
        }
        else if ($catalogo === 'tipo_usuario') {
            $tmptable = catTipoUsuario::where('activo', 1);
            $responseSync = $this->SyncCatalogo($catalogo, $opt);
        }
        else if ($catalogo === 'seleccion') {
            $tmptable = catSeleccion::where('activo', 1);
            $responseSync = $this->SyncCatalogo($catalogo, $opt);
        }

        if ($opt === 'get') {
            $tmpData = $tmptable->get();
            return $this->ResponseSuccess('Catálogo cargado con éxito', $tmpData);
        }
        else if ($opt === 'sync') {

            if (empty($responseSync)) {
                return $this->ResponseError('SYNC-01', 'Error de sincronización, es posible que los servicios no respondan');
            }

            return $this->ResponseSuccess('Catálogo sincronizado con éxito', print_r($logSync, true));
        }
    }

    public function SyncCatalogo($catalogo, $opt){
        $dataCatalogo = $this->GetSyncCatalogoSlugs();
        $dataCatalogo = $dataCatalogo[$catalogo];
        $responseSync = null;

        $codigoTabla = $dataCatalogo['codigoTabla'];
        $ruta = $dataCatalogo['ruta'];
        $campos = $dataCatalogo['campos'];
        $class = $dataCatalogo['class'];

        $dataSync = [
            'nprogram' => 'XXPD539',
            'dtainput' => "<datosIdEmpresaGC><idEmpresa>01</idEmpresa><datos01><catalogosGestorComercial><codigoTabla>{$codigoTabla}</codigoTabla><nombreTabla></nombreTabla><descripcionTabla></descripcionTabla></catalogosGestorComercial></datos01><datos02></datos02><moneda></moneda></datosIdEmpresaGC>",
        ];

        if ($opt === 'sync') $responseSync = $this->sync($dataSync);

        if (!empty($responseSync)) {
            $items = $responseSync;
            $class = "App\Models\\{$class}";
            $tmpClass = new $class();
            if (!empty($items)) {

                foreach($ruta as $path){
                    $items = $items[$path];
                }

                foreach ($items as $item) {
                    $tmp = $tmpClass->where('codigo', $item['codigo'])->first();

                    if (empty($tmp)) {
                        $tmp =  new $class();
                    }
                    else {
                        if (!empty($tmp->flock)) continue;
                    }

                    foreach($campos as $camp){
                        if(empty($item[$camp])) continue;
                        if(is_array($item[$camp])) $item[$camp] = '';
                        $tmp->$camp = $item[$camp];
                    }

                    $tmp->save();
                }
            }
        }

        return $responseSync;
    }

    public function LoadCatalogoFields(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['admin/catalogo/sync'])) return $AC->NoAccess();

        $id = $request->get('id');
        $catalogo = $request->get('slug');

        //$tareasH = new TareaController();
        $campos = $this->GetSyncCatalogoSlugs();

        $camposTmp = [];
        if (!empty($campos[$catalogo])) {
            // traigo valor
            $class = "App\Models\\{$campos[$catalogo]['class']}";
            $tmpClass = new $class();
            if (!empty($id)) {
                $fila = $tmpClass->where('id', $id)->first();
            }
            else {
                $fila = $tmpClass->first();
            }

            if ($fila) {
                $fila = $fila->toArray();
            }


            foreach ($campos[$catalogo]['campos'] as $slug) {
                $camposTmp[] = [
                    'id' => $id,
                    'campo' => $slug,
                    'valor' => ($id > 0) ? ($fila[$slug] ?? '') : '',
                ];
            }

            if ($catalogo === 'productos') {
                $camposTmp['access'] = $camposTmp;
            }
        }
        return $this->ResponseSuccess('Campos cargados con éxito', $camposTmp);
    }

    public function LoadCatalogoAccess(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['admin/catalogo/sync'])) return $AC->NoAccess();

        $id = $request->get('id');
        $catalogo = $request->get('slug');

        //$tareasH = new TareaController();
        $campos = $this->GetSyncCatalogoSlugs();

        $camposTmp = [
            'canales_assign' => [],
            'grupos_assign' => [],
            'roles_assign' => [],
        ];
        if (!empty($campos[$catalogo])) {
            // traigo valor
            $class = "App\Models\\{$campos[$catalogo]['class']}";
            $tmpClass = new $class();
            if (!empty($id)) {
                $fila = $tmpClass->where('id', $id)->first();
            }
            else {
                $fila = $tmpClass->first();
            }

            if (isset($fila->acceso)) {
                $camposTmp = json_decode($fila->acceso);
            }
        }
        return $this->ResponseSuccess('Campos cargados con éxito', $camposTmp);
    }

    public function BringCatalogos(Request $request) {
        $AC = new AuthController();
        // if (!$AC->CheckAccess(['admin/catalogo/sync'])) return $AC->NoAccess();

        $catalogos = $request->get('catalogos');
        //$catalogo = $request->get('slug');
        //$tareasH = new TareaController();
        $campos = $this->GetSyncCatalogoSlugs();

        $camposTmp = [];
        foreach($catalogos as $catalogo){
            if (!empty($campos[$catalogo])) {
                // traigo valor
                $class = "App\Models\\{$campos[$catalogo]['class']}";
                $tmpClass = new $class();
                $filas = $tmpClass->where('activo', 1)->get();
                $camposTmp[$catalogo] = [];
                foreach ($filas as $fila) {
                    $label = $fila->nombre ?? ($fila->descripcion ?? '');
                    $codigo = $fila->codigo ?? '';
                    $id = $fila->id ?? '';
                    $camposTmp[$catalogo][] = [
                        'value' => $codigo,
                        'label' => $label,
                        'id' => $id,
                    ];
                }
            }
        }

        return $this->ResponseSuccess('Campos cargados con éxito', $camposTmp);
    }

    public function saveRowCatalogo(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['admin/catalogo/sync'])) return $AC->NoAccess();

        $id = $request->get('id');
        $catalogo = $request->get('slug');
        $camposSave = $request->get('campos');
        $access = $request->get('access');

        $campos = $this->GetSyncCatalogoSlugs();

        $camposTmp = [];
        foreach ($camposSave as $item) {
            if (isset($item['campo'])) {
                $camposTmp[$item['campo']] = $item['valor'];
            }
        }

        if (!empty($campos[$catalogo])) {

            // traigo valor
            $isNew = true;
            $class = "App\Models\\{$campos[$catalogo]['class']}";
            $tmpClass = new $class();

            if (!empty($id)) {
                $fila = $tmpClass->where('id', $id)->first();
                $isNew = false;
            }
            else {
                $fila = new $tmpClass();
            }

            // enviar a as400
            $sendToAs400 = $campos[$catalogo]['sendToAs400'] ?? false;

            if ($sendToAs400) {

                // cambia por catálogo el servicio
                if ($catalogo === 'productos') {
                    $operation = ($isNew) ? 'A' : 'U';
                    $dataSync = [
                        'nprogram' => 'XXPD539',
                        'dtainput' => "<datosIdEmpresaGC><idEmpresa>01</idEmpresa><datos01><creacionProductosGestorComercial><id>{$camposTmp['codigoProducto']}</id><nombre>{$camposTmp['nombre']}</nombre><descripcion>{$camposTmp['descripcion']}</descripcion><desde>{$camposTmp['rangoPolizaDesde']}</desde><hasta>{$camposTmp['rangoPolizaHasta']}</hasta><estado>{$camposTmp['estado']}</estado><moneda>{$camposTmp['idMoneda']}</moneda><tipoOperacion>{$operation}</tipoOperacion></creacionProductosGestorComercial></datos01><datos02></datos02></datosIdEmpresaGC>",
                    ];

                    $responseSync = $this->sync($dataSync);

                    if (empty($responseSync['datos03']['creacionProductosGestorComercial']['id'])) {
                        return $this->ResponseError('SYNC-CAT-AS400', $responseSync['datos03']['creacionProductosGestorComercial']['msgRespuesta']);
                    }
                }
                else if ($catalogo === 'tarifas') {
                    /*'campos' => [
                    'idTarifa',
                    'clasificacion',
                    'descripcion',
                    'activo',
                    'flock',
                ],*/
                    $operation = ($isNew) ? 'A' : 'U';
                    $dataSync = [
                        'nprogram' => 'XXPD539',
                        'dtainput' => "<datosIdEmpresaGC><idEmpresa>01</idEmpresa><datos01><creacionTarifaenAS400GestorComercial2><codigoTarifa>{$camposTmp['idTarifa']}</codigoTarifa><clasificacion>{$camposTmp['clasificacion']}</clasificacion><moneda>Q</moneda><descripcion>{$camposTmp['descripcion']}</descripcion><tipoOperacion>{$operation}</tipoOperacion><listaCobertura/></creacionTarifaenAS400GestorComercial2></datos01><datos02></datos02></datosIdEmpresaGC>             ",
                        //'dtainput' => "<datosIdEmpresaGC><idEmpresa>01</idEmpresa><datos01><creacionTarifaenAS400GestorComercial2><codigoTarifa>{$camposTmp['idTarifa']}</codigoTarifa><clasificacion>{$camposTmp['clasificacion']}</clasificacion><moneda>Q</moneda><descripcion>{$camposTmp['descripcion']}</descripcion><tipoOperacion>{$operation}</tipoOperacion><listaCobertura><coberturas><idCobertura></idCobertura><descripcion></descripcion><tasa></tasa><prima></prima><sumaAsegurada></sumaAsegurada><tasaDeducible></tasaDeducible><valorDeducible></valorDeducible></coberturas><coberturasRango><lista><idCobertura2>14</idCobertura2><descripcion2>EXCESO DE RESPONSABILIDAD CIVIL</descripcion2><rangos><idRango>1</idRango><sumaAseguradaDesde>0</sumaAseguradaDesde><sumaAseguradaHasta>600000</sumaAseguradaHasta><tasa2></tasa2><prima2>665.70</prima2><sumaAsegurada></sumaAsegurada><tasaDeducible2></tasaDeducible2><valorDeducible2></valorDeducible2><primaminima2>2200</primaminima2></rangos></lista></coberturasRango></listaCobertura></creacionTarifaenAS400GestorComercial2></datos01></datos02></datos02></datosIdEmpresaGC>",
                    ];
                    // dd($dataSync);
                    $responseSync = $this->sync($dataSync);

                    if (empty($responseSync['datos03']['creacionTarifaenAS400GestorComercial2']['codigoTarifa'])) {
                        return $this->ResponseError('SYNC-CAT-AS400', $responseSync['datos03']['creacionTarifaenAS400GestorComercial2']['msgRespuesta']);
                    }
                }
            }
            //$fila = $fila->toArray();

            foreach ($campos[$catalogo]['campos'] as $slug) {

                if (isset($camposTmp[$slug])) {
                    if (in_array($slug, ['activo', 'flock', 'isRoja', 'asegurable', 'noAsegurable', 'altoRiesgo'])) {
                        $camposTmp[$slug] = intval($camposTmp[$slug]) === 1;
                    }
                    $fila->{$slug} = $camposTmp[$slug];
                }
            }

            if ($catalogo === 'productos' && is_array($access)) {
                $accesoSave = json_encode($access);
                $fila->acceso = $accesoSave;
            }

            $fila->save();
        }
        return $this->ResponseSuccess('Guardado con éxito', $camposTmp);
    }

    // configuración de productos y tarifas
    public function confProdTarifa(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['admin/catalogo/sync'])) return $AC->NoAccess();

        $productoId = $request->get('productoId');

        $tmptable = catProductoTarifa::where('activo', 1)->where('idProducto', $productoId)->get();
        return $this->ResponseSuccess('Tarifas obtenidas con éxito', $tmptable);
    }

    public function getProdTarifa(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['admin/catalogo/sync'])) return $AC->NoAccess();

        $productoId = $request->get('productoId');
        $tarifaId = $request->get('tarifaId');

        $tmp = catProductoTarifa::where('activo', 1)->where('idTarifa', $tarifaId)->where('idProducto', $productoId)->first();

        if (!empty($tmp)) {
            $tmp->gu = catProductoTarifaGrupoUsuario::where('idTarifa', $tarifaId)->where('idProducto', $productoId)->get();
        }

        $tmp->descRec = catProductoTarifaDescuentoRecargo::where('idProducto', $productoId)->where('idTarifa', $tarifaId)->get();

        return $this->ResponseSuccess('Tarifa obtenida con éxito', $tmp);
    }

    public function getProdCobertura(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['admin/catalogo/sync'])) return $AC->NoAccess();

        $productoId = $request->get('productoId');
        $coberturaId = $request->get('coberturaId');

        $tmp = catProductoCobertura::where('activo', 1)->where('idCobertura', $coberturaId)->where('idProducto', $productoId)->first();
        $tmp = $tmp->toArray();

        /*$arrTmpDesc = [];
        foreach ($tmp['descuento_recargo'] as $key => $row) {
            $acceso = @json_decode($row['accesos'], true);
            $tmp['descuento_recargo'][$key]['canales'] = $acceso['canales'] ?? [];
            $tmp['descuento_recargo'][$key]['grupos'] = $acceso['grupos'] ?? [];
            $tmp['descuento_recargo'][$key]['roles'] = $acceso['roles'] ?? [];
            $tmp['descuento_recargo'][$key]['usuarios'] = $acceso['usuarios'] ?? [];
        }*/

        //$tmp->descuentoRecargo = $arrTmpDesc;

        return $this->ResponseSuccess('Cobertura obtenida con éxito', $tmp);
    }

    public function saveProdTarifa(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['admin/catalogo/sync'])) return $AC->NoAccess();

        $productoId = $request->get('productoId');
        $tarifaId = $request->get('tarifaId');
        $tarifas = $request->get('tarifas');
        $coberturas = $request->get('coberturas');
        $coberturaEditing = $request->get('coberturaEditing');
        $descuentosTarifa = $request->get('descuentosTarifa');
        $gruposSup = $request->get('gruposSup');

        if (!empty($coberturaEditing)) {

            $tmp = catProductoCobertura::where('activo', 1)->where('idProducto', $productoId)->where('idCobertura', $coberturaEditing['idCobertura'])->first();
            $tmp->idProducto = $coberturaEditing['idProducto'];
            $tmp->idCobertura = $coberturaEditing['idCobertura'];
            $tmp->obligatorio = $coberturaEditing['obligatorio'];
            $tmp->llevaValorVehiculo = $coberturaEditing['llevaValorVehiculo'];
            $tmp->condicionVar = $coberturaEditing['condicionVar'];
            $tmp->condicionOperacion = $coberturaEditing['condicionOperacion'];
            $tmp->condicionValor = $coberturaEditing['condicionValor'];
            $tmp->save();

            return $this->ResponseSuccess('Cobertura guardada con éxito');
        }
        else {
            catProductoCobertura::where('activo', 1)->where('idProducto', $productoId)->delete();
            foreach ($coberturas as $item) {
                $tmp = new catProductoCobertura();
                $tmp->idProducto = $productoId;
                $tmp->idCobertura = $item['coberturaId'];
                $tmp->save();
            }
        }

        catProductoTarifa::where('activo', 1)->where('idProducto', $productoId)->delete();

        foreach ($tarifas as $tarifa) {
            $tmp = new catProductoTarifa();
            $tmp->idProducto = $productoId;
            $tmp->idTarifa = $tarifa['tarifaId'];
            $tmp->save();
        }

        // descuentos
        if (!empty($descuentosTarifa)) {
            catProductoTarifaDescuentoRecargo::where('idProducto', $productoId)->where('idTarifa', $tarifaId)->delete();
            foreach ($descuentosTarifa as $descuento) {

                $access = [
                    'canales' => $descuento['canales'] ?? [],
                    'grupos' => $descuento['grupos'] ?? [],
                    'roles' => $descuento['roles'] ?? [],
                    'usuarios' => $descuento['usuarios'] ?? [],
                ];

                $tmpPC = new catProductoTarifaDescuentoRecargo();
                $tmpPC->idProducto = $productoId;
                $tmpPC->idTarifa = $tarifaId;
                $tmpPC->tipo = $descuento['tipo'];
                $tmpPC->nombre = $descuento['nombre'] ?? null;
                $tmpPC->porcentaje = $descuento['porcentaje'] ?? null;
                $tmpPC->monto = $descuento['monto'] ?? null;
                $tmpPC->accesos = @json_encode($access);
                $tmpPC->save();
            }
        }

        /*if ($tarifaId > 0) {
            catProductoCobertura::where('activo', 1)->where('idProducto', $productoId)->where('idTarifa', $tarifaId)->delete();
        }
        foreach ($coberturas as $cobertura) {
            $tmp = new catProductoCobertura();
            $tmp->idProducto = $productoId;
            $tmp->idTarifa = $cobertura['tarifaId'];
            $tmp->idCobertura = $cobertura['coberturaId'];
            $tmp->save();
        }*/

        catProductoTarifaGrupoUsuario::where('idTarifa', $tarifaId)->where('idProducto', $productoId)->delete();

        foreach ($gruposSup as $grupoUser) {
            $tmp = new catProductoTarifaGrupoUsuario();
            $tmp->grupoUsuarioId = $grupoUser;
            $tmp->idProducto = $productoId;
            $tmp->idTarifa = $tarifaId;
            $tmp->save();
        }

        return $this->ResponseSuccess('Configuración guardada con éxito');
    }

    public function getProdTarifaCobertura(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['admin/catalogo/sync'])) return $AC->NoAccess();

        $productoId = $request->get('productoId');

        $response = [
            'c' => catProductoCobertura::where('activo', 1)->where('idProducto', $productoId)->get(),
            't' => catProductoTarifa::where('activo', 1)->where('idProducto', $productoId)->get()
        ];

        return $this->ResponseSuccess('Información obtenida con éxito', $response);
    }

    public function getTarifaCoberturaCotizador(Request $request) {

        $AC = new AuthController();
        //if (!$AC->CheckAccess(['admin/catalogo/sync'])) return $AC->NoAccess();

        $marcaId = $request->get('marcaId');
        $lineaId = $request->get('lineaId');
        $vehiculoId = $request->get('vehiculoId');
        $vehiNumber = $request->get('vehiNumber');
        $productoId = $request->get('productoId');
        $modelo = $request->get('modelo');

        $marcas = Cache::remember("catMarca", env('CACHE_SECONDS', 600), function() {
            return  catMarca::where('activo', 1)->get();
        });

        $lineas = [];
        $productos = [];
        $moneda = "Q";
        $error = '';
        $errorD = '';
        $altoRiesgo = false;

        $usuarioLogueado = auth('sanctum')->user();
        $rolUsuarioLogueado = ($usuarioLogueado) ? $usuarioLogueado->rolAsignacion->rol : 0;

        $vehiculo = CotizacionDetalleVehiculo::where('id', $vehiculoId)->first();

        if (empty($vehiculo)) {
            return false;
        }

        $cotizacion = $vehiculo->cotizacion;

        $arrTarifasId = [];
        $productosFilter = [];

        $tipoVehiculo = Cache::remember("catTipoVehiculo", env('CACHE_SECONDS', 600), function() {
            return  catTipoVehiculo::where('activo', 1)->get();
        });

        $arrProductos = [];

        if (!empty($marcaId)) {
            $lineas = catLinea::where('activo', 1)->where('marcaId', $marcaId)->get();

            // asegurabilidad
            $marcaTmp = catMarca::where('activo', 1)->where('id', $marcaId)->first();

            if (empty($marcaTmp->asegurable)) {
                $error = 'La marca no es asegurable';
                $errorD = 'no_aseg';
            }
        }

        if (!empty($lineaId)) {
            $linea = catLinea::where('activo', 1)->where('id', $lineaId)->first();

            if (!empty($linea->noAsegurable)) {
                $error = 'La línea no es asegurable';
                $errorD = 'no_aseg_l';
            }

            if (!empty($linea->altoRiesgo)) {

                if (intval($vehiculo->modelo) >= $linea->altoRiesgoMin && intval($vehiculo->modelo) <= $linea->altoRiesgoMax) {
                    $altoRiesgo = true;
                }
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

            if (isset($tarifasArr[$linea->clasificacion])) {
                foreach ($tarifasArr[$linea->clasificacion] as $tmp) {
                    $arrTarifasId[] = $tmp->id;
                }
            }

            // Se traen los productos
            $productosIds = [];
            $productosTarifaTmp = catProductoTarifa::whereIn('idTarifa', $arrTarifasId)->where('activo', 1)->get();
            foreach ($productosTarifaTmp as $tmp) {
                $productosIds[$tmp->idProducto] = $tmp->idProducto;
            }

            // valida productos
            $productos = catProductos::whereIn('id', $productosIds)->where('activo', 1)->get();

            // intemerdiario linea
            // si tiene código de agente
            $existInTable = false;
            $arrZonasEmi = [];
            if (!empty($cotizacion->codigoAgente)) {
                $catLineaIntermediario = catLineaIntermediario::where('activo', 1)->where('codigoIntermediario', $cotizacion->codigoAgente)->get();
                foreach ($catLineaIntermediario as $rowTmp) {
                    $arrZonasEmi[] = trim($rowTmp->codigoZonaEmision);
                    $existInTable = true;
                }
            }

            $authHandler = new AuthController();

            foreach ($productos as $pr) {
                $acceso = json_decode($pr->acceso);
                if (isset($acceso->roles_assign) && isset($acceso->grupos_assign) && isset($acceso->canales_assign)) {
                    $access = $authHandler->CalculateVisibility($usuarioLogueado->id ?? 0, $rolUsuarioLogueado->id ?? 0, false, $acceso->roles_assign ?? [], $acceso->grupos_assign ?? [], $acceso->canales_assign ?? []);
                    /*var_dump($access);
                    die();*/
                    if (!$access) continue;

                    // si tiene código de agente
                    if ($existInTable) {
                        if (in_array(trim($pr->zonaEmision), $arrZonasEmi)) {
                            $arrProductos[] = $pr;
                        }
                    }
                    else {
                        $arrProductos[] = $pr;
                    }
                }
            }
            /*var_dump($arrProductos);
            die();*/
        }

        $cotizaciones = CotizacionDetalleVehiculoCotizacion::where('cotizacionDetalleVehiculoId', $vehiculoId)->get();

        $coberturas = [];

        if (!empty($cotizaciones)) {

            $countCotizacion = 1;
            foreach ($cotizaciones as $keyCoti => $coti) {

                //var_dump($coti['id']);

                // coberturas seleccionadas
                $coberturasActivas = CotizacionDetalleVehiculoCotizacionCobertura::where('cotizacionDetalleVehiculoCotId', $coti['id'])->get();

                $tarifaId = 0;
                $tarifaCod = "";
                $tarifaName = "";
                $productosTarifaId = [];
                $productosTarifa = catProductoTarifa::where('idProducto', $coti['productoId'])->whereIn('idTarifa', $arrTarifasId)->where('activo', 1)->get();
                foreach ($productosTarifa as $tmp) {
                    $productosTarifaId[] = $tmp->id;
                    $tarifaId = $tmp->idTarifa;
                    $tarifaCod = $tmp->tarifa->idTarifa;
                    $tarifaName = $tmp->tarifa->descripcion;
                }

                // var_dump($productosTarifaId);

                $productosTarifaCoberTmp = catProductoTarifaCobertura::where('idProducto', $coti['productoId'])->whereIn('idProductoTarifa', $productosTarifaId)->where('activo', 1)->get();
                //var_dump($productosTarifaCoberTmp);

                // se actualiza la tarifa de la cotización
                //$cotizacionTemp = CotizacionDetalleVehiculoCotizacion::where('id', $coti['cotId'])->first();
                /*$coti->tarifaId = $tarifaId ?? null;
                $coti->save();*/

                /*$tareaTmp = new TareaController();

                if (!empty($vehiNumber)) {
                    $tareaTmp->saveReplaceCustomVar($cotizacion->id, 'TARIFA_CODIGO', $tarifaCod, $vehiculoId, $vehiNumber, $countCotizacion, $coti->id);
                    $tareaTmp->saveReplaceCustomVar($cotizacion->id, 'TARIFA_NOMBRE', $tarifaName, $vehiculoId, $vehiNumber, $countCotizacion, $coti->id);
                }*/

                $producto = $coti->producto;
                $coberturas[$keyCoti]['id'] = $coti->id;
                $coberturas[$keyCoti]['pD'] = (!isset($producto->tieneDescuento)) ? 1 : $producto->tieneDescuento;
                $coberturas[$keyCoti]['rC'] = (!isset($producto->rc)) ? 0 : $producto->rc;
                $coberturas[$keyCoti]['sumAseg'] = number_format($coti->sumaAsegurada, 2);

                // var_dump($productosTarifaCoberTmp);

                foreach ($productosTarifaCoberTmp as $tmpPTC) {

                    $productosCober = catProductoCobertura::where('id', $tmpPTC->idProductoCobertura)->where('idProducto', $coti['productoId'])->where('activo', 1)->with('cobertura')->get();

                    foreach ($productosCober as $pCoberTmp) {

                        $tipo = $pCoberTmp->tipo;
                        $montos = @json_decode("{$tmpPTC->valor}", true);
                        //$monto = $vehiculo->valorProm;
                        $monto = $coti->sumaAsegurada;
                        $montosTasa = 0;
                        $montosSel = [];
                        $rangoMonto = null;

                        if($tipo === 'rt') {
                            $montosTasa = floatval($monto) ?? 0;
                            $rangoMonto = $montos;
                        }

                        if (is_array($montos)) {
                            if($tipo === 'rt') {
                                $montosTasa = floatval($montos[0]) ?? 0;
                            }
                            foreach ($montos as $montoProdCober) {
                                if($tipo === 't') {
                                    $montosTasa = floatval($montoProdCober) ?? 0;
                                }
                                if($tipo === 'm'){
                                    if (strpos($montoProdCober, '-')) {
                                        $tmpV = explode('-', $montoProdCober);
                                        $montosSel[] = [
                                            'show' => number_format($tmpV[0] ?? 0, 2),
                                            'val' => number_format($tmpV[0] ?? 0, 2),
                                        ];
                                    }
                                    else {
                                        $montosSel[] = [
                                            'show' => number_format($montoProdCober ?? 0, 2),
                                            'val' => number_format($montoProdCober ?? 0, 2),
                                        ];
                                    }
                                }
                            }
                        }
                        //var_dump($montos);
                        //var_dump($coberturasActivas);


                        $selected = $coberturasActivas->where('coberturaId', $pCoberTmp->cobertura->id ?? 0)->first();

                        // Operación de cobertura
                        if ($montosTasa > 0) {
                            $monto = (string)(floatval($coti->sumaAsegurada) * ($montosTasa/100));
                        }

                        if (!empty($selected->monto)) {
                            $monto = $selected->monto;
                        }

                        if (!empty($pCoberTmp->llevaValorVehiculo)) {
                            $monto = $coti->sumaAsegurada;
                        }

                        $condicionalResult = true;
                        $condicionalViewResult = true;
                        $condicionalReplaced = '';
                        $condicionalViewReplaced = '';
                        if (!empty($pCoberTmp->condicionales) || !empty($pCoberTmp->condicionalesVisibilidad)) {

                            if (!empty($pCoberTmp->condicionales)) $condicionalResult = false;


                            $data = $cotizacion->campos->toArray();
                            $data['SA'] =['id' => 'SA', 'nombre' => '', 'valorLong' => $coti->sumaAsegurada];
                            $data['TC'] = ['id' => 'TC', 'nombre' => '', 'valorLong' => $montosTasa];
                            $data['VC'] = ['id' => 'VC', 'nombre' => '', 'valorLong' => $monto];

                            $tareaController = new TareaController();

                            // valuación de condicionales
                            $smpl = new \Le\SMPLang\SMPLang();

                            if (!empty($pCoberTmp->condicionales)) {
                                $condicionalReplaced = $tareaController->reemplazarValoresSalida($data, $pCoberTmp->condicionales, false, false, true);
                                $condicionalResult = @$smpl->evaluate($condicionalReplaced);
                            }
                            if (!empty($pCoberTmp->condicionalesVisibilidad)) {
                                $condicionalViewReplaced = $tareaController->reemplazarValoresSalida($data, $pCoberTmp->condicionalesVisibilidad, false, false, true);
                                $condicionalViewResult = @$smpl->evaluate($condicionalViewReplaced);
                            }
                        }

                        if (!empty($pCoberTmp->cobertura->id)) {

                            if (strpos($monto, '-') !== false) {
                                $tmpMonto = explode('-', $monto);

                                if (isset($montosSel[0])) {
                                    $monto = $montosSel[0]['show'];
                                }
                                else {
                                    $monto = $tmpMonto[0] ?? 0;
                                }
                            }

                            $coberturas[$keyCoti]['cober'][$pCoberTmp->cobertura->id] = [
                                'id' => $pCoberTmp->id,
                                'value' => $pCoberTmp->cobertura->id,
                                'codigo' => $pCoberTmp->cobertura->codigo,
                                'nombre' => $pCoberTmp->cobertura->nombre ?? 'Nombre no disponible',
                                'obligatorio' => $pCoberTmp->obligatorio,
                                'llvh' => $pCoberTmp->llevaValorVehiculo,
                                'sumaA' => $pCoberTmp->sumaAsegurada,
                                'mp' => $pCoberTmp->montoPrima,
                                'mdm' => $pCoberTmp->montoDeduMin,
                                'primaMinima' => $pCoberTmp->primaMinima,
                                'msa' => $pCoberTmp->montoSumaAsegurada,
                                'moneda' => $pCoberTmp->moneda,
                                'rango' => $pCoberTmp->rango,
                                'tipoVisibilidad' => $pCoberTmp->tipoVisibilidad,
                                'montoV' => $montosSel,
                                'monto' => number_format($monto, 2),
                                'c' => $pCoberTmp->condicionales,
                                'ccp' => $condicionalReplaced,
                                'cr' => $condicionalResult,
                                'cv' => $pCoberTmp->condicionalesVisibilidad,
                                'cvr' => $condicionalViewReplaced,
                                'cvres' => $condicionalViewResult,
                                'rangoMonto' => $rangoMonto,
                                'selected' => ((!empty($selected) || $pCoberTmp->obligatorio) && $condicionalResult) ? 1 : 0,
                            ];
                        }

                        if (!empty($pCoberTmp->moneda)) {
                            $moneda = $pCoberTmp->moneda;
                        }
                    }
                }

                // traer sus descuentos
                /*foreach ($coberturasTMp as $key => $cobertura) {
                    $coberturas[$keyCoti]['cober'][$key] = $cobertura;
                }*/

                $countCotizacion++;
            }
        }

        $response = [
            'm' => $marcas,
            'l' => $lineas,
            //'p' => $productos,
            'p' => $arrProductos,
            'coti' => $coberturas,
            'tv' => $tipoVehiculo,
            'altoRies' => $altoRiesgo,
            'error' => $error,
            'errorD' => $errorD,
            'valorProm' => $vehiculo->valorProm,
            'valorPromDef' => $vehiculo->valorPromDef,
            'siniesBlock' => $cotizacion->siniesBlock,
        ];



        return $this->ResponseSuccess('Información obtenida con éxito', $response);
    }

    // Catalogos custom, van a través del campo custom en GetSyncCatalogoSlugs, se llaman en tareas calculate catalog
    public function customTarifasPorUsuario($cotizacion) {

        $gruposUsuario = UserGrupoUsuario::where('userId', $cotizacion->usuarioIdAsignado)->get();

        $grupos = [];
        foreach ($gruposUsuario as $grupo) {
            $grupos[] = $grupo->userGroupId;
        }

        $tarifasId = [];
        $tarifas = catProductoTarifaGrupoUsuario::whereIn('grupoUsuarioId', $grupos)->get();
        foreach ($tarifas as $tmp) {
            $tarifasId[] = $tmp->idTarifa;
        }

        $tarifas = catTarifas::whereIn('id', $tarifasId)->get();
        return $tarifas->toArray();
    }

    // Vehiculos
    public function autoGetTipos($cotizacion) {

        $gruposUsuario = UserGrupoUsuario::where('userId', $cotizacion->usuarioIdAsignado)->get();

        $grupos = [];
        foreach ($gruposUsuario as $grupo) {
            $grupos[] = $grupo->userGroupId;
        }

        $tarifasId = [];
        $tarifas = catProductoTarifaGrupoUsuario::whereIn('grupoUsuarioId', $grupos)->get();
        foreach ($tarifas as $tmp) {
            $tarifasId[] = $tmp->idTarifa;
        }

        $tarifas = catTarifas::whereIn('id', $tarifasId)->get();
        return $tarifas->toArray();
    }

    public function autoGetFrecuenciaPagos() {

        $frecuenciaPagos = catFormaPago::where('activo', 1)->get();
        $frecPago = [];
        foreach ($frecuenciaPagos as $frec) {
            $frecPago[$frec->id] = [
                'id' => $frec->id,
                'codigo' => $frec->codigo,
                'descripcion' => $frec->descripcion,
                'numeroPagos' => json_decode($frec->numeroPagos)
            ];
        }
        return $this->ResponseSuccess('Información obtenida con éxito', $frecPago);
    }


    // otros

    // Valor promedio
    public function getValorPromedio($marcaId, $lineaId, $modelo, $moneda, $cotId) {

        if (!empty($marcaId) && !empty($lineaId) && !empty($modelo)) {
            $url = env('ACSEL_AUTO_URL');
            $wsAcsel = new \ACSEL_WS();
            $wsAcsel->setAuthData($url . '/session/login', '{"usuario": "' . env('ACSEL_AUTO_USER') . '","contrasenia": "' . env('ACSEL_AUTO_PASS') . '","origen": "services"}');

            $marca = catMarca::where('id', $marcaId)->first();
            $marcaCodigo = $marca->codigo ?? '';

            $linea = catLinea::where('id', $lineaId)->first();
            $lineaCodigo = $linea->nombre ?? '';

            $dataSend = [
                'nprogram' => '539',
                'dtainput' => "<datosIdEmpresaGC><idEmpresa>01</idEmpresa><datos01><consultaValorPromedioVehiculo><marca>{$marcaCodigo}</marca><linea>{$lineaCodigo}</linea><ano>{$modelo}</ano><moneda>{$moneda}</moneda></consultaValorPromedioVehiculo></datos01><datos02></datos02></datosIdEmpresaGC>",
            ];

            $response = false;
            $data = $wsAcsel->post("{$url}/automoviles/cotizador/api/gestor_comercial", $dataSend, false, false);
            $datasendjson = json_encode($dataSend);
            $datajson = json_encode($data);
                $bitacoraCoti = new CotizacionBitacora();
                $bitacoraCoti->cotizacionId = $cotId;
                $bitacoraCoti->usuarioId = 0;
                $bitacoraCoti->onlyPruebas = 1;
                $bitacoraCoti->dataInfo = "<b>Enviado:</b> {$datasendjson}, <b>Recibido:</b> {$datajson}";
                $bitacoraCoti->log = "Cotizacion Calculada";
                $bitacoraCoti->save();
            if (!empty($data['dtaoutput'])) {

                // Chapus para valor promedio
                $data['dtaoutput'] = str_replace('<datos03>428<', '<datos03><', $data['dtaoutput']);
                $data = $wsAcsel->parseXml($data['dtaoutput']);
                // Chapus para convertir en array
                $json = json_encode($data);
                $array = json_decode($json, true);
                if (is_array($array)) {
                    $response = $array;
                }
            }

            return $response['datos03']['consultaValorPromedioVehiculo']['valorPromedio'] ?? '';
        }
        else {
            return '';
        }
    }


    // ventana nueva producto, tarifa y cobertura
    public function getProdTarCober(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['admin/catalogo/sync'])) return $AC->NoAccess();

        $productoId = $request->get('productoId');

        $producto = catProductos::where('id', $productoId)->first();
        $productoTarifas = catProductoTarifa::where('idProducto', $productoId)->get();
        $coberturas = catProductoCobertura::where('activo', 1)->where('idProducto', $productoId)->get();


        foreach ($coberturas as $e) {

            $tarifascobertura = catProductoTarifaCobertura::where('idProductoCobertura', $e->id)->get();

            // defaults para los productos tarifas
            foreach ($productoTarifas as $prTarifa) {
                $tmp = $tarifascobertura->where('idProductoTarifa', $prTarifa->id)->first();

                if (empty($tmp)) {
                    $productosTarifasCobertura = new catProductoTarifaCobertura();
                    $productosTarifasCobertura->idProductoTarifa = $prTarifa->id;
                    $productosTarifasCobertura->idProductoCobertura = $e->id;
                    $productosTarifasCobertura->idProducto = $productoId;
                    $productosTarifasCobertura->valor = json_encode([]);
                    $productosTarifasCobertura->rangos = json_encode([]);
                    $productosTarifasCobertura->save();
                }
            }
        }

        $coberturas = catProductoCobertura::where('activo', 1)->where('idProducto', $productoId)->get();
        foreach ($coberturas as $e) {

            $detalle = [];
            $rangos = [];
            $showDetails = [];

            $tarifascobertura = $e->tarifasCobertura;

            foreach($tarifascobertura as $tc){

                //var_dump($tc);
                $tarifa = $tc->tarifa;
                $tc = $tc->toArray();
                $tc['valor'] = !empty($tc['valor'])? json_decode($tc['valor'], true) : [];

                if (!empty($tarifa->idTarifa)) {
                    $showDetails[$tarifa->idTarifa] = false;
                    $rangos[$tarifa->idTarifa] = !empty($tc['rangos'])? json_decode($tc['rangos'], true) : [];
                }

                $detalle[$tc['idProductoTarifa']] = $tc;
                //var_dump($rangos);
            }
            $e->rango = $detalle;
            $e['det'] = $detalle;
            $e['rangos'] = $rangos;
            $e['showDetails'] = $showDetails;
        }

        $response = [
            'cat' => [
                'tarifas' => catTarifas::where('activo', 1)->get(),
                'cobertura' => catCoberturas::where('activo', 1)->get(),
            ],
            'c' => $coberturas,
            't' => catProductoTarifa::where('activo', 1)->where('idProducto', $productoId)->get(),
            'p' => $producto->nombre,
        ];

        return $this->ResponseSuccess('Información obtenida con éxito', $response);
    }

    public function addProdTarifa(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['admin/catalogo/sync'])) return $AC->NoAccess();

        $productoId = $request->get('productoId');

        $productoTarifa = new catProductoTarifa();
        $productoTarifa->idProducto = $productoId;
        $productoTarifa->idTarifa = 0;
        $productoTarifa->save();

        //CREAR NUEVOS COBERTURATARIFAS
        $coberturas = catProductoCobertura::where('activo', 1)->where('idProducto', $productoId)->get();
        foreach($coberturas as $cob){
            $productoTarifaCobertura = new catProductoTarifaCobertura();
            $productoTarifaCobertura->idProducto  = $productoId;
            $productoTarifaCobertura->idProductoTarifa = $productoTarifa->id;
            $productoTarifaCobertura->idProductoCobertura = $cob->id;
            $productoTarifaCobertura->save();
        }

        return $this->ResponseSuccess('Tarifa agregada con éxito');
    }

    public function deleteProdTarifa(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['admin/catalogo/sync'])) return $AC->NoAccess();

        $productoId = $request->get('productoId');
        $tarifaId = $request->get('tarifaId');

        $tmp = catProductoTarifa::where('activo', 1)->where('idTarifa', $tarifaId)->where('idProducto', $productoId)->first();

        if (!empty($tmp)) {
            $tmp->delete();
        }

        return $this->ResponseSuccess('Tarifa eliminada con éxito', $tmp);
    }

    public function addProdTarifaCobertura(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['admin/catalogo/sync'])) return $AC->NoAccess();

        $productoId = $request->get('productoId');

        $productoTarifaCober = new catProductoCobertura();
        $productoTarifaCober->idProducto = $productoId;
        $productoTarifaCober->idTarifa = 0;
        $productoTarifaCober->idCobertura = 0;
        $productoTarifaCober->save();

        //CREAR NUEVOS COBERTURATARIFAS
        $tarifas = catProductoTarifa::where('activo', 1)->where('idProducto', $productoId)->get();
        foreach($tarifas as $tarifa){
            $productoTarifaCobertura = new catProductoTarifaCobertura();
            $productoTarifaCobertura->idProducto  = $productoId;
            $productoTarifaCobertura->idProductoTarifa = $tarifa->id;
            $productoTarifaCobertura->idProductoCobertura = $productoTarifaCober->id;
            $productoTarifaCobertura->save();
        }

        return $this->ResponseSuccess('Cobertura agregada con éxito');
    }

    public function deleteProdTarifaCobertura(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['admin/catalogo/sync'])) return $AC->NoAccess();

        $productoId = $request->get('productoId');
        $coberturaId = $request->get('coberturaId');

        $tmp = catProductoCobertura::where('activo', 1)->where('id', $coberturaId)->where('idProducto', $productoId)->first();

        if (!empty($tmp)) {
            $tmp->delete();
        }

        return $this->ResponseSuccess('Cobertura eliminada con éxito', $tmp);
    }

    public function saveProdTarifaCobertura(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['admin/catalogo/sync'])) return $AC->NoAccess();

        $productoId = $request->get('productoId');
        $tarifas = $request->get('tarifas');
        $coberturas = $request->get('coberturas');
        $sendAS400 = $request->get('sendAS400');

        $producto = catProductos::where('id', $productoId)->first();

        $productoTarifaId = [];

        foreach ($tarifas as $tarifa) {
            $tmp = catProductoTarifa::where('id', $tarifa['id'])->first();
            if (!empty($tmp)) {
                $tmp->idTarifa = $tarifa['idTarifa'];
                $tmp->save();
                $productoTarifaId[$tarifa['id']] = $tmp->id;
            }
        }

        catProductoTarifaCobertura::where('idProducto', $productoId)
            //->where('idProducto', $producto->id)
            //->where('idProductoTarifa', $tmp->id)
            ->delete();

        $coberturasPorTarifa = [];

        foreach ($coberturas as $cobertura) {

            $tmp = catProductoCobertura::where('id', $cobertura['id'])->first();
            if (!empty($tmp)) {
                $tmp->idTarifa = $cobertura['idTarifa'];
                $tmp->idCobertura = $cobertura['idCobertura'];
                $tmp->obligatorio = $cobertura['obligatorio'];
                $tmp->llevaValorVehiculo = $cobertura['llevaValorVehiculo'];
                $tmp->sumaAsegurada = $cobertura['sumaAsegurada'];
                $tmp->montoPrima = $cobertura['montoPrima'];
                $tmp->montoDeduMin = $cobertura['montoDeduMin'];
                $tmp->tasaDedu = $cobertura['tasaDedu'];
                $tmp->primaMinima = $cobertura['primaMinima'];
                $tmp->montoSumaAsegurada = $cobertura['montoSumaAsegurada'];
                $tmp->rango = json_encode($cobertura['rango']);
                $tmp->tipo = $cobertura['tipo'];
                $tmp->tipoNumeracion = $cobertura['tipoNumeracion'];
                $tmp->tipoVisibilidad = $cobertura['tipoVisibilidad'];
                $tmp->moneda = $cobertura['moneda'];
                $tmp->condicionalesVisibilidad = $cobertura['condicionalesVisibilidad'];
                $tmp->condicionales = $cobertura['condicionales'];
                $tmp->save();
            }
            $detalles = $cobertura['det'];
            $cobertura['tipoTasa'] = $cobertura['tipo'];

            /*var_dump($cobertura['idTarifa']);
            var_dump($cobertura['rangos']);*/

            $savedProdTarifa = [];

            foreach ($tarifas as $tarifa) {

                foreach($detalles as $det) {

                    if (!isset($det['tarifa']['idTarifa'])) continue;
                    //if (isset($savedProdTarifa[$det['idProductoTarifa']])) continue;
                    //if ($det['tarifa']['idTarifa'] > 0 && $det['tarifa']['idTarifa'] !== $tarifa['idTarifa']) continue;

                    $rangos = [];
                    if (!empty($det['tarifa']['idTarifa'])) {
                        $rangos = $cobertura['rangos'][$det['tarifa']['idTarifa']] ?? [];
                    }

                    $coberturasPorTarifa[$det['idProductoTarifa']][$cobertura['id']] = $cobertura;

                    $productosTarifasCobertura = catProductoTarifaCobertura::where('idProductoTarifa', $det['idProductoTarifa'])->where('idProductoCobertura', $tmp->id)->first();

                    if (empty($productosTarifasCobertura)) {
                        $productosTarifasCobertura = new catProductoTarifaCobertura();
                    }

                    $productosTarifasCobertura->idProductoTarifa = $det['idProductoTarifa'];
                    $productosTarifasCobertura->idProductoCobertura = $tmp->id;
                    $productosTarifasCobertura->idProducto = $productoId;
                    $productosTarifasCobertura->valor = json_encode($det['valor']);
                    $productosTarifasCobertura->rangos = json_encode($rangos);
                    $productosTarifasCobertura->save();

                    $savedProdTarifa[$det['idProductoTarifa']] = true;
                }
            }
        }

        /*var_dump($coberturasPorTarifa);
        die();*/

        //dd($coberturasPorTarifa);
        $dataSync = [];
        $dataLog = [];

        if ($sendAS400) {

            foreach ($coberturasPorTarifa as $idTarifa => $coberturas) {

                $listaCoberturas = '';
                $listaCoberturasRango = '';

                $productoTarifa = catProductoTarifa::where('id', $idTarifa)->first();
                $tarifa = $productoTarifa->tarifa ?? false;

                if (empty($tarifa->idTarifa)) continue;

                foreach ($coberturas as $cobertura) {

                    // obtener la tasa
                    $monto = 0;
                    $montoTasa = 0;
                    $montoPrima = 0;
                    foreach ($cobertura['det'] as $detalle) {
                        if($detalle['idProductoTarifa'] === $idTarifa) {
                            $monto = $detalle['valor'][0] ?? 0;

                            if ($cobertura['tipoTasa'] === 'rt') {
                                $monto = max($detalle['valor']);
                            }
                        }
                    }

                    $coberturaRow = catCoberturas::where('id', $cobertura['idCobertura'])->first();

                    if (empty($cobertura['rangos'][$tarifa->id]) || (count($cobertura['rangos'][$tarifa->id]) === 0)) {

                        //var_dump($cobertura);
                        if ($cobertura['tipoTasa'] === 't' || $cobertura['tipoTasa'] === 'rt') {
                            $montoTasa = $monto;
                        }
                        else {
                            $montoPrima = $monto;
                        }

                        if (str_contains($montoPrima, '-')) {
                            $tmpMonto = explode('-', $montoPrima);
                            $montoPrima = $tmpMonto[1] ?? 0;
                            $cobertura['montoSumaAsegurada'] = $tmpMonto[0] ?? 0;
                        }

                        if (!empty($coberturaRow->codigo)) {
                            /* $listaCoberturas .= <<<XML
                            <coberturas>
                                <idCobertura>{$coberturaRow->codigo}</idCobertura>
                                <descripcion>{$coberturaRow->nombre}</descripcion>
                                <tasa>{$montoTasa}</tasa>
                                <prima>{$montoPrima}</prima>
                                <sumaAsegurada>{$cobertura['montoSumaAsegurada']}</sumaAsegurada>
                                <tasaDeducible>{$cobertura['tasaDedu']}</tasaDeducible>
                                <valorDeducible>{$cobertura['montoDeduMin']}</valorDeducible>
                            </coberturas>
                            XML;*/

                            $primaMinima = (!empty($cobertura['primaMinima']) ? $cobertura['primaMinima'] : 0);
                            $listaCoberturas .= "<coberturas><idCobertura>{$coberturaRow->codigo}</idCobertura><descripcion>{$coberturaRow->nombre}</descripcion><tasa>{$montoTasa}</tasa><prima>{$montoPrima}</prima><sumaAsegurada>{$cobertura['montoSumaAsegurada']}</sumaAsegurada><tasaDeducible>{$cobertura['tasaDedu']}</tasaDeducible><valorDeducible>{$cobertura['montoDeduMin']}</valorDeducible><primaMinima>{$primaMinima}</primaMinima></coberturas>";
                        }
                    }
                    else {

                        $rangoStr = '';
                        //var_dump($cobertura);
                        foreach ($cobertura['rangos'][$tarifa->id] as $keyRango => $rango) {

                            //var_dump($rango);
                            $sumaAsegurada = $rango['suma_asegurada'] ?? '';
                            $keyRangoTmp = $keyRango+1;

                            /*
                            $rangoStr .= <<<XML
                                            <rangos>
                                                <idRango>{$keyRangoTmp}</idRango>
                                                <sumaAseguradaDesde>{$rango['suma_asegurada_desde']}</sumaAseguradaDesde>
                                                <sumaAseguradaHasta>{$rango['suma_asegurada_hasta']}</sumaAseguradaHasta>
                                                <tasa2>{$rango['tasa']}</tasa2>
                                                <prima2>{$rango['prima']}</prima2>
                                                <sumaAsegurada>{$sumaAsegurada}</sumaAsegurada>
                                                <tasaDeducible2>{$rango['tasa_deducible']}</tasaDeducible2>
                                                <valorDeducible2>{$rango['valor_deducible']}</valorDeducible2>
                                                <primaminima2>{$rango['prima_minima']}</primaminima2>
                                            </rangos>
                            XML;
                            */


                            $rangoStr .= "<rangos><idRango>{$keyRangoTmp}</idRango><sumaAseguradaDesde>{$rango['suma_asegurada_desde']}</sumaAseguradaDesde><sumaAseguradaHasta>{$rango['suma_asegurada_hasta']}</sumaAseguradaHasta><tasa2>{$rango['tasa']}</tasa2><prima2>{$rango['prima']}</prima2><sumaAsegurada>{$sumaAsegurada}</sumaAsegurada><tasaDeducible2>{$rango['tasa_deducible']}</tasaDeducible2><valorDeducible2>{$rango['valor_deducible']}</valorDeducible2><primaminima2>{$rango['prima_minima']}</primaminima2></rangos>";
                        }

                        $listaCoberturasRango .= "<coberturasRango><lista><idCobertura2>{$coberturaRow->codigo}</idCobertura2><descripcion2>{$coberturaRow->nombre}</descripcion2>{$rangoStr}</lista></coberturasRango>";

                    }
                }

                // Enviar al as400
                $operation = 'U';
                $xmlTmp = [
                    'nprogram' => 'XXPD539',
                    'dtainput' => "<datosIdEmpresaGC><idEmpresa>01</idEmpresa><datos01><creacionTarifaenAS400GestorComercial2><codigoTarifa>{$tarifa->idTarifa}</codigoTarifa><clasificacion>{$tarifa->clasificacion}</clasificacion><moneda>{$producto->idMoneda}</moneda><descripcion>{$tarifa->descripcion}</descripcion><tipoOperacion>{$operation}</tipoOperacion><listaCobertura>{$listaCoberturas}{$listaCoberturasRango}</listaCobertura></creacionTarifaenAS400GestorComercial2></datos01><datos02></datos02></datosIdEmpresaGC>",
                ];

                $dataSync[] = $xmlTmp;
                $dataLog[] = $this->sync($xmlTmp);

                //var_dump($dataSync);
                // dd($dataSync);
                //$dataLog[] = $this->sync($xmlTmp);
            }
        }

        return $this->ResponseSuccess('Configuración guardada con éxito', ['xmlAS' => $dataSync, 'log' => $dataLog]);
    }

    public function cloneProdTarifaCobertura(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['admin/catalogo/sync'])) return $AC->NoAccess();

        $productoId = $request->get('productoId');
        $productoIdNew = $request->get('productoIdNew');

        catProductoTarifa::where('idProducto', $productoIdNew)->delete();
        catProductoCobertura::where('idProducto', $productoIdNew)->delete();
        catProductoTarifaCobertura::where('idProducto', $productoIdNew)->delete();

        $tarifas = catProductoTarifa::where('idProducto', $productoId)->get();

        $coberSaved = false;

        $tarifasEquivalentes = [];

        foreach ($tarifas as $tarifa) {

            $tarifaTmp = new catProductoTarifa();
            $tarifaTmp->idProducto = $productoIdNew;
            $tarifaTmp->idTarifa = $tarifa->idTarifa;
            $tarifaTmp->save();

            $tarifasEquivalentes[$tarifa->id] = $tarifaTmp->id;
        }

        //var_dump($tarifasEquivalentes);

        foreach ($tarifasEquivalentes as $tarifaAntigua => $tarifaId) {

            $coberturas = catProductoCobertura::where('idProducto', $productoId)->get();
            foreach ($coberturas as $cobertura) {

                if (!$coberSaved) {
                    $coberturaNew = $cobertura->replicate();
                    $coberturaNew->idProducto = $productoIdNew;
                    $coberturaNew->save();

                    $arrRango = [];
                    $rango = json_decode($cobertura->rango, true);

                    foreach ($rango as $idTarifa => $data) {
                        if (!empty($tarifasEquivalentes[$idTarifa])) {
                            $newTarifaId = $tarifasEquivalentes[$idTarifa];
                            $data['idProducto'] = $productoIdNew;
                            $data['idProductoTarifa'] = $newTarifaId;
                            $data['idProductoCobertura'] = $coberturaNew->id;
                            $arrRango[$newTarifaId] = $data;
                        }
                    }

                    $coberturaNew->rango = json_encode($arrRango);
                    $coberturaNew->save();

                    $tarifaCoberturas = catProductoTarifaCobertura::where('idProducto', $productoId)->where('idProductoCobertura', $cobertura->id)->get();
                    //$tarifaCoberturasNew = catProductoTarifaCobertura::where('idProducto', $productoIdNew)->where('idProductoCobertura', $coberturaNew->id)->where('idProductoTarifa', $tarifaId)->first();

                    foreach ($tarifaCoberturas as $item) {
                        if (!empty($tarifasEquivalentes[$item->idProductoTarifa])) {
                            $tarifaCoberturaNew = $item->replicate();
                            $tarifaCoberturaNew->idProducto = $productoIdNew;
                            $tarifaCoberturaNew->idProductoTarifa = $tarifasEquivalentes[$item->idProductoTarifa];
                            $tarifaCoberturaNew->idProductoCobertura = $coberturaNew->id;
                            $tarifaCoberturaNew->save();
                        }
                    }
                }
            }

            $coberSaved = true;
        }
        return $this->ResponseSuccess('Configuración clonada con éxito');
    }

}
