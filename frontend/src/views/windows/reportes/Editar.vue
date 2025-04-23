<template>
    <CRow>
        <CCol :xs="12">
            <CCard class="mb-4">
                <CCardHeader>
                    <strong>Editar reporte</strong>
                </CCardHeader>
                <CCardBody>
                    <h5>Datos generales</h5>
                    <hr>
                    <div class="row">
                        <div class="col-12 col-sm-4">
                            <div class="mb-3">
                                <label class="form-label">Nombre</label>
                                <input type="text" class="form-control" placeholder="Escribe aquí" v-model="nombre">
                            </div>
                        </div>
                        <div class="col-12 col-sm-4">
                            <div class="mb-3">
                                <label class="form-label">Tipo</label>
                                <select class="form-control" v-model="tipo">
                                    <!--<option value="n">Reporte normal</option>
                                    <option value="v">Reporte por vehículo</option>-->
                                    <option value="c">Reporte por cotización</option>
                                    <!--<option value="m">Reporte con descarga masiva</option>-->
                                    <!--<option value="s">Reporte de sistema</option>-->
                                </select>
                            </div>
                        </div>
                        <div v-if="tipo === 's'" class="col-12 col-sm-4">
                            <div class="mb-3">
                                <label class="form-label">Tipo de  reporte de sistema</label>
                                <div>
                                    <select class="form-control" v-model="system">
                                        <!--
                                        <option value="R1">Reporte de solicitudes ingresadas</option>
                                        <option value="R2">Reporte de solicitudes por nodo</option>
                                        -->
                                        <option value="R3">Reporte factor comercial</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-4">
                            <div class="mb-3">
                                <label class="form-label">Flujo</label>
                                <div>
                                    <multiselect
                                        v-model="flujos"
                                        :options="flujosOptions"
                                        :mode="'tags'"
                                        :searchable="true"
                                        @select="getCampos"
                                    />
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-4">
                            <div class="mb-3">
                                <label class="form-label">Estado</label>
                                <select class="form-control" v-model="activo">
                                    <option value="1">Activo</option>
                                    <option value="0">Desactivado</option>
                                </select>
                            </div>
                        </div>
                        <!--
                        <div  v-if="tipo !== 's'" class="col-12 col-sm-4">
                            <div class="mb-3">
                                <label class="form-label">Plantila +Docs</label>
                                <multiselect
                                    v-model="docsTpl"
                                    :options="docsTplOptions"
                                    :mode="'single'"
                                    :searchable="true"
                                />
                            </div>
                        </div>
                        -->
                    </div>
                    <div class="row">
                        <h6 class="text-primary">Configuración de visibilidad</h6>
                        <div class="row">
                            <div class="col-12 col-sm-4">
                                <div class="mb-3">
                                    <span>Selecciona el canal de usuarios</span>
                                    <multiselect
                                        v-model="canales_assign"
                                        :options="canales"
                                        :mode="'tags'"
                                        :searchable="true"/>
                                </div>
                            </div>
                            <div class="col-12 col-sm-4">
                                <div class="mb-3">
                                    <span>Selecciona los distribuidores</span>
                                    <multiselect
                                        v-model="grupos_assign"
                                        :options="grupos"
                                        :mode="'tags'"
                                        :searchable="true"/>
                                </div>
                            </div>
                            <div class="col-12 col-sm-4">
                                <div class="mb-3">
                                    <span>Selecciona los roles</span>
                                    <multiselect
                                        v-model="roles_assign"
                                        :options="roles"
                                        :mode="'tags'"
                                        :searchable="true"/>
                                </div>
                            </div>
                            <div class="col-12 col-sm-4">
                                <div class="mb-3">
                                    <span>Selecciona los usuarios</span>
                                    <multiselect
                                        v-model="users_assign"
                                        :options="users"
                                        :mode="'tags'"
                                        :searchable="true"/>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="text-muted">
                                    * Atención, si selecciona accesos se sobreescribirán en cascada Canales > Grupos > Roles
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--
                    <div class="row">
                        <h6 class="text-primary mt-3">Configuración de envio programado</h6>
                        <div class="row">
                            <div class="col-12 col-sm-4">
                                <div class="mb-3">
                                    <span>Envio Programado</span>
                                    <multiselect
                                        v-model="allowSendReport"
                                        label="text"
                                        value-prop="value"
                                        :options="sendReporteOp"/>
                                </div>
                            </div>
                            <div class="col-12 col-sm-4">
                                <div class="mb-3">
                                    <span>Fecha de Inicio</span>
                                    <input type="date" class="form-control"
                                        v-model="dateStart"
                                    >
                                </div>
                            </div>
                            <div class="col-12 col-sm-4">
                                <div class="mb-3">
                                    <span>Periodicidad</span>
                                    <multiselect
                                        v-model="period_assign"
                                        label="text"
                                        value-prop="value"
                                        :options="period"/>
                                </div>
                            </div>
                            <div v-if="allowSendReport">
                                <h6 class="text-primary">Configuracion de Correo</h6>
                                <div class="row mb-4">
                                    <div class="col-12 col-sm-4">
                                        <label class="form-label">API Key</label>
                                        <input type="text" class="form-control" placeholder="Escribe aquí" v-model="mailConfig.mailgun.apiKey">
                                    </div>
                                    <div class="col-12 col-sm-4">
                                        <label class="form-label">Dominio</label>
                                        <input type="text" class="form-control" placeholder="Escribe aquí" v-model="mailConfig.mailgun.domain">
                                    </div>
                                    <div class="col-12 col-sm-4">
                                        <label class="form-label">Enviar desde (from)</label>
                                        <input type="text" class="form-control" placeholder="Escribe aquí" v-model="mailConfig.mailgun.from">
                                    </div>
                                </div>
                                <h5>Dirección de envío</h5>
                                <input class="form-control mb-1" v-model="mailConfig.destino"/>
                                <h5>Asunto</h5>
                                <input class="form-control mb-3" v-model="mailConfig.asunto"/>
                                <div class="mb-3">
                                    <Editor
                                        :init="{
                                                    plugins: plugins,
                                                    toolbar: toolbar,
                                                    language: language,
                                                    promotion: false,
                                                    branding: false
                                                }"
                                        v-model="mailConfig.salidasEmail"
                                        :api-key="apiKey"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                    -->
                    <h5 class="mt-4">Configuración</h5>
                    <hr>
                    <div v-if="['c', 'v', 'n'].includes(tipo)">
                        <label>Campos a mostrar en reporte</label>
                        <div>
                            <multiselect
                                v-model="campos"
                                :options="camposOptions"
                                :mode="'tags'"
                                @select="selectCampos($event)"
                                @deselect="deselectCampos($event)"
                                @clear="clearCampos()"
                                :searchable="true"/>
                        </div>
                    </div>
                    <!--
                    <div v-if="tipo === 'm'">
                        <div>
                            <label>Campos de agrupación</label>
                            <div class="input-group mb-3"  v-for="(campo, indexDep ) in camposAgrupacion">
                                <select class="form-control" v-model="campo.campoOpt" style="max-width: 300px">
                                    <option value="showg"> Mostrar agrupado</option>
                                    <option value="sum"> Sumar</option>
                                    <option value="prom"> Promedio</option>
                                </select>
                                <multiselect class="form-control"
                                    v-model="campo.id"
                                    :options="camposOptions"
                                    :mode="'single'"
                                    :searchable="true"/>
                                <div class="btn btn" v-if="indexDep > 0" @click="camposAgrupacion.splice(-1, 1)">-</div>
                            </div>
                            <div class="text-end">
                                <div class="btn btn" @click="camposAgrupacion.push({ value: '', id: '', campoOpt: '' });">+</div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label>Campos para mostrar en reporte masivo</label>
                            <div>
                                <multiselect
                                    v-model="campos"
                                    :options="camposOptions"
                                    :mode="'tags'"
                                    :searchable="true"/>
                            </div>
                        </div>
                    </div>
                    -->
                    <div v-if="['c', 'v', 'n'].includes(tipo)" class="mt-4 col-12">
                        <div class="mb-3">
                            <label>Variables por Defecto </label>
                            <vue3-tags-input :tags="variablesDefault"
                                placeholder="Ingresa las variables por defecto"
                                class="form-control"
                                @on-tags-changed="addTag"
                            />
                            <div class="mt-3 col-12">
                                <b>FECHA_COTIZACION:</b> Imprime la fecha en la cual se realizó la cotización<br>
                                <b>FECHA_HOY:</b> Imprime la fecha del día<br>
                                <b>FECHA_EMISION:</b> Imprime la fecha de emisión<br>
                                <b>ID_COTIZACION:</b> Imprime el identificador de la cotización<br>
                                <b>CREADOR_NOMBRE:</b> Imprime el nombre del usuario creador (solo nodos privados)<br>
                                <b>CREADOR_NOMBRE_USUARIO:</b> Imprime el usuario del usuario creador (solo nodos privados)<br>
                                <b>CREADOR_CORP:</b> Imprime el corporativo del usuario creador (solo nodos privados)<br>
                                <b>CREADOR_CANAL:</b> Imprime los canales del usuario creador (solo nodos privados)<br>
                                <b>CREADOR_CANAL_CODIGO_INTERNO:</b> Imprime los codigos internos de los canales del usuario creador (solo nodos privados)<br>
                                <b>CREADOR_DISTRIBUIDOR:</b> Imprime los distribuidores del usuario creador (solo nodos privados)<br>
                                <b>CREADOR_TIENDA:</b> Imprime las tiendas del usuario creador (solo nodos privados)<br>
                                <b>CREADOR_EJECUTIVO:</b> Imprime los ejecutivo del usuario creador (solo nodos privados)<br>
                                <b>LINK_FORM:</b> Imprime el enlace de la cotización<br>
                                <b>ESTADO_ACTUAL:</b> Imprime el estado de la cotización<br>
                                <b>veh[NUMERO_VEHICULO]|[CAMPO_ID]</b> Imprime la informacion por vehiculo. Ej. veh1|datos_vehiculo_color<br>
                                <b>cot[NUMERO_COTIZACION]|[CAMPO_ID]</b> Imprime la informacion por cotizacion. Ej. cot1|COTIZACION_AS400.datosIdEmpresaGC.datos03.datosCotizacionGestorComercial2.listaCoberturas.coberturas.12.descripcion<br>
                                <br>
                                <h6 class="text-info"><strong>DATA VEHÍCULO:</strong></h6>
                                <p>vehX|variable, la X se reemplaza por el número de vehículo, ejemplos:</p>
                                <b>veh1|id</b> Imprime la informacion por vehiculo (id).<br>
                                <b>veh1|marca</b> Imprime la informacion por vehiculo (marca).<br>
                                <b>veh1|linea</b> Imprime la informacion por vehiculo (linea).<br>
                                <b>veh1|tipo</b> Imprime la informacion por vehiculo (tipo).<br>
                                <b>veh1|noPasajeros</b> Imprime la informacion por vehiculo (noPasajeros).<br>
                                <b>veh1|noChasis</b> Imprime la informacion por vehiculo (noChasis).<br>
                                <b>veh1|noMotor</b> Imprime la informacion por vehiculo (noMotor).<br>
                                <b>veh1|modelo</b> Imprime la informacion por vehiculo (modelo).<br>
                                <b>veh1|valorProm</b> Imprime la informacion por vehiculo (valorProm).<br>
                                <b>veh1|valorPromDef</b> Imprime la informacion por vehiculo (valorPromDef).<br>
                                <b>veh1|placa</b> Imprime la informacion por vehiculo (placa).<br>
                                <b>veh1|vehiculoNuevo</b> Imprime la informacion por vehiculo (vehiculo Nuevo).<br>
                                <br>
                                <!--<h6 class="text-info"><strong>DATA COTIZACIÓN:</strong></h6>
                                <p>Eludir el [[NUMERO_COTIZACION]] si la generación es a nivel de cotización</p>
                                <p>Agregar la preposición siguiente en la variable: (eludirla en caso la cotización sea a nivel de cotización)</p>
                                <p><b>veh[NUMERO_VEHICULO] |</b></p>
                                <div :style="{marginLeft: '20px'}">
                                    <b>cot[NUMERO_COTIZACION]|id</b> Imprime la informacion por cotizacion (id).<br>
                                    <b>cot[NUMERO_COTIZACION]|producto</b> Imprime la informacion por cotizacion (producto).<br>
                                    <b>cot[NUMERO_COTIZACION]|tarifa</b> Imprime la informacion por cotizacion (tarifa).<br>
                                    <b>cot[NUMERO_COTIZACION]|descuentoPorcentaje</b> Imprime la informacion por cotizacion (descuento Porcentaje).<br>
                                    <b>cot[NUMERO_COTIZACION]|formaPago</b> Imprime la informacion por cotizacion (forma Pago).<br>
                                    <b>cot[NUMERO_COTIZACION]|numeroPagos</b> Imprime la informacion por cotizacion (numero Pagos).<br>
                                    <b>cot[NUMERO_COTIZACION]|primaNeta</b> Imprime la informacion por cotizacion (prima Neta).<br>
                                    <b>cot[NUMERO_COTIZACION]|primaTotal</b> Imprime la informacion por cotizacion (prima Total).<br>
                                    <b>cot[NUMERO_COTIZACION]|recargoPorcentaje</b> Imprime la informacion por cotizacion (recargo).<br>
                                    <b>cot[NUMERO_COTIZACION]|emitirPoliza</b> Imprime la informacion por cotizacion (emitir poliza).<br>
                                    <b>cot[NUMERO_COTIZACION]|numeroCotizacionAS400</b> Imprime la informacion por cotizacion (numero cotizacion desde el AS400).<br>
                                    <b>cot[NUMERO_COTIZACION]|idCorrelativo</b> Imprime la informacion por cotizacion (correlativo id).<br>
                                    <b>cot[NUMERO_COTIZACION]|cob[NUMERO_COBERTURA]|cobertura</b> Imprime la informacion por cobertura (nombre cobertura).<br>
                                    <b>cot[NUMERO_COTIZACION]|cob[NUMERO_COBERTURA]|monto</b> Imprime la informacion por cobertura (monto).<br>
                                    <b>cot[NUMERO_COTIZACION]|cob[NUMERO_COBERTURA]|codigoCobertura</b> Imprime la informacion por cobertura (codigo cobertura).<br>
                                </div>-->
                            </div>
                        </div>
                    </div>
                    <div v-if="['c', 'v', 'n'].includes(tipo)" class="mt-4 col-12">
                        <h5 class="mt-4">Orden de Variables</h5>
                        <hr>
                        <div
                        v-for="(item, index) in ordenVariables"
                        :key="index+'_'+item"
                        class="list-item btn btn-success col-12 mt-3"
                        draggable="true"
                        @dragstart="onDragStart($event, index)"
                        @dragover="onDragOver($event)"
                        @drop="onDrop($event, index)"
                        >
                        {{ !!camposOptions.find(e => e.value === item)? camposOptions.find(e => e.value === item).label : item  }}
                        </div>
                    </div>
                    <div>
                        <div class="mt-4 text-end">
                            <button @click="$router.push('/reportes/configuracion')" class="btn btn-danger me-4">Cancelar</button>
                            <button @click="guardar" class="btn btn-primary">Guardar</button>
                        </div>
                    </div>
                </CCardBody>
            </CCard>
        </CCol>
    </CRow>
