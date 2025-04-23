<?php

namespace App\Http\Controllers;

use app\core\Response;
use App\Models\Clientes;
use App\Models\Cotizacion;
use App\Models\Etapas;
use App\Models\Expedientes;
use App\Models\ExpedientesEtapas;
use App\Models\Flujos;
use App\Models\Productos;
use App\Models\RequisitosCategorias;
use App\Models\SistemaVariable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProductosController extends Controller {

    use Response;

    /**
     * Get Steps
     * @param Request $request
     * @return array|false|string
     */
    public function getProductosFilter(Request $request) {
        if (!empty($request->idProducto)) {
            $productos = DB::table('productos')->where('productos.id', '=', $request->idProducto)->get(['id', 'nombreProducto']);

        }
        else {
            $productos = DB::table('productos')->groupBy('productos.id')->get(['id', 'nombreProducto']);
        }

        try {
            return $this->ResponseSuccess('Ok', $productos);
        } catch (\Throwable $th) {
            return $this->ResponseError('PROD-854', 'Error al obtener productos' . $th);
        }
    }

    public function getProducts(Request $request, $token = false, $validateAccess = false) {

        $usuarioLogueado = auth('sanctum')->user();
        $removeCatalogos = $request->get('rc');
        $rolUsuarioLogueado = ($usuarioLogueado) ? $usuarioLogueado->rolAsignacion->rol : 0;

        if (!empty($request->idProducto)) {
            $productos = Cache::remember("product_{$request->idProducto}", env('CACHE_SECONDS', 600), function() use ($request) {
                return DB::table('productos')->where('productos.id', '=', $request->idProducto)->get();
            });
        }
        else if (!empty($token)){
            $productos = Cache::remember("product_bytk_{$token}", env('CACHE_SECONDS', 600), function() use ($token) {
                return DB::table('productos')->where('productos.token', '=', $token)->get();
            });
        }
        else {
            if (!$removeCatalogos) {
                $productos = DB::table('productos')->where('productos.status', '=', 1)->groupBy('productos.id')->get();
            }
            else {
                $productos = DB::table('productos')->groupBy('productos.id')->get();
            }
        }

        //dd($productos);
        //dd(md5(uniqid()).uniqid());

        $productos->map(function ($producto) use ($usuarioLogueado, $removeCatalogos) {
            if (isset($producto->extraData) && $producto->extraData !== '') {
                $producto->extraData = json_decode($producto->extraData, true);
            }
            $flujo = Flujos::Where('productoId', '=', $producto->id)->Where('activo', '=', 1)->first();
            if (!empty($flujo)) {
                $producto->flujo = @json_decode($flujo->flujo_config, true);
                $producto->flujoId = $flujo->id;
                $producto->modoPruebas = $flujo->modoPruebas;
                $producto->valSin = $flujo->valSin;

                if (!empty($usuarioLogueado)) {
                    $producto->userVars = @json_decode($usuarioLogueado->userVars);
                }
            }
            else {
                $producto->flujo = [];
                $producto->flujoId = 0;
                $producto->modoPruebas = 0;
                $producto->valSin = 0;
                $producto->userVars = [];
            }

            if (!$removeCatalogos) {
                $producto->roles_assign = $producto->extraData['roles_assign'] ?? [];
                $producto->grupos_assign = $producto->extraData['grupos_assign'] ?? [];
                $producto->canales_assign = $producto->extraData['canales_assign'] ?? [];
            }

            // no devuelvo catálogos
            if ($removeCatalogos && isset($producto->extraData['planes'])) {
                $producto->flujo = false;
                unset($producto->extraData['planes']);
            }

            // catálogos sincronizados
            $tareaHandler = new CatalogosController();
            $slugs = $tareaHandler->GetSyncCatalogoSlugs();

            foreach ($slugs as $slug => $cat) {

                $arrFields = [];
                foreach ($cat['campos'] as $campo) {
                    $arrFields[$campo] = '';
                }

                $producto->extraData['planes'][$slug] = [
                    "slug" => $slug,
                    "nombreCatalogo" => $cat['nombre'],
                    "ct_def" => true,
                    "items" => [$arrFields],
                ];
            }

            return $producto;
        });

        $authHandler = new AuthController();

        $arrProductos = [];
        foreach ($productos as $pr) {
            if ($validateAccess) {
                //dd($pr->roles_assign);
                //dd($pr);
                $access = $authHandler->CalculateVisibility($usuarioLogueado->id, $rolUsuarioLogueado->id ?? 0, false, $pr->roles_assign ?? [], $pr->grupos_assign ?? [], $pr->canales_assign ?? []);
                if (!$access) continue;
            }
            $arrProductos[] = $pr;
        }

        try {
            return $this->ResponseSuccess('Ok', $arrProductos);
        } catch (\Throwable $th) {
            return $this->ResponseError('AUTH-AF60F', 'Error al generar pasos' . $th);
        }
    }

    public function editProductos(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['admin/flujos'])) return $AC->NoAccess();

        $validateForm = Validator::make($request->all(), ['nombreProducto' => 'required|string', 'descripcion' => '', 'isVirtual' => '', 'extraData' => '', 'id' => 'required', 'imagenData' => '', 'codigoInterno' => '', 'imagen' => '', 'valSin' => '', 'status' => '', 'sincronizar' => '']);

        if ($validateForm->fails()) {
            return $this->ResponseError('AUTH-AdfF10dsF', 'Faltan Campos');
        }

        if (!empty($request->imagenData)) {
            $extensiones_permitidas = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            /*$extension = $img->getClientOriginalExtension();*/

            $image_info = getimagesize($request->imagenData);

            if (!in_array($image_info['mime'] ?? null, $extensiones_permitidas)) {
                return $this->ResponseError('ERROR', 'Error, tipo de imagen no permitido');
            }
        }

        if ($request->id === 0) {
            $producto = new Productos();
            $producto->nombreProducto = $request->nombreProducto ?? '';
            $producto->descripcion = $request->descripcion ?? '';
            $producto->codigoInterno = $request->codigoInterno ?? '';
            $producto->imagenData = $request->imagenData ?? '';
            $producto->cssCustom = $request->cssCustom ?? '';
            $producto->jsCustom = $request->jsCustom ?? '';
            $producto->isVirtual = $request->isVirtual ?? 0;
            $producto->valSin = $request->valSin ?? 0;
            $producto->status = $request->status ?? 0;
            $producto->manErr = $request->manErr ?? 0;
            $producto->extraData = json_encode($request->extraData ?? '');
            $producto->token = md5(uniqid()).uniqid();
            $producto->sincronizar = $request->sincronizar ?? 0;
            $producto->save();
            return $this->ResponseSuccess('Ok', $producto);
        }
        else {
            $producto = Productos::where('id', $request->id)->first();
            if (!empty($producto)) {
                $producto->nombreProducto = $request->nombreProducto ?? '';
                $producto->descripcion = $request->descripcion ?? '';
                $producto->codigoInterno = $request->codigoInterno ?? '';
                $producto->imagenData = $request->imagenData ?? '';
                $producto->cssCustom = $request->cssCustom ?? '';
                $producto->jsCustom = $request->jsCustom ?? '';
                $producto->isVirtual = $request->isVirtual ?? 0;
                $producto->status = $request->status ?? 0;
                $producto->manErr = $request->manErr ?? 0;
                $producto->extraData = json_encode($request->extraData ?? '');
                $producto->sincronizar = $request->sincronizar ?? 0;
                $producto->save();
                return $this->ResponseSuccess('Ok', $producto);
            }
            else {
                return $this->ResponseError('AUTH-AFd10dsF', 'Producto no existe');
            }
        }
    }

    public function deleteProductos(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['admin/flujos'])) return $AC->NoAccess();

        $validateForm = Validator::make($request->all(), ['id' => 'required',

        ]);

        if ($validateForm->fails()) {
            return $this->ResponseError('AUTH-AdfF10dsF', 'Faltan Campos');
        }
        if ($request->id === 0) {
            return $this->ResponseError('AUTH-AFdd10dsF', 'Producto no existe');
        }
        else {
            $producto = Productos::where('id', $request->id)->first();
            if (!empty($producto)) {
                $producto->delete();
                return $this->ResponseSuccess('Eliminado con éxito', $producto);
            }
            else {
                return $this->ResponseError('AUTH-AFd10dsF', 'Producto no existe');
            }
        }
    }

    public function getProductsPanel(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['tareas/listar/flujo'])) return $AC->NoAccess();

        $AC = new AuthController();
        //if (!$AC->CheckAccess(['admin/flujos'])) return $AC->NoAccess();

        return $this->getProducts($request, false, true);
    }

    public function copyProductos(Request $request) {

        $AC = new AuthController();
        if (!$AC->CheckAccess(['admin/flujos'])) return $AC->NoAccess();

        $validateForm = Validator::make($request->all(), ['id' => 'required']);

        if ($validateForm->fails()) {
            return $this->ResponseError('AUTH-AdfF10dsF', 'Faltan Campos');
        }
            $producto = Productos::where('id', $request->id)->first();
            $flujo = Flujos::Where('productoId', '=', $producto->id)->Where('activo', '=', 1)->first();

            if (!empty($producto)) {
                $nuevaCopia = new Productos();
                $nuevaCopia->nombreProducto = 'Nueva copia de ' . $producto->nombreProducto;
                $nuevaCopia->descripcion = $producto->descripcion ?? '';
                $nuevaCopia->codigoInterno = $producto->codigoInterno ?? '';
                $nuevaCopia->imagenData = $producto->imagenData ?? '';
                $nuevaCopia->cssCustom = $producto->cssCustom ?? '';
                $nuevaCopia->jsCustom = $producto->jsCustom ?? '';
                $nuevaCopia->isVirtual = $producto->isVirtual ?? 0;
                $nuevaCopia->status = $producto->status ?? 0;
                $nuevaCopia->extraData = $producto->extraData?? '';
                $nuevaCopia->token = md5(uniqid()).uniqid();
                $nuevaCopia->status = $producto->prActivo;
                $nuevaCopia->valSin = $producto->valSin;
                $nuevaCopia->sincronizar = $producto->sincronizar ?? 0;
                $nuevaCopia->save();

                $nuevoflujo = new Flujos();
                $nuevoflujo->nombre = $flujo->nombre??'';
                $nuevoflujo->flujo_config = $flujo->flujo_config;
                $nuevoflujo->productoId = $nuevaCopia->id;
                $nuevoflujo->activo = $flujo->activo;
                $nuevoflujo->modoPruebas = $flujo->modoPrueba;
                $nuevoflujo->save();

                return $this->ResponseSuccess('Ok', $nuevaCopia);
            }
            else {
                return $this->ResponseError('AUTH-AFd10dsF', 'Producto no existe');
            }
    }

    public function downloadCatalogo(Request $request){
        try{
            // ordenar datos finales
            $AC = new AuthController();
            if (!$AC->CheckAccess(['admin/flujos'])) return $AC->NoAccess();
            $usuarioLogueado = auth('sanctum')->user();

            $datosFinal = $request->get('dataToSend');

            $spreadsheet = new Spreadsheet();

            $spreadsheet
                ->getProperties()
                ->setCreator("GastosMedicos-ElRoble")
                ->setLastModifiedBy('Automator') // última vez modificado por
                ->setTitle('Reporte de '. $usuarioLogueado->name)
                ->setDescription('Reporte');

            // first sheet
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle("Hoja 1");
            $sheet->fromArray($datosFinal, NULL, 'A1');

            $writer = new Xlsx($spreadsheet);
            $fileNameHash = md5(uniqid());
            $tmpPath = storage_path("tmp/{$fileNameHash}.xlsx");
            $writer->save($tmpPath);

            $disk = Storage::disk('s3');
            $path = $disk->putFileAs("/tmp/files", $tmpPath, "{$fileNameHash}.xlsx");
            $temporarySignedUrl = Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(10));

            return $this->ResponseSuccess('Reporte generado con éxito', ['url' => $temporarySignedUrl]);

        } catch (\Throwable $th){
            return $this->ResponseError('AUTH-AF65F', 'Error' . $th);
        }
    }

    public function getGraph(Request $request) {

        $AC = new AuthController();
        //if (!$AC->CheckAccess(['admin/flujos'])) return $AC->NoAccess();

        $fechaIni = $request->get('fechaIni');
        $fechaFin = $request->get('fechaFin');

        $fechaIni = Carbon::parse($fechaIni)->toDateString()." 00:00:00";
        $fechaFin = Carbon::parse($fechaFin)->toDateString()." 23:59:59";

        $userHandler = new AuthController();
        $CalculateAccess = $userHandler->CalculateAccess();
        /*$items = Cotizacion::where([['dateCreated', '>=', $fechaIni], ['dateCreated', '<=', $fechaFin]])->whereIn('usuarioIdAsignado', $CalculateAccess['all']);
        $items = $items->with(['usuario', 'usuarioAsignado', 'producto', 'campos'])->limit(10)->orderBy('id', 'DESC')->get();*/

        $usuarios = implode(",", $CalculateAccess['all']);

        $cotizaciones = [];
        // conteo por productos
        $strQueryFull = "SELECT COUNT(C.id) as c, P.nombreProducto as p, P.id as pid
                        FROM cotizaciones AS C
                        JOIN productos AS P ON C.productoId = P.id
                        WHERE 
                            C.usuarioIdAsignado IN ($usuarios)
                            AND C.dateCreated >= '{$fechaIni} 00:00:00'
                            AND C.dateCreated <= '{$fechaFin} 23:59:59'
                        GROUP BY P.nombreProducto, P.id";

        /*var_dump($strQueryFull);
        die();*/
        $cotizaciones = DB::select(DB::raw($strQueryFull));

        // conteo por productos
        $strQueryFull = "SELECT COUNT(C.id) as c, YEAR(C.dateCreated) as anio, MONTH(C.dateCreated) as mes
                        FROM cotizaciones AS C
                        WHERE 
                            C.usuarioIdAsignado IN ($usuarios)
                        GROUP BY YEAR(C.dateCreated), MONTH(C.dateCreated)";

        $cotizacionesY = DB::select(DB::raw($strQueryFull));

        $porYear = [];
        foreach ($cotizacionesY as $item) {
            if (!isset($porYear[$item->anio][$item->mes])) $porYear[$item->anio][$item->mes] = 0;
            $porYear[$item->anio][$item->mes] = $porYear[$item->anio][$item->mes] + $item->c;
        }

        return $this->ResponseSuccess('Gráfica obtenida con éxito', [
            'p' => $cotizaciones, 'y' => $porYear
        ]);
    }
}
