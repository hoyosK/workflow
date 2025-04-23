<template>
    <div class="bg-light min-vh-100 d-flex flex-row align-items-center">
        <CContainer>
            <CRow class="justify-content-center">
                <CCol :md="8">
                    <CCardGroup>
                        <CCard class="text-white bg-primary py-5" style="width: 100%">
                            <CCardBody class="text-center">
                                <div>
                                    <img src="../../assets/images/logo.png" style="max-width: 200px; margin: auto; margin-top: 16%">
                                    <!--<h2>Sign up</h2>
                                    <p>
                                        Lorem ipsum dolor sit amet, consectetur adipisicing elit,
                                        sed do eiusmod tempor incididunt ut labore et dolore magna
                                        aliqua.
                                    </p>
                                    <CButton color="light" variant="outline" class="mt-3">
                                        Register Now!
                                    </CButton>-->
                                </div>
                            </CCardBody>
                        </CCard>
                        <CCard class="p-4">
                            <CCardBody v-if="token === ''">
                                <h1>Recuperación de contraseña</h1>
                                <p class="text-medium-emphasis">Ingresa tus datos para iniciar el proceso de recuperación de contraseña</p>
                                <CInputGroup class="mb-3">
                                    <CInputGroupText>
                                        <CIcon icon="cil-user"/>
                                    </CInputGroupText>
                                    <CFormInput
                                        placeholder="Nombre de usuario"
                                        autocomplete="username"
                                        v-model="username"
                                    />
                                </CInputGroup>
                                <CRow>
                                    <div class="text-danger mb-4" v-if="msg !== ''">{{ msg }}</div>
                                    <CCol :xs="6">
                                        <CButton v-if="!loginProcess" color="primary" @click="send">Continuar</CButton>
                                        <div v-else class="text-muted">
                                            <img :src="loadingImg" style="max-width: 30px"> Cargando
                                        </div>
                                    </CCol>
                                    <CCol :xs="6" class="text-right">
                                        <CButton color="link" class="px-0" @click="$router.push('/login')">
                                            Ir a inicio de sesión
                                        </CButton>
                                    </CCol>
                                </CRow>
                            </CCardBody>
                            <CCardBody v-else>
                                <h1>Recuperación de contraseña</h1>
                                <p class="text-medium-emphasis">Ingresa tus nueva contraseña</p>
                                <CInputGroup class="mb-4">
                                    <CInputGroupText>
                                        <CIcon icon="cil-lock-locked"/>
                                    </CInputGroupText>
                                    <CFormInput
                                        type="password"
                                        placeholder="Contraseña"
                                        autocomplete="current-password"
                                        v-model="password"
                                    />
                                </CInputGroup>
                                <CRow>
                                    <div class="text-danger mb-4" v-if="msg !== ''">{{ msg }}</div>
                                    <CCol :xs="6">
                                        <CButton v-if="!loginProcess" color="primary" @click="reset">Guardar</CButton>
                                        <div v-else class="text-muted">
                                            <img :src="loadingImg" style="max-width: 30px"> Cargando
                                        </div>
                                    </CCol>
                                </CRow>
                            </CCardBody>
                        </CCard>
                    </CCardGroup>
                </CCol>
            </CRow>
        </CContainer>
    </div>
</template>

<script>
import toolbox from "@/toolbox";
import {mapMutations, useStore} from "vuex";
import loadingImg from '@/assets/images/loading.gif'

export default {
    name: 'Login',
    data() {
        return {
            username: '',
            token: '',
            password: '',
            msg: '',
            loginProcess: false,
        };
    },
    setup() {
        return {
            loadingImg,
        }
    },
    mounted() {
        this.token = (typeof this.$route.params.token !== 'undefined') ? this.$route.params.token : '';
    },
    methods: {
        ...mapMutations(["authSetInfo"]),
        send() {
            const self = this;

            if (self.username === '') {
                toolbox.alert('Debe ingresar un nombre de usuario', 'danger');
                return false;
            }

            this.loginProcess = true;
            toolbox.doAjax('POST', 'auth/reset-password', {
                nombreUsuario: self.username,
            }, function (response) {
                /*self.authSetInfo(response.data);
                self.$router.push('/apps/listado');
                */
                self.loginProcess = false;
            }, function (response) {
                self.msg = response.msg;
                self.loginProcess = false;
            })
        },
        reset() {
            const self = this;
            this.loginProcess = true;
            toolbox.doAjax('POST', 'auth/reset-my-password', {
                token: self.token,
                password: self.password,
            }, function (response) {
                /*self.authSetInfo(response.data);
                self.$router.push('/apps/listado');
                */
                toolbox.alert(response.msg, 'success');
                self.$router.push('/login');
                self.loginProcess = false;
            }, function (response) {
                self.msg = response.msg;
                self.loginProcess = false;
            })
        },
    }
}
</script>
