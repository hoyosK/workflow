<template>
    <CRow>
        <CCol :xs="12">
            <CCard class="mb-4">
                <CCardHeader>
                    <strong>Edición de {{nombreCatalogo}}</strong>
<!--                    <button @click="$router.push('/'+urlOpt+'/0')" class="btn btn-primary float-end"><i class="fas fa-plus me-2"></i>Crear nuevo</button>-->
                    <button @click="sync" class="btn btn-primary float-end me-2"><i class="fas fa-sync me-2"></i>Sincronizar con AS400</button>
                    <button @click="createNew" class="btn btn-primary float-end me-2"><i class="fas fa-plus me-2"></i>Crear nuevo</button>
                </CCardHeader>
                <CCardBody>
                    <div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Buscar por</label>
                            <div class="row">
                                <div class="col-3">
                                    <select class="form-select" v-model="typeSearch">
                                        <option :value="head.value" v-for="head in headers">{{ head.text }}</option>
                                    </select>
                                </div>
                                <div class="col-3">
                                    <input type="text" v-model="searchValue" class="form-control" placeholder="Escribe aquí tu búsqueda">
                                </div>
                            </div>
                        </div>
                    </div>
                    <EasyDataTable :headers="headers" :items="items" :search-field="typeSearch" :search-value="searchValue" alternating >
                        <template #item-operation="item">
                            <div class="text-center">
                                <i class="fas fa-pencil icon me-3" @click="editItem(item)"></i>
                                <span class="cursor-pointer" v-if="slugCatalogo === 'marcas'" @click="sync('lineas', item)"><i class="fas fa-sync me-2"></i>Líneas</span>
<!--                                <i class="fas fa-trash icon" @click="deleteItem(item)"></i>-->
                            </div>
                        </template>
                    </EasyDataTable>
                    <div class="mt-4">
                        <h2>Log de sincronización</h2>
                        <small>Este log está activo únicamente para marcas y líneas</small>
                        <textarea v-model="logSync" class="form-control w-100" style="min-height: 300px"></textarea>
                    </div>
                </CCardBody>
                <div v-if="showConfig" @close="() => { showConfig = false }" class="globalModal">
                    <div class="globalModalContainer position-relative">
                        <div @click="showConfig = false" class="globalModalClose mt-3" style="position: absolute"><i class="fas fa-times-circle"></i></div>
                        <div>
                            <div>
                                <strong>Edición de {{nombreCatalogo}}</strong>
                                <div class="mt-4">
                                    <div class="row">
                                        <div class="col-12 col-sm-4 mt-3" v-for="(item, key) in campos">
                                            <template v-if="key !== 'access'">
                                                <div v-if="['activo', 'flock', 'isRoja', 'asegurable', 'noAsegurable', 'altoRiesgo','tieneDescuento', 'rc'].includes(item.campo)">
                                                    <label v-if="item.campo === 'flock'" :for="item.campo">Bloqueado en sincronización</label>
                                                    <label v-else-if="item.campo === 'isRoja'" :for="item.campo">¿Es zona roja?</label>
                                                    <label v-else-if="item.campo === 'tieneDescuento'" :for="item.campo">¿Soporta descuento?</label>
                                                    <label v-else-if="item.campo === 'rc'" :for="item.campo">Es RC</label>
                                                    <label v-else :for="item.campo">{{item.campo}}</label>
                                                    <div>
                                                        <input :name="item.campo" type="radio" v-model="item.valor" :id="item.campo + '_si'" value="1"/><label :for="item.campo + '_si'" class="me-4 ms-1">Si</label>
                                                        <input :name="item.campo" type="radio" v-model="item.valor" :id="item.campo + '_no'" value="0"/><label :for="item.campo + '_no'" class="me-2 ms-1">No</label>
                                                    </div>
                                                </div>
                                                <div v-else-if="['zonaEmision'].includes(item.campo)">
                                                    <label>{{item.campo}}</label>
                                                    <select  class="form-select" v-model="item.valor">
                                                        <option v-for="cat in catalogosOutSide['zona_emision']"
                                                                :value="cat.value">{{cat.label}}</option>
                                                    </select>
                                                </div>
                                                <div v-else-if="['grupo'].includes(item.campo)">
                                                    <label>{{item.campo}}</label>
                                                    <select  class="form-select" v-model="item.valor">
                                                        <option value="0">Sin grupo</option>
                                                        <option v-for="cat in catalogosOutSide['grupo_coberturas']"
                                                                :value="cat.id">{{cat.label}}</option>
                                                    </select>
                                                </div>
                                                <div v-else>
                                                    <label>{{item.campo}}</label>
                                                    <div>
                                                        <input type="text" v-model="item.valor" class="form-control">
                                                    </div>
                                                </div>
                                            </template>

                                        </div>
                                    </div>
                                    <div class="row mt-3" v-if="slugCatalogo === 'productos'">
                                        <div class="col-12">
                                            <label class="fw-bold">Accesos de producto</label>
                                            <div class="row">
                                                <div class="col-12 col-sm-4">
                                                    <div class="mb-3">
                                                        <span>Selecciona el canal de usuarios</span>
                                                        <multiselect
                                                            v-model="access.canales_assign"
                                                            :options="canales"
                                                            :mode="'tags'"
                                                            :searchable="true"/>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-sm-4">
                                                    <div class="mb-3">
                                                        <span>Selecciona los distribuidores</span>
                                                        <multiselect
                                                            v-model="access.grupos_assign"
                                                            :options="grupos"
                                                            :mode="'tags'"
                                                            :searchable="true"/>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-sm-4">
                                                    <div class="mb-3">
                                                        <span>Selecciona los roles</span>
                                                        <multiselect
                                                            v-model="access.roles_assign"
                                                            :options="roles"
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
                                    </div>
                                    <div class="text-muted mt-4">
                                        El bloqueo de sincronización impedirá que se sobreescriba el contenido al sincronizar.
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 text-danger">
                                Atención, editar el catálogo puede provocar errores al enviar información hacia AS400, por favor, asegúrese que los datos que introduzca son son correctos.
                            </div>
                            <div class="mt-3 text-end">
                                <button @click="guardarModificacion" class="btn btn-success">Guardar edición</button>
                            </div>
                        </div>
                    </div>
                </div>
            </CCard>
        </CCol>
    </CRow>
