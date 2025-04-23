<template>
    <CRow>
        <CCol :xs="12">
            <CCard class="mb-4">
                <CCardHeader>
                    <strong>Editar usuario</strong>
                </CCardHeader>
                <CCardBody>
                    <div class="row">
                        <div class="col-12 col-sm-4">
                            <div class="mb-3">
                                <label for="Username" class="form-label">Nombre de usuario (Inicio de sesión) *</label>
                                <input type="text" class="form-control" id="Username" placeholder="Escribe aquí" v-model="user.nombreUsuario" disabled>
                            </div>
                        </div>
                        <div class="col-12 col-sm-4">
                            <div class="mb-3">
                                <label for="correoElectronico" class="form-label">Correo electrónico</label>
                                <input type="email" class="form-control" id="correoElectronico" placeholder="name@ejemplo.com" v-model="user.correoElectronico">
                            </div>
                        </div>
                        <div class="col-12 col-sm-4">
                            <div class="mb-3">
                                <label for="Nombre" class="form-label">Nombre</label>
                                <input type="text" class="form-control" id="Nombre" placeholder="Escribe aquí" v-model="user.nombre">
                            </div>
                        </div>
                        <div class="col-12 col-sm-4">
                            <div class="mb-3">
                                <label for="Nombre" class="form-label">Corporativo</label>
                                <input type="text" class="form-control" id="Corporativo" placeholder="Escribe aquí" v-model="user.corporativo">
                            </div>
                        </div>
                        <div class="col-12 col-sm-4">
                            <div class="mb-3">
                                <label for="password" class="form-label">Rol asignado</label>
                                <select class="form-select" v-model="user.rolUsuario">
                                    <option v-for="rol in roleList" :value="rol.id">{{rol.name}}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-sm-4">
                            <div class="mb-3">
                                <label for="codigo" class="form-label">Código interno</label>
                                <input type="text" class="form-control" id="Codigo" placeholder="Código interno" v-model="user.codigoInterno">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <h6 class="text-muted mb-3">Tiendas asignadas</h6>
                                <label for="password" class="form-label">Selecciona las tiendas asignadas</label>
                                <multiselect
                                    v-model="tiendas"
                                    :options="tiendasOptions"
                                    :mode="'tags'"
                                    :searchable="true"/>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <h6 class="text-muted mb-3">Códigos de agente</h6>
                                <vue3-tags-input :tags="codigosAgente"
                                                 placeholder="Ingresa los códigos de agente"
                                                 class="form-control"
                                                 @on-tags-changed="addTag"
                                />
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <h4>Variables de usuario</h4>
                        <div class="my-4 text-muted">
                            Atención, las variables de usuario no podrán utilizarse en formularios de visibilidad pública
                        </div>
                        <div>
                            <div class="mb-3">
                                <div class="row fw-bold mb-3">
                                    <div class="col-12 col-sm-6">
                                        Nombre
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        Valor
                                    </div>
                                </div>
                                <div v-for="(item, key) in variables">
                                    <div class="row mb-3">
                                        <div class="col-12 col-sm-6">
                                            <input class="form-control" v-model="item.nombre"/>
                                        </div>
                                        <div class="col-12 col-sm-6">
                                            <input class="form-control" v-model="item.valor"/>
                                        </div>
                                        <div class="col-12 text-end">
                                            <a @click="eliminarVariable(key)" class="text-danger cursor-pointer">Eliminar</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <a @click="agregarVariable" class="btn btn-primary btn-sm"><i class="fas fa-plus me-2"></i> Agregar variable</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <h4>Horario para inspecciones</h4>
                        <div class="text-muted my-4 text-center">
                            Configurar un horario habilita al usuario para atender inspecciones. Los horarios se configuran en formato de 24 horas, horas:minutos
                        </div>
                        <table class="table table-responsive table-borderless table-hover">
                            <thead>
                            <tr>
                                <th class="col-1">Habilitado</th>
                                <th class="col-1">Dia</th>
                                <th class="col-2">Inicio</th>
                                <th class="col-2">Fin</th>
                                <th class="col-1">Descanso</th>
                                <th class="col-2">Inicio</th>
                                <th class="col-2">Fin</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="dia in user.h.horarios" :key="'horario_'+0+'_'+dia.diaSemana">
                                <td class="text-center">
                                    <input type="checkbox"
                                           v-model="dia.estado"
                                           true-value="1"
                                           false-value="0"
                                           class="">
                                </td>
                                <td>{{ dia.nombreDia }}</td>
                                <td>
                                    <input type="text" class="form-control" v-model="dia.horaInicio" name="horaInicio" v-maska data-maska="##:##" placeholder="00:00"/>
                                </td>
                                <td>
                                    <input type="text" class="form-control" v-model="dia.horaFin" name="horaFin" v-maska data-maska="##:##" placeholder="00:00"/>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox"
                                           v-bind:id="user.id"
                                           v-bind:checked="!!dia.tieneDescanso && dia.estado == 1"
                                           v-model="dia.tieneDescanso"
                                           true-value="1"
                                           class="toggle-checkbox">
                                    <div class="toggle-switch"></div>
                                </td>
                                <td>
                                    <div v-if="dia.tieneDescanso == 1">
                                        <input type="text" class="form-control" v-model="dia.horaDescansoInicio" name="horaDescansoInicio"  v-maska data-maska="##:##" placeholder="00:00"/>
                                    </div>
                                </td>
                                <td>
                                    <div v-if="dia.tieneDescanso == 1">
                                        <input type="text" class="form-control" v-model="dia.horaDescansoFin" name="horaDescansoFin"  v-maska data-maska="##:##" placeholder="00:00"/>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div>
                        <div class="mt-4 text-end">
                            <button @click="$router.push('/usuarios/listado')" class="btn btn-danger me-4">Cancelar</button>
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
import Select from "@/views/forms/Select.vue";
import Multiselect from '@vueform/multiselect'
import Vue3TagsInput from 'vue3-tags-input';

