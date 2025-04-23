<template>
    <CCard class="mb-4">
        <CCardHeader>
            <strong>Inspecciones agendadas</strong>
        </CCardHeader>
        <CCardBody>
            <div class="row">
                <div class="col-12 col-sm-6">
                    <div>
                        <label class="form-label">Fecha Inicial</label>
                    </div>
                    <input type="date" class="form-control" placeholder="Selecciona la fecha" v-model="fechaIni">
                </div>
                <div class="col-12 col-sm-6">
                    <div>
                        <label class="form-label">Fecha Final</label>
                    </div>
                    <input type="date" class="form-control" placeholder="Selecciona la fecha" v-model="fechaFin">
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-12 text-end">
                    <button @click="loadData(false)" class="btn btn-primary me-3">Buscar</button>
                    <!--<button @click="getItems(true)" class="btn btn-danger">Limpiar</button>-->
                </div>
            </div>
        </CCardBody>
    </CCard>
    <CRow>
        <CCol :xs="12">
            <CCard class="mb-4">
                <CCardHeader>
                    <strong>Listado de inspecciones</strong>
                    <!--<button @click="$router.push('/admin/tareas/ver/0')" class="btn btn-primary float-end"><i class="fa fa-plus me-2"></i> Crear cotización nueva</button>-->
                </CCardHeader>
                <CCardBody>
                    <div v-for="(inspeccion, expedienteId) in inspecciones" class="mb-3">
                        <div class="row">
                            <div class="col-12 col-sm-6">
                                <h4 class="text-primary">Inspección No.{{inspeccion.id}}</h4>
                                <div>
                                    <b>Fecha:</b> {{inspeccion.fechaInspeccion}}
                                </div>
                                <div>
                                    <b>Hora:</b> {{inspeccion.horaInspeccion}}
                                </div>
                                <div>
                                    <b>Usuario asignado:</b> {{inspeccion.usuario.name}}
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 text-end">
                                <a class="btn btn-secondary btn-sm"><i class="fas fa-eye me-2"></i>Ver detalle</a>
                            </div>
                        </div>
                        <hr>
                    </div>
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
            fechaIni: dayjs().startOf('month').subtract(1, 'month').startOf('month').add(0, 'day').format('YYYY-MM-DD'),
            fechaFin: dayjs().endOf('month').subtract(1, 'month').endOf('month').format('YYYY-MM-DD'),
            items: [],
            estados: {},
            productos: [],
            productoId: 0,
            estadoFilter: '__all__',
            filterSearch: '',

            inspecciones: {}
        };
    },
    mounted() {
        this.loadData();
        // this.getItems();
        //this.productoIdSelected = (typeof useRoute().params.productoId !== 'undefined') ? parseInt(useRoute().params.productoId) : 0;
    },
    methods: {
        loadData() {
            const self = this;
            toolbox.doAjax('POST', 'inspecciones/get-agendadas', {}, function (response) {

                // self.getProductos();

                if (response.status) {
                    self.inspecciones = response.data;
                }
                else {
                    self.msg = response.msg;

                }
            }, function (response) {
                self.msg = response.msg;
            })
        },
    }
}
</script>
