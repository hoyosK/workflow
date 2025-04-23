<template>
    <div :class="'wizzardContainer ' + flujoActivo.nodoClass">
        <CCard class="mb-4">
            <CCardHeader class="wizzardHeader">
                <h5>
                    <strong>
                        {{ producto.nombreProducto || 'Producto sin nombre' }}
                        <span v-if="isCoti()">- Tarea No.{{ (cotizacion.identificador) ? cotizacion.identificador : cotizacion.no }}</span>
                    </strong>
                    <div  v-if="isCoti() && !isPublic">
                        <span class="text-success">Código de agente: {{ cotizacion.ca }}</span>
                    </div>
                    <div>
                        <span style="text-transform: capitalize;" class="text-primary" v-if="estadoCotizacion !== ''">Estado: {{ estadoCotizacion }}</span>
                    </div>
                </h5>
                <div class="btnBar">
                    <button v-if="producto.modoPruebas && (typeof authInfo.m['admin/show-test-mode'] !== 'undefined' && authInfo.m['admin/show-test-mode'])" class="btn btn-secondary btn-sm" @click="getVariablesTest">
                        <i class="fas fa-code"></i>
                        <span class="d-none d-sm-inline">Variables</span>
                    </button>
                    <button class="btn btn-secondary btn-sm" @click="getSoporteComentarios">
                        <i class="fas fa-question-circle"></i>
                        <span class="d-none d-sm-inline">Solicitar soporte</span>
                    </button>
                    <button v-if="isCoti()  && !isPublic && (typeof authInfo.m['inpecciones/agendar-en-flujo'] !== 'undefined' && authInfo.m['inpecciones/agendar-en-flujo'])" class="btn btn-secondary btn-sm" @click="getInspecciones">
                        <i class="fas fa-search"></i>
                        <span class="d-none d-sm-inline">Inspección</span>
                    </button>
                    <button v-if="!isPublic" class="btn btn-secondary btn-sm" @click="showFilesBar = true; previewAdjunto()">
                        <span><i class="fas fa-paperclip"></i></span>
                        <span class="d-none d-sm-inline">Ver adjuntos</span>
                    </button>
                    <button v-if="isCoti()  && !isPublic && (typeof authInfo.m['tareas/show-button-re-start'] !== 'undefined' && authInfo.m['tareas/show-button-re-start'])" class="btn btn-danger btn-sm" @click="backToStart()">
                        <span><i class="fa-solid fa-arrow-rotate-left"></i></span>
                        <span class="d-none d-sm-inline">Reiniciar</span>
                    </button>
                    <button v-if="isCoti() && !isPublic && (typeof authInfo.m['tareas/show-button-copy-coti'] !== 'undefined' && authInfo.m['tareas/show-button-copy-coti'])" class="btn btn-primary btn-sm" @click="copyCoti">
                        <span><i class="fa-solid fa-copy"></i></span>
                        <span class="d-none d-sm-inline">Copiar tarea</span>
                    </button>
                    <button v-if="!isPublic" class="btn btn-primary btn-sm" @click="$router.push('/admin/tareas')">
                        <span><i class="fas fa-arrow-alt-circle-left"></i></span>
                        <span class="d-none d-sm-inline">Ir a listado de tareas</span>
                    </button>
                    <!--<button v-if="flujoActivo.cmT && (flujoActivo.cmT === 'm' || (flujoActivo.cmT === 'p' && !isPublic))" class="btn btn-primary btn-sm" @click="getComentarios">
                    <i class="fas fa-comment"></i>
                    <span class="d-none d-sm-inline">Comentarios</span>
                </button>-->
                    <template v-if="!isPublic">
                        <!--<button v-if="isCoti()" class="btn btn-success btn-sm" @click="verProgresion">
                            <span><i class="fas fa-list-check"></i></span>
                            <span class="d-none d-sm-inline">Ver progresión</span>
                        </button>-->
                        <button v-if="isCoti()" class="btn btn-success btn-sm" @click="validarSiniestralidad">
                            <span><i class="fas fa-user-check"></i></span>
                            <span class="d-none d-sm-inline">Validar siniestralidad</span>
                        </button>
                        <button v-if="isCoti() && !isPublic && !!inspeccion" class="btn btn-success btn-sm" @click="showinspeccion = !showinspeccion">
                            <span><i class="fa-solid fa-car"></i></span>
                            <span class="d-none d-sm-inline">Reenvio de autoinpsección</span>
                        </button>
                    </template>
                    <div class="globalModal" v-if="showsiniestralidad">
                        <div class="globalModalContainer p-5">
                            <div @click="showsiniestralidad = false" class="globalModalClose mt-3">
                                <i class="fas fa-times-circle"></i></div>
                            <div v-if="!!siniestralidad['cliente'] || (siniestralidad['cliente'] !== undefined)">
                                <div>
                                    <h5 class="mt-5">Cliente</h5>
                                </div>
                                <hr>
                                <div v-if="!!siniestralidad['cliente']['errors']" class="text-center">
                                    <strong class="text-danger" v-for="error in siniestralidad['cliente']['errors']">
                                        <div v-if="typeof error['SINIESTRALIDAD_AS400.datosIdEmpresaGC.mensajeRespuesta'] !== 'undefined'">
                                            {{ error['SINIESTRALIDAD_AS400.datosIdEmpresaGC.mensajeRespuesta'].toUpperCase() }}
                                        </div>
                                        <div v-else>
                                            Error al validar siniestralidad del cliente
                                        </div>
                                    </strong>
                                </div>
                                <div v-else class="text-center">
                                    <div v-if="siniestralidad['cliente'] > 0" class="text-danger">
                                        El cliente posee siniestralidad, se aplicará un recargo de {{siniestralidad['cliente']}}%
                                        <div class="mt-4">
                                            <button class="btn btn-primary" @click="showsiniestralidad = false; getSoporteComentarios(siniestralidad['cliente'])">Crear ticket de soporte</button>
                                        </div>
                                    </div>
                                    <div v-else class="text-success">
                                        El cliente no posee siniestralidad, no se aplicará recargo
                                    </div>
                                </div>
                                <div v-if="!!siniestralidad['poliza'] && siniestralidad['poliza'].length > 0">
                                    <hr>
                                    <EasyDataTable :headers="headersPoliza" :items="siniestralidad['poliza']" alternating >
                                    </EasyDataTable>
                                </div>
                                <!--<div v-for="(result, keyresult) in siniestralidad['cliente']">
                                    <div v-for="consulta in result">
                                        <div v-if="keyresult === 'success' && !!consulta['SINIESTRALIDAD_AS400.datosIdEmpresaGC.datos03.consultaDatosClienteGestorComercial.clientePersonal.clienteListaNegra']">
                                            <strong :class="consulta['SINIESTRALIDAD_AS400.datosIdEmpresaGC.datos03.consultaDatosClienteGestorComercial.clientePersonal.clienteListaNegra'] === 'S'? 'text-danger' : ''">
                                                {{ consulta['SINIESTRALIDAD_AS400.datosIdEmpresaGC.datos03.consultaDatosClienteGestorComercial.clientePersonal.clienteListaNegra'] === 'S'? 'Cliente se encuentra en lista negra' : 'Cliente no se encuentra en lista negra' }}
                                            </strong>
                                        </div>
                                        <div v-if="keyresult === 'errors' && !!consulta['SINIESTRALIDAD_AS400.datosIdEmpresaGC.mensajeRespuesta']">
                                            <strong class="text-danger">
                                                {{ consulta['SINIESTRALIDAD_AS400.datosIdEmpresaGC.mensajeRespuesta'] }}
                                            </strong>
                                        </div>
                                    </div>
                                </div>-->
                            </div>
                            <div v-if="!!siniestralidad['vehiculo']">
                                <div>
                                    <h5 class="mt-5">Vehículo</h5>
                                </div>
                                <hr>
                                <EasyDataTable :headers="headersTable" :items="itemsTable" alternating >
                                </EasyDataTable>
                            </div>
                        </div>
                    </div>
                    <div class="globalModal" v-if="showInspeccionesModal">
                        <div class="globalModalContainer p-5">
                            <div @click="showInspeccionesModal = false" class="globalModalClose mt-3">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <div>
                                <h4>Inspección</h4>
                                <div v-for="item in inspeccionesItems">
                                    <div class="row mb-3">
                                        <div class="col-12 col-sm-6">
                                            <h4>Datos de vehículo</h4>
                                            <div>
                                                <b>Marca:</b> {{item.marca}}
                                            </div>
                                            <div>
                                                <b>Línea:</b> {{item.linea}}
                                            </div>
                                            <div>
                                                <b>Modelo:</b> {{item.modelo}}
                                            </div>
                                            <div>
                                                <b>No. Motor:</b> {{item.motor}}
                                            </div>
                                            <div>
                                                <b>No. Chasis:</b> {{item.chasis}}
                                            </div>
                                            <div>
                                                <b>Placa:</b> {{item.placa}}
                                            </div>
                                            <div>
                                                <b>Dirección:</b> {{item.direccion}}
                                                <input class="form-control" v-model="item.direccion" v-if="!item.autoInspeccion && !item.inspeccionId">
                                            </div>
                                        </div>
                                        <div class="col-12 col-sm-6">
                                            <h4 class="mb-2">Inspecciones</h4>
                                            <div v-if="!item.autoInspeccion">
                                                <h5 class="text-muted mb-2">A domicilio:</h5>
                                                <div v-if="item.inspeccionId > 0" class="badge bg-success">
                                                    Inspección agendada No. {{item.inspeccionId}}
                                                </div>
                                                <div v-else>
                                                    <div>
                                                        <div>Selecciona la fecha de tu agendamiento</div>
                                                        <date-picker v-model="dateInspeccion" mode="date" :min-date='new Date()' :attributes="datePickerParams" style="width: 100%"></date-picker>
                                                    </div>
                                                    <div v-if="horarios && Object.keys(horarios).length > 0" class="mt-3 text-center">
                                                        <ul class="list-group">
                                                            <li :class="{'list-group-item horarioItem active': horarioSelected === item, 'list-group-item horarioItem': horarioSelected !== item}" v-for="item in horarios" @click="horarioSelected = item">{{item}}</li>
                                                        </ul>
                                                        <div class="mt-5">
                                                            <button class="btn btn-primary" @click="iniciarAgendamiento(item.autoId, item.direccion)"><i class="fas fa-check-circle me-2"></i>Generar inspección domiciliar</button>
                                                        </div>
                                                    </div>
                                                    <div v-else class="mt-3 text-center text-danger">
                                                        No hay horarios disponibles
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mt-4" v-if="!item.inspeccionId">
                                                <h5 class="text-muted mb-2">Auto inspección:</h5>
                                                <div v-if="!!item.autoInspeccion">
                                                    <a :href="item.autoInspeccion">Redirigir a autoinspección</a>
                                                </div>
                                                <div v-else class="mt-3 text-center">
                                                    <button class="btn btn-primary" @click="iniciarAutoInspeccion(item.autoId, item.direccion)"><i class="fas fa-check-circle me-2"></i>Generar autoinspección</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="Object.keys(inspeccionesItems).length < 1">
                                <p class="text-muted text-center">No se encontraron vehículos a inspeccionar</p>
                            </div>
                            <div class="globalModalFooter text-end">
                                <CButton @click="showInspeccionesModal = false" class="btn btn-success me-2">
                                    Regresar
                                </CButton>
                                <CButton @click="importDataInspecciones()" class="btn btn-primary me-2">
                                    Importar datos desde inspecciones
                                </CButton>
                            </div>
                        </div>
                    </div>
                    <template v-if="!isPublic && !!inspeccion">
                        <!--<button v-if="isCoti()" class="btn btn-success btn-sm" @click="verProgresion">
                            <span><i class="fas fa-list-check"></i></span>
                            <span class="d-none d-sm-inline">Ver progresión</span>
                        </button>-->

                        <div class="globalModal" v-if="showinspeccion">
                            <div class="globalModalContainer p-5">
                                <div @click="showinspeccion = false" class="globalModalClose mt-3">
                                    <i class="fas fa-times-circle"></i></div>
                                <div>
                                    <div>
                                        <h5 class="mt-5">Link de inspección:</h5>
                                    </div>
                                    <hr>
                                    <div class="text-center">
                                        <a :href="inspeccion">{{ inspeccion }}</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </CCardHeader>
            <div v-if="typeof cotizacion.ed !== 'undefined' && cotizacion.ed !== ''" class="text-center my-3 text-danger">
                {{ cotizacion.ed }}
            </div>
            <div v-if="siniestralidadBlock > 0" class="text-center text-danger my-2">
                <hr>
                <div v-if="siniestralidadBlock === 1">
                    Bloqueado por siniestralidad, espere la aprobación de soporte
                </div>
                <div v-if="siniestralidadBlock === 2">
                    El cliente posee siniestralidad, la emisión se aprobó mediante soporte
                </div>
                <hr>
            </div>
            <CCardBody v-if="((isPublic || !isPublic && !isCoti()) && producto.imagenData !== '')">
                <div class="text-center">
                    <img v-if="typeof producto.extraData !== 'undefined'" :src="producto.imagenData || ''" style="max-width: 150px;"/>
                </div>
                <div class="mt-5" v-if="producto.descripcion !== ''" v-html="producto.descripcion"></div>
            </CCardBody>
            <CCardFooter class="p-4 text-center" v-if="!isCoti()">
                <div v-if="!isPublic" class="text-center mb-3">
                    <label>Selecciona tu código de agente</label>
                    <select class="form-select my-2" style="max-width: 200px; margin: auto" v-model="codigoAgenteSelected">
                        <option v-for="item in codigosAgente" :value="item">{{item}}</option>
                    </select>
                    <small class="text-muted">Con este código de agente se iniciará la cotización</small>
                </div>
                <CButton color="primary" @click="iniciarCotizacion">
                    <i class="fas fa-check-circle me-2"></i> Cotizar
                </CButton>
            </CCardFooter>
        </CCard>
        <CCard class="mt-4" v-if="!showCotizacion">
            <template v-if="isCoti()">
                <CCardHeader>
                    <h5 class="text-center">{{showCotizacionDesc}}</h5>
                </CCardHeader>
                <div class="mt-4 text-center" v-if="isPublic">
                    <button @click="goToProduct" class="btn btn-primary">Volver al inicio</button>
                </div>
                <CCardBody class="text-center py-5" v-if="estadoCotizacion === 'expirada' || estadoCotizacion === 'cancelada'">
                    <button @click="revivirCotizacion" class="btn btn-primary"><i class="fas fa-check-circle me-2"></i>Revivir tarea</button>
                </CCardBody>
            </template>
        </CCard>
        <template v-else>
            <div class="m-auto">
                <CCard :class="`mt-3 custom-form ${isPublic ? '':'col-12'} col-12`">
                    <CCardHeader>
                        <h5 class="custom-formTitle" v-if="flujoActivo.label">{{ flujoActivo.nodoName }}</h5>
                    </CCardHeader>
                    <CCardBody class="custom-formContainer">
                        <div v-if="flujoActivo.gVh === 'a'" class="mb-4">
                            <h6 class="mb-2">Selector de vehículo</h6>
                            <select ref="vehicleSelector" :value="vehiculoIdAgrupadorNodo" class="form-control" @change="changeSelectorVehiculo($event)">
                                <option v-for="(vehiculo, vehiculoID) in vehiculosCot" :value="vehiculoID">
                                    {{vehiculo['v']}}
                                </option>
                            </select>
                        </div>
                        <div v-if="flujoActivo.typeObject !== 'start'
                                    && flujoActivo.typeObject !== 'input'
                                    && flujoActivo.typeObject !== 'output'
                                    && flujoActivo.typeObject !== 'review'
                                    && flujoActivo.typeObject !== 'vehiculo'
                                    && flujoActivo.typeObject !== 'vehiculo_comp'
                                    && flujoActivo.typeObject !== 'vehiculo_pago'
                                    && flujoActivo.typeObject !== 'pagador'" class="text-muted text-center">
                            El formulario se encuentra en una etapa sin visualización, podrá continuarse cuando cambie de etapa.
                        </div>
                        <div v-else>
                            <template v-if="flujoActivo.typeObject === 'vehiculo'">
                                <div v-if="vehiculos.length === 0" class="text-center text-danger">
                                    Aún no cuenta con vehículos en tarea
                                </div>
                                <div v-for="(vehi, keyVehi) in vehiculos" class="mb-4">
                                    <div>
                                        <h5 class="text-primary">Vehículo No.{{parseInt(vehi.numberKey)}}</h5>
                                        <div v-for="(cotizacion, cotiKey) in vehi.cotizaciones">
                                            {{cotizacion.producto}}
                                        </div>
                                    </div>
                                    <hr>
                                    <!--<div>
                                        <div v-if="typeof descuentoAdicional[vehi.vehiculoId] !== 'undefined' && parseInt(descuentoAdicional[vehi.vehiculoId].descAdi) === 1" class="mb-3">
                                            <h6 class="mb-2">Descuento adicional</h6>
                                            <div>
                                                {{descuentoAdicional[vehi.vehiculoId].comm || 'Sin comentario'}}
                                            </div>
                                        </div>
                                    </div>-->
                                    <h6 class="mb-2">Datos de vehículo</h6>
                                    <div class="row">
                                        <div class="col-12 col-sm-4 mb-2">
                                            <label>Tipo vehículo</label>
                                            <multiselect
                                                :options="vehi.filter.tv"
                                                :searchable="true"
                                                v-model="vehi.tipoId"
                                                @select="getVehiculoCatalog(keyVehi, vehi.numberKey); saveVehiclesOnBlur(vehi.vehiculoId, vehi.numberKey)"/>
                                        </div>
                                        <div class="col-12 col-sm-4 mb-2">
                                            <label>¿Desea validar el vehículo?</label>
                                            <div>
                                                <input class="form-check-input me-2" v-model="vehi.validarVeh" value="1" type="radio" @change="saveVehiclesOnBlur(vehi.vehiculoId, vehi.numberKey)">
                                                <label class="form-check-label me-2">Si</label>
                                            </div>
                                            <div>
                                                <input class="form-check-input me-2" v-model="vehi.validarVeh" value="0" type="radio" @change="saveVehiclesOnBlur(vehi.vehiculoId, vehi.numberKey)">
                                                <label class="form-check-label me-2">No</label>
                                            </div>
                                        </div>
                                        <div class="col-12 col-sm-4 mb-2" v-if="parseInt(vehi.validarVeh) === 1">
                                            <label>Placa</label>
                                            <div class="input-group mb-3">
                                                <!--<select class="form-select">
                                                    <option value="P">P</option>
                                                    <option value="M">M</option>
                                                    <option value="C">C</option>
                                                    <option value="O">O</option>
                                                    <option value="CD">CD</option>
                                                </select>-->
                                                <input class="form-control" v-model="vehi.placa" @input="(val) => (vehi.placa = vehi.placa.toUpperCase())" @change="saveVehiclesOnBlur(vehi.vehiculoId, vehi.numberKey)" v-maska data-maska="ZL-NNN@@@" data-maska-tokens="{ 'Z': { 'pattern': '[PMCODpmcod]' }, 'N': { 'pattern': '[0-9]'}, 'L': { 'pattern': '[E]', 'optional': 'true'}}">
                                            </div>
                                        </div>
                                        <div class="col-12 col-sm-4 mb-2" v-if="parseInt(vehi.validarVeh) === 1">
                                            <label>No. Chasis*</label>
                                            <input class="form-control" v-model="vehi.noChasis" @change="saveVehiclesOnBlur(vehi.vehiculoId, vehi.numberKey)" maxlength="17" minlength="17">
                                        </div>
                                        <div class="col-12 col-sm-4 mb-2" v-if="parseInt(vehi.validarVeh) === 1">
                                            <label>No. Motor*</label>
                                            <input class="form-control" v-model="vehi.noMotor" @change="saveVehiclesOnBlur(vehi.vehiculoId, vehi.numberKey)" maxlength="20">
                                        </div>
                                        <div class="col-12 col-sm-4 mb-2">
                                            <label>No. pasajeros*</label>
                                            <input class="form-control" v-model="vehi.noPasajeros" @change="saveVehiclesOnBlur(vehi.vehiculoId, vehi.numberKey)" maxlength="20" v-maska data-maska="##">
                                        </div>
                                    </div>
                                    <h6 class="mt-3 mb-2">Marca y seguro</h6>
                                    <div class="row mb-3">
                                        <div class="col-12 col-sm-4 mb-2">
                                            <label>Modelo</label>
                                            <input class="form-control" v-model="vehi.modelo" @change="saveVehiclesOnBlur(vehi.vehiculoId, vehi.numberKey, true)" v-maska data-maska="####">
                                        </div>
                                        <div class="col-12 col-sm-4 mb-2" v-if="vehi.modelo && vehi.modelo !== ''">
                                            <label>Marca</label>
                                             <multiselect
                                                 :options="vehi.filter.m"
                                                 :searchable="true"
                                                 v-model="vehi.marcaId"
                                                 @select="getVehiculoCatalog(keyVehi, vehi.numberKey); saveVehiclesOnBlur(vehi.vehiculoId, vehi.numberKey, true)"/>
                                        </div>
                                        <div class="col-12 col-sm-4 mb-2" v-if="vehi.errorD !== 'no_aseg' && vehi.modelo && vehi.modelo !== ''">
                                            <label>Línea</label>
                                            <multiselect
                                                :options="vehi.filter.l"
                                                :searchable="true"
                                                v-model="vehi.lineaId"
                                                @select="getVehiculoCatalog(keyVehi, vehi.numberKey); saveVehiclesOnBlur(vehi.vehiculoId, vehi.numberKey, true)"/>
                                        </div>
                                        <div class="col-12 mb-2">
                                            <h6 class="mt-3 mb-2">Otros</h6>
                                            <div class="mb-2">
                                                <CFormSwitch label="El vehículo es nuevo y posee factura" v-model="vehi.vehiculoNuevo" @change="valorPromedioAlert(vehi.vehiculoId, vehi.numberKey, true); saveVehiclesOnBlur(vehi.vehiculoId, vehi.numberKey, true)"/>
                                            </div>
                                            <div class="mb-2" v-if="vehi.altoRies">
                                                <label>Dispositivo de rastreo</label>
                                                <select class="form-control" v-model="vehi.altoRiesgoDisp" @change="saveVehiclesOnBlur(vehi.vehiculoId, vehi.numberKey, true)">
                                                    <option value="1">Dispositivo pagado por el cliente</option>
                                                    <option value="2">Sin dispositivo instalado</option>
                                                    <option value="3">Sin dispositivo, 10% prima neta</option>
                                                    <option value="4">Sin dispositivo, 15% prima neta</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-12 col-sm-4 mb-2" v-if="vehi.errorD !== 'no_aseg'">
                                            <label>Valor promedio</label>
                                            <br>
                                            <input type="hidden" class="form-control" v-model="vehi.valorPromDef" @change="saveVehiclesOnBlur(vehi.vehiculoId, vehi.numberKey)"/>
                                            <input type="text" class="form-control" v-model="vehi.valorProm" @change="saveVehiclesOnBlur(vehi.vehiculoId, vehi.numberKey);" v-maska:[maskaMoneyQ] data-maska="0.99" data-maska-tokens="0:\d:multiple|9:\d:optional" readonly/>
                                        </div>
                                    </div>
                                    <div v-if="vehi.error !== ''" class="text-danger text-center">
                                        {{vehi.error}}
                                    </div>
                                    <div v-if="vehi.error === '' && vehi.errorD !== 'no_aseg_l'">
                                        <h6 class="mt-3 mb-2 text-primary">Cotizaciones</h6>
                                        <div  v-for="(cotizacion, cotiKey) in vehi.cotizaciones" class="cotizacionItem">
                                            <h6><span class="me-3">Tarea No.{{cotiKey + 1}} ({{cotizacion.cotId}})</span> <i class="fas fa-trash text-danger me-3" @click="deleteCotizacion(vehi.vehiculoId, cotiKey, cotizacion.cotId);"></i></h6>
                                            <div class="row">
                                                <div class="col-12 col-sm-5">
                                                    <div>
                                                        <label>Seguro</label>
                                                        <multiselect
                                                            :options="vehi.filter.p"
                                                            :searchable="true"
                                                            v-model="cotizacion.productoId"
                                                            ref="multiselect"
                                                            @select="if(verifyAniosMax(keyVehi, cotiKey)){getVehiculoCatalog(vehi.vehiculoId, vehi.numberKey); saveVehiclesOnBlur(vehi.vehiculoId, vehi.numberKey, false, false, true);valorPromedioAlert(vehi.vehiculoId, vehi.numberKey, true)}"/>
                                                    </div>
                                                    <div class="mt-3" v-if="vehi.errorD !== 'no_aseg'" v-show="parseInt(cotizacion.rC) === 0">
                                                        <label>Suma asegurada</label>
                                                        <br>
                                                        <input type="text" class="form-control" v-model="cotizacion.sumAseg" @change="saveVehiclesOnBlur(vehi.vehiculoId, vehi.numberKey, false, true); valorPromedioAlert(vehi.vehiculoId, vehi.numberKey, true)" v-maska:[maskaMoneyQ] data-maska="0.99" data-maska-tokens="0:\d:multiple|9:\d:optional"/>
                                                        <div v-if="typeof allFieldsData['COTIZACION_VALOR_PROMEDIO_MSG'] !== 'undefined'">
                                                            <small class="text-muted">{{allFieldsData['COTIZACION_VALOR_PROMEDIO_MSG'].valor}}</small>
                                                        </div>
                                                        <div class="mt-1" v-if="vehi.vehiculoNuevo || cotizacion.valorGarantizado">
                                                            <small class="text-success">{{cotizacion.valorPromAlertValorGarantizado}}</small>
                                                        </div>
                                                        <div v-else>
                                                            <div class="mt-1">
                                                                <small class="text-danger">{{cotizacion.valorPromAlert}}</small>
                                                            </div>
                                                            <div class="mt-1">
                                                                <small class="text-danger">{{cotizacion.valorPromAlert2}}</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="mt-3">
                                                        <div class="row">
                                                            <div class="col-12 col-sm-6">
                                                                <label>Frecuencia de pago</label>
                                                            </div>
                                                            <div class="col-12 col-sm-6">
                                                                <label>Número de pagos</label>
                                                            </div>
                                                        </div>
                                                        <div class="row mt-3" v-for="frecu in cotizacion.frecuenciaPagos">
                                                            <div class="col-12 col-sm-6">
                                                                <multiselect
                                                                    :options="Object.values(frecuenciaPagosCat)"
                                                                    :searchable="true"
                                                                    v-model="frecu.f"
                                                                    value-prop="id"
                                                                    label="descripcion"
                                                                    placeholder="Selecciona una opción"
                                                                    @select="(value) => {

                                                                        frecu.p = [];
                                                                        let already = false;
                                                                        Object.keys(cotizacion.frecuenciaPagos).map(function (a){

                                                                            if (value === cotizacion.frecuenciaPagos[a].f) {
                                                                                if (!already) {
                                                                                    already = true;
                                                                                }
                                                                                else {
                                                                                    frecu.f = '';
                                                                                    Toastify({
                                                                                        text: 'No puede seleccionar dos veces la misma forma de pago',
                                                                                        duration: 6000,
                                                                                        close: true,
                                                                                        gravity: 'top',
                                                                                            position: 'center',
                                                                                            style: {
                                                                                            background: 'success',
                                                                                            }
                                                                                        }).showToast();
                                                                                }
                                                                            }
                                                                        })

                                                                        if (frecu.f !== '' && typeof frecuenciaPagosCat[frecu.f].numeroPagos[0] !== 'undefined') {
                                                                            if (frecuenciaPagosCat[frecu.f].numeroPagos.length === 1) {
                                                                                frecu.p.push(frecuenciaPagosCat[frecu.f].numeroPagos[0])
                                                                            }
                                                                            saveVehiclesOnBlur(vehi.vehiculoId, vehi.numberKey);
                                                                        }

                                                                    }"
                                                                    noResultsText="No existen más opciones"
                                                                />
                                                            </div>
                                                            <div class="col-12 col-sm-6">
                                                                <multiselect
                                                                    :options="!!frecuenciaPagosCat[frecu.f]? frecuenciaPagosCat[frecu.f].numeroPagos : []"
                                                                    :searchable="true"
                                                                    v-model="frecu.p"
                                                                    :mode="'tags'"
                                                                    placeholder="Selecciona una opción"
                                                                    @select="saveVehiclesOnBlur(vehi.vehiculoId, vehi.numberKey);"
                                                                    noResultsText="No existen más opciones"
                                                                />
                                                            </div>
                                                        </div>
                                                        <div class="row mt-3">
                                                            <div class="col-12 col-sm-6">
                                                                <button class="btn btn-success btn-sm me-1" @click="cotizacion.frecuenciaPagos.push(JSON.parse(JSON.stringify(newFrecuenciaPago)))"><i class="fas fa-plus me-2"></i></button>
                                                                <button class="btn btn-danger btn-sm me-1" @click="cotizacion.frecuenciaPagos.pop(); saveVehiclesOnBlur(vehi.vehiculoId, vehi.numberKey);"><i class="fas fa-minus me-2"></i></button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="mt-3" v-show="parseInt(cotizacion.pD) === 1">
                                                       <!-- <div class="mb-2">
                                                            <label>Descuentos disponibles</label>
                                                            <ul class="list-group">
                                                                <li class="list-group-item" v-for="desc in cotizacion.descuentos">
                                                                    {{desc.nombre}}
                                                                </li>
                                                            </ul>
                                                        </div>
                                                        <div class="mb-2">
                                                            <label>Descuentos disponibles</label>
                                                            <select class="form-control" v-model="cotizacion.descuentoSelect" @change="selectDiscount($event, keyVehi, cotiKey)">
                                                                <option :value="''"> -- Seleccione un descuento -- </option>
                                                                <option v-for="(desc, keydesc) in descuentos" :value="desc.id">
                                                                    {{ desc.nombre }}
                                                                    <strong class="fw-bold">(
                                                                        {{desc.valormin}} - {{String(desc.valormax)}}) {{desc.tipo === 'q'? 'Q' :'%'}}
                                                                    </strong>
                                                                </option>
                                                            </select>
                                                        </div> -->
                                                       <div v-if="!!descuentos && descuentos.length > 0">
                                                            <label>Descuento (%): </label>
                                                            <input
                                                                type="number"
                                                                class="form-control"
                                                                v-model="cotizacion.descuento"
                                                                step="0.01"
                                                                @change="verifyDiscounts($event, keyVehi, cotiKey, vehi.vehiculoId, vehi.numberKey)"
                                                            />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-sm-7">
                                                    <label>Coberturas</label>
                                                    <div>
                                                        <ul class="list-group">
                                                            <template v-for="(cobertura, keyCober) in cotizacion.coberturas">
                                                                <li class="list-group-item" v-if="(producto.modoPruebas || cobertura.condicionalResult)" v-show="((producto.modoPruebas  && (typeof authInfo.m['admin/show-test-mode'] !== 'undefined' && authInfo.m['admin/show-test-mode'])) || cobertura.condicionalVResult) && (cobertura.tipoVisibilidad !== 'nada' || !cobertura.tipoVisibilidad)">
                                                                    <div class="row">
                                                                        <div class="col-12 col-sm-6">
                                                                            <CFormSwitch :label="cobertura.label" v-model="cobertura.selected" :defaultChecked="parseInt(cobertura.obligatorio) === 1 && cobertura.condicionalResult" :disabled="parseInt(cobertura.obligatorio) === 1" @click="saveVehiclesOnBlur(vehi.vehiculoId, vehi.numberKey);"/>
                                                                        </div>
                                                                        <div v-if="!cobertura.condicionalResult" style="background: #facdcd; width: 100%; padding: 0.5em; text-align: center; margin-bottom: 0.5em">
                                                                            <b>No podrá seleccionarse</b>
                                                                        </div>
                                                                        <div v-if="!cobertura.condicionalVResult" style="background: #facdcd; width: 100%; padding: 0.5em; text-align: center; margin-bottom: 0.5em">
                                                                            <b>No se mostrará</b>
                                                                        </div>
                                                                        <div class="col-12 col-sm-6" v-show="cobertura.selected && (cobertura.tipoVisibilidad === 'monto')">
                                                                            <div v-if="!!cobertura.rangoMonto">
                                                                                <label>Monto de cobertura</label>
                                                                                <input v-model="cobertura.monto" class="form-control" @blur="verificarValorRangoMonto(keyVehi, cotiKey, keyCober);"/>
                                                                            </div>
                                                                            <div v-else-if="!cobertura.llevaValorVehiculo" >
                                                                                <div v-if="cobertura.montoV && cobertura.montoV.length > 0">
                                                                                    <label>Monto de cobertura</label>
                                                                                    <select v-model="cobertura.monto" class="form-select"  @change="saveVehiclesOnBlur(vehi.vehiculoId, vehi.numberKey);">
                                                                                        <option :value="valueTmp.val" v-for="valueTmp in cobertura.montoV">
                                                                                            {{setCurrency(valueTmp.show)}}
                                                                                        </option>
                                                                                    </select>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-12" v-if="producto.modoPruebas && (typeof authInfo.m['admin/show-test-mode'] !== 'undefined' && authInfo.m['admin/show-test-mode']) && (typeof authInfo.m['admin/show-test-mode'] !== 'undefined' && authInfo.m['admin/show-test-mode'])">
                                                                            <div class="text-danger mb-2">
                                                                                Modo pruebas activo:
                                                                            </div>
                                                                            <div v-if="cobertura.monto !== ''">
                                                                                <b>Monto de cobertura:</b>
                                                                                {{setCurrency(cobertura.monto)}}
                                                                            </div>
                                                                            <div v-if="cobertura.condicional && cobertura.condicional !== ''" class="mt-2">
                                                                                <span class="fw-bold text-secondary">Condicional de activación</span>
                                                                                <div>
                                                                                    {{cobertura.condicionalPreview}}
                                                                                </div>
                                                                                <b>Resultado:</b>
                                                                                <div>
                                                                                    {{cobertura.condicionalResult}}
                                                                                </div>
                                                                            </div>
                                                                            <div v-if="cobertura.condicionalV && cobertura.condicionalV !== ''" class="mt-2">
                                                                                <span class="fw-bold text-secondary">Condicional de visibilidad</span>
                                                                                <div>
                                                                                    {{cobertura.condicionalVPreview}}
                                                                                </div>
                                                                                <b>Resultado:</b>
                                                                                <div>
                                                                                    {{cobertura.condicionalVResult}}
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </li>
                                                            </template>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-1">
                                            <button class="btn btn-success btn-sm" @click="addCotizacion(vehi.vehiculoId)"><i class="fas fa-plus me-2"></i>Agregar cotización</button>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button class="btn btn-danger" @click="eliminarVehiculo(vehi.vehiculoId, keyVehi)"><i class="fas fa-trash me-2"></i>Eliminar vehículo</button>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <hr>
                                    <button class="btn btn-primary" @click="agregarVehiculo">Agregar vehículo</button>
                                </div>
                            </template>
                            <template v-if="flujoActivo.typeObject === 'vehiculo_comp'">
                                <div class="mb-4" v-for="(vehiculo, vehiculoId) in vehiculosCot">
                                    <div class="mt-1 mb-2 text-end">
                                        <button class="btn btn-primary" @click="showCompare = true; formatDataCompareTable(vehiculoId)">Comparar</button>
                                    </div>
                                    <h4>{{vehiculo['v']}}</h4>
                                    <hr>
                                    <div v-for="(cotizacion, cotizacionId) in vehiculo['c']">
                                        <div class="row">
                                            <div class="col-12 col-sm-4">
                                                <h5 class="text-primary">
                                                    Cotización No.{{cotizacionId}} - {{cotizacion.producto}}
                                                </h5>
                                                <!--<div v-if="typeof descuentoAdicional[vehiculoId] !== 'undefined'" class="mt-3">
                                                    <label>Solicitar descuento adicional</label>
                                                    <div>
                                                        <input class="form-check-input me-2" v-model="descuentoAdicional[vehiculoId].descAdi" value="1" type="radio" @change="saveVehiclesOnBlur(vehiculoId, vehiculo['n'])">
                                                        <label class="form-check-label me-2">Si</label>
                                                    </div>
                                                    <div>
                                                        <input class="form-check-input me-2" v-model="descuentoAdicional[vehiculoId].descAdi" value="0" type="radio" @change="saveVehiclesOnBlur(vehiculoId, vehiculo['n'])">
                                                        <label class="form-check-label me-2">No</label>
                                                    </div>
                                                    <div>
                                                        <label>Comentario</label>
                                                        <input class="form-control" v-model="descuentoAdicional[vehiculoId].comm" type="text" @change="saveVehiclesOnBlur(vehiculoId, vehiculo['n'])">
                                                    </div>
                                                </div>-->
                                                <div class="mt-4">
                                                    <div :class="{'emitirControl': cotizacion.emitirPoliza, 'emitirControlNone': !cotizacion.emitirPoliza}">
                                                        <CFormSwitch @change="setEmitirPoliza(cotizacionId, !cotizacion.emitirPoliza, vehiculoId)" v-model="cotizacion.emitirPoliza" label="Emitir estas cotizaciones" class="m-0"/>
                                                    </div>
                                                </div>
                                                <div v-if="typeof cotizacion.COTIZACION_AS400.datosIdEmpresaGC.datos03.datosCotizacionGestorComercial2.listaFrecuenciaPagos !== 'undefined'" class="mt-3">
                                                    <h6 class="text-success">Detalle de pago</h6>
                                                    <div class="mt-3" v-for="(prima, keyTmp) in cotizacion.COTIZACION_AS400.datosIdEmpresaGC.datos03.datosCotizacionGestorComercial2.listaFrecuenciaPagos.listaPagos"
                                                        v-if="!cotizacion.COTIZACION_AS400.datosIdEmpresaGC.datos03.datosCotizacionGestorComercial2.listaFrecuenciaPagos.listaPagos.listaPagosMensual"
                                                    >
                                                        <input type="checkbox"
                                                        :checked="cotizacion.idCorrelativo == prima.listaPagosMensual.idCorrelativo"
                                                        @change="cotizacion.idCorrelativo = $event.target.checked ? prima.listaPagosMensual.idCorrelativo: ''"/>
                                                        <label class="ms-2 text-primary fw-bold">{{
                                                            prima.listaPrimas.primas.numeroPagos}} pago{{
                                                            Number(prima.listaPrimas.primas.numeroPagos) > 1 ? 's' : ''
                                                              }} {{
                                                            catalogosPay.formas_pago.find(e => e.value === prima.listaPagosMensual.idFrecuencia)['label'].toLowerCase()
                                                            + (Number(prima.listaPrimas.primas.numeroPagos) > 1 ? 'es' : '')
                                                            }}</label>
                                                        <div><b>Prima total:</b> {{ setCurrency(prima.listaPagosMensual.primaTotalMensual) }}</div>
                                                    </div>
                                                    <div v-else>
                                                        <input type="checkbox"
                                                        :checked="cotizacion.idCorrelativo == cotizacion.COTIZACION_AS400.datosIdEmpresaGC.datos03.datosCotizacionGestorComercial2.listaFrecuenciaPagos.listaPagos.listaPagosMensual.idCorrelativo"
                                                        @change="cotizacion.idCorrelativo = $event.target.checked ? cotizacion.COTIZACION_AS400.datosIdEmpresaGC.datos03.datosCotizacionGestorComercial2.listaFrecuenciaPagos.listaPagos.listaPagosMensual.idCorrelativo: ''"/>
                                                        <label class="ms-2 text-primary fw-bold">{{
                                                            cotizacion.COTIZACION_AS400.datosIdEmpresaGC.datos03.datosCotizacionGestorComercial2.listaFrecuenciaPagos.listaPagos.listaPrimas.primas.numeroPagos}} pago{{
                                                            Number(cotizacion.COTIZACION_AS400.datosIdEmpresaGC.datos03.datosCotizacionGestorComercial2.listaFrecuenciaPagos.listaPagos.listaPrimas.primas.numeroPagos) > 1 ? 's' : ''
                                                              }} {{
                                                            catalogosPay.formas_pago.find(e => e.value === cotizacion.COTIZACION_AS400.datosIdEmpresaGC.datos03.datosCotizacionGestorComercial2.listaFrecuenciaPagos.listaPagos.listaPagosMensual.idFrecuencia)['label'].toLowerCase()
                                                            + (Number(cotizacion.COTIZACION_AS400.datosIdEmpresaGC.datos03.datosCotizacionGestorComercial2.listaFrecuenciaPagos.listaPagos.listaPrimas.primas.numeroPagos) > 1 ? 'es' : '')
                                                            }}</label>
                                                        <div><b>Prima total:</b>  {{ setCurrency(cotizacion.COTIZACION_AS400.datosIdEmpresaGC.datos03.datosCotizacionGestorComercial2.listaFrecuenciaPagos.listaPagos.listaPagosMensual.primaTotalMensual) }}</div>
                                                    </div>
                                                    <div class="mt-5">
                                                        <EasyDataTable :headers="cotizacion.opcionesPago['headers']" :items="cotizacion.opcionesPago['data']" hide-footer alternating >
                                                        </EasyDataTable>
                                                    </div>
                                                    <div class="mt-5" v-for="(head, keyHead) in cotizacion.desglosePago['headers']">
                                                        <EasyDataTable :headers="head" :items="cotizacion.desglosePago['data']" hide-footer alternating >
                                                            <template v-slot:item.primaPago="{ item }">
                                                                <span>{{ setCurrency(item.primaPago) }}</span>
                                                            </template>
                                                        </EasyDataTable>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-sm-8">
                                                <div class="text-center text-danger mt-3" v-if="cotizacion.msgAs !== 'SATISFACTORIO'">
                                                    {{cotizacion.msgAs}}
                                                </div>
                                                <div v-if="typeof cotizacion.COTIZACION_AS400.datosIdEmpresaGC.datos03.datosCotizacionGestorComercial2.listaCoberturas !== 'undefined'" class="mt-3">
                                                    <h6 class="text-success">Coberturas</h6>
                                                    <table class="table table-bordered">
                                                        <thead>
                                                            <tr>
                                                                <th>
                                                                    Descripción
                                                                </th>
                                                                <th>
                                                                    Monto
                                                                </th>
                                                                <th>
                                                                    %
                                                                </th>
                                                                <th>
                                                                    Deducible
                                                                </th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <!--
                                                            <template v-for="cobertura in cotizacion.COTIZACION_AS400.datosIdEmpresaGC.datos03.datosCotizacionGestorComercial2.listaCoberturas.coberturas">
                                                                <tr class="col-12 col-sm-4" v-if="parseFloat(cobertura.sumaAsegurada) > 0">
                                                                    <td>
                                                                        {{cobertura.descripcion}}
                                                                    </td>
                                                                    <td>
                                                                        {{setCurrency(cobertura.sumaAsegurada)}}
                                                                    </td>
                                                                </tr>
                                                            </template>
                                                            -->
                                                            <template v-for="grupo in cotizacion.grupoCoberturas">
                                                                <tr class="col-12 col-sm-4">
                                                                    <td colspan="4">
                                                                        <strong>{{grupo['nombre']}}: {{grupo['descripcion']}}</strong>
                                                                    </td>
                                                                </tr>
                                                                <template v-for="cobertura in grupo['lista']">
                                                                    <tr class="col-12 col-sm-4">
                                                                        <td>
                                                                            {{cobertura.descripcion}}
                                                                        </td>
                                                                        <td>
                                                                            <span v-if="cobertura.sumaAsegurada !== '.00'">
                                                                                {{setCurrency(cobertura.sumaAsegurada)}}
                                                                            </span>
                                                                        </td>
                                                                        <td>
                                                                            <span v-if="cobertura.porcentajeDeducible !== '.00'">
                                                                                {{cobertura.porcentajeDeducible}}
                                                                            </span>
                                                                        </td>
                                                                        <td>
                                                                            <span v-if="cobertura.deducible !== '.00'">
                                                                                {{setCurrency(cobertura.deducible)}} <span v-if="cobertura.deducibleMinimo === 'S'" class="small">mínimo</span>
                                                                            </span>
                                                                        </td>
                                                                    </tr>
                                                                </template>
                                                            </template>
                                                            <tr class="col-12 col-sm-4" v-if="!!cotizacion.COTIZACION_AS400.datosIdEmpresaGC.datos03.datosCotizacionGestorComercial2.observaciones">
                                                                <td colspan="4">
                                                                    {{cotizacion.COTIZACION_AS400.datosIdEmpresaGC.datos03.datosCotizacionGestorComercial2.observaciones}}
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="globalModal" v-if="showCompare">
                                    <div class="globalModalContainerExtend p-5">
                                        <div @click="showCompare = false" class="globalModalClose mt-3">
                                            <i class="fas fa-times-circle"></i></div>
                                        <div>
                                            <div>
                                                <h5 class="mt-5">Comparación</h5>
                                            </div>
                                            <div v-for="(cotizaciones, vehiculo) in dataTableCompare.c">
                                                <h3>{{vehiculo}}</h3>
                                                <table class="table">
                                                    <thead>
                                                        <tr>
                                                            <th>Cobertura</th>
                                                            <th v-for="(item) in dataTableCompare.p[vehiculo]" :colspan="3">
                                                                {{item}}
                                                            </th>
                                                        </tr>
                                                        <tr>
                                                            <th>&nbsp;</th>
                                                            <template v-for="(item) in dataTableCompare.p[vehiculo]">
                                                                <th>Suma asegurada</th>
                                                                <th>Tasa</th>
                                                                <th>Deducible</th>
                                                            </template>
                                                        </tr>
                                                    </thead>
                                                    <tbody v-for="(coberturas, seccion) in cotizaciones">
                                                        <tr>
                                                            <td :colspan="(Object.keys(dataTableCompare.p[vehiculo]).length * 3) + 1"><b>{{seccion}}</b></td>
                                                        </tr>
                                                        <tr v-for="(producto, cobertura) in coberturas">
                                                            <td>{{cobertura}}</td>
                                                            <template v-for="(cotis, pr) in producto">
                                                                <td>
                                                                    <div v-for="(cot, cotId) in cotis">
                                                                        <div v-if="cotId > 0"><small class="text-muted">Cotización #{{cotId}}</small></div>
                                                                        <div>{{cot.sumaAsegurada}}</div>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <div v-for="(cot, cotId) in cotis">
                                                                        <div v-if="parseFloat(cot.porcentajeDeducible) > 0"><small class="text-muted">Cotización #{{cotId}}</small></div>
                                                                        <div v-if="parseFloat(cot.porcentajeDeducible) > 0">{{cot.porcentajeDeducible}}</div>
                                                                        <div v-else></div>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <div v-for="(cot, cotId) in cotis">
                                                                        <div v-if="cotId > 0 && cot.deducible !== ''"><small class="text-muted">Cotización #{{cotId}}</small></div>
                                                                        <div>{{cot.deducible}}</div>
                                                                    </div>
                                                                </td>
                                                            </template>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <!--<div>
                                                {{dataTableCompare}}
                                            </div>-->
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <template v-if="flujoActivo.typeObject === 'pagador'">
                                <div class="mb-4" v-for="(vehiculo, vehiculoId) in vehiculosCot">
                                    <h4>{{vehiculo['v']}}</h4>
                                    <hr>
                                    <div v-for="(cotizacion, cotizacionId) in vehiculo['c']">
                                        <div class="row" v-if="!!cotizacion['emitirPoliza']">
                                            <div class="col-12 col-sm-4">
                                                <h5 class="text-primary">
                                                    Cotización No.{{cotizacionId}}
                                                </h5>
                                                <div v-if="typeof cotizacion.COTIZACION_AS400.datosIdEmpresaGC.datos03.datosCotizacionGestorComercial2.listaFrecuenciaPagos !== 'undefined'" class="mt-3">
                                                    <h6 class="text-success">Detalle de pago</h6>
                                                    <div class="mt-3" v-for="(prima, keyTmp) in cotizacion.COTIZACION_AS400.datosIdEmpresaGC.datos03.datosCotizacionGestorComercial2.listaFrecuenciaPagos.listaPagos"
                                                        v-if="!cotizacion.COTIZACION_AS400.datosIdEmpresaGC.datos03.datosCotizacionGestorComercial2.listaFrecuenciaPagos.listaPagos.listaPagosMensual"
                                                    >
                                                    <div v-if="cotizacion.idCorrelativo == prima.listaPagosMensual.idCorrelativo">
                                                        <div class="text-primary fw-bold">{{
                                                            prima.listaPrimas.primas.numeroPagos}} pago{{
                                                            Number(prima.listaPrimas.primas.numeroPagos) > 1 ? 's' : ''
                                                              }} {{
                                                            catalogosPay.formas_pago.find(e => e.value === prima.listaPagosMensual.idFrecuencia)['label'].toLowerCase()
                                                            + (Number(prima.listaPrimas.primas.numeroPagos) > 1 ? 'es' : '')
                                                            }}</div>
                                                        <div><b>Prima total:</b>  {{setCurrency(prima.listaPagosMensual.primaTotalMensual) }}</div>
                                                    </div>
                                                    </div>
                                                    <div v-else-if="!!cotizacion.COTIZACION_AS400.datosIdEmpresaGC.datos03.datosCotizacionGestorComercial2.listaFrecuenciaPagos.listaPagos.listaPagosMensual">
                                                        <div class="text-primary fw-bold">{{
                                                            cotizacion.COTIZACION_AS400.datosIdEmpresaGC.datos03.datosCotizacionGestorComercial2.listaFrecuenciaPagos.listaPagos.listaPrimas.primas.numeroPagos}} pago{{
                                                            Number(cotizacion.COTIZACION_AS400.datosIdEmpresaGC.datos03.datosCotizacionGestorComercial2.listaFrecuenciaPagos.listaPagos.listaPrimas.primas.numeroPagos) > 1 ? 's' : ''
                                                              }} {{
                                                            catalogosPay.formas_pago.find(e => e.value === cotizacion.COTIZACION_AS400.datosIdEmpresaGC.datos03.datosCotizacionGestorComercial2.listaFrecuenciaPagos.listaPagos.listaPagosMensual.idFrecuencia)['label'].toLowerCase()
                                                            + (Number(cotizacion.COTIZACION_AS400.datosIdEmpresaGC.datos03.datosCotizacionGestorComercial2.listaFrecuenciaPagos.listaPagos.listaPrimas.primas.numeroPagos) > 1 ? 'es' : '')
                                                            }}</div>
                                                        <div><b>Prima total:</b> {{ setCurrency(cotizacion.COTIZACION_AS400.datosIdEmpresaGC.datos03.datosCotizacionGestorComercial2.listaFrecuenciaPagos.listaPagos.listaPagosMensual.primaTotalMensual) }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-sm-8">
                                                <div>
                                                    <div class="mb-3">
                                                        <label>Medio de cobro:</label>
                                                        <select v-model="vehiculosCot[vehiculoId]['c'][cotizacionId].medioCobro" class="form-select">
                                                            <option v-for="cat in catalogosPay['medio_cobro']" :value="cat.value">{{cat.label}}</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3" v-if="vehiculosCot[vehiculoId]['c'][cotizacionId].medioCobro === '03'">
                                                        <label>Nombre de tarjeta:</label>
                                                        <input v-model="vehiculosCot[vehiculoId]['c'][cotizacionId].nombreTarjeta" class="form-control"/>
                                                    </div>
                                                    <div class="mb-3" v-if="vehiculosCot[vehiculoId]['c'][cotizacionId].medioCobro === '03'">
                                                        <label>Numero de Tarjeta:</label>
                                                        <input v-model="vehiculosCot[vehiculoId]['c'][cotizacionId].numCuentaTarjeta" placeholder="XXXX-XXXX-XXXX-XXXX" class="form-control" v-maska data-maska="####-####-####-####"/>
                                                    </div>
                                                    <div class="mb-3" v-if="vehiculosCot[vehiculoId]['c'][cotizacionId].medioCobro === '03' || vehiculosCot[vehiculoId]['c'][cotizacionId].medioCobro === '02'">
                                                        <label>Tipo:</label>
                                                        <select v-model="vehiculosCot[vehiculoId]['c'][cotizacionId].tipoCuentaTarjeta" class="form-select">
                                                            <option v-for="cat in catalogosPay['tipo_cuenta_tarjeta'].filter(e =>
                                                                (vehiculosCot[vehiculoId]['c'][cotizacionId].medioCobro === '03' && ['C', 'D', 'V'].includes(e.value)) ||
                                                                (vehiculosCot[vehiculoId]['c'][cotizacionId].medioCobro === '02' && ['A', 'M'].includes(e.value))
                                                            )" :value="cat.value">{{cat.label}}</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3" v-if="vehiculosCot[vehiculoId]['c'][cotizacionId].medioCobro === '02'">
                                                        <label>Numero de cuenta:</label>
                                                        <input v-model="vehiculosCot[vehiculoId]['c'][cotizacionId].numCuentaTarjeta" class="form-control" v-if="vehiculosCot[vehiculoId]['c'][cotizacionId].tipoCuentaTarjeta === 'A'" maxlength="10" placeholder="No. cuenta de ahorro"/>
                                                        <input v-model="vehiculosCot[vehiculoId]['c'][cotizacionId].numCuentaTarjeta" class="form-control" v-else placeholder="No. cuenta"/>
                                                    </div>
                                                    <div class="mb-3" v-if="vehiculosCot[vehiculoId]['c'][cotizacionId].medioCobro === '03' && !!flujoActivo.addcvv">
                                                        <label>CVV:</label>
                                                        <input v-model="vehiculosCot[vehiculoId]['c'][cotizacionId].codCuentaTarjeta" class="form-control" placeholder="***" type="password"  autocomplete="new-password"/>
                                                    </div>
                                                    <!--<div class="mb-3" v-if="!!vehiculosCot[vehiculoId]['c'][cotizacionId].medioCobro">
                                                        <label>Codigo cuenta tarjeta:</label>
                                                        <input v-model="vehiculosCot[vehiculoId]['c'][cotizacionId].codCuentaTarjeta" class="form-control" placeholder="***"/>
                                                    </div>-->
                                                    <div class="mb-3" v-if="vehiculosCot[vehiculoId]['c'][cotizacionId].medioCobro === '03'">
                                                        <label>Clase tarjeta:</label>
                                                        <select v-model="vehiculosCot[vehiculoId]['c'][cotizacionId].claseTarjeta" class="form-select">
                                                            <option v-for="cat in catalogosPay['clase_tarjeta']" :value="cat.value">{{cat.label}}</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3" v-if="vehiculosCot[vehiculoId]['c'][cotizacionId].medioCobro === '03'">
                                                        <label>Fecha de vencimiento:</label>
                                                        <input v-model="vehiculosCot[vehiculoId]['c'][cotizacionId].venciTarjeta" class="form-control" placeholder="MM/YY" v-maska data-maska="##/##"/>
                                                    </div>
                                                    <div class="mb-3" v-if="vehiculosCot[vehiculoId]['c'][cotizacionId].medioCobro === '02'">
                                                        <label>Banco emisor:</label>
                                                        <select v-model="vehiculosCot[vehiculoId]['c'][cotizacionId].bancoEmisor" class="form-select">
                                                            <option v-for="cat in catalogosPay['banco_emisor']" :value="cat.value">{{cat.label}}</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3" v-if="vehiculosCot[vehiculoId]['c'][cotizacionId].medioCobro === '02'">
                                                        <label>Tipo cuenta bancaria:</label>
                                                        <select v-model="vehiculosCot[vehiculoId]['c'][cotizacionId].tipoCuentaBancarias" class="form-select">
                                                            <option v-for="cat in catalogosPay['tipo_cuenta_bancaria']" :value="cat.value">{{cat.label}}</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3" v-if="vehiculosCot[vehiculoId]['c'][cotizacionId].medioCobro === '03' &&  vehiculosCot[vehiculoId]['c'][cotizacionId].tipoCuentaTarjeta === 'V'">
                                                        <label>Numero de cuotas:</label>
                                                        <select v-model="vehiculosCot[vehiculoId]['c'][cotizacionId].numeroCuotas" class="form-select">
                                                            <option v-for="cat in [3, 6, 10, 12 ]" :value="cat">{{cat}}</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div v-else>
                                            Este vehículo no se seleccionó para emisión, no se realizará cobro.
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <template v-else>
                                <div v-if="flujoActivo.gVh === 'a' && vehiculoIdAgrupadorNodo === 0" class="text-danger text-center">
                                    Selecciona un vehículo
                                </div>
                                <div v-if="(flujoActivo.gVh === 'a' && vehiculoIdAgrupadorNodo !== 0) || flujoActivo.gVh !== 'a'">
                                    <div class="row p-0 m-0">
                                        <div class="col-12 p-0">
                                            <div class="row p-0 m-0">
                                                <div v-if="flujoActivo.formulario.secciones.length > 0" :class="'col-1 col-sm-3'">
                                                    <ul :class="'progress-indicator nocenter stacked'">
                                                        <template v-for="(etapa, index) in flujoActivo.formulario.secciones" :key="index">
                                                            <li :class="(etapa.completada)?'completed':((currentTabIndex === index)?'active':'')" @click="cambiarSeccion(index)" v-if="etapa.show">
                                                                <div class="bubble">
                                                                    {{index+1}}
                                                                </div>
                                                                <h6
                                                                    class="stacked-text d-none d-sm-block">
                                                                    {{ etapa.nombre }}
                                                                </h6>
                                                            </li>
                                                        </template>
                                                    </ul>
                                                </div>
                                                <div :class="'col-11 col-sm-9 formContainer'" v-if="typeof (flujoActivo.formulario.secciones[currentTabIndex]) !=='undefined' ">
                                                    <div class="d-block d-sm-none mb-3">
                                                        <h4 class="text-primary fw-bold">
                                                            {{ flujoActivo.formulario.secciones[currentTabIndex].nombre }}
                                                        </h4>
                                                    </div>
                                                    <div class="mb-3" v-if="flujoActivo.formulario.secciones[currentTabIndex].instrucciones">
                                                        <div class="bg-light rounded p-3 mb-4">
                                                            <h6 class="fw-bold">Instrucciones:</h6>
                                                            {{ flujoActivo.formulario.secciones[currentTabIndex].instrucciones }}
                                                        </div>
                                                    </div>
                                                    <div class="mb-3" v-if="flujoActivo.ocr && flujoActivo.ocr === 'a'">
                                                        <div class="rounded p-3 mb-4" style="background: #fafafa">
                                                            <h6 class="fw-bold">{{ flujoActivo.ocrDesc }}</h6>
                                                            <file-pond type="file"
                                                                       class="filepond"
                                                                       name="ocrProcess"
                                                                       label-idle="Clic para seleccionar o arrastra tus archivos acá"
                                                                       credits="false"
                                                                       data-allow-reorder="true"
                                                                       data-max-file-size="150MB"
                                                                       :server="{
                                                                            process: (fieldName, file, metadata, load, error, progress, abort) => {
                                                                                handleUpload(file, flujoActivo.ocrVC, load, error, progress, true);
                                                                            },
                                                                        }"
                                                                       ref="filepondInputOCR">
                                                            </file-pond>
                                                        </div>
                                                    </div>
                                                    <div v-if="flujoActivo.typeObject === 'review'">
                                                        <div class="bg-light rounded p-3 mb-1">
                                                            <h6 class="fw-bold">Aprobación de datos</h6>
                                                            Verifique todos los datos, si existe algún dato incorrecto puede rechazarlo.
                                                        </div>
                                                        <div class="text-end mb-3">
                                                            <button class="btn btn-sm btn-danger me-2" @click="rechazarTodo(true)">Rechazar todo</button>
                                                            <button class="btn btn-sm btn-success" @click="rechazarTodo(false)">Aprobar todo</button>
                                                        </div>
                                                    </div>
                                                    <CForm class="row">
                                                        <template v-if="flujoActivo.formulario.secciones[currentTabIndex].show">
                                                            <template v-for="(input, keyInput) in flujoActivo.formulario.secciones[currentTabIndex].campos" :key="'inputKey'+ input.id">
                                                                <template v-if="typeof camposCumplidores[input.id] !== 'undefined' && camposCumplidores[input.id] && !input.group">
                                                                    <div v-if="typeof(input) !=='undefined' && input.activo" :class="'fieldContainer col-' + ((input.layoutSizeMobile) ? input.layoutSizeMobile : '12') + ' col-sm-' + ((input.layoutSizePc) ? input.layoutSizePc : '4') + ' mb-3' + (!input.visible ? ' d-none' : '')">
                                                                        <div v-if="input.tipoCampo === 'title'">
                                                                            <h5 class="text-primary fw-bold mb-3 mt-4">{{ input.nombre }}</h5>
                                                                        </div>
                                                                        <div v-if="input.tipoCampo === 'subtitle'">
                                                                            <h6 class="text-primary fw-bold mb-3 mt-4">{{ input.nombre }}</h6>
                                                                        </div>
                                                                        <div v-if="input.tipoCampo === 'txtlabel'">
                                                                            <div v-html="input.valor"></div>
                                                                        </div>
                                                                        <div v-if="input.tipoCampo === 'add'">
                                                                            <div class="btn btn-sm btn-primary mb-2" @click="addFieldsGroup(input, keyInput, currentTabIndex)">Agregar {{ input.nombre }} <i class="fa-solid fa-plus"></i></div>
                                                                            <i v-if="input.ttp && input.ttp !== ''" class="fas fa-question-circle ms-2" v-tooltip="input.ttp"></i>
                                                                            <br/>
                                                                            <div class="btn btn-sm btn-danger" @click="subFieldsGroup(input, keyInput, currentTabIndex)">Quitar {{ input.nombre }} <i class="fa-solid fa-minus"></i></div>
                                                                            <i v-if="input.ttp && input.ttp !== ''" class="fas fa-question-circle ms-2" v-tooltip="input.ttp"></i>
                                                                            <br/>
                                                                            <small class="text-muted">{{ input.desc }}</small>
                                                                        </div>
                                                                        <div v-if="(input.tipoCampo === null || input.tipoCampo === 'text' || input.tipoCampo === 'encrypt') && (input.mascara !== '' || input.mascara !== null)">
                                                                            <label :for="input.id"><span v-if="input.requerido">*</span>{{ input.nombre }}</label>
                                                                            <i v-if="input.ttp && input.ttp !== ''" class="fas fa-question-circle ms-2" v-tooltip="input.ttp"></i>
                                                                            <input type="text" :class="{'form-control': !input.requeridoError, 'form-control requiredInput': input.requeridoError}"
                                                                                   :readonly="input.readonly || input.valorCalculado"
                                                                                   :label="input.nombre"
                                                                                   :aria-label="input.nombre"
                                                                                   v-model="input.valor"
                                                                                   :id="input.id"
                                                                                   v-maska
                                                                                   :data-maska="input.mascara"
                                                                                   :data-maska-tokens="input.tokenMask"
                                                                                   :disabled="input.deshabilitado || flujoActivo.typeObject === 'review'"
                                                                                   :placeholder="input.ph"
                                                                                   :minlength="input.longitudMin"
                                                                                   :maxlength="input.longitudMax"
                                                                                   @change="campoCumpleCondiciones(input)"
                                                                                   @blur="saveWhenOnBlur(input)"
                                                                            >
                                                                            <small class="text-muted">{{ input.desc }}</small>
                                                                        </div>
                                                                        <div v-if="(input.tipoCampo === 'currency') && (input.mascara !== '' || input.mascara !== null)">
                                                                            <label :for="input.id"><span v-if="input.requerido">*</span>{{ input.nombre }}</label>
                                                                            <i v-if="input.ttp && input.ttp !== ''" class="fas fa-question-circle ms-2" v-tooltip="input.ttp"></i>
                                                                            <div class="input-group mb-3">
                                                                                <span class="input-group-text">{{ input.currency }}</span>
                                                                                <input type="text" :class="{'form-control': !input.requeridoError, 'form-control requiredInput': input.requeridoError}"
                                                                                       :readonly="input.readonly || input.valorCalculado"
                                                                                       :label="input.nombre"
                                                                                       :aria-label="input.nombre"
                                                                                       :value="toConvertDecimal(input.valor)"
                                                                                       :id="input.id"
                                                                                       v-maska
                                                                                       :data-maska="input.mascara"
                                                                                       :data-maska-tokens="input.tokenMask"
                                                                                       :disabled="input.deshabilitado || flujoActivo.typeObject === 'review'"
                                                                                       :placeholder="input.ph"
                                                                                       :minlength="input.longitudMin"
                                                                                       :maxlength="input.longitudMax"
                                                                                       @change="campoCumpleCondiciones(input)"
                                                                                       @input="toSaveNumber($event, keyInput, currentTabIndex)"
                                                                                       @blur="async ($event) => { await toRefactorCurrency($event, keyInput, currentTabIndex);
                                                                               saveWhenOnBlur(input)
                                                                            }"
                                                                                />
                                                                            </div>
                                                                            <small class="text-muted">{{ input.desc }}</small>
                                                                        </div>
                                                                        <div v-if="(input.tipoCampo !== null && input.tipoCampo === 'textArea') && (input.mascara !== '' || input.mascara !== null)">
                                                                            <label :for="input.id"><span v-if="input.requerido">*</span>{{ input.nombre }}</label>
                                                                            <i v-if="input.ttp && input.ttp !== ''" class="fas fa-question-circle ms-2" v-tooltip="input.ttp"></i>
                                                                            <textarea type="text" :class="{'form-control': !input.requeridoError, 'form-control requiredInput': input.requeridoError}"
                                                                                      :readonly="input.readonly || input.valorCalculado"
                                                                                      :label="input.nombre"
                                                                                      :aria-label="input.nombre"
                                                                                      v-model="input.valor"
                                                                                      :id="input.id"
                                                                                      v-maska
                                                                                      :data-maska="input.mascara"
                                                                                      :data-maska-tokens="input.tokenMask"
                                                                                      :disabled="input.deshabilitado || flujoActivo.typeObject === 'review'"
                                                                                      :placeholder="input.ph"
                                                                                      :minlength="input.longitudMin"
                                                                                      :maxlength="input.longitudMax"
                                                                                      @change="campoCumpleCondiciones(input)"
                                                                                      @blur="saveWhenOnBlur(input)"
                                                                            >
                                                                            </textarea>
                                                                            <small class="text-muted">{{ input.desc }}</small>
                                                                        </div>
                                                                        <div v-if="(input.tipoCampo !== null && input.tipoCampo === 'date') && (input.mascara !== '' || input.mascara !== null)">
                                                                            <label :for="input.id"><span v-if="input.requerido">*</span>{{ input.nombre }}</label>
                                                                            <i v-if="input.ttp && input.ttp !== ''" class="fas fa-question-circle ms-2" v-tooltip="input.ttp"></i>
                                                                            <input type="date" :class="{'form-control': !input.requeridoError, 'form-control requiredInput': input.requeridoError}"
                                                                                   :readonly="input.readonly"
                                                                                   :label="input.nombre"
                                                                                   :aria-label="input.nombre"
                                                                                   v-model="input.valor"
                                                                                   :id="input.id"
                                                                                   v-maska
                                                                                   :data-maska="input.mascara"
                                                                                   :data-maska-tokens="input.tokenMask"
                                                                                   :disabled="input.deshabilitado || flujoActivo.typeObject === 'review'"
                                                                                   :placeholder="input.ph"
                                                                                   @change="campoCumpleCondiciones(input)"
                                                                                   @blur="saveWhenOnBlur(input)"
                                                                            >
                                                                            <small class="text-muted">{{ input.desc }}</small>
                                                                        </div>
                                                                        <div v-if="(input.tipoCampo !== null && input.tipoCampo === 'dateMask') && (input.mascara !== '' || input.mascara !== null)">
                                                                            <label :for="input.id"><span v-if="input.requerido">*</span>{{ input.nombre }}</label>
                                                                            <i v-if="input.ttp && input.ttp !== ''" class="fas fa-question-circle ms-2" v-tooltip="input.ttp"></i>
                                                                            {{input.tipoCampo}}
                                                                            <!--<div class="input-group mb-3">
                                                                                <span class="input-group-text">Día</span>
                                                                                <input type="text" class="form-control" v-model="input.dMday" placeholder="&#45;&#45;" @change="() => {input.value = setDateValue(input)}"  v-maska data-maska="##" >
                                                                                <span class="input-group-text">Mes</span>
                                                                                <input type="text" class="form-control" v-model="input.dMonth" placeholder="&#45;&#45;" @change="() => {input.value = setDateValue(input)}"  v-maska data-maska="##">
                                                                                <span class="input-group-text">Año</span>
                                                                                <input type="text" class="form-control" v-model="input.dMYear" placeholder="&#45;&#45;&#45;&#45;" @change="() => {input.value = setDateValue(input)}"  v-maska data-maska="####">
                                                                            </div>
                                                                            <input type="text" :class="{'form-control': !input.requeridoError, 'form-control requiredInput': input.requeridoError}"
                                                                                   :readonly="input.readonly"
                                                                                   :label="input.nombre"
                                                                                   :aria-label="input.nombre"
                                                                                   v-model="input.valor"
                                                                                   :id="input.id"
                                                                                   v-maska
                                                                                   :data-maska="input.mascara"
                                                                                   :data-maska-tokens="input.tokenMask"
                                                                                   :disabled="input.deshabilitado || flujoActivo.typeObject === 'review'"
                                                                                   :placeholder="input.ph"
                                                                                   @change="campoCumpleCondiciones(input)"
                                                                                   @blur="saveWhenOnBlur(input)"
                                                                            >-->
                                                                            <VueDatePicker v-model="input.valor" text-input="true" :enable-time-picker="false" :time-picker-inline='false' placeholder="--/--/----" @blur="saveWhenOnBlur(input)" :auto-apply="true" :config="{
                                                                                closeOnAutoApply: true,
                                                                            }" :format="'dd/MM/yyyy'">
                                                                            </VueDatePicker>
                                                                            <small class="text-muted">{{ input.desc }}</small>
                                                                        </div>
                                                                        <div v-if="(input.tipoCampo !== null && input.tipoCampo === 'number') && (input.mascara !== '' || input.mascara !== null)">
                                                                            <label :for="input.id"><span v-if="input.requerido">*</span>{{ input.nombre }}</label>
                                                                            <i v-if="input.ttp && input.ttp !== ''" class="fas fa-question-circle ms-2" v-tooltip="input.ttp"></i>
                                                                            <input type="text" :class="{'form-control': !input.requeridoError, 'form-control requiredInput': input.requeridoError}"
                                                                                   :readonly="input.readonly"
                                                                                   :aria-label="input.nombre"
                                                                                   v-model="input.valor"
                                                                                   :id="input.id"
                                                                                   pattern="\d*"
                                                                                   v-maska
                                                                                   :data-maska="input.mascara"
                                                                                   :data-maska-tokens="input.tokenMask"
                                                                                   :disabled="input.deshabilitado || flujoActivo.typeObject === 'review'"
                                                                                   :placeholder="input.ph"
                                                                                   :minlength="input.longitudMin"
                                                                                   :maxlength="input.longitudMax"
                                                                                   @change="campoCumpleCondiciones(input)"
                                                                                   @blur="saveWhenOnBlur(input)"
                                                                            >
                                                                            <small class="text-muted">{{ input.desc }}</small>
                                                                        </div>
                                                                        <div v-if="(input.tipoCampo !== null && input.tipoCampo === 'numslider') && (input.mascara !== '' || input.mascara !== null)">
                                                                            <div :class="{'': !input.requeridoError, 'requiredInput': input.requeridoError}">
                                                                                <label class="mb-2" :for="input.id"><span v-if="input.requerido">*</span>{{ input.nombre }}</label>
                                                                                <i v-if="input.ttp && input.ttp !== ''" class="fas fa-question-circle ms-2" v-tooltip="input.ttp"></i>
                                                                                <Slider v-model="input.valor" :min="parseInt(input.longitudMin)" :max="parseInt(input.longitudMax)" :showTooltip="'focus'"/>
                                                                            </div>
                                                                            <small class="text-muted">{{ input.desc }}</small>
                                                                        </div>
                                                                        <div v-if="input.tipoCampo !== null && input.tipoCampo === 'select' && (input.mascara === '' || input.mascara === null)">
                                                                            <label :for="input.id"><span v-if="input.requerido">*</span>{{ input.nombre }}</label>
                                                                            <i v-if="input.ttp && input.ttp !== ''" class="fas fa-question-circle ms-2" v-tooltip="input.ttp"></i>
                                                                            <select
                                                                                :class="{'form-select': !input.requeridoError, 'form-select requiredInput': input.requeridoError}"
                                                                                v-model="input.valor"
                                                                                aria-label=".form-select-lg example"
                                                                                :readonly="input.readonly"
                                                                                :disabled="input.deshabilitado || flujoActivo.typeObject === 'review'"
                                                                                @change="campoCumpleCondiciones(input, true); saveWhenOnBlur(input)"
                                                                            >
                                                                                <template v-if="typeof catalogos[input.id] !== 'undefined'">
                                                                                    <option v-if="typeof (input.catalogoId) !== 'undefined'" v-for="(item , index) in catalogos[input.id]" :value="item[input.catalogoValue]" :key="index">
                                                                                        {{ item[input.catalogoLabel] }}
                                                                                    </option>
                                                                                </template>
                                                                            </select>
                                                                            <small class="text-muted">{{ input.desc }}</small>
                                                                        </div>
                                                                        <div v-if="input.tipoCampo !== null && input.tipoCampo === 'multiselect' && (input.mascara === '' || input.mascara === null) && Array.isArray(input.valor)">
                                                                            <label :for="input.id"><span v-if="input.requerido">*</span>{{ input.nombre }}</label>
                                                                            <i v-if="input.ttp && input.ttp !== ''" class="fas fa-question-circle ms-2" v-tooltip="input.ttp"></i>
                                                                            <div :class="{'': !input.requeridoError, 'requiredInput': input.requeridoError}">
                                                                                <multiselect
                                                                                    :options="obtenerItemsPorCatalogo(input.id, input.catalogoLabel, input.catalogoValue)"
                                                                                    :searchable="true"
                                                                                    :mode="'tags'"
                                                                                    :label="input.catalogoLabel"
                                                                                    :value-prop="input.catalogoValue"
                                                                                    :disabled="input.deshabilitado || flujoActivo.typeObject === 'review'"
                                                                                    v-model="input.valor"
                                                                                    :max="input.max"
                                                                                    :allow-empty="!input.requerido"
                                                                                    :min="input.min"
                                                                                    @change="saveValueMultiSelect(input)"/>
                                                                            </div>
                                                                            <small class="text-muted">{{ input.desc }}</small>
                                                                        </div>
                                                                        <div v-if="input.tipoCampo !== null && input.tipoCampo === 'checkbox' && (input.mascara === '' || input.mascara === null)">
                                                                            <label :for="input.id"><span v-if="input.requerido">*</span>{{ input.nombre }}</label>
                                                                            <i v-if="input.ttp && input.ttp !== ''" class="fas fa-question-circle ms-2" v-tooltip="input.ttp"></i>
                                                                            <div :class="{'form-check': !input.requeridoError, 'form-check requiredInput': input.requeridoError}" v-if="typeof (input.catalogoId) !== 'undefined'" v-for="(item, indexOption) in obtenerItemsPorCatalogoMS(input.id, input.catalogoLabel, input.catalogoValue)">
                                                                                <input class="form-check-input" :name="input.id" :checked="!!input.valor && input.valor.includes(item.value)" :value="item.value" type="checkbox" :id="input.id+'_'+item[input.catalogoLabel]+'_'+indexOption" :disabled="input.deshabilitado || flujoActivo.typeObject === 'review'" @change="selectedMultiValue(input, item.value, keyInput, currentTabIndex)"
                                                                                       @blur="saveWhenOnBlur(input)">
                                                                                <label class="form-check-label" :for="input.id+'_'+item[input.catalogoLabel]+'_'+indexOption"> {{ item.label }}</label>
                                                                            </div>
                                                                        </div>
                                                                        <div v-if="input.tipoCampo !== null && input.tipoCampo === 'option' && (input.mascara === '' || input.mascara === null)">
                                                                            <div>
                                                                                <label :for="input.id"><span v-if="input.requerido">*</span>{{ input.nombre }}</label>
                                                                            </div>
                                                                            <i v-if="input.ttp && input.ttp !== ''" class="fas fa-question-circle ms-2" v-tooltip="input.ttp"></i>
                                                                            <span :class="{'form-check': !input.requeridoError, 'form-check requiredInput': input.requeridoError}" v-if="typeof (input.catalogoId) !== 'undefined'" v-for="item in obtenerItemsPorCatalogo(input.id)">
                                                                        <input class="form-check-input" :name="input.id" v-model="input.valor" :value="item[input.catalogoValue]" type="radio" :id="input.id+'_'+item[input.catalogoLabel]" :disabled="input.deshabilitado || flujoActivo.typeObject === 'review'" @change="campoCumpleCondiciones(input)"
                                                                               @blur="saveWhenOnBlur(input)">
                                                                        <label class="form-check-label" :for="input.id+'_'+item[input.catalogoLabel]">
                                                                            {{ item[input.catalogoLabel] }}
                                                                        </label>
                                                                    </span>

                                                                        </div>
                                                                        <div v-if="(input.tipoCampo !== null && input.tipoCampo === 'range') && (input.mascara === '' || input.mascara === null)">
                                                                            <label :for="input.id"><span v-if="input.requerido">*</span>{{ input.nombre }} {{ input.valor }}</label>
                                                                            <i v-if="input.ttp && input.ttp !== ''" class="fas fa-question-circle ms-2" v-tooltip="input.ttp"></i>
                                                                            <div class="input-group">
                                                                                <input type="range"
                                                                                       class="form-range"
                                                                                       :id="input.id"
                                                                                       :min="input.longitudMin"
                                                                                       :max="input.longitudMax"
                                                                                       step="1"
                                                                                       v-model="input.valor"
                                                                                       :disabled="input.deshabilitado || flujoActivo.typeObject === 'review'"
                                                                                       @change="campoCumpleCondiciones(input)"
                                                                                       @blur="saveWhenOnBlur(input)"
                                                                                >
                                                                            </div>
                                                                        </div>
                                                                        <div v-if="(input.tipoCampo !== null && (input.tipoCampo === 'file' ||  input.tipoCampo === 'fileER')) && (input.mascara === '' || input.mascara === null)">
                                                                            <label :for="input.id"><span v-if="input.requerido">*</span>{{ input.nombre }}</label>
                                                                            <i v-if="input.ttp && input.ttp !== ''" class="fas fa-question-circle ms-2" v-tooltip="input.ttp"></i>
                                                                            <div :class="{'': !input.requeridoError, 'requiredInput': input.requeridoError}">
                                                                                <file-pond type="file"
                                                                                           class="filepond"
                                                                                           :key="'filepondInput_'+input.id"
                                                                                           :name="input.id"
                                                                                           label-idle="Clic para seleccionar o arrastra tus archivos acá"
                                                                                           credits="false"
                                                                                           data-allow-reorder="true"
                                                                                           data-max-file-size="150MB"
                                                                                           :disabled="input.deshabilitado || flujoActivo.typeObject === 'review'"
                                                                                           :server="{
                                                                                    process: (fieldName, file, metadata, load, error, progress, abort) => {
                                                                                    handleUpload(file, input.id, load, error, progress);
                                                                                    }
                                                                                }"
                                                                                           ref="filepondInput">
                                                                                </file-pond>
                                                                            </div>
                                                                        </div>
                                                                        <div v-if="(input.tipoCampo !== null && input.tipoCampo === 'signature')">
                                                                            <label :for="input.id"><span v-if="input.requerido">*</span>{{ input.nombre }}</label>
                                                                            <i v-if="input.ttp && input.ttp !== ''" class="fas fa-question-circle ms-2" v-tooltip="input.ttp"></i>
                                                                            <div :class="{'': !input.requeridoError, 'requiredInput': input.requeridoError}">
                                                                                <div v-show="input.valor && input.valor !== ''" class="text-center">
                                                                                    <div v-if="typeof signatureData[input.id] !== 'undefined'">
                                                                                        <div>
                                                                                            <img :src="signatureData[input.id]" style="max-height: 60px"/>
                                                                                        </div>
                                                                                        <small style="color: #bdbdbd; font-size: 10px">Firma guardada</small>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="text-center">
                                                                                    <VueSignaturePad :ref="'signature_'+input.id" style="border: 1px solid #c0c0c0; height: 200px; width: 100%" class="rounded" :options="{onEnd: function(){saveSignature(keyInput, input)}}"/>
                                                                                    <a @click="resetSignature('signature_'+input.id)" class="text-danger cursor-pointer" style="font-size: 10px">Reiniciar</a>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div v-if="(input.tipoCampo !== null && input.tipoCampo === 'aprobacion')">
                                                                            <div class="text-center mt-2 mb-2">
                                                                                <div :class="{'': !input.requeridoError, 'requiredInput': input.requeridoError}">
                                                                                    <fieldset>
                                                                                        <h6>
                                                                                            <span v-if="input.requerido">*</span>{{ input.nombre }}
                                                                                        </h6>
                                                                                        <i v-if="input.ttp && input.ttp !== ''" class="fas fa-question-circle ms-2" v-tooltip="input.ttp"></i>
                                                                                        <div class="toggle m-auto text-center">
                                                                                            <input type="radio" value="aprobar" :id="input.id + 'aprobarBtn'" :name="input.id + 'aprobacionBtn'" :disabled="input.deshabilitado || flujoActivo.typeObject === 'review'" v-model="input.valor" @change="campoCumpleCondiciones(input)"
                                                                                                   @blur="saveWhenOnBlur(input)"
                                                                                            />
                                                                                            <label :for="input.id + 'aprobarBtn'">Aprobar</label>
                                                                                            <input type="radio" value="rechazar" :id="input.id + 'rechazarBtn'" :name="input.id + 'aprobacionBtn'" :disabled="input.deshabilitado || flujoActivo.typeObject === 'review'" v-model="input.valor" @change="campoCumpleCondiciones(input)"
                                                                                                   @blur="saveWhenOnBlur(input)"
                                                                                            />
                                                                                            <label :for="input.id + 'rechazarBtn'" style="background-color: #f5f5f5">Rechazar</label>
                                                                                        </div>
                                                                                    </fieldset>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <span v-if="flujoActivo.typeObject === 'review'" class="approveField">
                                                                    <div>
                                                                        <input type="checkbox" :id="input.id + 'aprobarCheck'" v-model="input.rechazado"/><label :for="input.id + 'aprobarCheck'" class="ms-2">Rechazar</label>
                                                                    </div>
                                                                </span>
                                                                    </div>
                                                                </template>
                                                            </template>
                                                            <div class="mt-4" v-if="flujoActivo.typeObject === 'review'">
                                                                <div class="AprobarComment mt-2">
                                                                    <h6 class="mb-2">Motivos de rechazo</h6>
                                                                    <textarea type="text" class="form-control" v-model="comentarioGeneralRechazo"></textarea>
                                                                </div>
                                                            </div>
                                                        </template>
                                                    </CForm>
                                                </div>
                                            </div>
                                            <div v-if="(typeof flujoActivo.salidaReplaced !== 'undefined')">
                                                <div v-html="flujoActivo.salidaReplaced"></div>
                                            </div>
                                            <COffcanvas placement="end" :visible="showComentariosBar" @hide="() => { showComentariosBar = !showComentariosBar }">
                                                <COffcanvasHeader>
                                                    Comentarios
                                                    <CCloseButton class="text-reset" @click="() => { showComentariosBar = false }"/>
                                                </COffcanvasHeader>
                                                <COffcanvasBody>
                                                    <div class="chatBar">
                                                        <div class="chatBarContainer">
                                                            <div class="chatBarItem" v-for="item in comentarios">
                                                                <div class="chatBarItemUser">{{ item.usuario }},
                                                                    <span :class="{'text-success': item.a === 'privado', 'text-danger': item.a === 'publico'}">{{ item.a }}</span>
                                                                </div>
                                                                {{ item.comentario }}
                                                            </div>
                                                        </div>
                                                        <div class="chatBarInput pt-2">
                                                            <div class="input-group">
                                                                <input type="text" class="form-control" placeholder="Escribe aquí" v-model="comentarioTmp" v-on:keyup.enter="sendComentario" style="width: 70%;">
                                                                <select class="form-control" v-model="comentarioAcceso" v-if="!isPublic">
                                                                    <option value="privado">Privado</option>
                                                                    <option value="publico">Público</option>
                                                                </select>
                                                                <button class="btn btn-primary" @click="sendComentario">
                                                                    <i class="fa fa-paper-plane me-1"></i></button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </COffcanvasBody>
                                            </COffcanvas>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <!--Barra de soporte-->
                        <COffcanvas placement="end" :visible="showSoporteBar" @hide="() => { showSoporteBar = !showSoporteBar }">
                            <COffcanvasHeader>
                                <h3 class="m-0">Solicitud de soporte</h3>
                                <CCloseButton class="text-reset" @click="() => { showSoporteBar = false }"/>
                            </COffcanvasHeader>
                            <COffcanvasBody>
                                <div class="text-center text-muted mb-3">
                                    <div>
                                        Si posee alguna pregunta que se encuentra en el catálogo de preguntas frecuentes, puede consultarlas dando clic aquí
                                    </div>
                                    <button class="btn btn-primary btn-sm mt-2" @click="goPreguntasFrecuentes">Ir a preguntas frecuentes</button>
                                </div>
                                <div class="chatBar">
                                    <div class="chatBarContainer">
                                        <div v-for="(item, key) in soportes">
                                            <div :class="{'chatBarItem ms-3': parseInt(item.isR) === 1, 'chatBarItem': parseInt(item.isR) === 0}">
                                                <div>
                                                    <span class="fw-bold">{{ item.usuario }}&nbsp;&nbsp;&nbsp;</span>
                                                    <span class="badge bg-secondary" v-if="parseInt(item.isR) === 0">Ticket Workflow: {{ item.wkId }}</span>
                                                </div>
                                                <div class="my-2 text-capitalize">
                                                    {{ item.comentario }}
                                                </div>
                                                <div v-if="(typeof authInfo.m['tareas/show-in-wk'] !== 'undefined' && authInfo.m['tareas/show-in-wk'])">
                                                    <!--<small @click="verSoporteDetalle(item.link)" class="text-secondary cursor-pointer me-2"><i class="fas fa-eye me-2"></i>Ver detalle</small>-->
                                                    <small @click="verSoporteDetalleTab(item.link)" class="text-secondary cursor-pointer"><i class="fas fa-eye me-2"></i>Ver en Workflow</small>
                                                </div>
                                                <div v-if="typeof item.detalle !== 'undefined'" class="mt-3">
                                                    <div v-for="sup in item.detalle" class="mb-3">
                                                        <b class="text-primary">{{sup.nombre}}</b>
                                                        <div v-for="campo in sup.campos">
                                                            <b>{{campo.label}}</b>: {{campo.value}}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="chatBarInput pt-2">
                                        <div class="input-group">
                                            <select class="form-select" v-model="soporteTipoSelected">
                                                <option v-for="(sop, tokenSop) in soporteTipos" :value="tokenSop">{{sop}}</option>
                                            </select>
                                            <input type="text" class="form-control" placeholder="Escribe aquí" v-model="soporteComentario" v-on:keyup.enter="sendSoporteComentario" style="width: 70%;">
                                            <button class="btn btn-primary" @click="sendSoporteComentario">
                                                <i class="fa fa-paper-plane me-1"></i></button>
                                        </div>
                                    </div>
                                    <div class="globalModal supportIframe" v-if="showSoporteFrame">
                                        <div class="globalModalContainer text-center p-5">
                                            <div @click="showSoporteFrame = false" class="globalModalClose mt-3">
                                                <i class="fas fa-times-circle"></i>
                                            </div>
                                            <iframe :src="showSoporteFrameUrl"></iframe>
                                        </div>
                                    </div>
                                </div>
                            </COffcanvasBody>
                        </COffcanvas>
                        <!--Barra de soporte-->
                        <COffcanvas placement="end" :visible="showVarBar" @hide="() => { showVarBar = !showVarBar }">
                            <COffcanvasHeader>
                                <h3 class="m-0">Variables disponibles (solo modo de pruebas)</h3>
                                <CCloseButton class="text-reset" @click="() => { showVarBar = false }"/>
                            </COffcanvasHeader>
                            <COffcanvasBody>
                                <div v-for="(value, key) in varsTest">
                                    <b>{{key}}:</b> {{value}}
                                </div>
                            </COffcanvasBody>
                        </COffcanvas>
                    </CCardBody>
                    <CCardFooter class="p-4 text-end">
                        <div class="text-start" v-if="cotizacion.rve || cotizacion.rvw">
                            <h5 class="mb-3">Reenvío</h5>
                            <div v-if="cotizacion.rve">
                                <input type="checkbox" v-model="rveOn" id="cambioCorreo">
                                <label for="cambioCorreo">&nbsp;Reenviar a correo diferente</label>
                            </div>
                            <div>
                                <input v-if="rveOn" type="text" class="form-control d-inline-block w-auto" placeholder="Enviar a nuevo Email" v-model="newEmailReenvio"/>
                            </div>
                            <div class="mt-2" v-if="cotizacion.rvw">
                                <input type="checkbox" v-model="rvwOn" id="cambioWhatsapp">
                                <label for="cambioWhatsapp">&nbsp;Reenviar a Whatsapp diferente</label>
                            </div>
                            <div>
                                <input v-if="rvwOn" type="text" class="form-control d-inline-block w-auto" placeholder="Enviar a nuevo Email" v-model="newWspReenvio"/>
                            </div>
                            <div class="mt-3">
                                <CButton v-if="cotizacion.rve" color="success" @click="reenviarCoti('email')" class="custom-formButton">
                                    Enviar por Email
                                </CButton>
                                <CButton v-if="cotizacion.rvw" color="success" @click="reenviarCoti('whatsapp')" class="custom-formButton">
                                    Enviar por Whatsapp
                                </CButton>
                            </div>
                            <hr>
                        </div>
                        <div>
                            <CButton v-if="flujoActivo.btnText.cancel !== ''" color="danger" @click="cancelarCotizacion" class="custom-formButton">
                                {{ flujoActivo.btnText.cancel }}
                            </CButton>
                            <CButton v-if="flujoHasPrev && flujoActivo.btnText.prev !== ''" color="primary" @click="continuarCotizacion('prev')" class="custom-formButton">
                                {{ flujoActivo.btnText.prev }}
                            </CButton>
                            <CButton v-if="flujoHasNext && flujoActivo.btnText.next !== ''" color="primary" @click="continuarCotizacion('next')" class="custom-formButton">
                                {{ flujoActivo.btnText.next }}
                            </CButton>
                            <CButton v-if="!flujoHasNext && flujoActivo.btnText.finish !== ''" color="primary" @click="continuarCotizacion('next', (isPublic ? 'guardada' : 'finalizada'))" class="custom-formButton">
                                {{ flujoActivo.btnText.finish }}
                            </CButton>
                        </div>
                    </CCardFooter>
                </CCard>
                <COffcanvas placement="end" :visible="showFilesBar" @hide="() => { showFilesBar = !showFilesBar }">
                    <COffcanvasHeader>
                        <CCloseButton class="text-reset" @click="() => { showFilesBar = false }"/>
                    </COffcanvasHeader>
                    <COffcanvasBody>
                        <template v-if="!isPublic && (typeof authInfo.m['admin/show-adj'] !== 'undefined' && authInfo.m['admin/show-adj'])">
                            <div class="globalModal" v-if="showImagePreview">
                                <div class="globalModalContainer text-center p-5">
                                    <div @click="showImagePreview = false" class="globalModalClose mt-3">
                                        <i class="fas fa-times-circle"></i></div>
                                    <img :src="imagePreviewTmp.url" style="max-width: 100%"/>
                                    <div class="text-center">
                                        <a class="btn btn-primary mt-5" :href="imagePreviewTmp.url" :download="imagePreviewTmp.name" target="_blank"><i class="fa fa-download me-2"></i>Descargar</a>
                                        <a class="btn btn-danger mt-5 ms-3" :href="imagePreviewTmp.url" :download="imagePreviewTmp.name" target="_blank"><i class="fa fa-trash me-2"></i>Eliminar</a>
                                    </div>
                                </div>
                            </div>
                            <h5 class="mb-3">Archivos adjuntos</h5>
                            <div class="row d-flex flex-wrap" id="fileAttachGallery">
                                <template v-for="(file, key) in previewFiles">
                                    <div class="col-md-12 col-4" v-if="!file.salida">
                                        <input type="checkbox" v-model="file.rveOn">
                                        <div class="fileGalleryItemText">
                                            {{ file.name }}
                                        </div>
                                        <div class="fileGalleryItem">
                                            <a
                                                :key="'attachTmp_' + key"
                                                :href="file.url"
                                                target="_blank"
                                                rel="noreferrer"
                                                :data-pswp-width="900"
                                                :data-pswp-height="900"
                                                :data-download="file.download"
                                                v-if="file.type !== 'pdf'"
                                            >
                                                <img :src="file.url" :class="'previewFile-' + file.type"/>
                                            </a>
                                            <div  @click="changeImagenPreview(file)">
                                                <i class="fas fa-file" style="font-size: 2em; cursor: pointer"></i>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <div
                                    v-if="showPdfPreview"
                                    :style="{
                                        position: 'fixed',
                                        top: '0px',
                                        left: '0px',
                                        margin: '0',
                                        padding: '0',
                                        backgroundColor: 'rgba(0, 0, 0, 0.5)',
                                        zIndex: '10000',
                                        height: '100vh',
                                        width: '100vw',
                                        display: 'flex',
                                        justifyContent: 'center',
                                        alignItems: 'center',
                                    }"
                                    @click.stop="changeImagenPreview()"
                                >
                                    <div
                                        :style="{position: 'absolute', right: '40px', top: '5px', }">

                                        <i class="fa-solid fa-download"
                                           :style="{height: '30px', width: '30px', color: 'white', fontSize: '24px'}"
                                           @click.stop="openBlank()"
                                        ></i>

                                        <i class="fa-solid fa-xmark"
                                           :style="{height: '30px', width: '30px', color: 'white', fontSize: '24px'}"
                                           @click.stop="changeImagenPreview()"
                                        ></i>
                                    </div>
                                    <div :style="{
                                        maxHeight: 'calc(100vh - 100px)',
                                        maxWidth: 'calc(100vw - 100px)',
                                        height: 'calc(100vh - 100px)',
                                        width: 'calc(100vw - 100px)',
                                        overflowY: 'auto',
                                    }"
                                         @click.stop=""
                                    >
                                        <vue-pdf-embed
                                            :source="pdfSource"
                                        />
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template v-if="!isPublic && (typeof authInfo.m['admin/show-adgen'] !== 'undefined' && authInfo.m['admin/show-adgen'])">
                            <h5>Archivos generados</h5>
                            <div class="row">
                                <template v-for="file in previewFiles">
                                    <div class="col-12 col-sm-3" v-if="file.salida">
                                        <input type="checkbox" v-model="file.rveOn">
                                        <div class="mb-3 fw-bold">{{ file.name }}</div>
                                        <!--<div class="text-center rounded cursor-pointer" style="padding: 20px; background: #d7d7d7" @click="openPreview(file)">-->
                                        <div class="text-center rounded cursor-pointer" style="padding: 20px; background: #d7d7d7" @click="changeImagenPreview(file)">
                                            <div v-if="file.type === 'image'">
                                                <img :src="file.url" style="max-height: 60px">
                                            </div>
                                            <div v-if="file.type === 'pdf'">
                                                <div style="min-height: 60px">
                                                    <a class="btn btn-primary mt-3" target="_blank">Ver PDF</a>
                                                </div>
                                            </div>
                                            <div v-if="file.type === 'docx'">
                                                <div style="min-height: 60px">
                                                    <a class="btn btn-primary mt-3" target="_blank">Ver documento</a>
                                                </div>
                                            </div>
                                            <div>{{ file.label }}</div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <div class="text-start">
                                <h5 class="mt-3">Reenvío de adjuntos</h5>
                                <div>
                                    <input type="text" class="form-control d-inline-block w-auto mt-3" placeholder="Enviar a nuevo Email" v-model="newEmailReenvioAdjunto"/>
                                </div>
                                <button @click="reenviarAdjunto('email')" class="btn btn-success mt-3">
                                    Enviar por Email
                                </button>
                                <!--
                                <div>
                                    <input type="text" class="form-control d-inline-block w-auto mt-3" placeholder="Enviar a nuevo Celular" v-model="newWspReenvioAdjunto"/>
                                </div>
                                <CButton color="success" @click="reenviarAdjunto('whatsapp')" class="custom-formButton mt-3">
                                    Enviar por Whatsapp
                                </CButton>
                                -->
                                <hr>
                            </div>
                        </template>
                    </COffcanvasBody>
                </COffcanvas>
            </div>
            <CCard class="mt-3" v-if="!isPublic && (typeof authInfo.m['admin/show-resumen'] !== 'undefined' && authInfo.m['admin/show-resumen'])">
                <CCardHeader>
                    <h5>Comentarios</h5>
                </CCardHeader>
                <CCardBody>
                    <div v-for="item in comentariosGen" class="mb-1 commentList">
                        <div>
                            <span class="fw-bold me-2">{{item.d}}</span>
                            <div class="my-2">
                                {{item.c}}
                            </div>
                            <div v-if="Object.keys(item.f).length > 0" >
                                <small class="text-muted">Rechazado por:</small>
                                <ul class="m-0">
                                    <li v-for="(field, key) in item.f">
                                        {{allFieldsLabel[key]}}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </CCardBody>
            </CCard>
            <CCard class="mt-3" v-if="!isPublic && (typeof authInfo.m['admin/show-resumen'] !== 'undefined' && authInfo.m['admin/show-resumen'])">
                <CCardHeader>
                    <h5>Resumen de tarea</h5>
                    <div class="text-muted">
                        * Únicamente se muestran secciones y campos llenos
                    </div>
                </CCardHeader>
                <CCardBody>
                    <div v-for="item in resumen" class="mb-4">
                        <div v-if="typeof item.campos !== 'undefined'">
                            <h6 class="cursor-pointer" @click="item.active = !item.active">{{ item.nombre || 'Sin nombre' }}</h6>
                            <hr>
                            <div class="row" v-if="item.active">
                                <template v-if="typeof item.campos !== 'undefined'">
                                    <template v-for="(campo, key) in item.campos">
                                        <div class="col-12 col-sm-4 mb-3" v-if="campo.value !== '' && (campo.t !== 'signature' && campo.t !== 'file')">
                                            <div class="text-primary fw-bold">
                                                {{ campo.label }}
                                            </div>
                                            <div v-html="campo.value"></div>
                                        </div>
                                    </template>
                                </template>
                                <template v-else>
                                    <div class="col-12 text-danger">
                                        Sin campos llenos
                                    </div>
                                </template>

                            </div>
                        </div>
                    </div>
                </CCardBody>
            </CCard>
            <div v-if="!isPublic && typeof authInfo.m !== 'undefined' && typeof authInfo.m['tareas/admin/usuario-asignado'] !== 'undefined' && authInfo.m['tareas/admin/usuario-asignado']" class="mt-3">
                <CCard class="mt-2">
                    <CCardHeader>
                        <h5>Edición de usuario</h5>
                    </CCardHeader>
                    <CCardBody class="text-center">
                        <div class="text-start">
                            <h6>Cambiar usuario asignado</h6>
                            <div>
                                <multiselect :options="users" v-model="usuarioEditar" :searchable="true"></multiselect>
                            </div>
                            <div class="text-end mt-3">
                                <button class="btn btn-primary" @click="editarUsuarioCotizacion">Cambiar usuario</button>
                            </div>
                        </div>
                    </CCardBody>
                </CCard>
            </div>
            <div v-if="!isPublic && typeof authInfo.m !== 'undefined' && typeof authInfo.m['tareas/admin/usuario-asignado'] !== 'undefined' && authInfo.m['tareas/admin/usuario-asignado']" class="mt-3">
                <CCard class="mt-2">
                    <CCardHeader>
                        <h5>Cambio de estado</h5>
                    </CCardHeader>
                    <CCardBody class="text-center">
                        <div class="text-start">
                            <h6>Cambiar estado de tarea</h6>
                            <div>
                                <multiselect :options="estados" v-model="estadoEditar" :searchable="true"></multiselect>
                            </div>
                            <div class="text-end mt-3">
                                <button class="btn btn-primary" @click="editarEstadoCotizacion">Cambiar estado</button>
                            </div>
                        </div>
                    </CCardBody>
                </CCard>
            </div>
            <div v-if="!isPublic && (typeof authInfo.m['admin/show-bitacora'] !== 'undefined' && authInfo.m['admin/show-bitacora'])">
                <CCard class="mt-3">
                    <CCardHeader>
                        <strong>Bitácora</strong>
                        <div v-if="producto.modoPruebas && (typeof authInfo.m['admin/show-test-mode'] !== 'undefined' && authInfo.m['admin/show-test-mode'])" class="mt-3">
                            <h5 class="text-danger"><i class="fas fa-warning me-2"></i> Modo pruebas activo</h5>
                        </div>
                    </CCardHeader>
                    <CCardBody v-if="producto.id" style="overflow: auto; max-height: 700px">
                        <div class="row mb-2" v-for="bit in bitacora">
                            <div class="col-12"><b>Operación:</b> {{ bit.log }}</div>
                            <div class="col-12 col-sm-3"><b>Fecha:</b> {{ bit.createdAt }}</div>
                            <div class="col-12 col-sm-3"><b>Usuario:</b> {{ bit.usuarioNombre }}</div>
                            <div class="col-12 col-sm-3"><b>Corporativo:</b> {{ bit.usuarioCorporativo }}</div>
                            <div class="col-12" v-if="bit.dataInfo && bit.dataInfo !== '' && (typeof authInfo.m['admin/show-test-mode'] !== 'undefined' && authInfo.m['admin/show-test-mode'])">
                                <div class="mb-3 p-3 bg-light" v-html="bit.dataInfo"></div>
                            </div>
                            <div class="col-12">
                                <hr>
                            </div>
                        </div>
                    </CCardBody>
                </CCard>
                <CCard v-if="!isPublic && (typeof authInfo.m['admin/show-bitacora-process'] !== 'undefined' && authInfo.m['admin/show-bitacora-process'])" class="mt-3">
                    <CCardHeader>
                        <strong>Bitácora de proceso</strong>
                    </CCardHeader>
                    <CCardBody v-if="producto.id" style="overflow: auto; max-height: 700px">
                        <EasyDataTable :headers="headersBitaReca" :items="bitacoraReca" alternating >
                        </EasyDataTable>
                    </CCardBody>
                </CCard>
            </div>
        </template>
    </div>
    <div class="loading" v-if="Object.values(loadRequest).some(e => e === true)">
        <div class="loadingBox text-center">
            <div>
                <img :src="loadingImg">
            </div>
            <div class="mt-2">
                Cargando
            </div>
        </div>
    </div>
