<template>
    <CRow>
        <CCol :xs="12">
            <CCard class="mb-4">
                <CCardHeader>
                    <strong>Editar formulario</strong>
                </CCardHeader>
                <CCardBody>
                    <div>
                        <div>
                            <h5>Datos de formulario</h5>
                            <hr>
                        </div>
                        <div class="row">
                            <div class="col-12 col-sm-6">
                                <div class="mb-3">
                                    <label class="form-label">Nombre</label>
                                    <input type="text" class="form-control" placeholder="Escribe aquí" v-model="nombre" @keyup="friendlyUrl">
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 pt-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" id="appActiva" v-model="activo" :checked="activo">
                                    <label class="form-check-label" for="appActiva">
                                        Activo
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5">
                            <h5>Datos de publicación</h5>
                            <hr>
                        </div>
                        <div class="row">
                            <div class="col-12 col-sm-6">
                                <div class="mb-3">
                                    <label class="form-label">Dirección (URL) amigable</label>
                                    <div class="input-group mb-3">
                                        <span class="input-group-text" id="basic-addon3">{{urlApp}}formulario/</span>
                                        <input type="text" class="form-control" placeholder="Escribe aquí" v-model="urlAmigable">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5">
                        <div>
                            <h5>Configuración de formulario</h5>
                            <hr>
                        </div>
                        <div class="formBuilder">
                            <div class="secciones" v-for="(seccion, keySeccion) in secciones">
                                <div>
                                    <label class="form-label fw-bold">Nombre de sección</label>
                                    <input class="form-control" v-model="seccion.nombre">
                                </div>
                                <div class="mt-4">
                                    <div class="fw-bold mb-3">
                                        <span>Campos:</span>
                                    </div>
                                    <div>
                                        <table class="table">
                                            <thead>
                                            <tr>
                                                <th scope="col">Nombre a mostrar</th>
                                                <th scope="col">Archivador / Campo</th>
                                                <th scope="col"></th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr  v-for="(item, key) in seccion.campos">
                                                <th>{{item.nombre}}</th>
                                                <td>{{item.archivadorCampo}}</td>
                                                <td><i class="fa fa-trash text-danger" @click="deleteCampo(key, item)"></i></td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div v-if="seccion.agregarCampo">
                                        <div class="row">
                                            <div class="col-12 col-sm-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Archivador/Campo</label>
                                                    <multiselect v-model="campo.archivadorDetalleId" :options="optionsArchivadores" :searchable="true"/>
                                                </div>
                                            </div>
                                            <div class="col-12 col-sm-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Nombre a mostrar</label>
                                                    <input class="form-control" type="text" v-model="campo.nombre">
                                                </div>
                                            </div>
                                            <div class="col-12 col-sm-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Tamaño en PC</label>
                                                    <select class="form-control" v-model="campo.layoutSizePc">
                                                        <option v-for="i in 12" :value="i">{{i}} columna(s)</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-12 col-sm-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Tamaño en móvil</label>
                                                    <select class="form-control" v-model="campo.layoutSizeMobile">
                                                        <option v-for="i in 12" :value="i">{{i}} columna(s)</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="row">
                                                    <div class="col-12 col-sm-2">
                                                        <div class="mb-3">
                                                            <div class="pt-4">
                                                                <input  type="checkbox" v-model="campo.visible" value="1">
                                                                <label class="form-label ms-2">Visible</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 col-sm-2">
                                                        <div class="mb-3">
                                                            <div class="pt-4">
                                                                <input  type="checkbox" v-model="campo.deshabilitado" value="1">
                                                                <label class="form-label ms-2">Deshabilitado</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 col-sm-2">
                                                        <div class="mb-3">
                                                            <div class="pt-4">
                                                                <input  type="checkbox" v-model="campo.activo" value="1">
                                                                <label class="form-label ms-2">Activo</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <div class="row">
                                            <div class="col-12 col-sm-6">
                                                <button v-if="!seccion.agregarCampo" @click="seccion.agregarCampo = true" class="btn btn-secondary me-4"><i class="fa fa-plus me-2"></i>Agregar campo</button>
                                                <button v-if="seccion.agregarCampo" @click="saveCampo(keySeccion)" class="btn btn-success me-4"><i class="fa fa-check me-2"></i>Continuar</button>
                                                <button v-if="seccion.agregarCampo" @click="seccion.agregarCampo = false" class="btn btn-danger me-4"><i class="fa fa-ban me-2"></i>Cancelar</button>
                                            </div>
                                            <div class="col-12 col-sm-6 text-end">
                                                <button @click="moverSeccionArriba(keySeccion)" class="btn btn-dark me-2"><i class="fa fa-arrow-up"></i></button>
                                                <button @click="moverSeccionAbajo(keySeccion)" class="btn btn-dark me-2"><i class="fa fa-arrow-down"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button @click="addSection" class="btn btn-primary me-4"><i class="fa fa-plus me-2"></i>Agregar sección</button>
                        </div>
                    </div>
                    <div class="mt-5 text-end">
                        <button @click="$router.push('/admin/formularios')" class="btn btn-danger me-4">Cancelar</button>
                        <button @click="guardar" class="btn btn-primary">Guardar</button>
                    </div>
                </CCardBody>
            </CCard>
        </CCol>
    </CRow>
</template>

<script>
import toolbox from "@/toolbox";
import {config} from "/src/config";
import Select from "@/views/forms/Select.vue";
import login from "@/views/pages/Login.vue";
import Multiselect from '@vueform/multiselect'