</template>

<script>
import toolbox from "@/toolbox";
import Multiselect from '@vueform/multiselect'
import Select from "@/views/forms/Select.vue";
import Vue3TagsInput from 'vue3-tags-input';

export default {
    name: 'Tables',
    components: {Select, Multiselect, Vue3TagsInput},
    data() {
        return {
            id: 0,
            nombre: '',
            activo: 1,
            tipo: 'n',
            producto: 0,
            tmpCampos: {},
            docsTpl: {},
            docsTplOptions: [],

            campos: [],
            camposOptions: [],

            flujos: [],
            flujosOptions: [],
            camposAgrupacion: [],

            system:'',
            systemOptions:[],

            roles: [],
            users: [],
            grupos: [],
            canales: [],
            variablesDefault: [],
            ordenVariables: [],
            sendReporteOp:[{value: true, text:'Si'}, {value: false, text:'No'}],
            period:[
                {value: 'day', text:'Diario'},
                {value: 'week', text:'Semanal'},
                {value: 'month', text:'Mes'},
                {value: 'year', text:'Año'},
            ],

            apiKey: 'n8ab72lgcjz7weqad287mk9pgjg0acg88z7xzhdf0y0hc9zn',
            canales_assign: [],
            grupos_assign: [],
            users_assign: [],
            roles_assign: [],

            allowSendReport: false,
            dateStart: '',
            period_assign: '',
            mailConfig: {
                salidasEmail: '',
                asunto: '',
                destino: '',
                attachments: '',
                autoSend: false,
                reenvio: false,
                copia: [{destino: ''}],
                mailgun: {
                    apiKey: '',
                    domain: '',
                    from: '',
                }
            },
            draggedIndex: null,
        };
    },
    mounted() {
        this.id = (typeof this.$route.params.id !== 'undefined') ? parseInt(this.$route.params.id) : 0;
        const self = this;
        /* toolbox.doAjax('GET', 'reportes/get-docsplus-tpl', {

            },
            function (response) {
                self.docsTpl = response.data;

                self.docsTplOptions = [];
                Object.keys(response.data).map(function (a, b) {
                    self.docsTplOptions.push({
                        value: response.data[a].t,
                        label: response.data[a].n,
                    })
                })

                self.getFlujos();
            },
            function (response) {
                toolbox.alert(response.msg, 'danger');
            }); */

        //this.getDocsTpl();
        self.getRoles();
        self.getCanales();
        self.getGrupos();
        self.getUsers();
        self.getFlujos();
    },
    methods: {
        getData() {
            const self = this;

            toolbox.doAjax('POST', 'reportes/get', {
                    id: self.id,
                },
                function (response) {
                    self.id = response.data.id;
                    self.activo = response.data.activo;
                    self.nombre = response.data.nombre;
                    self.tipo = response.data.tipo;
                    // self.docsTpl = response.data.c.tpl;

                    self.flujos = [];
                    Object.keys(response.data.c.p).map(function (a, b) {
                        self.flujos.push(response.data.c.p[a]);
                    })

                    /* self.camposAgrupacion = [];
                    if(response.data.c.ag){
                        Object.keys(response.data.c.ag).map(function (a, b) {
                            self.camposAgrupacion.push({ value: response.data.c.ag[a], id: response.data.c.ag[a].id, campoOpt: response.data.c.ag[a].opt });
                        })
                    } */

                    self.tmpCampos = response.data.c.c;
                    self.roles_assign = response?.data?.c?.visibilidad?.roles;
                    self.users_assign = response?.data?.c?.visibilidad?.users;
                    self.grupos_assign = response?.data?.c?.visibilidad?.grupos;
                    self.canales_assign = response?.data?.c?.visibilidad?.canales;
                    self.variablesDefault = response?.data?.c?.variablesDefault;
                    if(!!response?.data?.c?.ordenVariables){
                        self.ordenVariables = response?.data?.c?.ordenVariables;
                    }

                    /*
                    self.allowSendReport = !!response.data.sendReport;
                    self.dateStart = response.data.dateToSend;
                    self.period_assign = response.data.period; */
                    self.system = response?.data?.c?.system;
                    /*
                    if(!!response.data.mail) self.mailConfig = response.data.mail;
                    */

                    self.getCampos();

            })
        },
        getFlujos() {
            const self = this;

            toolbox.doAjax('GET', 'reportes/get-flujos', {},
                function (response) {
                    self.flujosOptions = [];
                    Object.keys(response.data).map(function (a, b) {
                        self.flujosOptions.push({
                            value: response.data[a].id,
                            label: response.data[a].nombreProducto,
                        })
                    })

                    self.getData();
                    //self.getCampos();
            })
        },
        guardar() {

            const self = this;

            let errors = false;
            if (toolbox.isEmpty(this.nombre)) {
                toolbox.alert('Debe ingresar un nombre de usuario', 'danger');
                errors = true;
            }

            if (!errors) {
                toolbox.doAjax('POST', 'reportes/save', {
                        id: self.id,
                        nombre: self.nombre,
                        activo: self.activo,
                        flujos: self.flujos,
                        productos: self.productos,
                        tipo: self.tipo,
                        campos: self.campos,
                        docsTpl: self.docsTpl,
                        agrupacion: self.camposAgrupacion,
                        visibilidad: {
                            roles: self.roles_assign,
                            users: self.users_assign,
                            grupos: self.grupos_assign,
                            canales: self.canales_assign,
                        },
                        variablesDefault: self.variablesDefault,
                        ordenVariables: self.ordenVariables,
                        allowSendReport: self.allowSendReport,
                        dateStart: self.dateStart,
                        period_assign: self.period_assign,
                        mailConfig: self.mailConfig,
                        system: self.system,
                    },
                    function (response) {
                        toolbox.alert(response.msg, 'success');
                        if (self.id === 0) {
                            self.id = response.data.id;
                            self.$router.push('/reportes/configuracion/' + response.data.id);
                        }
                        self.getData();
                    },
                    function (response) {
                        toolbox.alert(response.msg, 'danger');
                    })
            }
        },
        getCampos() {
            const self = this;

            toolbox.doAjax('POST', 'reportes/nodos/campos', {
                    productos: self.flujos
                },
                function (response) {

                    self.camposOptions = [];
                    Object.keys(response.data).map(function (a, b) {

                        self.camposOptions.push({
                            value: response.data[a].id,
                            label: response.data[a].pr + ' - ' +response.data[a].nodo + " - " + response.data[a].label,
                        })
                    })

                    self.campos = [];
                    Object.keys(self.tmpCampos).map(function (a, b) {
                        self.campos.push(self.tmpCampos[a].id);
                    })
                    if(self.ordenVariables.length < 1){
                        self.ordenVariables = [...self.campos, ...self.variablesDefault];
                    }
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                });
        },
        //###Roles######
        getUsers() {
            const self = this;
            toolbox.doAjax('GET', 'users/list', {},
                function (response) {
                    Object.keys(response.data).map(function (a, b) {
                        self.users.push({
                            value: response.data[a].id,
                            label: response.data[a].name,
                        })
                    })
                },
                function (response) {
                    //toolbox.alert(response.msg, 'danger');
                })
        },
        getRoles() {
            const self = this;
            toolbox.doAjax('GET', 'users/role/list', {},
                function (response) {
                    Object.keys(response.data).map(function (a, b) {
                        self.roles.push({
                            value: response.data[a].id,
                            label: response.data[a].name,
                        })
                    })
                },
                function (response) {
                    //toolbox.alert(response.msg, 'danger');
                })
        },
        getGrupos() {
            const self = this;
            toolbox.doAjax('GET', 'users/grupo/list', {},
                function (response) {
                    Object.keys(response.data).map(function (a, b) {
                        self.grupos.push({
                            value: response.data[a].id,
                            label: response.data[a].nombre,
                        })
                    })
                },
                function (response) {
                    //toolbox.alert(response.msg, 'danger');
                })
        },
        getCanales() {
            const self = this;
            toolbox.doAjax('GET', 'users/canal/list', {},
                function (response) {
                    Object.keys(response.data).map(function (a, b) {
                        self.canales.push({
                            value: response.data[a].id,
                            label: response.data[a].nombre,
                        })
                    })
                },
                function (response) {
                    //toolbox.alert(response.msg, 'danger');
                })
        },
        addTag (newTag) {
            const self = this;
            self.variablesDefault = newTag;
            const allVariables = [...self.campos, ...self.variablesDefault];
            self.ordenVariables = self.ordenVariables.filter(e => allVariables.includes(e));
            for (const val of self.variablesDefault){
                if(!self.ordenVariables.includes(val)) self.ordenVariables.push(val);
            }
        },
        selectCampos(event){
            const self = this;
            self.ordenVariables.push(event);
        },
        deselectCampos(event){
            const self = this;
            self.ordenVariables = self.ordenVariables.filter(e => e !== event);
        },
        clearCampos(){
            const self = this;
            self.ordenVariables = self.ordenVariables.filter(e => self.variablesDefault.includes(e));
        },

        //Drag and Drop
        onDragStart(event, index) {
            const self = this;
            self.draggedIndex = index;
            event.dataTransfer.effectAllowed = 'move';
        },
        onDragOver(event) {
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';
        },
        onDrop(event, index) {
            const self = this;
            event.preventDefault();
            const draggedItem = self.ordenVariables[self.draggedIndex];
            self.ordenVariables.splice(self.draggedIndex, 1);
            self.ordenVariables.splice(index, 0, draggedItem);
            self.draggedIndex = null;
        },
    }
}
</script>