</template>
<script>
import toolbox from "@/toolbox";
import 'form-wizard-vue3/dist/form-wizard-vue3.css'
import '@vueform/slider/themes/default.css';
import login from "@/views/pages/Login.vue";
import {CChart} from "@coreui/vue-chartjs";
import {vMaska} from "maska";
import {useRouter, useRoute} from 'vue-router';
import Multiselect from '@vueform/multiselect'

// Import FilePond
import vueFilePond from 'vue-filepond';
import 'filepond/dist/filepond.min.css';
import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.css';
import Button from "@/views/forms/form_elements/FormElementButton.vue";
import {mapGetters} from "vuex";
import * as dayjs from 'dayjs'
import Slider from '@vueform/slider'
import Select from "@/views/forms/Select.vue";
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css'

import PhotoSwipeLightbox from 'photoswipe/lightbox';
import 'photoswipe/style.css';

import VuePdfEmbed from 'vue-pdf-embed'
import Checkbox from "@/views/forms/form_elements/FormElementCheckbox.vue";
import loadingImg from '@/assets/images/loading.gif';
import { forEach } from "lodash";

import {DatePicker} from 'v-calendar';
import 'v-calendar/dist/style.css';
import Swal from "sweetalert2";
import Toastify from "toastify-js";

const FilePond = vueFilePond();

