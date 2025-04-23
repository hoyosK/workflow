<template>
    <CRow>
        <CCol :xs="12">
            <CCard class="mb-4">
                <CCardHeader>
                    <strong>Editar archivador</strong>
                </CCardHeader>
                <CCardBody>
                    <div>
                        <div>
                            <h5>Datos de archivador</h5>
                            <hr>
                        </div>
                        <div class="row">
                            <div class="col-12 col-sm-6">
                                <div class="mb-3">
                                    <label class="form-label">Nombre</label>
                                    <input type="text" class="form-control" placeholder="Escribe aquí" v-model="nombre">
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
                    </div>
                    <div class="mt-3">
                        <div>
                            <h5>Campos</h5>
                            <hr>
                        </div>
                        <div>
                            <table class="table">
                                <thead>
                                <tr>
                                    <th scope="col">Nombre</th>
                                    <th scope="col">Tipo</th>
                                    <th scope="col">Máscara</th>
                                    <th scope="col">Longitud</th>
                                    <th scope="col"></th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr  v-for="(item, key) in campos">
                                    <th>{{item.nombre}}</th>
                                    <td>{{item.tipoCampo}}</td>
                                    <td>{{item.mascara}}</td>
                                    <td><span v-if="item.mascara === null || item.mascara === ''">{{item.longitudMin}} - {{item.longitudMax}}</span></td>
                                    <td>
                                        <i class="fa fa-pencil text-dark me-2 cursor-pointer" @click="editCampo(item)"></i>
                                        <i class="fa fa-trash text-danger cursor-pointer" @click="deleteCampo(key, item)"></i>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-if="agregarCampo">
                            <div class="row">
                                <div class="col-12 col-sm-6">
                                    <div class="mb-3">
                                        <label class="form-label">Nombre de campo</label>
                                        <input class="form-control" type="text" v-model="campo.nombre">
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <div class="mb-3">
                                        <div>
                                            <label class="form-label">Tipo de campo</label>
                                        </div>
                                        <CDropdown class="w-100" v-model="campo.tipoCampo">
                                            <CDropdownToggle color="dark">
                                                <span v-if="typeof fieldTypes[campo.tipoCampo] !== 'undefined'"><i :class="'me-2 fa ' + fieldTypes[campo.tipoCampo].icon"></i> {{fieldTypes[campo.tipoCampo].name}}</span>
                                                <span v-else>Seleccione el tipo de campo</span>
                                            </CDropdownToggle>
                                            <CDropdownMenu  class="w-100">
                                                <CDropdownItem v-for="itemF in fieldTypes" @click="changeTipoCampo(itemF.type)"><i :class="'me-2 fa ' + itemF.icon"></i> {{itemF.name}}</CDropdownItem>
                                            </CDropdownMenu>
                                        </CDropdown>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <div class="mb-3">
                                        <label class="form-label">Máscara</label>
                                        <select class="form-control" v-model="campo.mascara">
                                            <option value="">Ninguna</option>
                                            <option value="DPI"><i class="fa fa-user"></i>DPI</option>
                                            <option value="NIT">NIT</option>
                                            <option value="telefono">Teléfono</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6" v-if="campo.mascara === '' || campo.mascara === null">
                                    <div class="mb-3">
                                        <label class="form-label">Dimensión</label>
                                        <div class="input-group mb-3">
                                            <span class="input-group-text">Min</span>
                                            <input type="text" class="form-control" v-model="campo.longitudMin">
                                            <span class="input-group-text">Max</span>
                                            <input type="text" class="form-control" v-model="campo.longitudMax">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button v-if="!agregarCampo" @click="agregarCampo = true" class="btn btn-primary me-4"><i class="fa fa-plus me-2"></i>Agregar campo</button>
                            <button v-if="agregarCampo" @click="saveCampo" class="btn btn-success me-4"><i class="fa fa-check me-2"></i>Continuar</button>
                            <button v-if="agregarCampo" @click="agregarCampo = false" class="btn btn-danger me-4"><i class="fa fa-ban me-2"></i>Cancelar</button>
                        </div>
                    </div>
                    <div class="mt-5 text-end">
                        <button @click="$router.push('/admin/archivadores')" class="btn btn-danger me-4">Cancelar</button>
                        <button @click="guardar" class="btn btn-primary">Guardar</button>
                    </div>
                </CCardBody>
            </CCard>
        </CCol>
    </CRow>
