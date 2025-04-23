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
                            <CCardBody class="text-center">
                                <h1>Gestor Auto</h1>
                                <div class="mt-4">
                                    <h4 class="text-primary">
                                        Iniciar sesión
                                    </h4>
                                </div>
                                <p class="text-medium-emphasis mt-4">Presiona el botón para iniciar sesión</p>
                                <!--<CInputGroup class="mb-3">
                                    <CInputGroupText>
                                        <CIcon icon="cil-user" class="text-dark"/>
                                    </CInputGroupText>
                                    <CFormInput
                                        placeholder="Nombre de usuario"
                                        autocomplete="username"
                                        v-model="username"
                                    />
                                </CInputGroup>
                                <CInputGroup class="mb-4">
                                    <CInputGroupText>
                                        <CIcon icon="cil-lock-locked" class="text-dark"/>
                                    </CInputGroupText>
                                    <CFormInput
                                        type="password"
                                        placeholder="Contraseña"
                                        autocomplete="current-password"
                                        v-model="password"
                                    />
                                </CInputGroup>-->
                                <CRow>
                                    <div class="text-danger mb-4" v-if="msg !== ''">{{msg}}</div>
                                    <CCol :xs="12" class="text-center mt-5">
                                        <CButton v-if="!loginProcess" color="primary" class="px-4 w-100" @click="login">Iniciar sesión</CButton>
                                        <div v-else class="text-muted">
                                            <img :src="loadingImg" style="max-width: 30px"> Cargando
                                        </div>
                                        <div class="mt-4">
                                            <a color="link" class="px-0 text-dark small" :href="ssoUrl + '/#/reset-password'">
                                                Olvidé mi contraseña
                                            </a>
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
import {config} from "@/config";


export default {
    name: 'Login',
    data() {
        return {
            ssoUrl: '',
            username: '',
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
        this.ssoUrl = config.ssoUrl;
    },
    methods: {
        ...mapMutations(["authSetInfo"]),
        login() {
            const self = this;
            this.loginProcess = true;
            const cache =  Math.floor(Math.random() * 99999);

            (function(w,d,s,l,i,c){
                window.ERSSO = {t: i, h: {}, c: c};
                let f=d.getElementsByTagName(s)[0],
                    j=d.createElement(s);j.async=true;j.src=
                    self.ssoUrl+'/ERLd.js?c='+cache;f.parentNode.insertBefore(j,f);
            })(window,document,'script','elRoble', config.ssoToken, function (response) {
                toolbox.doAjax('POST', 'auth/login', {
                    ssotoken: response.token,
                }, function (responseTmp) {
                    self.authSetInfo(responseTmp.data);
                    self.$router.push('/panel-productos');
                    self.loginProcess = false;
                }, function (responseTmp) {
                    self.msg = responseTmp.msg;
                    self.loginProcess = false;
                })
            });
        }
    }
}
</script>
