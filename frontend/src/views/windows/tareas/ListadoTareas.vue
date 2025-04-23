<template>
    <CCard class="mb-4">
        <CCardHeader>
            <strong>Filtros de búsqueda</strong>
        </CCardHeader>
        <CCardBody>
            <div class="row">
                <div class="col-12 col-sm-3">
                    <div>
                        <label class="form-label">Fecha Inicial</label>
                    </div>
                    <input type="date" class="form-control" placeholder="Selecciona la fecha" v-model="fechaIni">
                </div>
                <div class="col-12 col-sm-3">
                    <div>
                        <label class="form-label">Fecha Final</label>
                    </div>
                    <input type="date" class="form-control" placeholder="Selecciona la fecha" v-model="fechaFin">
                </div>
                <div class="col-12 col-sm-3">
                    <label class="form-label">Filtro por producto</label>
                    <select class="form-select" v-model="productoId">
                        <option :value="0">Todos los productos</option>
                        <option v-for="item in productos" :value="item.id">{{item.nombreProducto}}</option>
                    </select>
                </div>
                <div class="col-12 col-sm-3">
                    <label class="form-label">Filtro por estado</label>
                    <select class="form-select" v-model="estadoFilter">
                        <option value="__all__">Todos los estados</option>
                        <option v-for="(item, value) in estados" :value="value">{{item.n}}</option>
                    </select>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <label class="form-label">Buscar</label>
                    <input class="form-control" v-model="filterSearch"/>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-12 text-end">
                    <button @click="getItems(false)" class="btn btn-primary me-3">Buscar</button>
                    <button @click="getItems(true)" class="btn btn-danger">Limpiar</button>
                </div>
            </div>
        </CCardBody>
    </CCard>
    <CRow>
        <CCol :xs="12">
            <div class="row mb-3">
                <div class="col-12 col-sm-4" v-for="(item, value) in estados">
                    <div class="listadoTareasPanelItem" @click="autoFiltroEstado(value)">
                        <div class="mb-2">
                            <h1>{{item.c}}</h1>
                        </div>
                        <hr class="my-2">
                        {{item.n}}
                    </div>
                </div>
            </div>
        </CCol>
        <CCol :xs="12">
            <CCard class="mb-4">
                <CCardHeader>
                    <strong>Listado de tareas</strong>
                    <!--<button @click="$router.push('/admin/tareas/ver/0')" class="btn btn-primary float-end"><i class="fa fa-plus me-2"></i> Crear cotización nueva</button>-->
                </CCardHeader>
                <CCardBody>
                    <div v-for="(cotizacion, expedienteId) in items" class="mb-3">
                        <div class="row">
                            <div class="col-12 col-sm-6">
                                <h4>{{cotizacion.producto}} - <span class="text-primary">Tarea No. {{(cotizacion.identificador ? cotizacion.identificador : cotizacion.id)}}</span></h4>
                            </div>
                            <div class="col-12 col-sm-6 text-end">
                                <a :href="'/#/cotizar/producto/'+cotizacion.productoTk+'/'+cotizacion.token" class="btn btn-secondary btn-sm"><i class="fas fa-eye me-2"></i>Ver detalle</a>
                            </div>
                        </div>
                        <div>
                            <!--<div v-if="typeof cotizacion.cotizaciones !== 'undefined' && cotizacion.cotizaciones.length > 0" class="mt-4">
                                <div>
                                    <h6 class="text-primary">Cotizaciones AS400:</h6>
                                </div>
                                <div class="row">
                                    <div class="col-12 col-sm-12">
                                        <b>N. {{cotizacion.cotizaciones.join(', N. ')}}</b>
                                    </div>
                                </div>
                            </div>
                            <div v-if="typeof cotizacion.polizas !== 'undefined' && cotizacion.polizas.length > 0" class="mt-4">
                                <div>
                                    <h6 class="text-primary">Polizas:</h6>
                                </div>
                                <div class="row">
                                    <div class="col-12 col-sm-12">
                                        <b>N. {{cotizacion.polizas.join(', N. ')}}</b>
                                    </div>
                                </div>
                            </div>-->
                            <div v-if="typeof cotizacion.resumenCotizacionesVeh !== 'undefined' && Object.values(cotizacion.resumenCotizacionesVeh).length > 0" class="mt-4">
                                <div>
                                    <h6 class="text-primary">Cotizaciones:</h6>
                                </div>
                                <div class="row" v-for="coti in Object.values(cotizacion.resumenCotizacionesVeh)">
                                    <div class="col-12 col-sm-4" v-if="!!coti.producto">
                                        <p><b>{{ coti.producto }}</b></p>
                                    </div>
                                    <div class="col-12 col-sm-4" v-if="!!coti.poliza">
                                        <p><b>Número Poliza:</b> {{coti.poliza}}</p>
                                    </div>
                                    <div class="col-12 col-sm-4" v-if="!!coti.numeroCotizacionAS400">
                                        <p><b>Número Cotización AS400:</b> {{coti.numeroCotizacionAS400}}</p>
                                    </div>
                                </div>
                            </div>
                            <h6 class="text-muted mt-3">Detalles</h6>
                            <div class="row">
                                <div class="col-12 col-sm-4">
                                    <b>Expira:</b> <span style="text-transform: capitalize">{{cotizacion.expireAt}}</span>
                                </div>
                                <div class="col-12 col-sm-4">
                                    <b>Creada por:</b> <span style="text-transform: capitalize">{{cotizacion.usuario}}</span>
                                </div>
                                <div class="col-12 col-sm-4">
                                    <b>Estado:</b> <span style="text-transform: capitalize">{{cotizacion.estado}}</span>
                                </div>
                                <div class="col-12 col-sm-4">
                                    <b>Fecha de creación:</b> {{cotizacion.dateCreated}}
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12 col-sm-4">
                                    <b>Agente asignado:</b> <span v-if="cotizacion.usuarioAsignado" style="text-transform: capitalize">{{cotizacion.usuarioAsignado}}</span>
                                </div>
                            </div>
                            <div class="row" v-if="cotizacion.identificador">
                                <div class="col-12 col-sm-4 text-muted">
                                    <b>ID:</b> <span style="text-transform: capitalize">{{cotizacion.id}}</span>
                                </div>
                            </div>
                            <div v-if="typeof cotizacion.vehiculos !== 'undefined' && Object.keys(cotizacion.vehiculos).length > 0" class="mt-4" v-for="(veh, keyveh) in Object.values(cotizacion.vehiculos)">
                                <div>
                                    <h6 class="text-primary">Vehiculo {{ keyveh + 1 }}:</h6>
                                </div>
                                <div class="row">
                                    <div class="col-12 col-sm-4" v-for="res in veh">
                                        <b>{{res.l}}:</b> <span>{{res.v}}</span>
                                    </div>
                                </div>
                            </div>
                            <div v-if="typeof cotizacion.resumen !== 'undefined' && Object.keys(cotizacion.resumen).length > 0" class="mt-4">
                                <div>
                                    <h6 class="text-primary">Resumen</h6>
                                </div>
                                <div class="row">
                                    <div class="col-12 col-sm-4" v-for="res in cotizacion.resumen">
                                        <b>{{res.l}}:</b> <span>{{res.v}}</span>
                                    </div>
                                </div>
                            </div>
                            <div v-else>
                                <div class="mt-4">
                                    <h6 class="text-primary">Sin resumen disponible</h6>
                                </div>
                            </div>
                        </div>
                        <hr>
                    </div>
                    <!--<div class="text-end text-muted">
                        Limitado a 50 últimas cotizaciones
                    </div>-->
                </CCardBody>
            </CCard>
        </CCol>
    </CRow>