</template>

<script>
import toolbox from "@/toolbox";
import Select from "@/views/forms/Select.vue";
import login from "@/views/pages/Login.vue";


export default {
    name: 'Tables',
    computed: {
        login() {
            return login
        }
    },
    components: {

    },
    data() {
        return {
            id: 0,
            nombre: '',
            activo: 1,

            // agregar campo
            agregarCampo: false,
            campo: {
                id: 0,
                nombre: '',
                tipoCampo: '',
                mascara: '',
                longitudMin: 0,
                longitudMax: 0,
            },
            campos: [],

            // tipos de campos
            fieldTypes: {
                text: {
                    name: 'Texto de una sola línea',
                    icon: 'fa fa-font',
                    type: 'text',
                },
                textarea: {
                    name: 'Texto de varias líneas',
                    icon: 'fa fa-text-height',
                    type: 'text',
                },
                number: {
                    name: 'Número',
                    icon: 'fa fa-hashtag',
                    type: 'number',
                },
                date: {
                    name: 'Fecha',
                    icon: 'fa fa-calendar',
                    type: 'date',
                },
                checkbox: {
                    name: 'Casilla de verificación',
                    icon: 'fa fa-square-check',
                    type: 'checkbox',
                },
                option: {
                    name: 'Posibles respuestas',
                    icon: 'fa fa-circle-dot',
                    type: 'option',
                },
                select: {
                    name: 'Lista desplegable',
                    icon: 'fa fa-chevron-circle-down',
                    type: 'select',
                },
                file: {
                    name: 'Archivo',
                    icon: 'fa fa-paperclip',
                    type: 'file',
                },
                signature: {
                    name: 'Firma',
                    icon: 'fa fa-signature',
                    type: 'signature',
                },
            },
        };
    },
    mounted() {
        this.id = (typeof this.$route.params.id !== 'undefined') ? parseInt(this.$route.params.id) : 0;
        //console.log(this.id);
        this.getData();
    },
    methods: {
        getData() {

            const self = this;
            if (self.id > 0) {
                toolbox.doAjax('GET', 'admin/archivador/load/' + self.id, {},
                    function (response) {
                        self.id = response.data.id;
                        self.nombre = response.data.nombre;
                        self.activo = !!response.data.activo;

                        if (typeof response.data.detalle !== 'undefined') {
                            Object.keys(response.data.detalle).map(function (a) {
                                self.campos.push(response.data.detalle[a]);
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

            toolbox.doAjax('POST', 'admin/archivador/save', {
                    id: self.id,
                    nombre: self.nombre,
                    activo: self.activo,
                    campos: self.campos,
                },
                function (response) {
                    toolbox.alert(response.msg, 'success');
                    /*if (self.id === 0) {
                        self.id = response.data;
                    }*/
                    self.$router.push('/admin/archivadores');
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
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
        saveCampo() {
            this.agregarCampo = false;
            if (parseInt(this.campo.id) === 0) {
                this.campos.push(this.campo);
            }
            this.resetCampo();
        },
        deleteCampo(key, item) {
            const self = this;
            toolbox.confirm('Si elimina un campo que esté asociado a información de formularios, este únicamente se desactivará. ¿Desea continuar?', function () {
                self.campos.splice(key, 1);

                toolbox.doAjax('POST', 'admin/archivador/delete-field', {
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
    }
}
</script>
