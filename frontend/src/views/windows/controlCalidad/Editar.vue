<template>
    <CRow>
        <CCol :xs="12">
            <CCard class="mb-4">
                <CCardHeader>
                    <strong>Editar descuento</strong>
                </CCardHeader>
                <CCardBody>
                    <h5 class="mb-4 text-muted">Datos de tarea</h5>
                    <hr>
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
                    <h5 class="text-muted mt-5">Ficha de control de calidad</h5>
                    <hr>
                    <div>
                        <div v-for="item in dataFicha" class="itemControlCalidad">
                            <div class="row">
                                <div class="col-12 col-sm-4">
                                    <b>Fecha</b>: {{item.createdAt}}
                                </div>
                                <div class="col-12 col-sm-4">
                                    <b>Tipo</b>: {{item.tipificacion.principal}}
                                </div>
                                <div class="col-12 col-sm-4">
                                    <b>Subtipo</b>: {{item.tipificacion.secundaria}}
                                </div>
                                <div class="col-12">
                                    <div>
                                        <b>Usuario</b>: {{item.usuario.name}}
                                    </div>
                                    <div>
                                        <b>Comentario</b>: {{item.comentario}}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <h5 class="text-muted mt-5">Agregar a control</h5>
                    <hr>
                    <div class="row">
                        <div class="col-12 col-sm-6">
                            <div class="mb-3">
                                <label class="form-label">Tipo</label>
                                <select class="form-control" v-model="tipoUno">
                                    <option :label="key" v-for="(nomen, key) in nomenclatura">
                                        {{key}}
                                    </option>
                                    <!--<option value="q">Q</option>-->
                                    <!--<option value="u">USD</option>-->
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="mb-3">
                                <label class="form-label">Tipo secundario</label>
                                <select class="form-control" v-model="tipoDos" v-if="typeof nomenclatura[tipoUno] !== 'undefined'">
                                    <option :label="nomen" v-for="(nomen, key) in nomenclatura[tipoUno]">
                                        {{key}}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label">Comentario</label>
                                <textarea type="text" class="form-control" placeholder="Escribe aquí" v-model="comentario"></textarea>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label">Adjuntar archivo</label>
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
                                                }
                                            }"
                                           ref="filepondInput">
                                </file-pond>
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="mt-4 text-end">
                            <button @click="$router.push('/admin/descuentos')" class="btn btn-danger me-4">Cancelar</button>
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
import vueFilePond from 'vue-filepond';
import 'filepond/dist/filepond.min.css';
import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.css';

const FilePond = vueFilePond();


export default {
    name: 'Tables',
    components: {Select, Multiselect, Vue3TagsInput, FilePond},
    data() {
        return {
            cToken: '',
            dataFicha: {},
            resumen: {},
            nomenclatura: {},

            comentario: '',
            tipoUno: '',
            tipoDos: '',
        };
    },
    mounted() {
        this.cToken = (typeof this.$route.params.id !== 'undefined') ? this.$route.params.id : '';
        const self = this;

        self.getData();
    },
    methods: {
        getData() {
            const self = this;

            toolbox.doAjax('POST', 'control-calidad/get-ficha', {
                    token: self.cToken,
                },
                function (response) {
                    self.cToken = response.data.token;
                    self.dataFicha = response.data.ficha;
                    self.resumen = response.data.resumen;
                    self.nomenclatura = response.data.nomen;

                    self.comentario = '';
                    self.tipoUno = '';
                    self.tipoDos = '';

            })
        },
        handleUpload(file, campoId, load, error, progress, isOCR) {
            if (file) {

                if (!isOCR) isOCR = false;

                const self = this;

                // creo la data
                const formData = new FormData();
                formData.append('file', file);
                formData.append('seccionKey', 0);
                formData.append('token', self.cToken);
                formData.append('campoId', 'CONTROL_CALIDAD');
                formData.append('isOCR', false);
                formData.append('tp', '');

                toolbox.doAjax('FILE', 'tareas/upload-file' + (self.isPublic ? '/public' : ''), formData,
                    function (response) {

                        load();
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
        guardar() {

            const self = this;

            let errors = false;
            if (toolbox.isEmpty(this.nombre)) {
                toolbox.alert('Debe ingresar un nombre de usuario', 'danger');
                errors = true;
            }

            if (!errors) {
                toolbox.doAjax('POST', 'control-calidad/save', {
                        id: self.id,
                        cotizacionId: self.id,
                        comentario: self.comentario,
                        nomenclatura: self.tipoDos,
                    },
                    function (response) {
                        toolbox.alert(response.msg, 'success');
                        self.getData();
                    },
                    function (response) {
                        toolbox.alert(response.msg, 'danger');
                    })
            }
        },


        getFlujos() {
            const self = this;

            toolbox.doAjax('GET', 'descuentos/get-flujos', {},
                function (response) {
                    self.flujosOptions = [];
                    Object.keys(response.data).map(function (a, b) {
                        self.flujosOptions.push({
                            value: response.data[a].id,
                            label: response.data[a].nombreProducto,
                        })
                    })

                    self.getData();
            })
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
    }
}
</script>
