<template>
    <CRow>
        <CCol :xs="12">
            <CCard class="mb-4">
                <CCardHeader>
                    <strong>Crear usuario</strong>
                </CCardHeader>
                <CCardBody>
                    <div class="row">
                        <div class="col-12 col-sm-4">
                            <div class="mb-3">
                                <label for="Username" class="form-label">Nombre de usuario (Inicio de sesión) *</label>
                                <input type="text" class="form-control" id="Username" placeholder="Escribe aquí" v-model="user.nombreUsuario" required>
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
                                <label for="password" class="form-label">Rol asignado</label>
                                <select class="form-select" v-model="user.rolUsuario">
                                    <option v-for="rol in roleList" :value="rol.id">{{ rol.name }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-sm-4">
                            <div class="mb-3">
                                <label for="Nombre" class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="Telefono" placeholder="Escribe aquí" v-model="user.telefono">
                            </div>
                        </div>
                        <div class="col-12 col-sm-4">
                            <div class="mb-3">
                                <label for="Nombre" class="form-label">Corporativo</label>
                                <input type="text" class="form-control" placeholder="Escribe aquí" v-model="user.corporativo">
                            </div>
                        </div>
                        <div class="col-12 col-sm-4">
                            <div class="mb-3">
                                <label for="Nombre" class="form-label">Estado</label>
                                <select class="form-control" v-model="user.active">
                                    <option value="1">Activo</option>
                                    <option value="0">Desactivado</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 col-sm-6">
                            <div>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" value="1" id="cambiarpass" v-model="changePassword">
                                    <label class="form-check-label" for="cambiarpass">
                                        Cambiar contraseña
                                    </label>
                                </div>
                                <div class="mt-4" v-show="changePassword">
                                    <div>
                                        <label for="password" class="form-label">Contraseña</label>
                                        <input type="text" class="form-control" id="password" placeholder="Escribe aquí" v-model="user.password" @input="password_check">
                                    </div>
                                    <div>
                                        <div class="row mt-2">
                                            <div class="col-6">
                                                <div v-if="passwordSecurity.longitudPass > 0" class="frmValidation" :class="{'frmValidation--passed' : typeof user.password.length !== 'undefined' && has_lenght}">
                                                    <i class="frmIcon fas" :class="has_lenght ? 'fa-check' : 'fa-times'"></i> Más de {{ passwordSecurity.longitudPass }} caracteres
                                                </div>
                                                <div v-if="passwordSecurity.letrasPass > 0" class="frmValidation" :class="{'frmValidation--passed' :has_uppercase }">
                                                    <i class="frmIcon fas" :class="has_uppercase ? 'fa-check' : 'fa-times'"></i> Contiene mayúsculas
                                                </div>
                                                <!--<div v-if="passwordSecurity.letrasPass > 0" class="frmValidation" :class="{'frmValidation&#45;&#45;passed' :has_lowercase }"><i class="frmIcon fas" :class="has_lowercase ? 'fa-check' : 'fa-times'"></i> Contiene minúsculas</div>-->
                                            </div>
                                            <div class="col-6">
                                                <div v-if="passwordSecurity.numerosPass > 0" class="frmValidation" :class="{'frmValidation--passed' : has_number }">
                                                    <i class="frmIcon fas" :class="has_number ? 'fa-check' : 'fa-times'"></i> Contiene números
                                                </div>
                                                <div v-if="passwordSecurity.caracteresPass > 0" class="frmValidation" :class="{'frmValidation--passed' : has_special }">
                                                    <i class="frmIcon fas" :class="has_special ? 'fa-check' : 'fa-times'"></i> Contiene caracteres especiales
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div>
                            <div class="mt-4 text-end">
                                <button @click="guardar" class="btn btn-primary me-4">Crear</button>
                                <button @click="$router.push('/usuarios/listado')" class="btn btn-danger">Cancelar</button>
                            </div>
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
import Swal from "sweetalert2";

export default {
    name: 'Tables',
    components: {Select},
    data() {
        return {
            id: 0,
            user: {
                id: 0,
                nombre: '',
                nombreUsuario: '',
                correoElectronico: '',
                expiryDays: 0,
                telefono: '',
                corporativo: '',
                password: '',
                rolUsuario: 0,
                active: 0,
                log: {}
            },
            changePassword: false,
            sendCredentials: false,
            sendCredentialsEmail: false,
            sendCredentialsSMS: false,
            sendCredentialsWhatsapp: false,

            roleList: {},

            // medidor de fuerza
            has_lenght: false,
            has_number: false,
            has_lowercase: false,
            has_uppercase: false,
            has_special: false,

            // log
            logHeaders: [
                {text: "Fecha y hora", value: "date"},
                {text: "IP", value: "ipFrom"},
                {text: "Agente", value: "userAgent"},
            ],
            logItems: [],

            // apps
            appList: {},
            apiKeyList: {},

            // password
            passwordSecurity: {}
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

            let errors = false;
            if (toolbox.isEmpty(this.user.nombreUsuario)) {
                toolbox.alert('Debe ingresar un nombre de usuario', 'danger');
                errors = true;
            }

            this.password_check();

            if (this.changePassword) {

                /*if (!this.has_lenght) {
                    toolbox.alert('Tu contraseña no cumple con la longitud requerida', 'danger');
                    errors = true;
                    return false;
                }
                if (!this.has_number) {
                    toolbox.alert('Tu contraseña debe incluir números', 'danger');
                    errors = true;
                    return false;
                }
                if (!this.has_uppercase) {
                    toolbox.alert('Tu contraseña debe incluir mayusculas', 'danger');
                    errors = true;
                    return false;
                }
                if (!this.has_special) {
                    toolbox.alert('Tu contraseña debe incluir carácteres especiales', 'danger');
                    errors = true;
                    return false;
                }*/
            }

            if (!errors) {
                toolbox.doAjax('POST', 'users/create-user', data,
                    function (response) {
                        toolbox.alert(response.msg, 'success');

                        self.$router.push(`/usuarios/edit/${response.data}`);

                        /*if (self.id === 0) {
                            self.id = response.data;
                            self.$router.push('/usuarios/edit/' + response.data);
                        }
                        self.getData();*/
                    },
                    function (response) {
                        toolbox.alert(response.msg, 'danger');
                    })
            }
        },
        password_check: function () {
            if (this.passwordSecurity.longitudPass > 0) {
                this.has_lenght = (this.user.password.length > this.passwordSecurity.longitudPass);
            }
            else {
                this.has_lenght = true;
            }

            if (this.passwordSecurity.numerosPass > 0) {
                this.has_number = /\d/.test(this.user.password);
            }
            else {
                this.has_number = true;
            }

            if (this.passwordSecurity.letrasPass > 0) {
                this.has_uppercase = /[A-Z]/.test(this.user.password);
            }
            else {
                this.has_uppercase = true;
            }

            if (this.passwordSecurity.caracteresPass > 0) {
                this.has_special = /[!@#\$%\^\&*\)\(+=._-]/.test(this.user.password);
            }
            else {
                this.has_special = true;
            }
        }
    }
}
</script>