export default {
    name: 'Tables',
    props: ['tokenProducto', 'tokenCotizacion', 'isPublic'],
    directives: {maska: vMaska},
    components: {
        Checkbox,
        Select,
        Button,
        login,
        CChart,
        FilePond,
        useRoute,
        Multiselect,
        Slider,
        VuePdfEmbed,
        DatePicker,
        VueDatePicker
    },
    data() {
        return {
            jsCustomLoaded: false,
            cssLoaded: false,
            pToken: "",
            cToken: "",
            producto: {},
            cotizacion: {},
            logHistory: [],
            flujoActivo: {},
            flujoHasPrev: false,
            flujoHasNext: false,
            currentTabIndex: 0,
            estadoCotizacion: '',
            showCotizacion: false,
            showCotizacionDesc: '',
            bitacora: {},
            bitacoraReca: [],
            resumen: {},
            users: [],
            usuarioEditar: 0,
            headersBitaReca: [
                {text: "No", value: "orden"},
                {text: "Nombre", value: "nodoName"},
                //{text: "Identificador", value: "nodoNameId"},
                {text: "Tipo", value: "typeObject"},
                {text: "Fecha", value: "createdAt"},
                //{text: "Estado", value: "estado"},
                {text: "Usuario", value: "usuarioNombre"},
                {text: "Corporativo", value: "usuarioCorporativo"},
                //{text: "Usuario Asignado", value: "usuarioAsignadoNombre"},
                //{text: "Corporativo de Usuario Asignado", value: "usuarioAsignadoCorporativo"},
            ],
            codigoAgenteUsuarioEditar: '',
            codigosAgente: [],
            usersInfo: [],

            // estados
            catalogosCheckDepends: {},
            catalogos: {},

            // estados
            estados: {},
            estadoEditar: '',

            // condicionales
            seccionesCumplidoras: {},
            camposCumplidores: {},
            camposValores: {},

            // calculados
            camposCalculados: {},
            userVars: [],

            // preview de adjuntos
            previewFiles: {},
            showImagePreview: false,
            imagePreviewTmp: {},

            // progresión
            showsiniestralidad: false,
            siniestralidad: {},
            progresion: {},

            showCompare: false,
            dataTableCompare: {},

            showinspeccion: false,
            inspeccion: '',

            // toda la data historica
            allFieldsLabel: {},
            allFieldsData: {},
            signatureData: {},

            // archivos
            showFilesBar: false,

            // Comentarios
            showComentariosBar: false,
            comentarios: {},
            comentarioTmp: '',
            comentarioAcceso: 'privado',

            // soporte
            showSoporteFrame: false,
            showSoporteFrameUrl: false,
            showSoporteBar: false,
            soporteTipos: {},
            soportes: {},
            soporteTipoSelected: '',
            soporteComentario: '',

            //variables
            showVarBar: false,
            varsTest: {},

            newEmailReenvio: '',
            newWspReenvio: '',

            newEmailReenvioAdjunto: '',
            newWspReenvioAdjunto: '',

            // reenvio
            rveOn: false,
            rvwOn: false,

            // codigos agente
            codigoAgenteSelected: '',

            // galería
            lightboxAttachment: false,

            // PDF
            pdfSource: '',
            showPdfPreview: false,
            pdfUrl: '',

            // rechazo de campos
            comentarioGeneralRechazo: "",
            comentariosGen: {},


            // vehículos
            vehiculosFilter: {},
            vehiculos: {},
            vehiculosCot: {},
            vehiculoIdAgrupadorNodo: 0,

            // otros
            frecuenciaPagosCat: {},


            // maska
            maskaMoneyQ: {
                preProcess: val => val.replace(/[Q,]/g, ''),
                postProcess: val => {
                    if (!val) return ''

                    const sub = 3 - (val.includes('.') ? val.length - val.indexOf('.') : 0)

                    return Intl.NumberFormat('es-GT', {
                        style: 'currency',
                        currency: 'GTQ'
                    }).format(val)
                        .slice(0, sub ? -sub : undefined)
                }
            },

            catalogosPay: {},
            dataPago: {},

            //Tables
            headersTable: [
                {text: "No.", value: "orden"},
                {text: "Placa", value: "placa"},
                {text: "Motor", value: "motor"},
                {text: "Chasis", value: "chasis"},
                {text: "Color", value: "color"},
                {text: "Información", value: "siniestralidad"},
            ],
            itemsTable: [],

            headersPoliza: [
                {text: "No.", value: "orden"},
                {text: "Poliza", value: "poliza"},
              //  {text: "Certificado", value: "certificado"},
                {text: "Desde", value: "vigenciaDesde"},
                {text: "Hasta", value: "vigenciaHasta"},
                {text: "Pagos Pendientes", value: "pagosPendientes"},
                {text: "Prima Total", value: "primaTotal"},
                {text: "Estado", value: "estado"},
                {text: "Siniestralidad (%)", value: "porcentajeSiniestralidad"},
                {text: "Dias Morosidad", value: "diasMorosidad"},
            ],
            polizaTable: [],


            //Descuentos
            descuentos: [],
            modifyDescuento: false,

            // MONEDA
            cotizacionMoneda: "Q",

            //FrecuenciaPagos
            newFrecuenciaPago: {f:'', p:[]},
            headersListaPago: [
                {text: "No.", value: "numeroPagos"},
                {text: "Gastos Emisión", value: "gastosEmision"},
                {text: "IVA", value: "iva"},
                {text: "Total", value: "primaTotal"},
            ],

            // inspecciones
            showInspeccionesModal: false,
            inspeccionesItems: {},
            horarios: {},
            dateInspeccion: '',
            horarioSelected: {},
            datePickerParams: [
                {
                    dot: true,
                    key: 'today',
                    highlight: false,

                },
            ],

            // descuento adicional
            descuentoAdicional: {},

            // siniestralidad bloqueo
            siniestralidadBlock: false,
        };
    },
    mounted() {
        const self = this;
        if (self.tokenCotizacion === '') {
            this.cToken = 'view';
        }

        // validación de acceso custom
        if (!this.isPublic) {
            this.getUsers();
            this.getCodigosAgente();
        }

        this.loadData(function () {
            self.getFlujo();
        });

        self.getVehiculoCatalog();
        self.getFrecuenciaPagos();
        self.bringCatalogos();
    },
    unmounted() {
        if (this.lightboxAttachment) {
            this.lightboxAttachment.destroy();
            this.lightboxAttachment = null;
        }
    },
    computed: {
        ...mapGetters({
            authLogged: 'authLogged',
            authInfo: 'authInfo',
            loadRequest: 'loadRequest',
        }),

    },
    setup() {
        return {
            loadingImg,
        }
    },
    watch: {
        tokenProducto: function (val) {
            const self = this;
            this.loadData(function () {
                self.getFlujo();
            });
        },
        rveOn: function (val) {
            if (!val) {
                this.newEmailReenvio = '';
            }
        },
        rvwOn: function (val) {
            if (!val) {
                this.newWspReenvio = '';
            }
        },
        newEmailReenvio: function (value) {
            localStorage.setItem("cotCompNER", value);
        },
        newWspReenvio: function (value) {
            localStorage.setItem("cotCompNWR", value);
        },
        dateInspeccion: function (value) {
            const self = this;
            if (value) {
                self.getHorarios();
            }
        },
    },
    methods: {
        Toastify,
        loadData(callback) {
            const self = this;
            this.pToken = this.tokenProducto;
            this.cToken = this.tokenCotizacion;

            if (this.pToken !== '') {
                toolbox.doAjax('POST', 'productos/by/token/' + this.pToken, {
                    rc: true,
                }, function (response) {
                    if (response.status) {
                        self.producto = (typeof response.data[0] !== 'undefined' ? response.data[0] : {});
                        if (typeof callback === 'function') callback();

                        if (self.producto.cssCustom && !self.cssLoaded) {
                            const style = document.createElement('style');
                            style.textContent = self.producto.cssCustom;
                            document.head.append(style);
                            self.cssLoaded = true;
                        }
                        if (self.producto.jsCustom && !self.jsCustomLoaded) {
                            const script_tag = document.createElement('script');
                            script_tag.type = 'text/javascript';
                            script_tag.text = self.producto.jsCustom;
                            document.head.append(script_tag);
                            self.jsCustomLoaded = true;
                        }
                    } else {
                        toolbox.alert('Ha ocurrido un error obteniendo el producto', 'danger');
                    }
                }, function (response) {
                    toolbox.alert(response.msg, 'danger');
                },{load:'loadData'}, false)
            }
        },

        // Control de flujo
        isCoti() {
            return (this.cToken && this.cToken !== '' && this.cToken !== 'view');
        },
        iniciarCotizacion() {
            const self = this;
            toolbox.doAjax('POST', 'tareas/iniciar-cotizacion' + (self.isPublic ? '/public' : ''), {
                token: self.pToken,
                ca: self.codigoAgenteSelected,
            }, function (response) {
                if (response.status) {
                    self.cToken = response.data.token;
                    if (self.isPublic) {
                        self.$router.push('/f/' + self.pToken + '/' + self.cToken);
                    }
                    else {
                        self.$router.push('/cotizar/producto/' + self.pToken + '/' + self.cToken);
                    }
                    setTimeout(function () {
                        location.reload();
                    }, 800);
                } else {
                    toolbox.alert('Ha ocurrido un error obteniendo el producto', 'danger');
                }
            }, function (response) {
                toolbox.alert(response.msg, 'info');
            }, {load:'iniciarCotizacion'}, false)
        },
        getFlujo() {
            const self = this;
            if (self.cToken !== 'view') {
                const postObject = {token: self.cToken};
                if(self.vehiculoIdAgrupadorNodo !== 0 && self.flujoActivo.gVh === 'a') postObject['vehiculoIdAgrupadorNodo'] = self.vehiculoIdAgrupadorNodo;
                toolbox.doAjax('POST', 'tareas/calcular-paso' + (self.isPublic ? '/public' : ''), {
                    //token: self.cToken,
                    ...postObject
                }, async function (response) {
                    self.flujoActivo = response.data.actual;
                    self.flujoHasNext = (typeof response.data.next !== 'undefined' && response.data.next);
                    self.flujoHasPrev = (typeof response.data.prev !== 'undefined' && response.data.prev);
                    self.estadoCotizacion = response.data.estado;

                    if (typeof response.data.estado !== 'undefined' && !self.isPublic) {
                        self.getResumen();
                    }
                    if (typeof response.data.bit !== 'undefined' && !self.isPublic) {
                        self.bitacora = response.data.bit;
                        self.bitacoraReca = toolbox.prepareForTable(response.data.bitReca).map((e,i) => {return {'orden': response.data.bitReca.length - i, ...e}});
                    }
                    /*if (typeof response.data.visible !== 'undefined') {
                        self.noVisible = (response.data.visible !== '' ? response.data.visible : false);
                    }*/
                    if(!!response.data.inspeccion){
                        self.inspeccion = response.data.inspeccion
                    }
                    if (typeof response.data.c !== 'undefined') {
                        self.cotizacion = (response.data.c !== '' ? response.data.c : {});
                    }
                    if (typeof response.data.e !== 'undefined') {
                        self.estados = (response.data.e !== '' ? response.data.e : {});
                    }
                    if (typeof response.data.d !== 'undefined') {
                        self.allFieldsData = (response.data.d !== '' ? response.data.d : {});
                    }
                    if (typeof response.data.cG !== 'undefined') {
                        self.comentariosGen = (response.data.cG !== '' ? response.data.cG : {});
                    }

                    // se calcula si se muestra el flujo
                    if (self.estadoCotizacion === 'creada') {
                        self.showCotizacion = true;
                    }
                    else if (self.estadoCotizacion === 'expirada' || self.estadoCotizacion === 'expirada_opt') {
                        self.showCotizacion = false;
                        self.showCotizacionDesc = 'La tarea ha expirado';
                    }
                    else if (self.estadoCotizacion === 'cancelada') {
                        self.showCotizacion = false;
                        self.showCotizacionDesc = 'La tarea se ha cancelado';
                    }
                    else if (self.estadoCotizacion === 'finalizada') {
                        self.showCotizacion = false;
                        self.showCotizacionDesc = 'La tarea ha finalizado';
                    }
                    else {
                        self.showCotizacion = true;
                    }

                    // visibilidad
                    if (!self.flujoActivo) {
                        self.showCotizacion = false;
                        self.showCotizacionDesc = self.cotizacion.ed;
                    }
                    await self.filtrarCatalogos();
                    await self.campoCumpleCondiciones();
                    // self.previewAdjunto();
                    self.setSavedValuesForm(true);

                    // guardado de reenvío
                    const cotCompNER = localStorage.getItem("cotCompNER");
                    if (cotCompNER && cotCompNER !== '' && cotCompNER !== null) {
                        self.newEmailReenvio = cotCompNER;
                    }
                    const cotCompNWR = localStorage.getItem("cotCompNWR");
                    if (cotCompNER && cotCompNWR !== '' && cotCompNWR !== null) {
                        self.newWspReenvio = cotCompNWR;
                    }

                    // regreso el tab index para mostrar desde inicio la siguiente pantalla
                    self.currentTabIndex = 0;

                    self.getVehiclesForCoti();
                    self.getVehiclesCotizacion();

                }, function (response) {
                    toolbox.alert(response.msg, 'danger');
                }, {load:'getFlujo'}, false)
            }
        },
        setSavedValuesForm(replaceValues) {
            const self = this;
            if (!replaceValues) replaceValues = false;
            // colocar datos en campos guardados
            self.camposValores = {};
             /* Object.keys(self.allFieldsData).map(function (a) {
                self.camposValores[self.allFieldsData[a].id] = self.allFieldsData[a].valor;
            }) */

            if (replaceValues && !!(self.flujoActivo?.formulario?.secciones) ) {
                self.flujoActivo.formulario.secciones.forEach(function (seccion, seccionKey) {
                    self.flujoActivo.formulario.secciones[seccionKey].campos.forEach(function (a, b) {
                        if(a.tipoCampo === 'multiselect' || a.tipoCampo === 'checkbox'){
                            if(typeof a.valor !== 'object') a.valor = [];
                        }
                    })
                });
            }
        },
        getResumen() {
            const self = this;
            toolbox.doAjax('POST', 'tareas/get-resumen', {
                token: self.cToken,
            }, function (response) {
                self.resumen = response.data;
            }, function (response) {
                toolbox.alert(response.msg, 'danger');
            }, {load:'getResumen'}, false)
        },
        cancelarCotizacion(text) {
            const self = this;
            toolbox.confirm('Se cancelará la tarea, esta acción no se puede revertir, ¿desea continuar?', function () {

                toolbox.doAjax('POST', 'tareas/cambiar-estado' + (self.isPublic ? '/public' : ''), {
                    token: self.cToken,
                    estado: 'cancelada',
                }, function (response) {
                    toolbox.alert(response.msg);
                    self.getFlujo();
                }, function (response) {
                    toolbox.alert(response.msg, 'danger');
                }, {load:'cancelarCotizacion'}, false)
            })
        },
        editarUsuarioCotizacion() {
            const self = this;
            toolbox.confirm('Se cambiará el usuario asignado a esta tarea, ¿desea continuar?', function () {

                toolbox.doAjax('POST', 'tareas/cambiar-usuario', {
                    token: self.cToken,
                    usuarioId: self.usuarioEditar,
                }, function (response) {
                    toolbox.alert(response.msg);
                    self.getFlujo();
                }, function (response) {
                    toolbox.alert(response.msg, 'danger');
                }, {load:'editarUsuarioCotizacion'}, false)
            })
        },
        editarEstadoCotizacion() {
            const self = this;
            toolbox.confirm('Se cambiará el estado asignado a esta tarea, ¿desea continuar?', function () {

                toolbox.doAjax('POST', 'tareas/editar-estado', {
                    token: self.cToken,
                    estado: self.estadoEditar,
                }, function (response) {
                    toolbox.alert(response.msg);
                    self.getFlujo();
                }, function (response) {
                    toolbox.alert(response.msg, 'danger');
                }, {load:'editarEstadoCotizacion'}, false)
            })
        },
        getUsers() {

            const self = this;
            toolbox.doAjax('GET', 'users/list/active/store', {},
                function (response) {
                    //self.items = response.data;
                    self.users = [];
                    Object.keys(response.data).map(function (a, b) {
                        self.users.push({
                            value: response.data[a].id,
                            label: response.data[a].name + "(" + response.data[a].email + ")",
                        })
                    })
                },
                function (response) {
                    //toolbox.alert(response.msg, 'danger');
                }, {load:'getUsers'}, false)
        },
        getCodigosAgente() {

            const self = this;
            toolbox.doAjax('POST', 'get/codag', {},
                function (response) {
                    self.codigosAgente = response.data;

                    if (typeof self.codigosAgente[0] !== 'undefined') {
                        self.codigoAgenteSelected = self.codigosAgente[0];
                    }
                    else{
                        toolbox.alert('El usuario no tiene asignado ninún código de agente, por favor asígnelo', 'danger');
                    }
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                }, {load:'getCodigosAgente'}, false)
        },
        removeRequiredClass() {
            const self = this;
            if (typeof this.flujoActivo.formulario.secciones !== 'undefined') {
                self.flujoActivo.formulario.secciones.forEach(function (seccion, keySeccion) {
                    self.flujoActivo.formulario.secciones[keySeccion].campos.forEach(function (a) {
                        a.requeridoError = false;
                    });
                })
            }
        },
        continuarCotizacion(operacion, estado) {

            const self = this;

            let arrCampos = {};
            if (!estado) estado = false;
            let requeridosFail = false;
            let requeridosFailInSection = false;
            if(self.flujoActivo.gVh !== 'a') self.vehiculoIdAgrupadorNodo = 0;

            if (typeof this.flujoActivo.formulario.secciones !== 'undefined') {

                self.flujoActivo.formulario.secciones.forEach(function (seccion, keySeccion) {
                    self.flujoActivo.formulario.secciones[keySeccion].campos.forEach(function (a) {

                        if (typeof arrCampos[a.id] === 'undefined') arrCampos[a.id] = {};

                        // data del catálogo
                        if (a.catalogoId && a.catalogoId !== '') {
                            const dataTmp = self.obtenerItemsPorCatalogoMS(a.id, a.catalogoLabel, a.catalogoValue);
                            Object.keys(dataTmp).map(function (k) {
                                if (typeof dataTmp[k] !== 'undefined' && dataTmp[k].value === a.valor) {
                                    arrCampos[a.id]['vs'] = dataTmp[k].label;
                                }
                            })
                        }

                        arrCampos[a.id]['t'] = a.tipoCampo;
                        arrCampos[a.id]['v'] = a.valor;
                        arrCampos[a.id]['r'] = a.rechazado;

                        if (a.tipoCampo === 'currency') {
                            arrCampos[a.id]['v'] = a.valor.toString().replace(/\s/g, '');
                            const parts = arrCampos[a.id]['v'].split('.');
                            if (!parts[0]) arrCampos[a.id]['v'] = '0';
                            if (typeof parts[1] === 'undefined') {
                                arrCampos[a.id]['v'] += '.00'
                            } else if (parts[1].length === 0) {
                                arrCampos[a.id]['v'] += '00'
                            } else if (parts[1].length === 1) {
                                arrCampos[a.id]['v'] += '0'
                            }
                            //console.log()
                        }

                        if (a.tipoCampo === 'signature') {

                            if (typeof self.$refs['signature_' + a.id] !== 'undefined' && typeof self.$refs['signature_' + a.id][0] !== 'undefined' && typeof self.$refs['signature_' + a.id][0].undoSignature === 'function') {
                                const {isEmpty, data} = self.$refs['signature_' + a.id][0].saveSignature();
                                if (!isEmpty) {
                                    arrCampos[a.id]['v'] = data;
                                    a.valor = data;
                                }
                            }
                        }

                        const valoresCampo = [];
                        let selected = false;
                        if (a.tipoCampo === 'checkbox' || a.tipoCampo === 'multiselect') {
                            arrCampos[a.id]['v'] = [];
                            if (typeof a.catalogoId !== "undefined") {
                                arrCampos[a.id]['v'] = a.valor;
                            }
                        }

                        if (a.requerido && (a.valor === '' || !a.valor) && !a.group) {
                            if (typeof self.camposCumplidores[a.id] !== 'undefined' && self.camposCumplidores[a.id]) {
                                a.requeridoError = true;
                                requeridosFail = true;
                                if (parseInt(keySeccion) === parseInt(self.currentTabIndex)) {
                                    requeridosFailInSection = true;
                                }
                            }
                        } else {
                            a.requeridoError = false;
                        }

                        // catálogos
                        if (typeof a.catalogoId !== 'undefined' && a.catalogoId !== '' && a.catalogoId) {
                            if (typeof self.catalogos[a.id] !== 'undefined' && a.catalogoLabel) {
                                self.catalogos[a.id].forEach(function (b) {
                                    if (b[a.catalogoValue] === a.valor) {
                                        arrCampos[a.id]['vs'] = b[a.catalogoLabel];
                                    }
                                })
                            }
                        }

                        if (typeof a.vError !== 'undefined' && a.vError) {
                            requeridosFail = true;
                            requeridosFailInSection = true;
                            a.requeridoError = true;
                        }

                        // se autoguarda todo lo que esté invisible
                        if (self.flujoActivo.gVh === 'a' && a.valorCalculado !== '' && !a.visible) {
                            if (!a.requeridoError) {
                                self.saveWhenOnBlur(a);
                            }
                        }
                    });
                })
            }

            if (operacion === 'next') {

                // validación vehiculos
                let errorVehi = false;
                Object.keys(self.vehiculos).map(function(tmpkey){
                    Object.keys(self.vehiculos[tmpkey].cotizaciones).map(function (cotiTmpKey) {
                        if (typeof self.vehiculos[tmpkey].cotizaciones[cotiTmpKey].noOperable !== 'undefined' && self.vehiculos[tmpkey].cotizaciones[cotiTmpKey].noOperable) {
                            toolbox.alert('No se puede asegurar el vehículo con la suma asegurada ingresada', 'danger');
                            errorVehi = true;
                            return false;
                        }
                    })
                })

                if (errorVehi) {
                    return false;
                }

                if (requeridosFail && requeridosFailInSection) {
                    toolbox.alert('Debe llenar todos los campos requeridos', 'danger');
                    return false;
                }

                // validación de llenado de vehiculos
                if (self.flujoActivo.gVh === 'a') {
                    if (!self.vehiculoIdAgrupadorNodo) {
                        self.selectFirstVehiculo();
                    }
                    else {
                        const keys = Object.keys(self.vehiculosCot);
                        const nextItem = keys.at(keys.indexOf(this.vehiculoIdAgrupadorNodo) +1);

                        if (typeof self.vehiculosCot[nextItem] !== 'undefined' ) {

                            // guardado
                            if (typeof this.flujoActivo.formulario.secciones !== 'undefined') {
                                self.flujoActivo.formulario.secciones.forEach(function (seccion, keySeccion) {
                                    self.flujoActivo.formulario.secciones[keySeccion].campos.forEach(function (a) {
                                        // se autoguarda todo lo que esté invisible
                                        if (self.flujoActivo.gVh === 'a' && a.valorCalculado !== '' && !a.visible) {
                                            self.saveWhenOnBlur(a);
                                        }
                                    });
                                })
                            }


                            self.vehiculoIdAgrupadorNodo = nextItem;
                            setTimeout(function (){
                                self.calcularPasoVehicle(true);
                            }, 200);
                            return false;
                        }
                    }
                }

                if ((this.flujoActivo.formulario.secciones.length - 1) > this.currentTabIndex) {
                    self.removeRequiredClass();
                    const tmpNext = this.currentTabIndex + 1;
                    if (this.cambiarSeccion(tmpNext)) {
                        this.campoCumpleCondiciones();
                        return false;
                    }
                }

                if (this.flujoActivo.typeObject === 'vehiculo_comp'){
                    let countEmitir = 0;
                    for(let vehiculo in self.vehiculosCot){
                        for(let cotizacion in self.vehiculosCot[vehiculo]['c']){
                            if(self.vehiculosCot[vehiculo]['c'][cotizacion].emitirPoliza) countEmitir++;
                        }
                    }
                    if(countEmitir < 1) {
                        toolbox.alert((typeof self.allFieldsData['ERROR_COTIZACION_NO_SELECCIONADA'] !== 'undefined' ? self.allFieldsData['ERROR_COTIZACION_NO_SELECCIONADA'].valor : 'Debe seleccionar una cotización'), 'danger');
                        return false;
                    }
                }
            }
            /*console.log('murio');
            return false;*/

            if (operacion === 'prev') {
                this.currentTabIndex = 0;
            }

            const cambiarEstado = function () {
                toolbox.doAjax('POST', 'tareas/cambiar-estado' + (self.isPublic ? '/public' : ''), {
                    token: self.cToken,
                    paso: operacion,
                    seccionKey: self.currentTabIndex,
                    campos: arrCampos,
                    rG: self.comentarioGeneralRechazo,
                    estado: estado,
                    vehiculosCot: self.vehiculosCot,
                    vehiculoIdAgrupadorNodo: self.vehiculoIdAgrupadorNodo,
                }, function (response) {
                    self.vehiculoIdAgrupadorNodo = 0;
                    toolbox.alert(response.msg);
                    window.scrollTo({top: 0, behavior: 'smooth'});
                    self.getFlujo();
                }, function (response) {
                    self.getFlujo();
                    Swal.fire({
                        title: response.msg || 'Ha ocurrido un error al cambiar de paso, por favor, intente de nuevo',
                        showCancelButton: false,
                        confirmButtonText: 'Continuar',
                    })
                    //toolbox.alert(response.msg, 'danger');
                }, {load:'cambiarEstado'}, false)
            }

            if(Object.values(self.loadRequest).every(e => e === false)){
                if (estado === 'finalizada') {
                    toolbox.confirm('Si finaliza la tarea no podrá volver a editarla', function () {
                        cambiarEstado();
                    })
                } else {
                    cambiarEstado();
                }
            }
        },
        selectFirstVehiculo() {
            const self = this;
            for(let vehiculoId in self.vehiculosCot) {
                self.vehiculoIdAgrupadorNodo = vehiculoId;
                setTimeout(function (){
                    self.calcularPasoVehicle(true);
                }, 200);
                return false;
            }
        },
        execRegex(regex, valor) {
            const arr = regex.exec(valor);
            if (arr) {
                return arr;
            } else {
                return false
            }
        },
        async campoCumpleCondiciones(inputField, isForFilter = true) {

            const self = this;
            self.camposValores = {};
            Object.keys(this.allFieldsData).map(function (a) {
                self.camposValores[self.allFieldsData[a].id] = self.allFieldsData[a].valor;
            });

            const conditionEvaluator = {
                '=': function (valueA, valueB, beforeValue, glue) {
                    console.log(String(valueA))
                    console.log(String(valueB))
                    const result = String(valueA) === String(valueB);
                    /*console.log(result)
                    console.log(beforeValue)*/
                    return glue === 'AND' ? beforeValue && result : beforeValue || result
                },
                '<': function (valueA, valueB, beforeValue, glue) {
                    const result = parseFloat(valueA) < parseFloat(valueB);
                    return glue === 'AND' ? beforeValue && result : beforeValue || result
                },
                '<=': function (valueA, valueB, beforeValue, glue) {
                    const result = parseFloat(valueA) <= parseFloat(valueB);
                    return glue === 'AND' ? beforeValue && result : beforeValue || result
                },
                '>': function (valueA, valueB, beforeValue, glue) {
                    const result = parseFloat(valueA) > parseFloat(valueB);
                    return glue === 'AND' ? beforeValue && result : beforeValue || result
                },
                '>=': function (valueA, valueB, beforeValue, glue) {
                    const result = parseFloat(valueA) >= parseFloat(valueB);
                    return glue === 'AND' ? beforeValue && result : beforeValue || result
                },
                '<>': function (valueA, valueB, beforeValue, glue) {
                    const result = valueA !== valueB;
                    return glue === 'AND' ? beforeValue && result : beforeValue || result
                },
                'like': function (valueA, valueB, beforeValue, glue) {
                    const result = valueA.toLowerCase().includes(valueB.toLowerCase());
                    return glue === 'AND' ? beforeValue && result : beforeValue || result
                },
            };

            if (typeof this.flujoActivo.formulario.secciones !== 'undefined' && typeof this.flujoActivo.formulario.secciones[this.currentTabIndex] !== 'undefined') {

                self.flujoActivo.formulario.secciones.forEach(function (seccion, seccionKey) {

                    if (seccion.condiciones.length > 0 && !seccion.condiciones[0].campoId) {
                        self.flujoActivo.formulario.secciones[seccionKey].show = true;
                    }

                    self.flujoActivo.formulario.secciones[seccionKey].campos.forEach(function (a, b) {
                        self.allFieldsLabel[a.id] = a.nombre;
                        self.camposValores[a.id] = ((a.valor) ? a.valor.toString() : '');

                        // validación de catálogos, si dependen otros depués de el
                        if (typeof a.catFId !== 'undefined' && a.catFId !== '' && a.catFId) {
                            a.catFId = a.catFId.toString();
                            if (a.catFId !== '') self.catalogosCheckDepends[a.catFId] = a.catFId;
                        }
                    })

                    self.flujoActivo.formulario.secciones[seccionKey].campos.forEach(function (a, b) {

                        self.camposCumplidores[a.id] = true;

                        // Validación de valores
                        if (a.tipoCampo === 'number') {
                            if (parseFloat(a.valor) > parseFloat(a.longitudMax)) {
                                a.valor = a.longitudMax;
                                toolbox.alert('Valor máximo ' + a.longitudMax, 'danger');
                            } else if (parseFloat(a.valor) < parseFloat(a.longitudMin)) {
                                a.valor = a.longitudMin;
                                toolbox.alert('Valor mínimo ' + a.longitudMin, 'danger');
                            }
                        }

                        if (typeof a.valorCalculado !== 'undefined' && a.valorCalculado) {

                            let valorCalculadoOpt = (a.valorCalculado !== '') ? a.valorCalculado : '';
                            Object.keys(self.camposValores).map(function (key) {
                                valorCalculadoOpt = valorCalculadoOpt.replaceAll("{{" + key + "}}", self.camposValores[key]);
                            })

                            /*if (typeof self.userVars !== 'undefined') {
                                self.userVars.forEach(function (uvar) {
                                    valorCalculadoOpt = valorCalculadoOpt.replaceAll("{{"+uvar.nombre+"}}", uvar.valor);
                                })
                            }*/

                            // Funciones
                            let hasDate = false;
                            let valorCalculado = '';

                            // FN.EDAD
                            let regex = self.execRegex(/FN.EDAD\((.*)\)/g, valorCalculadoOpt);
                            if (regex) {
                                let data = (regex[1]) ? regex[1] : '';
                                data = data.replaceAll('"', '').replaceAll("'", '').split(',');
                                const date = (data[0]) ? data[0] : false;
                                const formatInput = (data[1]) ? data[1] : false;
                                let value = '';
                                if (date && formatInput) {
                                    value = dayjs(date, formatInput);
                                    value = dayjs().diff(value, 'year', false)
                                }
                                valorCalculadoOpt = valorCalculadoOpt.replace(/FN.EDAD\((.*)\)/g, value);
                            }

                            // FN.SUMARDIAS
                            regex = self.execRegex(/FN.SUMARDIAS\((.*)\)/g, valorCalculadoOpt);
                            if (regex) {
                                let data = (regex[1]) ? regex[1] : '';
                                data = data.replaceAll('"', '').replaceAll("'", '').split(',');
                                const date = (data[0]) ? data[0] : false;
                                const formatInput = (data[1]) ? data[1] : false;
                                const formatOutput = (data[2]) ? data[2] : 0;
                                const dias = (data[3]) ? data[3] : 0;
                                let value = '';
                                if (date && formatInput && dias) {
                                    value = dayjs(date, formatInput).add(parseInt(dias), 'day');
                                    value = value.format(formatOutput);
                                }
                                valorCalculadoOpt = valorCalculadoOpt.replace(/FN.SUMARDIAS\((.*)\)/g, value);
                                hasDate = true;
                            }

                            valorCalculado = valorCalculadoOpt;

                            //console.log(valorCalculadoOpt);
                            if (!hasDate && valorCalculadoOpt !== '') {
                                try {
                                    valorCalculado = new Function('return ' + valorCalculadoOpt)();
                                } catch (e) {
                                    valorCalculado = '';
                                    console.log('Error al realizar campo calculado' + e);
                                }
                            }

                            if (!valorCalculado) valorCalculado = '';

                            // reemplazo el valor
                            a.valor = valorCalculado.toString();
                            self.camposValores[a.id] = valorCalculado.toString();
                            // console.log(self.camposValores[a.id]);
                        }

                        if (typeof a.dependOn !== 'undefined') {
                            if (typeof a.dependOn[0] !== 'undefined' && typeof a.dependOn[0].campoId !== 'undefined' && a.dependOn[0].campoId) {
                                self.camposCumplidores[a.id] = false;
                            }
                        }
                    })

                    // JS POST
                    self.flujoActivo.formulario.secciones[seccionKey].campos.forEach(function (a, b) {
                        if (inputField && inputField.id === a.id) {
                            // jsPost
                            let jsTmp = '';
                            if (typeof a.jsPost !== 'undefined' && a.jsPost && a.jsPost !== '') {
                                jsTmp = a.jsPost;
                                Object.keys(self.camposValores).map(function (key) {
                                    jsTmp = jsTmp.replaceAll("{{" + key + "}}", self.camposValores[key]);
                                })
                                if (jsTmp !== '') {
                                    try {
                                        const tmpJsRes = new Function('return (function(){' + jsTmp + '})();')();
                                        if (!tmpJsRes) {
                                            let mensajeJs = "Error en validación de " + a.nombre;
                                            if(a.jsPostAlert) mensajeJs = a.jsPostAlert;
                                            toolbox.alert(mensajeJs, 'danger');
                                            self.flujoActivo.formulario.secciones[self.currentTabIndex].campos[b].vError = true;
                                        } else {
                                            self.flujoActivo.formulario.secciones[self.currentTabIndex].campos[b].vError = false;
                                        }
                                    } catch (e) {
                                        console.log('Error al evaluar JS post llenado' + e);
                                    }
                                }
                            }
                        }
                    })

                    self.flujoActivo.formulario.secciones[seccionKey].campos.forEach(function (a, b) {
                        if (typeof a.dependOn !== 'undefined') {

                            a.dependOn.forEach(function (item, index) {
                                const useCampoVar = (typeof item.campoVar !== 'undefined' && item.campoVar !== '' && item.campoVar);
                                if (item.campoId || useCampoVar) {

                                    const identiVar = (useCampoVar) ? item.campoVar.replace('{{', '').replace('}}', '') : item.campoId;
                                    // console.log(identiVar);

                                    if (typeof self.camposValores[identiVar] !== 'undefined') {

                                        const tmpValue = self.camposValores[identiVar];
                                        console.log(a.id);
                                        console.log(index);
                                        console.log(identiVar);
                                        /*console.log(tmpValue);
                                        console.log(item.campoValue);*/

                                        // console.log(self.camposValores[identiVar])
                                        let glue = index === 0 ? 'OR' : item.glue;

                                        /*if (useCampoVar) {
                                            glue = index === 0 ? 'AND' : item.glue;
                                        }*/

                                        self.camposCumplidores[a.id] =
                                            typeof conditionEvaluator[item.campoIs] === 'function'
                                                ? conditionEvaluator[item.campoIs](tmpValue, item.campoValue, self.camposCumplidores[a.id], glue)
                                                : false;
                                        console.log(self.camposCumplidores[a.id]);

                                    }
                                }
                            })
                        }
                    })
                })

                // validacion de secciones
                self.flujoActivo.formulario.secciones.forEach(function (seccion, seccionKey) {

                    if (typeof seccion.condiciones != 'undefined' && seccion.condiciones.length > 0) {
                        seccion.condiciones.forEach(function (item, index) {

                            const useCampoVar = (typeof item.campoVar !== 'undefined' && item.campoVar !== '' && item.campoVar);

                            if (item.campoId || useCampoVar) {

                                const identiVar = (useCampoVar) ? item.campoVar.replace('{{', '').replace('}}', '') : item.campoId;

                                if (typeof self.camposValores[identiVar] !== 'undefined') {
                                    let glue = item.glue;
                                    if (index === 0) {
                                        self.flujoActivo.formulario.secciones[seccionKey].show = false;
                                        glue = 'OR';
                                    }

                                    const tmpValue = self.camposValores[identiVar];

                                    self.flujoActivo.formulario.secciones[seccionKey].show =
                                        typeof conditionEvaluator[item.campoIs] === 'function' ?
                                            conditionEvaluator[item.campoIs](
                                                tmpValue,
                                                item.value,
                                                (useCampoVar ? false : self.flujoActivo.formulario.secciones[seccionKey].show),
                                                glue
                                            )
                                            : false
                                } else {
                                    self.flujoActivo.formulario.secciones[seccionKey].show = false;
                                }
                            }
                        })
                    } else {
                        self.flujoActivo.formulario.secciones[seccionKey].show = true;
                    }
                })
            }

            for (const seccion of self.flujoActivo.formulario.secciones) {
                for (const campo of seccion.campos) {
                    // Llamada a filtro si no viene input
                    if (!inputField && typeof self.catalogosCheckDepends[campo.id] !== 'undefined' && campo.valor !== '') {
                        if (isForFilter) {
                            await self.filtrarCatalogos(campo);
                        }
                    }
                }
            }

            if (inputField && typeof self.catalogosCheckDepends[inputField.id] !== 'undefined') {
                if(isForFilter) await self.filtrarCatalogos(inputField);
            }
        },
        cambiarSeccion(seccion) {

            const nextSection = (typeof this.flujoActivo.formulario.secciones[seccion] !== 'undefined') ? this.flujoActivo.formulario.secciones[seccion] : false;

            if (!nextSection) return false;

            // Filtrar las secciones que cumplen con las condiciones
            this.campoCumpleCondiciones();

            // si está visible
            if (nextSection.show) {
                this.currentTabIndex = seccion;
                return true;
            } else {
                return false;
            }
        },

        // archivos adjuntos
        handleUpload(file, campoId, load, error, progress, isOCR) {
            if (file) {

                if (!isOCR) isOCR = false;
                let vehicleNumber = false;

                const self = this;

                // creo la data
                const formData = new FormData();
                formData.append('file', file);
                formData.append('seccionKey', this.currentTabIndex);
                formData.append('token', self.cToken);
                formData.append('campoId', campoId);
                formData.append('isOCR', isOCR);
                formData.append('tp', this.flujoActivo.ocrTpl);
                formData.append('vehiGroup', this.vehiculoIdAgrupadorNodo);

                if (this.vehiculoIdAgrupadorNodo) {
                    vehicleNumber = (typeof this.vehiculosCot[this.vehiculoIdAgrupadorNodo].n !== 'undefined') ? this.vehiculosCot[this.vehiculoIdAgrupadorNodo].n : false;
                }

                formData.append('vehicleNumber', vehicleNumber);

                if(self.flujoActivo.gVh === 'a')  formData.append('vehiculoIdAgrupadorNodo', this.vehiculoIdAgrupadorNodo);

                toolbox.doAjax('FILE', 'tareas/upload-file' + (self.isPublic ? '/public' : ''), formData,
                    function (response) {

                        if (response.status) {
                            self.flujoActivo.formulario.secciones[self.currentTabIndex].campos.forEach(function (a, b) {
                                if (campoId === a.id) {
                                    self.flujoActivo.formulario.secciones[self.currentTabIndex].campos[b].valor = '__SKIP__FILE__';
                                }
                            })

                            if (isOCR) {
                                self.loadData(function () {
                                    self.getFlujo();
                                });
                            }

                            toolbox.alert(response.msg, 'success');

                            setTimeout(() => {
                                self.$refs.filepondInputOCR.removeFile()
                            }, 5000);

                        }
                        load();
                        self.previewAdjunto();
                    },
                    function (response) {
                        error('Error en subida de archivo');
                        toolbox.alert(response.msg, 'danger');
                    }, {load:'handleUpload'}, false)
            } else {
                // Indicar que no se ha seleccionado ningún archivo
                error('No se ha seleccionado ningún archivo');
            }
        },
        previewAdjunto() {
            const self = this;
            if (!this.isPublic) {
                toolbox.doAjax('POST', 'tareas/file-get-preview', {
                    token: self.cToken,
                    seccionKey: self.currentTabIndex,
                }, function (response) {
                    self.previewFiles = response.data;

                    Object.keys(self.previewFiles).map(function (key) {
                        if (self.previewFiles[key].url !== '' && self.previewFiles[key].type) {

                            // se guarda el download
                            self.previewFiles[key].download = self.previewFiles[key].url;

                            if (self.previewFiles[key].type === 'signature') {
                                self.signatureData[self.previewFiles[key].name] = self.previewFiles[key].url;
                            } else if (self.previewFiles[key].type === 'image') {
                            } else if (self.previewFiles[key].type === 'pdf') {
                                self.previewFiles[key].url = 'filetypes/PDF.png';
                            } else if (self.previewFiles[key].type === 'DOCX' || self.previewFiles[key].type === 'DOC') {
                                self.previewFiles[key].url = 'filetypes/DOCX.png';
                            }
                        }
                    })
                    self.campoCumpleCondiciones(null, false);

                    if (!self.lightboxAttachment) {
                        self.lightboxAttachment = new PhotoSwipeLightbox({
                            gallery: '#fileAttachGallery',
                            children: 'a',
                            pswpModule: () => import('photoswipe'),
                            showHideAnimationType: 'none',
                            history: false,
                            focus: false,
                            showAnimationDuration: 0,
                            hideAnimationDuration: 0,
                            zoomEl: true,
                            clickToCloseNonZoomable: false,
                            maxSpreadZoom: 6,
                            pinchToClose: false,
                            initialZoomLevel: 1,
                            maxZoomLevel: 6,
                            secondaryZoomLevel: (zoomLevelObject) => {
                                return 6;
                            }
                        });
                        self.lightboxAttachment.on('uiRegister', function () {
                            self.lightboxAttachment.pswp.ui.registerElement({
                                name: 'download-button',
                                order: 8,
                                isButton: true,
                                tagName: 'a',

                                // SVG with outline
                                html: {
                                    isCustomSVG: true,
                                    inner: '<path d="M20.5 14.3 17.1 18V10h-2.2v7.9l-3.4-3.6L10 16l6 6.1 6-6.1ZM23 23H9v2h14Z" id="pswp__icn-download"/>',
                                    outlineID: 'pswp__icn-download'
                                },
                                onInit: (el, pswp) => {
                                    el.setAttribute('download', '');
                                    el.setAttribute('target', '_blank');
                                    el.setAttribute('rel', 'noopener');
                                    pswp.on('change', () => {
                                        el.href = pswp._initialItemData.element.getAttribute('data-download');
                                    });
                                }
                            });
                        });
                        self.lightboxAttachment.init();
                    }
                }, function (response) {
                    toolbox.alert(response.msg, 'danger');
                }, {load:'previewAdjunto'}, false)
            } else {
                self.campoCumpleCondiciones(null, false);
            }
        },
        openPreview(file) {
            if (file.type === 'image') {
                this.showImagePreview = true;
                this.imagePreviewTmp = file;
            } else {
                window.open(file.download);
            }
        },

        // Ver progresión
        verProgresion() {

            const self = this;
            toolbox.doAjax('POST', 'tareas/get-progression', {
                token: self.cToken,
            }, function (response) {
                self.showProgresion = true;
                self.progresion = response.data;
            }, function (response) {
                toolbox.alert(response.msg, 'danger');
            }, {load:'verProgresion'}, false)
        },
        validarSiniestralidad() {

            const self = this;
            toolbox.doAjax('POST', 'tareas/calcular-siniestralidad', {
                token: self.cToken,
            }, function (response) {
                self.showsiniestralidad = true;
                self.siniestralidad = response.data;
                self.itemsTable = toolbox.prepareForTable(response.data.vehiculo);
                if (typeof response.data.siniesBlock !== 'undefined') {
                    self.siniestralidadBlock = parseInt(response.data.siniesBlock);
                }
            }, function (response) {
                toolbox.alert(response.msg, 'danger');
            }, {load:'validarSiniestralidad'}, false)
        },
        getInspecciones() {

            const self = this;
            toolbox.doAjax('POST', 'tareas/inspecciones', {
                token: self.cToken,
            }, function (response) {
                self.showInspeccionesModal = true;
                self.inspeccionesItems = response.data;
                self.getHorarios();
            }, function (response) {
                toolbox.alert(response.msg, 'danger');
            },false)
        },
        getHorarios() {
            const self = this;
            toolbox.doAjax('POST', 'inspecciones/get-horarios', {
                    date: self.dateInspeccion,
                },
                function (response) {
                    self.horarios = response.data.horario;
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
        iniciarAgendamiento(autoId, direccion) {
            const self = this;
            if(!direccion){
                toolbox.alert('Llenar dirección para la inspección', 'danger');
                return false;
            };
            toolbox.confirm('Se generará una inspección, ¿desea continuar?', function () {

                toolbox.doAjax('POST', 'inspecciones/start-agenda', {
                        autoId: autoId,
                        date: self.dateInspeccion,
                        time: self.horarioSelected,
                        type: self.inspeccionType,
                        direccion,
                    },
                    function (response) {
                        if (response.status) {
                            toolbox.alert('Inspección agendada con éxito', 'success');
                            self.getInspecciones();
                        }
                        else {
                            toolbox.alert('Ha ocurrido un error obteniendo el producto', 'danger');
                        }
                    },
                    function (response) {
                        toolbox.alert(response.msg, 'danger');
                    })
            })
        },
        iniciarAutoInspeccion(autoId, direccion) {
            const self = this;
            if(!direccion){
                toolbox.alert('Llenar dirección para la inspección', 'danger');
                return false;
            };
            toolbox.confirm('Se generará una autoinspección, ¿desea continuar?', function () {

                toolbox.doAjax('POST', 'inspecciones/start-auto', {
                        vehiculoId: autoId,
                        token: self.cToken,
                        direccion,
                    },
                    function (response) {
                        if (response.status) {
                            toolbox.alert('Inspección agendada con éxito', 'success');
                            self.getInspecciones();
                        }
                        else {
                            toolbox.alert('Ha ocurrido un error obteniendo el producto', 'danger');
                        }
                    },
                    function (response) {
                        toolbox.alert(response.msg, 'danger');
                    })
            })

        },
        importDataInspecciones() {
            const self = this;
            toolbox.confirm('Se importara la data de las inspecciones, ¿desea continuar?', function () {

                toolbox.doAjax('POST', 'inspecciones/import-data', {
                        token: self.cToken
                    },
                    function (response) {
                        if (response.status) {
                            toolbox.alert('Inspecciones importadas con éxito', 'success');
                            self.getFlujo();
                        }
                        else {
                            toolbox.alert('Ha ocurrido un error', 'danger');
                        }
                    },
                    function (response) {
                        toolbox.alert(response.msg, 'danger');
                    })
            })
        },
        resetSignature(ref, reset, currentTabIndex, keyInput) {
            if (!reset) reset = false;
            if (typeof this.$refs[ref] !== 'undefined' && typeof this.$refs[ref][0] !== 'undefined' && typeof this.$refs[ref][0].clearSignature === 'function') {
                this.$refs[ref][0].clearSignature();
                if (reset) {
                    this.flujoActivo.formulario.secciones[currentTabIndex].campos[keyInput].valor = '';
                }
            }
        },

        // catalogos
        async filtrarCatalogos(inputID) {

            if (!inputID) inputID = {};

            const self = this;
            await toolbox.doAjax('POST', 'tareas/calcular-catalogo/public', {
                'token': self.cToken,
                'depends': (typeof inputID.id !== 'undefined') ? inputID.id : '',
                'value': (typeof inputID.valor !== 'undefined') ? inputID.valor : '',
            },  function (response) {
                if (response.status) {
                    if (typeof inputID.id === 'undefined' || (typeof inputID.id !== 'undefined' && !inputID)) {
                        self.catalogos = response.data;
                    } else {
                        Object.keys(response.data).map(function (a) {
                            const valueA = response.data[a];

                            if (inputID.id !== a) {
                                self.catalogos[a] = response.data[a];
                            }
                        });
                    }
                } else {
                    toolbox.alert('Ha ocurrido un error obteniendo el producto', 'danger');
                }
            }, function (response) {
                toolbox.alert(response.msg, 'danger');
            }, {load:'filtrarCatalogos'}, false)
        },
        obtenerItemsPorCatalogo(inputId) {

            if (typeof this.catalogos[inputId] !== 'undefined') {
                return this.catalogos[inputId];
            } else {
                return [];
            }
        },
        obtenerItemsPorCatalogoMS(inputId, catalogoLabel, catalogoValue) {
            const tmp = [];
            const self = this;
            if (typeof self.catalogos[inputId] !== 'undefined') {
                self.catalogos[inputId].forEach(function (a) {
                    tmp.push({label: a[catalogoLabel], value: a[catalogoValue]});
                })
                return tmp;
            } else {
                return [];
            }
        },

        goToProduct() {
            this.$router.push('/f/' + this.pToken + '/view');
            setTimeout(function () {
                location.reload();
            }, 500);
        },

        revivirCotizacion() {
            const self = this;
            if (!this.isPublic) {
                toolbox.confirm('Se creará una nueva tarea, ¿desea continuar?', function () {
                    toolbox.doAjax('POST', 'tareas/revivir-cotizacion', {
                        token: self.cToken,
                    }, function (response) {
                        //console.log(response.data.token);
                        self.$router.push('/cotizar/producto/' + self.pToken + '/' + response.data.token);
                        setTimeout(function () {
                            location.reload();
                        }, 800);
                    }, function (response) {
                        toolbox.alert(response.msg, 'danger');
                    }, {load:'revivirCotizacion'}, false)
                })
            }
        },

        // Comentarios
        getComentarios() {
            const self = this;
            toolbox.doAjax('POST', 'tareas/comment/get', {
                token: self.cToken,
            }, function (response) {
                self.showComentariosBar = true;
                self.comentarios = response.data;
            }, function (response) {
                toolbox.alert(response.msg, 'danger');
            }, {load:'getComentarios'}, false)
        },
        sendComentario() {
            const self = this;

            if (this.isPublic) {
                self.comentarioAcceso = 'publico';
            }

            toolbox.doAjax('POST', 'tareas/comment/save', {
                token: self.cToken,
                comment: self.comentarioTmp,
                comentarioAcceso: self.comentarioAcceso,
            }, function (response) {
                self.comentarioTmp = '';
                self.getComentarios()
            }, function (response) {
                toolbox.alert(response.msg, 'danger');
            }, {load:'sendComentario'}, false)
        },

        // soporte
        getSoporteComentarios(siniestralidad) {
            const self = this;

            if (!siniestralidad) siniestralidad = false;

            toolbox.doAjax('POST', 'tareas/support/get', {
                token: self.cToken,
            }, function (response) {
                self.showSoporteBar = true;
                self.soporteTipos = JSON.parse(self.allFieldsData.FLUJOS_SOPORTE.valor);
                self.soporteTipoSelected = Object.keys(self.soporteTipos)[0];

                if (siniestralidad > 0) {
                    self.soporteComentario = "Cliente con siniestralidad del "+ siniestralidad + "%, se solicita autorización";
                }
                else {
                    self.soporteComentario = "Se solicita soporte para el caso No. " + self.cotizacion.no;
                }

                self.soportes = response.data;
            }, function (response) {
                toolbox.alert(response.msg, 'danger');
            }, {load:'getSoporteComentarios'}, false)
        },
        getVariablesTest() {
            const self = this;

            toolbox.doAjax('POST', 'tareas/vrtest', {
                token: self.cToken,
            }, function (response) {
                self.showVarBar = true;
                self.varsTest = response.data;
            }, function (response) {
                toolbox.alert(response.msg, 'danger');
            },  true)
        },
        sendSoporteComentario() {
            const self = this;

            toolbox.doAjax('POST', 'tareas/support/save', {
                token: self.cToken,
                comment: self.soporteComentario,
                soporteTipoSelected: self.soporteTipoSelected,
            }, function (response) {
                self.soporteComentario = '';
                self.getSoporteComentarios()
            }, function (response) {
                toolbox.alert(response.msg, 'danger');
            }, {load:'sendSoporteComentario'}, false)
        },
        verSoporteDetalle(link) {
            const self = this;

            self.showSoporteFrame = true;
            self.showSoporteFrameUrl = 'https://wk.seguroselroble.com/#/solicitar/producto/' + link;
            //window.open(self.showSoporteFrameUrl);

            /*toolbox.doAjax('POST', 'tareas/support/detail', {
                token: token,
            }, function (response) {
                self.showSoporteFrameUrl = response.data.link;
                // self.soportes[key].detalle = response.data.data;

            }, function (response) {
                toolbox.alert(response.msg, 'danger');
            })*/
        },
        verSoporteDetalleTab(link) {
            const self = this;

            //self.showSoporteFrame = true;
            //self.showSoporteFrameUrl = 'https://wk.seguroselroble.com/#/solicitar/producto/' + link;
            window.open(link);

            /*toolbox.doAjax('POST', 'tareas/support/detail', {
                token: token,
            }, function (response) {
                self.showSoporteFrameUrl = response.data.link;
                // self.soportes[key].detalle = response.data.data;

            }, function (response) {
                toolbox.alert(response.msg, 'danger');
            })*/
        },

        // Check Select
        selectedMultiValue(input, value, keyInput, currentTabIndex) {
            const self = this;
            self.campoCumpleCondiciones(input);
            if(!self.flujoActivo.formulario.secciones[currentTabIndex].campos[keyInput].valor){
                self.flujoActivo.formulario.secciones[currentTabIndex].campos[keyInput].valor = []
            }
            ;
            if (self.flujoActivo.formulario.secciones[currentTabIndex].campos[keyInput].valor.includes(value)) {
                self.flujoActivo.formulario.secciones[currentTabIndex].campos[keyInput].valor =
                    self.flujoActivo.formulario.secciones[currentTabIndex].campos[keyInput].valor.filter(e => e !== value)
            } else {
                self.flujoActivo.formulario.secciones[currentTabIndex].campos[keyInput].valor.push(value)
            }
        },

        // Currency
        toConvertDecimal(value) {
            value = value.toString();
            const convertToFormat = (numberStr) => {
                numberStr = numberStr.toString();
                const formattedNumber = numberStr
                    .replace(/,/g, '')
                    .split('')
                    .reverse()
                    .reduce((result, digit, index) => {
                        return (index % 3 === 0 && index !== 0) ? `${result},${digit}` : result + digit;
                    }, '');
                return formattedNumber.split('').reverse().join('')
            };
            const parts = value.split('.');
            const decimal = parts.length > 1 ? '.' + parts[1].slice(0, 2).replace(/[^0-9,]/g, '') : '';
            const numberInformat = convertToFormat(parts[0].replace(/[^0-9,]/g, '')) + decimal;
            return numberInformat;
        },
        toSaveNumber(event, key, currentTabIndex) {
            const self = this;
            const pivot = event.target.value;
            const parts = pivot.split('.');
            let decimal = parts.length > 1 ? '.' + parts[1].slice(0, 2).replace(/[^0-9]/g, ' ') : '';
            if (parts.length > 1 && parts[1].length > 2) decimal += Array(parts[1].length - 2).fill(' ').join('');
            if (parts.length > 2) decimal += Array(parts.length - 2).fill(' ').join('')
            const numberInformat = parts[0].replace(/[^0-9]/g, ' ').replace(/,/g, '');
            self.flujoActivo.formulario.secciones[currentTabIndex].campos[key].valor = numberInformat + decimal;

            const preValue = self.flujoActivo.formulario.secciones[currentTabIndex].campos[key];

            const formateoIndex = self.flujoActivo.formulario.secciones[currentTabIndex].campos.findIndex(
                camp => camp.id === `${preValue.id}_FORMATEADO`
            );

            if (formateoIndex !== -1) {
                self.flujoActivo.formulario.secciones[currentTabIndex].campos[formateoIndex].valor =
                    self.toConvertDecimal(preValue.valor)
            }
        },
        toRefactorCurrency(event, key, currentTabIndex) {
            const self = this;
            const pivot = event.target.value;
            const parts = pivot.split('.');
            let valor = pivot;
            if (parts.length > 1) {
                if (parts[1].length === 1)
                    valor = pivot + '0';
                if (parts[1].length === 0)
                    valor = pivot + '00';
            }
            if (parts.length === 1) {
                valor = pivot + '.00'
            }

            self.flujoActivo.formulario.secciones[currentTabIndex].campos[key].valor = valor
            const preValue = self.flujoActivo.formulario.secciones[currentTabIndex].campos[key];
            const formateoIndex = self.flujoActivo.formulario.secciones[currentTabIndex].campos.findIndex(
                camp => camp.id === `${preValue.id}_FORMATEADO`
            );

            if (formateoIndex !== -1) {
                self.flujoActivo.formulario.secciones[currentTabIndex].campos[formateoIndex].valor =
                    self.toConvertDecimal(valor)
            }

        },
        setCurrency(value) {
            return /*this.cotizacionMoneda + " " +*/"Q. " + this.toConvertDecimal(value);
        },

        // reenvío
        reenviarCoti(tipoReenvio) {
            const self = this;

            toolbox.doAjax('POST', 'tareas/reenviar-salida', {
                token: self.cToken,
                tipo: tipoReenvio,
                newEmailReenvio: self.newEmailReenvio,
                newWspReenvio: self.newWspReenvio,
            }, function (response) {
                toolbox.alert(response.msg, 'success');
                self.getFlujo();
            }, function (response) {
                toolbox.alert(response.msg, 'danger');
            }, {load:'reenviarCoti'}, false)
        },

        reenviarAdjunto(tipoReenvio) {
            const self = this;
            const attachments = [];
            for (let adjunto in self.previewFiles){
                if(!!self.previewFiles[adjunto].rveOn) attachments.push(adjunto)
            }

            if(attachments.length < 1){
                toolbox.alert('No se han seleccionado documentos', 'danger');
                return false
            }

            if(tipoReenvio === 'whatsapp' && !self.newWspReenvioAdjunto){
                toolbox.alert('Agregar celular', 'danger');
                return false
            }

            if(tipoReenvio === 'email' && !self.newEmailReenvioAdjunto){
                toolbox.alert('Agregar correo electronico', 'danger');
                return false
            }

            toolbox.doAjax('POST', 'tareas/send-attach', {
                token: self.cToken,
                tipo: tipoReenvio,
                newEmailReenvio: self.newEmailReenvioAdjunto,
                newWspReenvio: self.newWspReenvioAdjunto,
                attachments,
            }, function (response) {
                toolbox.alert(response.msg, 'success');
                self.newEmailReenvioAdjunto = '';
                self.newWspReenvioAdjunto = '';
                self.getFlujo();
            }, function (response) {
                toolbox.alert(response.msg, 'danger');
            }, {load:'reenviarAdjunto'}, false)
        },

        changeImagenPreview(pdf) {
            const self = this;
            self.showPdfPreview = !self.showPdfPreview;
            if (pdf) {
                self.pdfSource = pdf.basePDF;
                self.pdfUrl = pdf.download
            }
        },

        openBlank() {
            const self = this;
            window.open(self.pdfUrl, '_blank')
        },

        // rechazar aprobar
        rechazarTodo(selectAll) {
            const self = this;
            self.flujoActivo.formulario.secciones.forEach(function (seccion, keySeccion) {
                self.flujoActivo.formulario.secciones[keySeccion].campos.forEach(function (a) {
                    a.rechazado = selectAll;
                });
            })
        },

        // save change onblur
        saveWhenOnBlur(input, vs) {

            const self = this;
            const {id, showInReports, valor, tipoCampo, nombre} = input;
            const campo = {
                'v':valor,
                't':tipoCampo
            };
            let loadData = false;
            if(!!input.procesoExec){
                loadData = {load:'saveonblur'};
            }

            if(vs) campo['vs'] = vs;
            toolbox.doAjax('POST', 'tareas/save-field-on-blur' + (self.isPublic ? '/public' : ''), {
                token: self.cToken,
                seccionKey: self.currentTabIndex,
                campoKey: id,
                vehiculoIdAgrupadorNodo: self.vehiculoIdAgrupadorNodo,
                campo: campo,
                showInReports: showInReports,
                nombre: nombre,
            }, function (response) {
                setTimeout(function () {
                    if(!!input.procesoExec){
                        toolbox.doAjax('POST', 'tareas/exec-process' + (self.isPublic ? '/public' : ''), {
                            token: self.cToken,
                            nodoId: input.procesoExec
                        }, function (response) {
                            toolbox.alert(response.msg, 'success');
                            setTimeout(function (){
                                self.getFlujo();
                            }, 1000)
                        }, function (response) {
                            if(response.msg === "Ha ocurrido realizando el proceso de envío de datos. NO_EXISTE (COTW-001)") {
                                response.msg = 'El cliente no existe en AS400'
                            }
                            toolbox.alert(response.msg, 'info');
                            setTimeout(function (){
                                self.getFlujo();
                            }, 1000)

                        }, {load:'execprocess'}, false)
                    }
                }, 1000)
            }, function (response) {
            }, loadData, false)
        },

        // get vehiculos
        getVehiclesForCoti() {
            const self = this;
            if (this.flujoActivo.typeObject === 'start') {
                return false;
            }

            toolbox.doAjax('POST', 'tareas/get-vehiculos', {
                token: self.cToken,
            }, function (response) {

                // se asignan los ids recién guardados si es vehículo nuevo
                let count = 1;
                const number = Object.keys(response.data).length;

                Object.keys(response.data).map(function (a) {

                    self.vehiculos[response.data[a].id] = {
                        vehiculoId: response.data[a].id,
                        number: number,
                        numberKey: count,
                        error: '',
                        errorD: '',
                        altoRies: false,
                        marcaId: response.data[a].marcaId,
                        lineaId: response.data[a].lineaId,
                        tipoId: response.data[a].tipoId,
                        noPasajeros: response.data[a].noPasajeros,
                        noChasis: response.data[a].noChasis,
                        noMotor: response.data[a].noMotor,
                        validarVeh: response.data[a].validarVeh,
                        modelo: response.data[a].modelo,
                        valorProm: response.data[a].valorProm,
                        valorPromDef: response.data[a].valorPromDef,
                        placa: response.data[a].placa,
                        vehiculoNuevo: response.data[a].vehiculoNuevo,
                        altoRiesgoDisp: response.data[a].altoRiesgoDisp,
                        cotizaciones: [],
                        filter: {
                            m: {},
                            l: {},
                            t: {},
                            c: {},
                        },
                    };



                    Object.keys(response.data[a].cotizaciones).map(function (b) {

                        /*const coberturas = [];
                        Object.keys(response.data[a].cotizaciones[b].coberturas).map(function (b) {
                            self.vehiculos[response.data[a].id].cotizaciones.push({
                                productoappUrlId: response.data[a].cotizaciones[b].productoId,
                                descuento: response.data[a].cotizaciones[b].catProductoTarifaDescRecargoId,
                                formaPago: response.data[a].cotizaciones[b].formaPagoId,
                                coberturas: [],
                            });
                        })*/

                        self.vehiculos[response.data[a].id].cotizaciones.push({
                            cotId: response.data[a].cotizaciones[b].id,
                            productoId: response.data[a].cotizaciones[b].productoId,
                            sumAseg: response.data[a].cotizaciones[b].sumaAsegurada	,
                            //descuento: response.data[a].cotizaciones[b].catProductoTarifaDescRecargoId,
                            descuento: response.data[a].cotizaciones[b].descuentoPorcentaje,
                            descuentoSelect: response.data[a].cotizaciones[b].descuentoId,
                            numeroPagos: response.data[a].cotizaciones[b].numeroPagos,
                            formaPago: response.data[a].cotizaciones[b].formaPagoId,
                            frecuenciaPagos: response.data[a].cotizaciones[b].frecuenciaPagos,
                            producto: response.data[a].cotizaciones[b].producto,
                        });

                    })

                    setTimeout(function () {
                        self.getVehiculoCatalog(response.data[a].id);
                        self.valorPromedioAlert(response.data[a].id, count)
                    }, 100);

                    count++;
                })

            }, function (response) {
            }, false, false)
        },

        // get vehiculos
        getVehiclesCotizacion() {
            const self = this;
            if (this.flujoActivo.typeObject === 'start') {
                return false;
            }

            toolbox.doAjax('POST', 'tareas/get-vehiculos-cotizacion', {
                token: self.cToken,
                onlyE: (self.flujoActivo.typeObject !== 'vehiculo_comp'),
            }, function (response) {

                self.vehiculosCot = response.data;
                //console.log(self.vehiculosCot);

                Object.keys(self.vehiculosCot).map(function (a){
                    //console.log(self.vehiculosCot[a]);
                    self.descuentoAdicional[a] = {
                        descAdi: parseInt(self.vehiculosCot[a].descAdi || 0),
                        comm: self.vehiculosCot[a].descAdiC || '',
                    }
                })

                if (self.flujoActivo.gVh === 'a') {
                    if (!self.vehiculoIdAgrupadorNodo) {
                        self.selectFirstVehiculo();
                    }
                }

            }, function (response) {
            }, false, false)
        },

        // save vehicles on blur
        saveVehiclesOnBlur(vehiculoId, vehiculoKey, vehicleChange, refreshCoti, refreshDiscounts, forceVehicleRefresh) {
            const self = this;

            if (!vehiculoKey) vehiculoKey = null;
            if (!vehicleChange) vehicleChange = false;
            if (!refreshCoti) refreshCoti = false;
            if (!refreshDiscounts) refreshDiscounts = false;
            if (!forceVehicleRefresh) forceVehicleRefresh = false;
            /*let tmpToSend = {...self.vehiculos[vehiculoId]};
            delete tmpToSend.filter;*/

            //self.continueCotiRequest = true;

            setTimeout(function () {
                toolbox.doAjax('POST', 'tareas/save-vehiculos-on-blur' + (self.isPublic ? '/public' : ''), {
                    token: self.cToken,
                    vehiculoId: vehiculoId,
                    vehiculoN: vehiculoKey,
                    vehiculo: self.vehiculos[vehiculoId],
                    vehicleCh: vehicleChange,
                    descuentoAdicional: self.descuentoAdicional,
                    refreshDiscounts: refreshDiscounts,
                    node: self.flujoActivo.typeObject,
                }, function (response) {

                    self.getVehiculoCatalog(vehiculoId, false, refreshCoti);

                    if (refreshDiscounts) {
                        self.descuentoAdicional[vehiculoId].descAdi = 0;
                    }

                    if (vehicleChange || forceVehicleRefresh) {
                        self.getFlujo();
                    }
                    // se asignan los ids recién guardados si es vehículo nuevo
                    /*Object.keys(response.data).map(function (a) {
                        self.vehiculos[a].id = response.data[a];
                    })*/
                }, function (response) {
                    if (vehicleChange) {
                        self.getFlujo();
                    }
                }, {load:'saveVehiclesOnBlur'}, false)
            }, 50); // espera para que a vue le de tiempo de poner la cobertura seleccionada
        },

        copyLink(link) {
            const linkTmp = config.appUrl + link;
            toolbox.copyToClipboard(linkTmp);
        },

        async saveValueMultiSelect(input) {
            const self = this;
            await self.campoCumpleCondiciones(input);
            self.saveWhenOnBlur(input);
        },
        backToStart(){
            const self = this;
            toolbox.confirm('Se reiniciará la tarea, ¿desea continuar?', function () {
                toolbox.doAjax('POST', 'tareas/cambiar-estado' + (self.isPublic ? '/public' : ''), {
                    token: self.cToken,
                    paso: 'start'
                }, function (response) {
                    toolbox.alert(response.msg);
                    window.scrollTo({top: 0, behavior: 'smooth'});
                    self.getFlujo();
                }, function (response) {
                    self.getFlujo();
                    toolbox.alert(response.msg, 'danger');
                }, {load:'backToStart1'}, false)
            });
        },
        copyCoti(){
            const self = this;
            toolbox.confirm('Se copiara esta tarea en una nueva, ¿desea continuar?', function () {
                    toolbox.doAjax('POST', 'tareas/iniciar-cotizacion' + (self.isPublic ? '/public' : ''), {
                        token: self.pToken,
                    }, async function (response) {
                        if (response.status) {
                            const beforeCToken = self.cToken;
                            self.cToken = response.data.token;
                            toolbox.doAjax('POST', 'tareas/linking-cotizaciones' + (self.isPublic ? '/public' : ''), {
                            token: self.cToken,
                            lToken: beforeCToken
                            }, async function (response) {
                                if (self.isPublic) {
                                   await self.$router.push('/f/' + self.pToken + '/' + self.cToken);
                                } else {
                                   await self.$router.push('/cotizar/producto/' + self.pToken + '/' + self.cToken);
                                }
                                await  location.reload();
                            }, function (response) {
                                toolbox.alert(response.msg, 'danger');
                            },{load:'copycopi1'}, false)
                        } else {
                            toolbox.alert('Ha ocurrido un error obteniendo el producto', 'danger');
                    }
                }, function (response) {
                    toolbox.alert(response.msg, 'danger');
                },{load:'copycopi2'}, false)
            });
        },

        goPreguntasFrecuentes(){
            window.open('/#/ayuda/preguntas-frecuentes');
        },

        // nodo de vehiculos
        getVehiculoCatalog(vehiculoId, vehiNumber, refreshCoti) {

            // para no traer en todos lados los catálogos
            if (this.flujoActivo.typeObject !== 'vehiculo') {
                return false;
            }

            if (!vehiNumber) vehiNumber = 1;

            if (!refreshCoti) refreshCoti = false;

            const self = this;
            let productoId = false;
            let marcaId = false;
            let lineaId = false;
            let cotizaciones = false;
            let modelo = false;

            if (typeof this.vehiculos[vehiculoId] !== 'undefined') {
                productoId = this.vehiculos[vehiculoId].productoId;
                marcaId = this.vehiculos[vehiculoId].marcaId;
                lineaId = this.vehiculos[vehiculoId].lineaId;
                cotizaciones = this.vehiculos[vehiculoId].cotizaciones;
                modelo = this.vehiculos[vehiculoId].modelo;
            }

            toolbox.doAjax('POST', 'admin/load/cotizador-tarifas-c', {
                'token': self.cToken,
                vehiculoId: vehiculoId,
                vehiNumber: vehiNumber,
                marcaId: marcaId,
                lineaId: lineaId,
                cotizaciones: cotizaciones,
                modelo: modelo,
            },  function (response) {
                if (response.status) {

                    if (typeof response.data.siniesBlock !== 'undefined') {
                        self.siniestralidadBlock = parseInt(response.data.siniesBlock);
                    }

                    if (typeof self.vehiculos[vehiculoId] !== 'undefined') {
                        self.vehiculos[vehiculoId].filter.m = [];
                        self.vehiculos[vehiculoId].filter.l = [];
                        self.vehiculos[vehiculoId].filter.p = [];
                        self.vehiculos[vehiculoId].filter.coti = [];
                        self.vehiculos[vehiculoId].filter.tv = [];
                        self.vehiculos[vehiculoId].valorPromDef = response.data.valorPromDef;
                        self.vehiculos[vehiculoId].valorProm = response.data.valorProm;
                        self.vehiculos[vehiculoId].error = response.data.error;
                        self.vehiculos[vehiculoId].errorD = response.data.errorD;
                        self.vehiculos[vehiculoId].altoRies = response.data.altoRies;

                        Object.keys(response.data.tv).map(function (a, b) {
                            self.vehiculos[vehiculoId].filter.tv.push({
                                value: response.data.tv[a].id,
                                label: response.data.tv[a].nombre,
                            })
                        })
                        Object.keys(response.data.m).map(function (a, b) {
                            self.vehiculos[vehiculoId].filter.m.push({
                                value: response.data.m[a].id,
                                label: response.data.m[a].nombre,
                            })
                        })

                        Object.keys(response.data.l).map(function (a, b) {
                            self.vehiculos[vehiculoId].filter.l.push({
                                value: response.data.l[a].id,
                                label: response.data.l[a].nombre,
                            })
                        })

                        Object.keys(response.data.p).map(function (a, b) {
                            self.vehiculos[vehiculoId].filter.p.push({
                                value: response.data.p[a].id,
                                label: response.data.p[a].descripcion,
                                codigo: response.data.p[a].codigoProducto,
                                maxAnios: response.data.p[a].maxAnios,
                            })
                        })

                        Object.keys(response.data.coti).map(function (key, value) {

                            self.vehiculos[vehiculoId].cotizaciones[key].ID = response.data.coti[key].id;
                            self.vehiculos[vehiculoId].cotizaciones[key].pD = response.data.coti[key].pD;
                            self.vehiculos[vehiculoId].cotizaciones[key].rC = response.data.coti[key].rC;
                            self.vehiculos[vehiculoId].cotizaciones[key].coberturas = [];


                            let tmpSumAseg = 0;
                            if (response.data.coti[key].sumAseg) {
                                tmpSumAseg = response.data.coti[key].sumAseg;
                            }

                            if (typeof self.vehiculos[vehiculoId].cotizaciones[key] !== 'undefined') {
                                self.vehiculos[vehiculoId].cotizaciones[key].sumAseg = tmpSumAseg;
                            }
                            //self.vehiculos[vehiculoId].cotizaciones[key].descuentos = response.data.coti[key].desc;

                            Object.keys(response.data.coti[key].cober).map(function (a, b) {

                                /*if (typeof self.vehiculos[vehiculoId].cotizaciones[key] !== 'undefined') {
                                    cobertura.selected
                                }*/
                                let monto = 0;
                                /*if (typeof self.vehiculos[vehiculoId].cotizaciones[key].coberS !== 'undefined') {
                                    Object.keys(self.vehiculos[vehiculoId].cotizaciones[key].coberS).map(function (ctmp) {
                                        if (self.vehiculos[vehiculoId].cotizaciones[key].coberS[ctmp].codigoCobertura === response.data.coti[key].cober[a].codigo) {
                                            monto = self.vehiculos[vehiculoId].cotizaciones[key].coberS[ctmp].monto;
                                            selected = true;
                                        }
                                    })
                                }*/

                                const coberturaData = response.data.coti[key].cober[a];
                                let montoCobertura = coberturaData.monto;

                                if(!coberturaData.llevaValorVehiculo && coberturaData.montoV && coberturaData.montoV.length > 0){
                                    if(coberturaData.montoV.every(e => e.val != montoCobertura)) montoCobertura = response.data.coti[key].cober[a].montoV[0].val;
                                }

                                self.vehiculos[vehiculoId].cotizaciones[key].coberturas.push({
                                    value: response.data.coti[key].cober[a].value,
                                    label: response.data.coti[key].cober[a].nombre,
                                    obligatorio: response.data.coti[key].cober[a].obligatorio,
                                    llevaValorVehiculo: response.data.coti[key].cober[a].llvh,
                                    rangoMonto: response.data.coti[key].cober[a].rangoMonto,
                                    //descRec: response.data.coti[key][a].descRec,
                                    codigo: response.data.coti[key].cober[a].codigo,
                                    sumaAsegurada: response.data.coti[key].cober[a].sumaA,
                                    montoPrima: response.data.coti[key].cober[a].mp,
                                    montoSumaAsegurada: response.data.coti[key].cober[a].msa,
                                    moneda: response.data.coti[key].cober[a].moneda,
                                    montoV: response.data.coti[key].cober[a].montoV,
                                    monto: montoCobertura,
                                    condicional: response.data.coti[key].cober[a].c,
                                    condicionalPreview: response.data.coti[key].cober[a].ccp,
                                    condicionalResult: response.data.coti[key].cober[a].cr,
                                    condicionalV: response.data.coti[key].cober[a].cv,
                                    condicionalVPreview: response.data.coti[key].cober[a].cvr,
                                    condicionalVResult: response.data.coti[key].cober[a].cvres,
                                    tipoVisibilidad: response.data.coti[key].cober[a].tipoVisibilidad,
                                    selected: parseInt(response.data.coti[key].cober[a].selected),
                                })
                            })

                            setTimeout(function (){
                                self.valorPromedioAlert(vehiculoId);
                            })
                        })

                        self.bringDiscounts();

                        if (refreshCoti) {
                            self.saveVehiclesOnBlur(vehiculoId);
                        }
                    }

                } else {
                    toolbox.alert('Ha ocurrido un error obteniendo el producto', 'danger');
                }
            }, function (response) {
                toolbox.alert(response.msg, 'danger');
            }, {load:'getVehiculoCatalog'}, true)
        },

        agregarVehiculo() {
            const self = this;
            toolbox.doAjax('POST', 'tareas/add-vehiculo', {
                token: self.cToken,
            }, function (response) {

                /*const vehiculoId = response.data;
                self.vehiculos[vehiculoId] = {
                    vehiculoId: response.data,
                    error: '',
                    errorD: '',
                    marcaId: 0,
                    lineaId: 0,
                    tipoId: 0,
                    noPasajeros: '',
                    noChasis: '',
                    noMotor: '',
                    validarVeh: '',
                    modelo: '',
                    valorProm: '',
                    placa: '',
                    vehiculoNuevo: false,
                    cotizaciones: [],
                    filter: {
                        m: {},
                        l: {},
                        t: {},
                        c: {},
                    },
                };*/
                self.getVehiclesForCoti();
            }, function (response) {
            }, false, false)
        },

        addCotizacion(key) {
              this.vehiculos[key].cotizaciones.push({
                  productoId: 0,
                  descuento: 0,
                  numeroPagos: 0,
                  formaPago: 0,
                  sumAseg: this.vehiculos[key].valorProm,
                  coberturas: [],
                  frecuenciaPagos: [JSON.parse(JSON.stringify(this.newFrecuenciaPago))],
              });

              this.saveVehiclesOnBlur(key, false, false, false, false, true);
        },

        deleteCotizacion(key, cotiKey, cotizacionId) {
            const self =  this;

            console.log(key)
            console.log(cotiKey)
            console.log(cotizacionId)

            if (cotizacionId && cotizacionId) {
                toolbox.confirm('Se eliminará esta cotización, esta acción no se puede revertir, ¿desea continuar?', function () {
                    toolbox.doAjax('POST', 'tareas/delete-cot' + (self.isPublic ? '/public' : ''), {
                        token: self.cToken,
                        cotizacionId: cotizacionId,
                    }, function (response) {
                        self.vehiculos[key].cotizaciones.splice(cotiKey, 1)
                    }, function (response) {
                    }, false, false)
                })
            }
            else {
                toolbox.alert('Existe un problema desvinculando la cotización, intente eliminando el vehículo');
            }

        },

        eliminarVehiculo(vehiculoId, keyVehi) {

            const self = this;

            toolbox.confirm('Se eliminará este vehículo de la cotización, esta acción no se puede revertir, ¿desea continuar?', function () {
                toolbox.doAjax('POST', 'tareas/delete-vehiculo' + (self.isPublic ? '/public' : ''), {
                    token: self.cToken,
                    vehiculoId: vehiculoId,
                }, function (response) {

                    delete self.vehiculos[keyVehi];
                }, function (response) {
                }, false, false)
            })
        },

        setEmitirPoliza(cotizacionId, status, vehiculoId) {

            const self = this;

            setTimeout(function () {


                let errorPoliza = false;
                Object.keys(self.vehiculosCot).map(function (key) {
                    let vehiEmitidaPoliza = 0;
                    Object.keys(self.vehiculosCot[key]['c']).map(function (cotizacionKey) {
                        if (typeof self.vehiculosCot[key]['c'][cotizacionKey].emitirPoliza !== 'undefined' && self.vehiculosCot[key]['c'][cotizacionKey].emitirPoliza) {
                            vehiEmitidaPoliza++
                        }
                    })

                    if (vehiEmitidaPoliza > 1) {
                        toolbox.alert('Solo puede emitir una póliza por vehículo', 'danger');
                        errorPoliza = true;
                        return false;
                    }
                })

                if (errorPoliza) {
                    self.vehiculosCot[vehiculoId]['c'][cotizacionId].emitirPoliza = false;
                    return false;
                }

                toolbox.doAjax('POST', 'tareas/set-emitir-poliza' + (self.isPublic ? '/public' : ''), {
                    token: self.cToken,
                    cotizacionId: cotizacionId,
                    status: status,
                }, function (response) {
                    toolbox.alert(response.msg, 'success');
                }, function (response) {
                    toolbox.alert(response.msg, 'danger');
                }, false, false)
            }, 250);
        },

        // otros
        getFrecuenciaPagos() {

            const self = this;
            toolbox.doAjax('POST', 'catalogos/frecuencia/pago', {

            },  function (response) {
                if (response.status) {
                    self.frecuenciaPagosCat = response.data;

                } else {
                    toolbox.alert('Ha ocurrido un error obteniendo el producto', 'danger');
                }
            }, function (response) {
                toolbox.alert(response.msg, 'danger');
            }, {load:'getFrecuenciaPagos'}, false)
        },

        valorPromedioAlert(vehiculoId, vehiNumber, save) {
            const self = this;
            if (!save) save = false;

            setTimeout(function (){

                self.vehiculos[vehiculoId].cotizaciones.forEach(function (value, cotiKey) {
                    if (typeof self.allFieldsData.SUMA_ASEGURADA_MAXIMA_1 !== 'undefined' && typeof self.allFieldsData.SUMA_ASEGURADA_MAXIMA_2 !== 'undefined') {

                        const valorPromDef = parseFloat(self.vehiculos[vehiculoId].valorPromDef);
                        const sumAsegurada = parseFloat((self.vehiculos[vehiculoId].cotizaciones[cotiKey].sumAseg ? self.vehiculos[vehiculoId].cotizaciones[cotiKey].sumAseg  : '').replace(/[^\d.]/g, ''));
                        const sumaMaxGlobalPercent = parseFloat(self.allFieldsData.SUMA_ASEGURADA_MAXIMA_1.valor);
                        const vehNuevo =  self.vehiculos[vehiculoId].vehiculoNuevo;
                        const maxValorProm =  valorPromDef + (valorPromDef * (sumaMaxGlobalPercent / 100));
                        const minValorProm = valorPromDef - (valorPromDef * (sumaMaxGlobalPercent / 100));

                        const sumaMax2GlobalPercent = parseFloat(self.allFieldsData.SUMA_ASEGURADA_MAXIMA_2.valor);
                        const maxValorProm2 =  valorPromDef + (valorPromDef * (sumaMax2GlobalPercent / 100));
                        const minValorProm2 = valorPromDef - (valorPromDef * (sumaMax2GlobalPercent / 100));

                        self.vehiculos[vehiculoId].cotizaciones[cotiKey].valorGarantizado = 0;
                        self.vehiculos[vehiculoId].cotizaciones[cotiKey].valorPromAlert = "";
                        self.vehiculos[vehiculoId].cotizaciones[cotiKey].valorPromAlert2 = "";
                        self.vehiculos[vehiculoId].cotizaciones[cotiKey].noOperable = false;

                        console.log('nuevo: ' + vehNuevo);
                        console.log('valor promedio: ' + valorPromDef);
                        console.log('suma asegurada: ' + sumAsegurada);
                        console.log('minValorProm: ' + minValorProm);
                        console.log('maxValorProm: ' + maxValorProm);
                        console.log('minValorProm2: ' + minValorProm2);
                        console.log('maxValorProm2: ' + maxValorProm2);

                        if (valorPromDef > 0) {

                            if (sumAsegurada > maxValorProm2 || sumAsegurada < minValorProm2) {
                                self.vehiculos[vehiculoId].cotizaciones[cotiKey].valorPromAlert2 = self.allFieldsData.COTIZACION_VALOR_EXCEDIDO_2_MSG.valor;
                                self.vehiculos[vehiculoId].cotizaciones[cotiKey].noOperable = true;
                            }
                            else {
                                if (sumAsegurada > maxValorProm || sumAsegurada < minValorProm) {
                                    self.vehiculos[vehiculoId].cotizaciones[cotiKey].valorPromAlert = self.allFieldsData.COTIZACION_VALOR_EXCEDIDO_1_MSG.valor;
                                }
                                else {
                                    if (sumAsegurada > minValorProm && sumAsegurada < maxValorProm) {
                                        self.vehiculos[vehiculoId].cotizaciones[cotiKey].valorGarantizado = 1;
                                    }
                                }
                            }
                        }
                        else {
                            self.vehiculos[vehiculoId].cotizaciones[cotiKey].valorGarantizado = 0;
                            self.vehiculos[vehiculoId].cotizaciones[cotiKey].valorPromAlert2 = self.allFieldsData.COTIZACION_VALOR_EXCEDIDO_1_MSG.valor;
                        }

                        if (vehNuevo) {
                            self.vehiculos[vehiculoId].cotizaciones[cotiKey].valorGarantizado = 1;
                            self.vehiculos[vehiculoId].cotizaciones[cotiKey].valorPromAlert = "";
                            self.vehiculos[vehiculoId].cotizaciones[cotiKey].valorPromAlert2 = "";
                        }

                        self.vehiculos[vehiculoId].cotizaciones[cotiKey].valorPromAlertValorGarantizado = self.allFieldsData.COTIZACION_VALOR_GARANTIZADO_MSG.valor;


                        if (save) {
                            toolbox.doAjax('POST', 'tareas/cusv/save', {
                                vehiId: vehiculoId,
                                vehiNumber: vehiNumber,
                                cotiK: cotiKey + 1,
                                cotiId: self.vehiculos[vehiculoId].cotizaciones[cotiKey].cotId,
                                ct: self.cToken,
                                valorG: self.vehiculos[vehiculoId].cotizaciones[cotiKey].valorGarantizado,
                                valorA1: (self.vehiculos[vehiculoId].cotizaciones[cotiKey].valorPromAlert !== '') ? 1 : 0,
                                valorA2: (self.vehiculos[vehiculoId].cotizaciones[cotiKey].valorPromAlert2 !== '') ? 1 : 0,
                            }, function (response) {

                            }, function (response) {
                                toolbox.alert(response.msg, 'danger');
                            }, {load:'bringCatalogos'}, false)
                        }
                    }
                })
            }, 300);
        },

        //Bring catalogo
        bringCatalogos(){
            const self = this;
            toolbox.doAjax('POST', 'tareas/catalogos/bring', {
                catalogos: ['medio_cobro', 'tipo_cuenta_tarjeta','clase_tarjeta','banco_emisor', 'tipo_cuenta_bancaria', 'formas_pago'],
            }, function (response) {
                self.catalogosPay = response.data;
            }, function (response) {
                toolbox.alert(response.msg, 'danger');
            }, {load:'bringCatalogos'}, false)
        },

        //Descuentos
        selectDiscount($event, keyVehi, cotiKey){
            const self = this;
            const value = $event.target.value;
            const descuento = self.descuentos.find(e => e.id == Number(value));
            self.modifyDescuento = true;
            if(!!descuento){
                const valorDescuento = Number(descuento.valormin);
                self.vehiculos[keyVehi].cotizaciones[cotiKey].descuento = valorDescuento;
                self.vehiculos[keyVehi].cotizaciones[cotiKey].descuentoSelect = value;
            }else{
                self.vehiculos[keyVehi].cotizaciones[cotiKey].descuento = 0;
                self.vehiculos[keyVehi].cotizaciones[cotiKey].descuentoSelect = '';
            }
            self.saveVehiclesOnBlur(self.vehiculos[keyVehi].vehiculoId);
        },
        bringDiscounts(){
            const self = this;
            const products = [];

            Object.keys(self.vehiculos).map(function (v){
                self.vehiculos[v].cotizaciones.forEach(function (b) {
                    products.push(b.productoId);
                })
            })

            toolbox.doAjax('POST', 'descuentos/listado', {
                    products: products
                },
                function (response) {
                    self.descuentos = response.data;
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
        verifyDiscounts(event, keyVehi, cotiKey, vehiculoId, vehiNumber){
            const self = this;
            let value = event.target.value;

            const products = [];
            self.vehiculos[keyVehi].cotizaciones.forEach(function (b) {
                products.push(b.productoId);
            })

            toolbox.doAjax('POST', 'descuentos/listado', {
                    products: products
                },
                function (response) {
                    self.descuentos = response.data;
                    const productoId = self.vehiculos[keyVehi].cotizaciones[cotiKey].productoId;

                    if(isNaN(parseFloat(value)) || !isFinite(value) || parseFloat(value) < 0){
                        self.vehiculos[keyVehi].cotizaciones[cotiKey].descuento = 0;
                        toolbox.alert(`El descuento debe ser un número`, 'danger');
                    }

                    let min = 0;
                    let max = 0;
                    self.descuentos.forEach(function (a) {
                        let accessproduct = false;
                        if (typeof a.pra !== 'undefined') {
                            a.pra.forEach(function (b) {
                                if (b === productoId) {
                                    accessproduct = true;
                                }
                            })
                        }

                        // si tiene acceso a ese producto
                        if (accessproduct || typeof a.pra === 'undefined') {
                            if (min === 0) {
                                min = a.valormin;
                            }
                            else if (a.valormin < min) {
                                min = a.valormin;
                            }

                            if (max === 0) {
                                max = a.valormax;
                            }
                            else if (a.valormax > max) {
                                max = a.valormax;
                            }
                        }
                    })

                    /*console.log(min);
                    console.log(max);*/
                    if (Number(value) > Number(max)) {
                        self.vehiculos[keyVehi].cotizaciones[cotiKey].descuento = max;
                        toolbox.alert(`Descuento máximo permitido ${max}%`, 'danger');
                    }
                    /*else {
                        if(!self.descuentos.every(e =>  Number(e.valormax) >= Number(value))) {
                            const valormax = Math.max(...self.descuentos.map(e => Number(e.valormax)));

                        }
                    }*/

                    self.saveVehiclesOnBlur(vehiculoId, vehiNumber)
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
        verifyAniosMax(keyVehi, cotiKey){
            const self = this;
            const vehiculo = self.vehiculos[keyVehi];
            const modelo = vehiculo.modelo;
            const productoId = vehiculo.cotizaciones[cotiKey].productoId;
            const maxAnios = vehiculo.filter.p.find(p => Number(p.value) === Number(productoId)).maxAnios;
            const currentDate = new Date();
            const currentYear = currentDate.getFullYear();
            if(Number(maxAnios) > 0
                && Math.abs(Number(currentYear) - Number(modelo)) > Number(maxAnios)){
                self.$refs.multiselect[0].clear();
                self.vehiculos[keyVehi].cotizaciones[cotiKey].productoId = 0;
                toolbox.alert('El vehículo no esta dentro del rango de años permitidos para este Seguro, por favor pruebe con otro', 'danger');
                return false;
            }
            return true;
        },
        changeSelectorVehiculo(event){
            const self = this;
            if (self.cToken !== 'view') {
                const self = this;
                const prevVehicleId = self.vehiculoIdAgrupadorNodo;
                self.vehiculoIdAgrupadorNodo = (event) ? event.target.value : false;
                if(!!prevVehicleId){
                    let arrCampos = {};
                    let requeridosFail = false;
                    let requeridosFailInSection = false;
                    if (typeof this.flujoActivo.formulario.secciones !== 'undefined') {

                        self.flujoActivo.formulario.secciones.forEach(function (seccion, keySeccion) {
                            self.flujoActivo.formulario.secciones[keySeccion].campos.forEach(function (a) {

                                if (typeof arrCampos[a.id] === 'undefined') arrCampos[a.id] = {};

                                // data del catálogo
                                if (a.catalogoId && a.catalogoId !== '') {
                                    const dataTmp = self.obtenerItemsPorCatalogoMS(a.id, a.catalogoLabel, a.catalogoValue);
                                    Object.keys(dataTmp).map(function (k) {
                                        if (typeof dataTmp[k] !== 'undefined' && dataTmp[k].value === a.valor) {
                                            arrCampos[a.id]['vs'] = dataTmp[k].label;
                                        }
                                    })
                                }

                                arrCampos[a.id]['t'] = a.tipoCampo;
                                arrCampos[a.id]['v'] = a.valor;
                                arrCampos[a.id]['r'] = a.rechazado;

                                if (a.tipoCampo === 'currency') {
                                    arrCampos[a.id]['v'] = a.valor.toString().replace(/\s/g, '');
                                    const parts = arrCampos[a.id]['v'].split('.');
                                    if (!parts[0]) arrCampos[a.id]['v'] = '0';
                                    if (typeof parts[1] === 'undefined') {
                                        arrCampos[a.id]['v'] += '.00'
                                    } else if (parts[1].length === 0) {
                                        arrCampos[a.id]['v'] += '00'
                                    } else if (parts[1].length === 1) {
                                        arrCampos[a.id]['v'] += '0'
                                    }
                                    //console.log()
                                }
                                ;

                                if (a.tipoCampo === 'signature') {

                                    if (typeof self.$refs['signature_' + a.id] !== 'undefined' && typeof self.$refs['signature_' + a.id][0] !== 'undefined' && typeof self.$refs['signature_' + a.id][0].undoSignature === 'function') {
                                        const {isEmpty, data} = self.$refs['signature_' + a.id][0].saveSignature();
                                        if (!isEmpty) {
                                            arrCampos[a.id]['v'] = data;
                                            a.valor = data;
                                        }
                                    }
                                }

                                const valoresCampo = [];
                                let selected = false;
                                if (a.tipoCampo === 'checkbox' || a.tipoCampo === 'multiselect') {
                                    arrCampos[a.id]['v'] = [];
                                    if (typeof a.catalogoId !== "undefined") {
                                        arrCampos[a.id]['v'] = a.valor;
                                    }
                                }

                                if (a.requerido && (a.valor === '' || !a.valor)) {
                                    if (typeof self.camposCumplidores[a.id] !== 'undefined' && self.camposCumplidores[a.id]) {
                                        a.requeridoError = true;
                                        requeridosFail = true;
                                        if (parseInt(keySeccion) === parseInt(self.currentTabIndex)) {
                                            requeridosFailInSection = true;
                                        }
                                    }
                                } else {
                                    a.requeridoError = false;
                                }

                                // catálogos
                                if (typeof a.catalogoId !== 'undefined' && a.catalogoId !== '' && a.catalogoId) {
                                    if (typeof self.catalogos[a.id] !== 'undefined' && a.catalogoLabel) {
                                        self.catalogos[a.id].forEach(function (b) {
                                            if (b[a.catalogoValue] === a.valor) {
                                                arrCampos[a.id]['vs'] = b[a.catalogoLabel];
                                            }
                                        })
                                    }
                                }

                                if (typeof a.vError !== 'undefined' && a.vError) {
                                    requeridosFail = true;
                                    requeridosFailInSection = true;
                                    a.requeridoError = true;
                                }
                            });
                        })
                    }
                    toolbox.doAjax('POST', 'tareas/cambiar-estado' + (self.isPublic ? '/public' : ''), {
                        token: self.cToken,
                        paso: 'save',
                        seccionKey: self.currentTabIndex,
                        campos: arrCampos,
                        vehiculoIdAgrupadorNodo: prevVehicleId,
                    }, function (response) {
                        self.calcularPasoVehicle();
                    }, function (response) {
                        toolbox.alert(response.msg, 'danger');
                    }, {load:'cambiarEstado2'}, false)
                } else {
                    self.calcularPasoVehicle();
                }
            }
        },
        calcularPasoVehicle(loading){
            const self = this;
            if (!loading) loading = false;
            toolbox.doAjax('POST', 'tareas/calcular-paso' + (self.isPublic ? '/public' : ''), {
                token: self.cToken,
                vehiculoIdAgrupadorNodo: self.vehiculoIdAgrupadorNodo,
                }, async function (response) {
                    self.flujoActivo = response.data.actual;
                    // visibilidad
                    if (!self.flujoActivo) {
                        self.showCotizacion = false;
                        self.showCotizacionDesc = self.cotizacion.ed;
                    }
                    self.campoCumpleCondiciones();
                }, function (response) {
                    toolbox.alert(response.msg, 'danger');
                }, {load:'getFlujo2'}, loading)
        },
        formatDataCompareTable(vehiculoId){
            const self = this;

            toolbox.doAjax('POST', 'tareas/get-vehiculos-cotizacion-comp', {
                token: self.cToken,
                vehiculoId: vehiculoId
            }, function (response) {
                self.showCompare = true;
                self.dataTableCompare = response.data;
            }, function (response) {
            }, false, false)
        },
        saveSignature(keyInput, input){
            const self = this;
            if (input.tipoCampo === 'signature') {
                if (typeof self.$refs['signature_' + input.id] !== 'undefined' && typeof self.$refs['signature_' + input.id][0] !== 'undefined' && typeof self.$refs['signature_' + input.id][0].undoSignature === 'function') {
                    const { isEmpty, data } = self.$refs['signature_' + input.id][0].saveSignature();
                    if (!isEmpty) {
                      //  arrCampos[input.id]['v'] = data;
                        input.valor = data;
                        self.flujoActivo.formulario.secciones[self.currentTabIndex].campos[keyInput].valor = data;
                        const {id, showInReports, valor, tipoCampo} = input;
                        const campo = {
                            'v':valor,
                            't':tipoCampo
                        };
                        toolbox.doAjax('POST', 'tareas/save-field-on-blur' + (self.isPublic ? '/public' : ''), {
                            token: self.cToken,
                            seccionKey: self.currentTabIndex,
                            campoKey: id,
                            campo,
                            showInReports
                        }, function (response) {
                            self.signatureData[input.id] = self.flujoActivo.formulario.secciones[self.currentTabIndex].campos[keyInput].valor;
                        }, function (response) {
                        },{load:'saveSignature'}, false)
                    }
                }
            }
        },
        addFieldsGroup(input, keyInput, currentTabIndex){
            const self = this;
            const valor = (!input.valor || Number.isNaN(Number(input.valor))? 1 : Number(input.valor)) + 1;
            if(valor <= Number(input.longitudMax)) {
                let fieldsGroup = self.flujoActivo.formulario.secciones[currentTabIndex].campos
                    .filter(camp => camp['group'] === input.id );
                let fieldsGall = fieldsGroup.map(e => e.id);
                let fieldsGroupAdap =   fieldsGroup.map(campGroup => {
                    let campGroupNew = {...campGroup};
                    campGroupNew['id'] = `${campGroup['group']}_${campGroup['id']}_${valor}`;
                    campGroupNew['group'] = '';
                    if(fieldsGall.includes(campGroupNew['catFId'])){
                        campGroupNew['catFId'] = `${campGroup['group']}_${campGroup['catFId']}_${valor}`;
                    }
                    if(Array.isArray(campGroup['dependOn'])){
                        campGroupNew['dependOn'] = campGroup['dependOn'].map(c => {
                            //  dependOn: [{campoId: '', campoIs: '', campoValue: ''}],
                            let campoId = c.campoId;
                            if(!!c['campoId'] && fieldsGroup.some(camp => camp.id === c['campoId'])) campoId = `${campGroup['group']}_${c.campoId}_${valor}`;
                            return {...c, campoId};
                        });
                    }
                    for(let keyCamp in campGroup){
                        for(let idGroup of  fieldsGall){
                            if(typeof campGroup[keyCamp] !== 'string') continue;
                            campGroupNew[keyCamp] =  campGroupNew[keyCamp].replaceAll("{{"+idGroup+"}}", "{{"+`${campGroup['group']}_${idGroup}_${valor}`+"}}" );
                        }
                    }
                    self.camposCumplidores[campGroupNew['id']] = true;
                    return campGroupNew;
                });

                self.flujoActivo.formulario.secciones[currentTabIndex].campos[keyInput].valor = valor;
                self.saveWhenOnBlur(self.flujoActivo.formulario.secciones[currentTabIndex].campos[keyInput]);
                self.flujoActivo.formulario.secciones[currentTabIndex].campos.splice(keyInput, 0, ...fieldsGroupAdap);
                self.campoCumpleCondiciones();
            }else{
                toolbox.alert('No se permite agregar mas agrupadores', '');
            }
        },
        subFieldsGroup(input, keyInput, currentTabIndex){
            const self = this;
            const valor = (!input.valor || Number.isNaN(Number(input.valor))? 1 : Number(input.valor));
            let minvalor = 1 ;
            if(!!Number(input.longitudMin)) minvalor = Number(input.longitudMin);
            if(valor > minvalor) {
                let allfields = self.flujoActivo.formulario.secciones[currentTabIndex].campos;
                let fieldsGroupAdap= allfields.filter(camp => camp['group'] === input.id ).map(e => `${e['group']}_${e['id']}_${valor}`);
                self.flujoActivo.formulario.secciones[currentTabIndex].campos[keyInput].valor = valor-1;
                self.saveWhenOnBlur(self.flujoActivo.formulario.secciones[currentTabIndex].campos[keyInput]);
                self.flujoActivo.formulario.secciones[currentTabIndex].campos = allfields.filter(a => !fieldsGroupAdap.includes(a.id));
                self.campoCumpleCondiciones();
            }else{
                toolbox.alert('No se permite quitar mas agrupadores', '');
            }
        },
        verificarValorRangoMonto(keyVehi, cotiKey, keyCober){
            const self = this;
            const rangoMonto = self.vehiculos[keyVehi].cotizaciones[cotiKey].coberturas[keyCober].rangoMonto.map(e => Number(e)).sort((a,b) => a - b);
            const monto = parseFloat((self.vehiculos[keyVehi].cotizaciones[cotiKey].coberturas[keyCober].monto ? self.vehiculos[keyVehi].cotizaciones[cotiKey].coberturas[keyCober].monto  : '').replace(/[^\d.]/g, ''));
            //let valorProm = parseFloat((self.vehiculos[keyVehi].valorProm ? self.vehiculos[keyVehi].valorProm  : '').replace(/[^\d.]/g, ''));
            //let valorProm = parseFloat((self.vehiculos[keyVehi].valorProm ? self.vehiculos[keyVehi].valorProm  : '').replace(/[^\d.]/g, ''));
            let valorProm = parseFloat((self.vehiculos[keyVehi].cotizaciones[cotiKey].sumAseg ? self.vehiculos[keyVehi].cotizaciones[cotiKey].sumAseg  : '').replace(/[^\d.]/g, ''));
            const count = rangoMonto.length - 1;
            const montoMin = valorProm*(rangoMonto[0]/100);
            const montoMax = valorProm*(rangoMonto[count]/100);

            if(!(monto > montoMin && monto < montoMax)){
                self.vehiculos[keyVehi].cotizaciones[cotiKey].coberturas[keyCober].monto = String(monto * rangoMonto[0]);
                toolbox.alert(`Monto no se encuentra dentro del rango ${montoMin} y ${montoMax}`, '');
                return false;
            }

            self.saveVehiclesOnBlur(self.vehiculos[keyVehi].vehiculoId);
        },
        hasAllValueZero(item) {
            let hasZero = true;
            Object.keys(item).map(function (a) {
                if (parseFloat(item[a].sumaAsegurada) > 0) {
                    hasZero = false;
                }
            })
            return hasZero;
        },
        /*selectFrecuenciaPago(vehiculoId, vehiKey, cotiKey) {

            console.log(this.vehiculos[vehiKey].cotizaciones[cotiKey].frecuenciaPagos)
            const frecuenciaF = this.vehiculos[vehiKey].cotizaciones[cotiKey].frecuenciaPagos;
            if (this.frecuenciaPagosCat[frecu.f].numeroPagos)

            //saveVehiclesOnBlur(vehiculoId);
        },*/
        setDateValue(input) {
            return (input.dMYear || '') + '-' + (input.dMonth || '') + '-' + (input.dMday || '')
        },
        dateMaskFormat(date) {
            if (date) {
                const day = (typeof date.getDate !== 'undefined') ? date.getDate() : '';
                const month = (typeof date.getMonth !== 'undefined') ? date.getMonth() : '';
                const year = (typeof date.getFullYear !== 'undefined') ? date.getFullYear() : '';

                if (day && month && year) {
                    return `${day}/${month}/${year}`;
                }
                else {
                    return '';
                }

            }
            else {
                return ''
            }
        },
    }
}
</script>
<style>
.vue-pdf-embed > div {
    margin-bottom: 20px;
    box-shadow: 0 2px 8px 4px rgba(0, 0, 0, 0.1);
}
</style>