</template>

<script>
import toolbox from "@/toolbox";
import {useRoute} from "vue-router";
import Button from "@/views/forms/form_elements/FormElementButton.vue";
import Multiselect from '@vueform/multiselect'
import Vue3TagsInput from 'vue3-tags-input';


export default {
    name: 'Tables',
    components: {Button, Multiselect, Vue3TagsInput},
    data() {
        return {
            urlOpt: 'admin/catalogo',
            showConfig: false,
            idRow: 0,
            slugCatalogo: '',
            nombreCatalogo: '',
            typeSearch: 'nombre',
            searchValue: '',
            headers: [
                {text: "Nombre", value: "nombre"},
                {text: "Operación", value: "operation", width: 150 },
            ],
            items: [],

            //edición
            campos: {},

            // productos
            agentesOptions: [],
            agentes: {},

            tarifas: {},
            tarifaSelected: false,
            tarifasOptions: [],

            coberturas: {},
            coberturaSelected: false,
            coberturasEnTarifa: [],


            productoTarifas: [],
            catalogosOutSide: {'zonaEmision': []},

            // accesos
            canales: [],
            grupos: [],
            roles: [],

            access: {
                canales_assign: [],
                grupos_assign: [],
                roles_assign: [],
            },
            logSync: '',

        };
    },
    mounted() {
        this.slugCatalogo = (typeof this.$route.params.id !== 'undefined') ? this.$route.params.id : 0;
        this.loadCatalogo();
    },
    methods: {
        loadCatalogo() {

            this.showConfig = false;

            if (this.slugCatalogo === 'marcas') {
                this.nombreCatalogo = 'Marcas';
                this.headers = [
                    {text: "Nombre de marca", value: "nombre"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }
            else if (this.slugCatalogo === 'lineas') {
                this.nombreCatalogo = 'Líneas';
                this.headers = [
                    {text: "Nombre de línea", value: "nombre"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }
            else if (this.slugCatalogo === 'lineas_no_asegurables') {
                this.nombreCatalogo = 'Líneas no asegurables';
                this.headers = [
                    {text: "Nombre de línea", value: "nombre"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }
            else if (this.slugCatalogo === 'tipo_linea') {
                this.nombreCatalogo = 'Tipo de línea';
                this.headers = [
                    {text: "Código", value: "codigo"},
                    {text: "Nombre", value: "nombre"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }
            else if (this.slugCatalogo === 'tipo_placa') {
                this.nombreCatalogo = 'Tipo de placa';
                this.headers = [
                    {text: "Código", value: "codigo"},
                    {text: "Nombre", value: "nombre"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }
            else if (this.slugCatalogo === 'tipo_vehiculo') {
                this.nombreCatalogo = 'Tipo de vehículo';
                this.headers = [
                    {text: "Código", value: "codigo"},
                    {text: "Nombre", value: "nombre"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }
            else if (this.slugCatalogo === 'tipo_cuenta_tarjeta') {
                this.nombreCatalogo = 'Tipo de cuenta o tarjeta';
                this.headers = [
                    {text: "Código", value: "codigo"},
                    {text: "Nombre", value: "nombre"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }
            else if (this.slugCatalogo === 'tipo_licencia') {
                this.nombreCatalogo = 'Tipo de licencia';
                this.headers = [
                    {text: "Código", value: "codigo"},
                    {text: "Nombre", value: "nombre"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }
            else if (this.slugCatalogo === 'tipo_movimiento') {
                this.nombreCatalogo = 'Tipo de movimiento';
                this.headers = [
                    {text: "Código", value: "codigo"},
                    {text: "Nombre", value: "nombre"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }
            else if (this.slugCatalogo === 'tipo_movimiento') {
                this.nombreCatalogo = 'Tipo de movimiento';
                this.headers = [
                    {text: "Código", value: "codigo"},
                    {text: "Nombre", value: "nombre"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }
            else if (this.slugCatalogo === 'tipo_documento') {
                this.nombreCatalogo = 'Tipo de documento';
                this.headers = [
                    {text: "Código", value: "codigo"},
                    {text: "Nombre", value: "nombre"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }
            else if (this.slugCatalogo === 'tarifas') {
                this.nombreCatalogo = 'Tarifas';
                this.headers = [
                    {text: "Id tarifa", value: "idTarifa"},
                    {text: "Clasificación", value: "clasificacion"},
                    {text: "Descripcion", value: "descripcion"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }
            else if (this.slugCatalogo === 'tipo_tarifas') {
                this.nombreCatalogo = 'Tipo de tarifas';
                this.headers = [
                    {text: "Código", value: "codigo"},
                    {text: "Nombre", value: "nombre"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }
            else if (this.slugCatalogo === 'codigo_agente') {
                this.nombreCatalogo = 'Agentes';
                this.headers = [
                    {text: "Código", value: "codigo"},
                    {text: "Nombre", value: "nombre"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }
            else if (this.slugCatalogo === 'agente_tarifas') {
                this.nombreCatalogo = 'Tarifas de agente';
                this.headers = [
                    {text: "Código", value: "codigo"},
                    {text: "Nombre", value: "nombre"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }
            else if (this.slugCatalogo === 'productos') {
                this.nombreCatalogo = 'Productos';
                this.headers = [
                    {text: "Id de producto", value: "codigoProducto"},
                    {text: "Nombre", value: "nombre"},
                    {text: "Moneda", value: "idMoneda"},
                    {text: "Descripción", value: "descripcion"},
                    {text: "Estado", value: "estado"},
                    {text: "Activo", value: "activo"},
                    {text: "Rango desde", value: "rangoPolizaDesde"},
                    {text: "Rango hasta", value: "rangoPolizaHasta"},
                    {text: "RC", value: "rc"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }
            else if (this.slugCatalogo === 'tipo_productos') {
                this.nombreCatalogo = 'Tipo de productos';
                this.headers = [
                    {text: "Código", value: "codigo"},
                    {text: "Nombre", value: "nombre"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }
            else if (this.slugCatalogo === 'beneficiarios') {
                this.nombreCatalogo = 'Beneficiarios';
                this.headers = [
                    {text: "Código", value: "codigo"},
                    {text: "Nombre", value: "nombre"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }
            else if (this.slugCatalogo === 'formas_pago') {
                this.nombreCatalogo = 'Formas de pago';
                this.headers = [
                    {text: "Código", value: "codigo"},
                    {text: "Descripción", value: "descripcion"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }
            else if (this.slugCatalogo === 'estado_civil') {
                this.nombreCatalogo = 'Estado civil';
                this.headers = [
                    {text: "Código", value: "codigo"},
                    {text: "Descripción", value: "descripcion"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }
            else if (this.slugCatalogo === 'profesion') {
                this.nombreCatalogo = 'Profesión';
                this.headers = [
                    {text: "Código", value: "codigo"},
                    {text: "Descripción", value: "descripcion"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }

            else if (this.slugCatalogo === 'zona') {
                this.nombreCatalogo = 'Zonas';
                this.headers = [
                    {text: "Código", value: "codigo"},
                    {text: "Descripción", value: "descripcion"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }

            else if (this.slugCatalogo === 'medio_cobro') {
                this.nombreCatalogo = 'Medio de cobro';
                this.headers = [
                    {text: "Código", value: "codigo"},
                    {text: "Descripción", value: "nombre"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }

            else if (this.slugCatalogo === 'clase_tarjeta') {
                this.nombreCatalogo = 'Clase Tarjeta';
                this.headers = [
                    {text: "Código", value: "codigo"},
                    {text: "Descripción", value: "descripcion"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }

            else if (this.slugCatalogo === 'tipo_cuenta_bancaria') {
                this.nombreCatalogo = 'Tipo Cuenta Bancaria';
                this.headers = [
                    {text: "Código", value: "codigo"},
                    {text: "Descripción", value: "descripcion"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }

            else if (this.slugCatalogo === 'banco_emisor') {
                this.nombreCatalogo = 'Banco Emisor';
                this.headers = [
                    {text: "Código", value: "codigo"},
                    {text: "Descripción", value: "descripcion"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }

            else if (this.slugCatalogo === 'sexo') {
                this.nombreCatalogo = 'Sexo';
                this.headers = [
                    {text: "Código", value: "codigo"},
                    {text: "Descripción", value: "descripcion"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }

            else if (this.slugCatalogo === 'zona_emision') {
                this.nombreCatalogo = 'Zona emisión';
                this.headers = [
                    {text: "Código", value: "codigo"},
                    {text: "Descripción", value: "descripcion"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }

            else if (this.slugCatalogo === 'nacionalidad') {
                this.nombreCatalogo = 'Nacionalidad';
                this.headers = [
                    {text: "Código", value: "codigo"},
                    {text: "Descripción", value: "descripcion"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }
            else if (this.slugCatalogo === 'tipo_cliente') {
                this.nombreCatalogo = 'Tipo cliente';
                this.headers = [
                    {text: "Código", value: "codigo"},
                    {text: "Descripción", value: "descripcion"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }
            else if (this.slugCatalogo === 'tipo_sociedad') {
                this.nombreCatalogo = 'Tipo sociedad';
                this.headers = [
                    {text: "Código", value: "codigo"},
                    {text: "Descripción", value: "descripcion"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }
            else if (this.slugCatalogo === 'actividad_economica') {
                this.nombreCatalogo = 'Actividad Economica';
                this.headers = [
                    {text: "Código", value: "codigo"},
                    {text: "Descripción", value: "descripcion"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }
            else if (this.slugCatalogo === 'tipo_uso') {
                this.nombreCatalogo = 'Tipo uso';
                this.headers = [
                    {text: "Código", value: "codigo"},
                    {text: "Descripción", value: "descripcion"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }
            else if (this.slugCatalogo === 'tipo_combustible') {
                this.nombreCatalogo = 'Tipo combustible';
                this.headers = [
                    {text: "Código", value: "codigo"},
                    {text: "Descripción", value: "descripcion"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }
            else if (this.slugCatalogo === 'tipo_tecnologia') {
                this.nombreCatalogo = 'Tipo tecnología';
                this.headers = [
                    {text: "Código", value: "codigo"},
                    {text: "Descripción", value: "descripcion"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }
            else if (this.slugCatalogo === 'tipo_cartera') {
                this.nombreCatalogo = 'Tipo cartera';
                this.headers = [
                    {text: "Código", value: "codigo"},
                    {text: "Descripción", value: "descripcion"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }
            else if (this.slugCatalogo === 'subtipo_movimiento') {
                this.nombreCatalogo = 'Subtipo movimiento';
                this.headers = [
                    {text: "Código", value: "codigo"},
                    {text: "Descripción", value: "descripcion"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }
            else if (this.slugCatalogo === 'departamento') {
                this.nombreCatalogo = 'Departamento';
                this.headers = [
                    {text: "Código", value: "codigo"},
                    {text: "Descripción", value: "descripcion"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }
            else if (this.slugCatalogo === 'municipio') {
                this.nombreCatalogo = 'Municipio';
                this.headers = [
                    {text: "Código", value: "codigo"},
                    {text: "Descripción", value: "descripcion"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }
            else if (this.slugCatalogo === 'codigo_alarma') {
                this.nombreCatalogo = 'Código alarma';
                this.headers = [
                    {text: "Código", value: "codigo"},
                    {text: "Descripción", value: "descripcion"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }
            else if (this.slugCatalogo === 'promociones') {
                this.nombreCatalogo = 'Promociones';
                this.headers = [
                    {text: "Código", value: "codigo"},
                    {text: "Descripción", value: "descripcion"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }
            else if (this.slugCatalogo === 'tipo_asignacion') {
                this.nombreCatalogo = 'Tipo Asignación';
                this.headers = [
                    {text: "Código", value: "codigo"},
                    {text: "Descripción", value: "descripcion"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }
            else if (this.slugCatalogo === 'tipo_usuario') {
                this.nombreCatalogo = 'Tipo Usuario';
                this.headers = [
                    {text: "Código", value: "codigo"},
                    {text: "Descripción", value: "descripcion"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }
            else if (this.slugCatalogo === 'seleccion') {
                this.nombreCatalogo = 'Selección';
                this.headers = [
                    {text: "Código", value: "codigo"},
                    {text: "Descripción", value: "descripcion"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }
            else if (this.slugCatalogo === 'linea_por_intermediario') {
                this.nombreCatalogo = 'Línea por intermediario';
                this.headers = [
                    {text: "Código de intermediario", value: "codigoIntermediario"},
                    {text: "Código zona de emisión", value: "codigoZonaEmision"},
                    {text: "Operación", value: "operation", width: 150 },
                ];
            }

            const self = this;
            toolbox.doAjax('POST', 'admin/catalogo/load', {
                    slug: self.slugCatalogo,
                    opt: 'get',
                },
                function (response) {
                    //self.items = response.data;
                    self.items = toolbox.prepareForTable(response.data);

                    if (self.slugCatalogo === 'productos') {
                        console.log(response.data);
                        //self.access = response.data.acceso;
                    }
                    //console.log(self.items);
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
        deleteItem(item) {
            const self = this;
            toolbox.confirm('Se desactivará este usuario, ¿desea continuar?', function () {
                toolbox.doAjax('POST', 'admin/canales/delete', {
                        id: item.id,
                    },
                    function (response) {
                        toolbox.alert(response.msg, 'success');
                        self.getItems();
                    },
                    function (response) {
                        toolbox.alert(response.msg, 'danger');
                    })
            })
        },
        editItem(item) {
            const self = this;
            this.showConfig = true;
            this.idRow = item.id;
            this.getFields(item);

            if (this.slugCatalogo === 'productos') {
                this.getCanales();
                this.getGrupos();
                this.getRoles();
                setTimeout(function () {
                    self.getAccess(item);
                }, 500);
            }

            if(['productos', 'coberturas'].includes(this.slugCatalogo)) this.bringCatalogos();
        },
        sync(typeSync, item) {
            const self = this;
            toolbox.confirm('Se realizará una sincronización con AS400, esto puede demorar unos minutos, ¿desea continuar?', function () {

                let slugCat = self.slugCatalogo;

                if (typeSync === 'lineas') {
                    slugCat = 'lineas';
                }

                toolbox.doAjax('POST', 'admin/catalogo/load', {
                        slug: slugCat,
                        opt: 'sync',
                        data: (typeof item !== 'undefined' && typeof item.id !== 'undefined') ? item.id : false,
                    },
                    function (response) {
                        //self.items = response.data;
                        //self.items = toolbox.prepareForTable(response.data);
                        //console.log(self.items);
                        self.loadCatalogo();
                        self.logSync = response.data;

                        toolbox.alert(response.msg, 'success');
                    },
                    function (response) {
                        toolbox.alert(response.msg, 'danger');
                    })
            })
        },
        createNew() {
            this.showConfig = true;
            this.idRow = 0;
            this.getFields(0);
            if(['productos', 'coberturas'].includes(this.slugCatalogo)) this.bringCatalogos();
        },
        getFields(item) {
            const self = this;
            toolbox.doAjax('POST', 'admin/catalogo/load-fields', {
                    slug: self.slugCatalogo,
                    id: item.id,
                },
                function (response) {
                    //self.items = response.data;
                    //self.items = toolbox.prepareForTable(response.data);
                    //console.log(self.items);
                    //self.loadCatalogo();
                    self.campos = response.data;
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
        getAccess(item) {
            const self = this;
            toolbox.doAjax('POST', 'admin/catalogo/load-access', {
                    slug: self.slugCatalogo,
                    id: item.id,
                },
                function (response) {
                    //self.items = response.data;
                    //self.items = toolbox.prepareForTable(response.data);
                    //console.log(self.items);
                    //self.loadCatalogo();
                    self.access = response.data;
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
        guardarModificacion() {
            const self = this;
            toolbox.doAjax('POST', 'admin/catalogo/save-row', {
                    slug: self.slugCatalogo,
                    id: self.idRow,
                    campos: self.campos,
                    access: self.access,
                },
                function (response) {
                    self.loadCatalogo();
                    toolbox.alert(response.msg, 'success');
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },

        // especificos
        loadCatalogosProductos(catalogo) {
            const self = this;
            toolbox.doAjax('POST', 'admin/catalogo/load', {
                    slug: catalogo,
                    opt: 'get',
                },
            function (response) {

                if (catalogo === 'codigo_agente') {
                    self.agentes = response.data;
                }
                else if (catalogo === 'tarifas') {
                    self.tarifas = response.data;

                    self.tarifasOptions = [];
                    Object.keys(response.data).map(function (a, b) {
                        self.tarifasOptions.push({
                            value: response.data[a].id,
                            label: response.data[a].descripcion,
                        })
                    })
                }
                else if (catalogo === 'producto_tarifa') {
                    //self.productoTarifas = response.data;
                }

                /*self.agentesOptions = [];
                Object.keys(response.data).map(function (a, b) {
                    self.agentesOptions.push({
                        value: response.data[a].id,
                        label: response.data[a].nombre,
                    })
                })*/
            },
            function (response) {
                toolbox.alert(response.msg, 'danger');
            })
        },

        asociarTarifa (newTag) {
            const self = this;
            let descTarifa = '';
            this.tarifasOptions.forEach(function (a){
                if (a.value === self.tarifaSelected) {
                    descTarifa = a.label;
                }
            })

            this.productoTarifas.push({
                tarifaId: self.tarifaSelected,
                desc: descTarifa,
            });
            self.tarifaSelected = 0;

            /*this.coberturas.Options = newTag;
            this.page.Tags = newTag.toString();*/
        },

        bringCatalogos(){
            const self = this;
            toolbox.doAjax('POST', 'tareas/catalogos/bring', {
                catalogos: ['zona_emision', 'grupo_coberturas'],
            }, function (response) {
                self.catalogosOutSide = response.data;
            }, function (response) {
                toolbox.alert(response.msg, 'danger');
            })
        },

        getRoles() {

            const self = this;
            toolbox.doAjax('GET', 'users/role/list', {},
                function (response) {
                    //self.items = response.data;
                    self.roles = [];
                    Object.keys(response.data).map(function (a, b) {
                        self.roles.push({
                            value: response.data[a].id,
                            label: response.data[a].name,
                        })
                    })
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
        getGrupos() {

            const self = this;
            toolbox.doAjax('GET', 'users/grupo/list', {},
                function (response) {
                    //self.items = response.data;
                    self.grupos = [];
                    Object.keys(response.data).map(function (a, b) {
                        self.grupos.push({
                            value: response.data[a].id,
                            label: response.data[a].nombre,
                        })
                    })
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
        getCanales() {

            const self = this;
            toolbox.doAjax('GET', 'users/canal/list', {},
                function (response) {
                    //self.items = response.data;
                    self.canales = [];
                    Object.keys(response.data).map(function (a, b) {
                        self.canales.push({
                            value: response.data[a].id,
                            label: response.data[a].nombre,
                        })
                    })
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
    }
}
</script>