</template>

<script>
import toolbox from "@/toolbox";
import { useRouter, useRoute } from 'vue-router';
import Button from "@/views/forms/form_elements/FormElementButton.vue";
import Select from "@/views/forms/Select.vue";
import dayjs from "dayjs";

export default {
    name: 'Tables',
    components: {
        Select,
        Button,
        useRoute,
    },
    data() {
        return {
            fechaIni: dayjs().startOf('month').startOf('month').add(0, 'day').format('YYYY-MM-DD'),
            fechaFin: dayjs().endOf('month').endOf('month').format('YYYY-MM-DD'),
            items: [],
            estados: {},
            productos: [],
            productoId: 0,
            estadoFilter: '__all__',
            filterSearch: '',
        };
    },
    mounted() {
        this.getItems();
        //this.productoIdSelected = (typeof useRoute().params.productoId !== 'undefined') ? parseInt(useRoute().params.productoId) : 0;
    },
    methods: {
        loadData() {
            const self = this;
            toolbox.doAjax('GET', 'productos/internos/'+this.productoIdSelected, {}, function (response) {

                // self.getProductos();

                if (response.status) {
                    self.productos = (typeof response.data[0] !== 'undefined') ? response.data[0] : [];
                    self.productosToSend = response.data;
                    //self.filterInputNodes(false)
                }
                else {
                    self.msg = response.msg;

                }
            }, function (response) {
                self.msg = response.msg;
            })
        },
        /*getProductos() {
            const self = this;
            toolbox.doAjax('GET', 'productos/filter', {}, function (response) {

                if (response.status) {
                    self.productosFilter = response.data;
                }
                else {
                    self.msg = response.msg;

                }
            }, function (response) {
                self.msg = response.msg;
            })
        },*/
        getItems(limpiar) {

            if (!limpiar) limpiar = false;

            if (limpiar) {
                this.productoId = 0;
                this.filterSearch = '';
                this.estadoFilter = '__all__';
            }

            const self = this;
            toolbox.doAjax('POST', 'tareas/all', {
                    fechaIni: self.fechaIni,
                    fechaFin: self.fechaFin,
                    filterSearch: self.filterSearch,
                    estadoFilter: self.estadoFilter,
                    productoId: self.productoId,
                },
                function (response) {
                    //self.items = response.data;
                    //console.log(response.data)
                    self.estados = response.data.e;
                    self.productos = response.data.p;
                    self.items = response.data.c;
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
        autoFiltroEstado(estado) {
            this.estadoFilter = estado;
            this.getItems(false);
        }
    }
}
</script>