export default {
    name: 'Tables',
    components: {Select, Multiselect, Vue3TagsInput},
    data() {
        return {
            id: 0,
            user: {
                id: 0,
                nombre: '',
                nombreUsuario: '',
                correoElectronico: '',
                telefono: '',
                corporativo: '',
                password: '',
                rolUsuario: 0,
                active: 0,
                log: {},
                h: {},
                codigoInterno: '',
            },
            changePassword: false,
            sendCredentials: false,
            sendCredentialsEmail: false,
            sendCredentialsSMS: false,
            sendCredentialsWhatsapp: false,

            roleList: {},

            tiendas: [],
            tiendasOptions: [],

            // medidor de fuerza
            has_number:    false,
            has_lowercase: false,
            has_uppercase: false,
            has_special:   false,

            // log
            logHeaders: [
                {text: "Fecha y hora", value: "date"},
                {text: "IP", value: "ipFrom"},
                {text: "Agente", value: "userAgent"},
            ],
            logItems: [],

            // apps
            appList: {},

            // variables de usuario
            variables: [],

            codigosAgente: [],
            codigosAgenteStr: '',
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

            toolbox.doAjax('GET', 'users/role/list', {},
                function (response) {

                    self.roleList = response.data;

                    if (self.id > 0) {

                        toolbox.doAjax('GET', 'users/tiendas/list', {},
                            function (response) {

                                self.tiendasOptions = [];
                                Object.keys(response.data).map(function (a, b) {
                                    self.tiendasOptions.push({
                                        value: response.data[a].id,
                                        label: response.data[a].nombre,
                                    })
                                })

                                if (self.id > 0) {

                                    toolbox.doAjax('GET', 'users/load/user/' + self.id, {},
                                        function (response) {
                                            self.user.id = response.data.id;
                                            self.user.nombreUsuario = response.data.nombreUsuario;
                                            self.user.correoElectronico = response.data.email;
                                            self.user.nombre = response.data.name;
                                            self.user.rolUsuario = response.data.rolUsuario;
                                            self.user.telefono = response.data.telefono;
                                            self.user.corporativo = response.data.corporativo;
                                            self.user.active = response.data.active;
                                            self.user.password = '';
                                            self.user.codigoInterno = response.data.codigoInterno;
                                            self.logItems = toolbox.prepareForTable(response.data.logs);
                                            self.variables = response.data.variables || [];

                                            self.changePassword = false;

                                            self.user.h = (typeof response.data.h[0] !== 'undefined') ? response.data.h[0] : {};

                                            self.tiendas = [];
                                            Object.keys(response.data.tiendasAsig).map(function (a, b) {
                                                self.tiendas.push(response.data.tiendasAsig[a]);
                                            })

                                            self.codigosAgente = [];
                                            Object.keys(response.data.codigos_agente).map(function (a, b) {
                                                self.codigosAgente.push(response.data.codigos_agente[a].codigoAgente);
                                            })
                                        },
                                        function (response) {
                                            toolbox.alert(response.msg, 'danger');
                                        })
                                }
                            },
                            function (response) {
                                toolbox.alert(response.msg, 'danger');
                            });
                    }
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                });
        },
        guardar() {

            const self = this;

            const data = self.user;
            data.sendCredentials = this.sendCredentials;
            data.sendCredentialsEmail = this.sendCredentialsEmail;
            data.sendCredentialsSMS = this.sendCredentialsSMS;
            data.sendCredentialsWhatsapp = this.sendCredentialsWhatsapp;
            data.appList = this.appList;
            data.changePassword = this.changePassword;
            data.variables = this.variables;
            data.tiendas = this.tiendas;
            data.codigosAgente = this.codigosAgente;

            let errors = false;
            if (toolbox.isEmpty(this.user.nombreUsuario)) {
                toolbox.alert('Debe ingresar un nombre de usuario', 'danger');
                errors = true;
            }

            if (this.changePassword && (!this.has_number || !this.has_lowercase || !this.has_uppercase || !this.has_special)) {
                toolbox.alert('Tu contraseña no cumple con los parámetros requeridos', 'danger');
                errors = true;
            }

            if (!errors) {
                toolbox.doAjax('POST', 'users/save-user', data,
                    function (response) {
                        toolbox.alert(response.msg, 'success');
                        if (self.id === 0) {
                            self.id = response.data;
                            self.$router.push('/usuarios/edit/' + response.data);
                        }
                        self.getData();
                    },
                    function (response) {
                        toolbox.alert(response.msg, 'danger');
                    })
            }
        },
        password_check: function () {
            this.has_number    = /\d/.test(this.user.password);
            this.has_lowercase = /[a-z]/.test(this.user.password);
            this.has_uppercase = /[A-Z]/.test(this.user.password);
            this.has_special   = /[!@#\$%\^\&*\)\(+=._-]/.test(this.user.password);
        },
        agregarVariable() {
            this.variables.push({
                nombre: '',
                valor: '',
            })
        },
        eliminarVariable(key) {
            this.variables.splice(key, 1);
        },
        addTag (newTag) {
            this.codigosAgente = newTag;
        },
    }
}
</script>
