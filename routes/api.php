<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ArchivadoresController;
use App\Http\Controllers\FormularioController;
use App\Http\Controllers\PaginasController;
use App\Http\Controllers\TareaPlantillaController;
use App\Http\Controllers\TareaController;
use App\Http\Controllers\AppController;
use App\Http\Controllers\ConfigController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FormulariosController;
use App\Http\Controllers\ProductosController;
use App\Http\Controllers\EtapasController;
use App\Http\Controllers\ClientesController;
use App\Http\Controllers\CanalesController;
use App\Http\Controllers\RequisitosController;
use App\Http\Controllers\TareasController;
use App\Http\Controllers\FlujosController;
use App\Http\Controllers\ReportesController;
use App\Http\Controllers\CatalogosController;
use App\Http\Controllers\InspeccionesController;
use App\Http\Controllers\DescuentosController;
use App\Http\Controllers\RecargaSiniestralidadController;
use App\Http\Controllers\ControlCalidadController;

#test
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('auth/validate-login', [AuthController::class, 'loginValidate']);
Route::post('auth/login', [AuthController::class, 'loginUser']);
Route::post('auth/logout', [AuthController::class, 'loginClose']);
Route::post('auth/reset-password', [AuthController::class, 'resetPassword']);
Route::post('auth/reset-my-password', [AuthController::class, 'resetPasswordWithToken']);

// cotizador publico
Route::post('productos/by/token/{token}', [ProductosController::class,'getProducts']);

// temporal
// Route::post('sso/users/get-list', [AuthController::class, 'SSO_GetUserList']);

// cotizaciones publico
Route::post('tareas/iniciar-cotizacion/public', [TareaController::class, 'IniciarCotizacion']);
Route::post('tareas/calcular-paso/public', [TareaController::class, 'CalcularPasosPublic']);
Route::post('tareas/cambiar-estado/public', [TareaController::class, 'CambiarEstadoCotizacionPublic']);
Route::post('tareas/calcular-catalogo/public', [TareaController::class, 'CalcularCatalogo']);
Route::post('tareas/comment/get', [TareaController::class, 'GetComentarios']);
Route::post('tareas/comment/save', [TareaController::class, 'CrearComentario']);
Route::get('paginas/ver/{slug}', [PaginasController::class, 'GetPagina']);
Route::post('tareas/save-field-on-blur/public', [TareaController::class, 'saveFieldOnBlur']);
Route::post('tareas/save-vehiculos-on-blur/public', [TareaController::class, 'saveVehiculosOnBlur']);
Route::post('tareas/upload-file/public', [TareaController::class, 'uploadFileAttach']);
Route::post('tareas/get-vehiculos', [TareaController::class, 'getVehiculos']);
Route::post('tareas/get-vehiculos-cotizacion', [TareaController::class, 'getVehiculosCotizaciones']);
Route::post('tareas/get-vehiculos-cotizacion-comp', [TareaController::class, 'getVehiculosCotizacionesComp']);
Route::post('tareas/add-vehiculo', [TareaController::class, 'addVehiculo']);
Route::post('tareas/delete-vehiculo', [TareaController::class, 'deleteVehiculo']);
Route::post('tareas/delete-cot', [TareaController::class, 'deleteCotizacion']);
Route::post('tareas/set-emitir-poliza', [TareaController::class, 'setEmitirPoliza']);
Route::post('tareas/exec-process/public', [TareaController::class, 'execProcess']);
Route::post('tareas/cusv/save', [TareaController::class, 'customVarsSave']);
Route::post('tareas/move-snbk', [TareaController::class, 'SiniesBlock']);

// inspecciones

// proxyfile
Route::get('tareas/pxfile', [TareaController::class, 'ProxyFile']);

// soporte público
Route::post('tareas/support/retroalimentacion', [TareaController::class, 'SoporteCrearRetroalimentacion']);