export default {
    name: 'Tables',
    computed: {
        login() {
            return login
        }
    },
    components: {Multiselect},
    data() {
        return {
            id: 0,
            nombre: '',
            urlApp: '',
            urlAmigable: '',
            activo: 1,

            // agregar campo
            agregarCampo: false,
            //archivadorSelected: null,
            campo: {
                id: 0,
                archivadorCampo: '',
                nombre: '',
                layoutSizePc: '',
                layoutSizeMobile: '',
                cssClass: '',
                requerido: 0,
                deshabilitado: 0,
                visible: 0,
                activo: 0,
                archivadorDetalleId: 0,
            },
            campos: [],

            // sección
            secciones: [],

            // formulario valores
            optionsArchivadores: [],
        };
    },
    mounted() {
        this.id = (typeof this.$route.params.id !== 'undefined') ? parseInt(this.$route.params.id) : 0;
        this.urlApp = config.appUrl;
        //console.log(this.id);
        this.getData();
        this.getArchivadores();
    },
    methods: {
        getData() {

            const self = this;
            if (self.id > 0) {
                toolbox.doAjax('GET', 'admin/formulario/load/' + self.id, {},
                    function (response) {
                        self.id = response.data.id;
                        self.nombre = response.data.nombre;
                        self.urlAmigable = response.data.urlAmigable;
                        self.activo = !!response.data.activo;

                        if (typeof response.data.seccion !== 'undefined') {
                            Object.keys(response.data.seccion).map(function (a) {
                                const section = {
                                    id: response.data.seccion[a].id,
                                    nombre: response.data.seccion[a].nombre,
                                    campos: [],
                                }
                                Object.keys(response.data.seccion[a].campos).map(function (b) {

                                    const campo = {
                                        id: response.data.seccion[a].campos[b].id,
                                        nombre: response.data.seccion[a].campos[b].nombre,
                                        archivadorDetalleId: response.data.seccion[a].campos[b].archivadorDetalleId,
                                        archivadorCampo: response.data.seccion[a].campos[b].archivador_detalle.archivador.nombre + ' / ' + response.data.seccion[a].campos[b].archivador_detalle.nombre,
                                    }
                                    section.campos.push(campo);
                                })

                                self.secciones.push(section);
                            })
                        }
                    },
                    function (responseRole) {
                        toolbox.alert(responseRole.msg, 'danger');
                    });
            }
        },
        guardar() {

            const self = this;
            this.ordenarSecciones();

            toolbox.doAjax('POST', 'admin/formulario/save', {
                    id: self.id,
                    nombre: self.nombre,
                    activo: self.activo,
                    urlAmigable: self.urlAmigable,
                    campos: self.secciones,
                },
                function (response) {
                    toolbox.alert(response.msg, 'success');
                    /*if (self.id === 0) {
                        self.id = response.data;
                    }*/
                    self.$router.push('/admin/formularios');
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
        addSection() {
            const section = {
                nombre: '',
                campos: [],
            }
            this.secciones.push(section);
        },
        resetCampo() {
            this.campo = {
                id: 0,
                nombre: '',
                tipoCampo: '',
                mascara: '',
                longitudMin: 0,
                longitudMax: 0,
            };
        },
        saveCampo(seccionKey) {
            this.secciones[seccionKey].agregarCampo = false;

            // traigo el archivador desc
            const self = this;
            this.optionsArchivadores.forEach(function (a) {
                if (a.value === self.campo.archivadorDetalleId) {
                    self.campo.archivadorCampo = a.label;
                }
            });

            if (typeof this.secciones[seccionKey].campos === 'undefined') {
                this.secciones[seccionKey].campos = [];
            }

            if (parseInt(this.campo.id) === 0) {
                this.secciones[seccionKey].campos.push(this.campo);
            }
            this.resetCampo();
        },
        deleteCampo(key, item) {
            const self = this;
            toolbox.confirm('Si elimina un campo que esté asociado a información de formularios, este únicamente se desactivará. ¿Desea continuar?', function () {
                self.campos.splice(key, 1);

                toolbox.doAjax('POST', 'admin/formulario/delete-field', {
                        id: item.id,
                    },
                    function (response) {
                        toolbox.alert(response.msg, 'success');
                    },
                    function (response) {
                        toolbox.alert(response.msg, 'danger');
                    })
            });
        },
        editCampo(item) {
            this.agregarCampo = true;
            this.campo = item;
        },
        changeTipoCampo(tipo) {
            this.campo.tipoCampo = tipo;
        },
        getArchivadores() {
            const self = this;
            toolbox.doAjax('GET', 'admin/archivador/fields', {},
                function (response) {
                    Object.keys(response.data).map(function (a, b) {
                        self.optionsArchivadores.push({
                            value: response.data[a].id,
                            label: response.data[a].nombre,
                        })
                    })
                },
                function (responseRole) {
                    toolbox.alert(responseRole.msg, 'danger');
                });
        },
        friendlyUrl() {
            this.urlAmigable = this.nombre.replace(/[^a-z0-9_]+/gi, '-').replace(/^-|-$/g, '').toLowerCase();
        },
        ordenarSecciones() {
            const self = this;
            this.secciones.forEach(function (a, b) {
                self.secciones[b].orden = b;
            })
        },
        moverSeccionArriba(old_index) {
            const new_index = old_index-1;
            if (new_index >= this.secciones.length) {
                var k = new_index - this.secciones.length + 1;
                while (k--) {
                    this.secciones.push(undefined);
                }
            }
            this.secciones.splice(new_index, 0, this.secciones.splice(old_index, 1)[0]);
        },
        moverSeccionAbajo(old_index) {
            const new_index = old_index+1;
            if (new_index >= this.secciones.length) {
                var k = new_index - this.secciones.length + 1;
                while (k--) {
                    this.secciones.push(undefined);
                }
            }
            this.secciones.splice(new_index, 0, this.secciones.splice(old_index, 1)[0]);
        },
    }
}
</script>