// Privados
Route::middleware('auth:sanctum')->group(function () {

    Route::post('inspecciones/get-horarios', [InspeccionesController::class, 'getHorarios']);
    Route::post('inspecciones/start-auto', [TareaController::class, 'processAutoInspeccionAS400']);
    Route::post('inspecciones/start-agenda', [InspeccionesController::class, 'startAgenda']);

    // Accesos y menú
    //Route::post('users/get-menu', [AuthController::class, 'GetMenu']);
    Route::get('users/list', [AuthController::class, 'GetUserList']);
    Route::get('users/list/active', [AuthController::class, 'GetUserListActive']);
    Route::get('users/list/active/jerar', [AuthController::class, 'GetUserListActiveJerar']);
    Route::get('users/list/active/store', [AuthController::class, 'GetUserListActiveStore']);
    Route::get('users/load/access/{roleid}', [AuthController::class, 'LoadUserAccess']);

    // usuarios
    Route::get('users/load/user/{userid}', [AuthController::class, 'LoadUser']);
    Route::post('users/save-user', [AuthController::class, 'SaveUser']);
    Route::post('users/create-user', [AuthController::class, 'CreateUser']);
    Route::post('users/user/delete', [AuthController::class, 'DeleteUser']);
    Route::post('users/sync', [AuthController::class, 'UserSync']);
    Route::post('get/codag', [AuthController::class, 'GetCodigosAgente']);

    Route::get('users/grupo/get/{grupoId}', [AuthController::class, 'LoadUserGrupo']);
    Route::get('users/grupo/list', [AuthController::class, 'GetUserGrupoList']);
    Route::post('users/grupo/save-user', [AuthController::class, 'SaveUseGrupo']);
    Route::post('users/grupo/delete', [AuthController::class, 'DeleteUserGrupo']);
    Route::post('users/grupo/save-cintillo', [AuthController::class, 'uploadCintillo']);

    Route::get('users/canal/get/{grupoId}', [AuthController::class, 'LoadUserCanal']);
    Route::get('users/canal/list', [AuthController::class, 'GetUserCanalList']);
    Route::post('users/canal/save-user', [AuthController::class, 'SaveUseCanal']);
    Route::post('users/canal/delete', [AuthController::class, 'DeleteUserCanal']);

    Route::get('users/tiendas/get/{grupoId}', [AuthController::class, 'LoadUserTiendas']);
    Route::get('users/tiendas/list', [AuthController::class, 'GetUserTiendasList']);
    Route::post('users/tiendas/save', [AuthController::class, 'SaveTiendasGrupo']);
    Route::post('users/tiendas/delete', [AuthController::class, 'DeleteTiendasGrupo']);

    Route::get('users/jerarquia/get/{grupoId}', [AuthController::class, 'LoadUserJerarquia']);
    Route::get('users/jerarquia/list', [AuthController::class, 'GetUserJerarquiaList']);
    Route::post('users/jerarquia/save', [AuthController::class, 'SaveUseJerarquia']);
    Route::post('users/jerarquia/delete', [AuthController::class, 'DeleteUserJerarquia']);

    Route::post('users/change-fuera-oficina', [AuthController::class, 'changesStatusUsers']);


// USUARIOS
    Route::post('users/get-menu', [AuthController::class, 'GetMenu']);
    Route::get('users/list', [AuthController::class, 'GetUserList']);
    Route::get('users/role/list', [AuthController::class, 'GetRoleList']);
    Route::get('users/load/access/{roleid}', [AuthController::class, 'LoadUserAccess']);
    Route::post('users/save-role', [AuthController::class, 'SaveRole']);
    Route::post('users/role/delete', [AuthController::class, 'DeleteRole']);
    Route::get('users/load/user/{userid}', [AuthController::class, 'LoadUser']);
    Route::post('users/save-user', [AuthController::class, 'SaveUser']);
    Route::post('users/user/delete', [AuthController::class, 'DeleteUser']);

    // Clientes
    /*Route::get('clients/all/{productoId}', [ClientesController::class, 'getClientes']);
    Route::get('clients/expediente/{expedienteId}', [ClientesController::class, 'getValorByExpediente']);
    Route::delete('clients/delete', [ClientesController::class, 'deleteCliente']);
    Route::get('channels/user', [ClientesController::class, 'getUserConfig']);
    Route::post('clients/new', [ClientesController::class, 'addCliente']);
    Route::get('clients/dashboards', [ClientesController::class, 'generarDashboards']);*/

    // BLOG
    Route::post('paginas/new/edit/{id?}', [PaginasController::class, 'crudPage']);
    Route::get('paginas/post/all', [PaginasController::class, 'crudPage']);
    Route::get('paginas/post/all/show', [PaginasController::class, 'getPages']);
    Route::get('paginas/by/{id}', [PaginasController::class, 'crudPage']);
    Route::delete('paginas/borrar/{id}', [PaginasController::class, 'crudPage']);
    Route::put('paginas/editar/{id}', [PaginasController::class, 'crudPage']);
    Route::get('paginas/ver/{slug}', [PaginasController::class, 'GetPagina']);
    Route::get('paginas/get/banners', [PaginasController::class, 'GetPaginaBanners']);

    // páginas de ayuda
    Route::post('paginas-ayuda/new/edit/{id?}', [PaginasController::class, 'crudPageAyuda']);
    Route::get('paginas-ayuda/post/all', [PaginasController::class, 'crudPageAyuda']);
    Route::get('paginas-ayuda/post/all/show', [PaginasController::class, 'getPagesAyuda']);
    Route::get('paginas-ayuda/by/{id}', [PaginasController::class, 'crudPageAyuda']);
    Route::delete('paginas-ayuda/borrar/{id}', [PaginasController::class, 'crudPageAyuda']);
    Route::put('paginas-ayuda/editar/{id}', [PaginasController::class, 'crudPageAyuda']);
    Route::get('paginas-ayuda/ver/{slug}', [PaginasController::class, 'GetPaginaAyuda']);

    // páginas de promociones
    Route::post('paginas-promociones/new/edit/{id?}', [PaginasController::class, 'crudPagePromociones']);
    Route::get('paginas-promociones/post/all', [PaginasController::class, 'crudPagePromociones']);
    Route::get('paginas-promociones/post/all/show', [PaginasController::class, 'getPagesPromociones']);
    Route::get('paginas-promociones/by/{id}', [PaginasController::class, 'crudPagePromociones']);
    Route::delete('paginas-promociones/borrar/{id}', [PaginasController::class, 'crudPagePromociones']);
    Route::put('paginas-promociones/editar/{id}', [PaginasController::class, 'crudPagePromociones']);
    Route::get('paginas-promociones/ver/{slug}', [PaginasController::class, 'GetPaginaPromociones']);
    Route::post('flujos/new', [FlujosController::class, 'modificarFlujo']);
    Route::get('flujos/list/{producto}', [FlujosController::class, 'getFlujoDisp']);

    // productos
    Route::post('productos/get-graph', [ProductosController::class,'getGraph']);
    Route::post('productos/get', [ProductosController::class,'getProducts']);
    Route::post('productos/get-panel', [ProductosController::class,'getProductsPanel']);
    Route::get('productos/filter', [ProductosController::class,'getProductosFilter']);
    Route::get('productos/internos/{idProducto}', [ProductosController::class,'getProducts']);
    Route::put('productos/editar/{idProducto}', [ProductosController::class,'editProductos']);
    Route::post('productos/delete', [ProductosController::class,'deleteProductos']);
    Route::post('productos/copy', [ProductosController::class,'copyProductos']);
    Route::post('productos/catalogo/download', [ProductosController::class,'downloadCatalogo']);

    //Route::get('forms/list', [FormulariosController::class, 'GetFormList']);
    //Route::get('forms/detail/{formid}', [FormulariosController::class, 'GetFormDetail']);

    //Route::get('prueba-ws', [EtapasController::class, 'pruebaWs']);
    //Route::get('parentescos', [EtapasController::class, 'findParentescos']);
    //Route::get('planes', [EtapasController::class, 'findPlanesByProducto']);
    //Route::get('clientes-nit', [EtapasController::class, 'findCliente']);
    //Route::post('cotizar', [EtapasController::class, 'cotizarCliente']);
    //Route::post('flujos/prueba', [EtapasController::class, 'emulateServicioWeb']);
    //Route::post('flujos/cotizar/producto', [EtapasController::class, 'cotizarProducto']);
    //Route::post('flujos/email', [EtapasController::class, 'emulateEmail']);
    //Route::post('flujos/pdf', [EtapasController::class, 'generatePDF']);
    Route::post('flujos/upload-pdf-template', [FlujosController::class, 'uploadPdfTemplate']);

    // roles
    Route::get('users/role/access/list', [AuthController::class, 'GetRoleAccessList']);
    Route::get('users/role/list', [AuthController::class, 'GetRoleList']);
    Route::get('users/role/load/{rolId}', [AuthController::class, 'GetRoleDetail']);
    Route::post('users/save-role', [AuthController::class, 'SaveRole']);
    Route::post('users/role/delete', [AuthController::class, 'DeleteRole']);

    // Archivadores
    Route::get('admin/archivador/list', [ArchivadoresController::class, 'GetList']);
    Route::get('admin/archivador/load/{userid}', [ArchivadoresController::class, 'Load']);
    Route::post('admin/archivador/save', [ArchivadoresController::class, 'Save']);
    Route::post('admin/archivador/delete', [ArchivadoresController::class, 'Delete']);
    Route::post('admin/archivador/delete-field', [ArchivadoresController::class, 'DeleteField']);
    Route::get('admin/archivador/fields', [ArchivadoresController::class, 'GetListFields']);

    //formularios
    /*Route::get('admin/formulario/list', [FormularioController::class, 'GetList']);
    Route::get('admin/formulario/load/{userid}', [FormularioController::class, 'Load']);
    Route::post('admin/formulario/save', [FormularioController::class, 'Save']);
    Route::post('admin/formulario/delete', [FormularioController::class, 'Delete']);
    Route::post('admin/formulario/delete-field', [FormularioController::class, 'DeleteField']);
    Route::get('admin/formulario/fields', [FormularioController::class, 'GetListFields']);*/

    // Cotizaciones
    Route::post('tareas/iniciar-cotizacion', [TareaController::class, 'IniciarCotizacion']);
    Route::post('tareas/revivir-cotizacion', [TareaController::class, 'RevivirCotizacion']);
    Route::post('tareas/calcular-paso', [TareaController::class, 'CalcularPasos']);
    Route::get('tareas/get-cotizacion/{cotizacionId}', [TareaController::class, 'GetCotizacion']);
    Route::post('tareas/cambiar-estado', [TareaController::class, 'CambiarEstadoCotizacion']);
    Route::post('tareas/cambiar-usuario', [TareaController::class, 'CambiarUsuarioCotizacion']);
    Route::post('tareas/editar-estado', [TareaController::class, 'EditarEstadoCotizacion']);
    Route::post('tareas/upload-file', [TareaController::class, 'uploadFileAttach']);
    Route::post('tareas/file-get-preview', [TareaController::class, 'GetFilePreview']);
    Route::post('tareas/get-progression', [TareaController::class, 'GetProgression']);
    Route::post('tareas/calcular-campos', [TareaController::class, 'CalcularCampos']);
    Route::post('tareas/get-catalogo', [TareaController::class, 'GetCatalogo']);
    Route::post('tareas/save-field-on-blur', [TareaController::class, 'saveFieldOnBlur']);
    Route::post('tareas/save-vehiculos-on-blur', [TareaController::class, 'saveVehiculosOnBlur']);
    Route::post('tareas/calcular-siniestralidad', [TareaController::class, 'calculateAccidentRate']);
    Route::post('tareas/inspecciones', [TareaController::class, 'verInspecciones']);
    Route::post('tareas/exec-process', [TareaController::class, 'execProcess']);
    Route::post('tareas/send-attach', [TareaController::class, 'reenviarAdjuntos']);
    Route::post('tareas/linking-cotizaciones', [TareaController::class, 'linkingCotizaciones']);

    Route::post('tareas/all', [TareaController::class, 'GetCotizaciones']);
    Route::post('tareas/get-resumen', [TareaController::class, 'GetCotizacionResumen']);
    Route::post('tareas/get-fast-view', [TareaController::class, 'GetCotizacionesFastCount']);
    Route::post('tareas/catalogos/bring', [CatalogosController::class, 'BringCatalogos']);

    // plantillas pdf
    Route::post('tareas/save/pdf-template', [TareaController::class, 'uploadPdfTemplate']);
    Route::get('tareas/get/pdf-template-list', [TareaController::class, 'getPdfTemplateList']);
    Route::get('tareas/get/pdf-template/{id}', [TareaController::class, 'getPdfTemplate']);
    Route::post('tareas/delete/pdf-template', [TareaController::class, 'deletePdfTemplate']);

    // variables de sistema
    Route::post('config/vars/get', [ConfigController::class, 'GetVars']);
    Route::post('config/vars/save', [ConfigController::class, 'SaveVars']);
    Route::delete('config/vars/delete', [ConfigController::class, 'deleteVars']);

    // Configuración de sistema
    Route::post('config/system/cache-clear', [ConfigController::class, 'cacheClear']);

    // reportes
    Route::post('reportes/get-graph', [ReportesController::class,'getGraph']);
    Route::get('reportes/get-flujos', [ReportesController::class, 'ListadoFlujos']);
    Route::get('reportes/listado', [ReportesController::class, 'Listado']);
    Route::get('reportes/listado/all', [ReportesController::class, 'ListadoAll']);
    Route::get('reportes/listado/masivo', [ReportesController::class, 'ListadoMasivos']);
    Route::post('reportes/nodos/campos', [ReportesController::class, 'NodosCampos']);
    Route::post('reportes/save', [ReportesController::class, 'Save']);
    Route::post('reportes/get', [ReportesController::class, 'GetReporte']);
    Route::post('reportes/generar', [ReportesController::class, 'Generar']);
    Route::post('reportes/eliminar', [ReportesController::class, 'DeleteReporte']);

    // catalogos
    Route::post('admin/catalogo/load', [CatalogosController::class, 'LoadCatalogo']);
    Route::post('admin/catalogo/load-fields', [CatalogosController::class, 'LoadCatalogoFields']);
    Route::post('admin/catalogo/load-access', [CatalogosController::class, 'LoadCatalogoAccess']);
    Route::post('admin/catalogo/save-row', [CatalogosController::class, 'saveRowCatalogo']);

    // productos y tarifas
    Route::post('admin/load/productos-tarifas', [CatalogosController::class, 'confProdTarifa']);
    //Route::post('admin/delete/productos-tarifa', [CatalogosController::class, 'deleteProdTarifa']);
    Route::post('admin/productos-tarifas/save', [CatalogosController::class, 'saveProdTarifa']);

    Route::post('admin/load/producto-tarifa', [CatalogosController::class, 'getProdTarifa']);
    Route::post('admin/load/producto-cobertura', [CatalogosController::class, 'getProdCobertura']);
    Route::post('admin/load/cotizador-tarifas-c', [CatalogosController::class, 'getTarifaCoberturaCotizador']);

    Route::post('admin/load/productos-coberturas-tarifas', [CatalogosController::class, 'getProdTarifaCobertura']);
    Route::post('tareas/add-variante', [TareaController::class, 'addVariante']);
    Route::post('auto/valor-promedio', [CatalogosController::class, 'getValorPromedio']);


    Route::post('inspecciones/get-agendadas', [InspeccionesController::class, 'getAgendadas']);

    Route::post('tareas/vrtest', [TareaController::class, 'VarTest']);
    Route::post('tareas/support/get', [TareaController::class, 'SoporteGetComentarios']);
    Route::post('tareas/support/save', [TareaController::class, 'SoporteCrearComentario']);
    Route::post('tareas/support/detail', [TareaController::class, 'SoporteDetalleComentario']);


    // catalogos
    Route::post('catalogos/vehiculos/tipo', [CatalogosController::class, 'autoGetTipos']);
    Route::post('catalogos/frecuencia/pago', [CatalogosController::class, 'autoGetFrecuenciaPagos']);


    // ventana nueva productos, tarifa, cobertura
    Route::post('config/prod-tari-cober', [CatalogosController::class, 'getProdTarCober']);

    Route::post('config/add-prod-tari', [CatalogosController::class, 'addProdTarifa']);
    Route::post('config/delete-prod-tari', [CatalogosController::class, 'deleteProdTarifa']);

    Route::post('config/add-prod-tari-cober', [CatalogosController::class, 'addProdTarifaCobertura']);
    Route::post('config/delete-prod-tari-cober', [CatalogosController::class, 'deleteProdTarifaCobertura']);

    Route::post('config/save-prod-tari-cober', [CatalogosController::class, 'saveProdTarifaCobertura']);

    Route::post('config/clone-prod-tari-cober', [CatalogosController::class, 'cloneProdTarifaCobertura']);

    //Descuento
    Route::get('descuentos/get-flujos', [DescuentosController::class, 'ListadoFlujos']);
    Route::post('descuentos/listado', [DescuentosController::class, 'Listado']);
    Route::post('descuentos/verify', [DescuentosController::class, 'Verify']);
    Route::get('descuentos/listado/all', [DescuentosController::class, 'ListadoAll']);
    Route::post('descuentos/save', [DescuentosController::class, 'Save']);
    Route::post('descuentos/get', [DescuentosController::class, 'GetDescuento']);
    Route::post('descuentos/eliminar', [DescuentosController::class, 'DeleteDescuento']);

    //Recargas
    Route::post('recargas/siniestralidad/save', [RecargaSiniestralidadController::class, 'Save']);
    Route::get('recargas/siniestralidad/listado', [RecargaSiniestralidadController::class, 'Listado']);

    // control calidad
    Route::POST('control-calidad/get-ficha', [ControlCalidadController::class, 'GetFicha']);
    Route::POST('control-calidad/save', [ControlCalidadController::class, 'Save']);
});
