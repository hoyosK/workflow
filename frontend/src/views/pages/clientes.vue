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
                                </div>
                            </CCardBody>
                        </CCard>
                        <CCard class="p-4">
                            <CCardBody>
                                <h1>Iniciar sesión</h1>
                                <p class="text-medium-emphasis">Ingresa tus datos para iniciar sesión</p>
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
                                <div>
                                    <div class="text-danger mb-4" v-if="msg !== ''">{{msg}}</div>
                                    <div>
                                        <button v-if="!loginProcess" class="btn btn-primary w-100" @click="login">Iniciar sesión</button>
                                        <div v-else class="text-muted">
                                            <img :src="loadingImg" style="max-width: 30px"/> Cargando
                                        </div>
                                    </div>
                                </div>
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
import {mapGetters, mapMutations, useStore} from "vuex";
import loadingImg from '@/assets/images/loading.gif'

export default {
    name: 'Login',
    data() {
        return {
            sessionStarted: false,
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
    computed: {
        ...mapGetters({
            loading: 'loading',
            authLogged: 'authLogged',
            authInfo: 'authInfo',
        })
    },
    watch: {
        authInfo (value) {
            this.sessionStartValidation(value);
        }
    },
    mounted() {
        this.sessionStartValidation();
    },
    methods: {
        ...mapMutations(["authSetInfo"]),
        login() {
            const self = this;
            this.loginProcess = true;
            toolbox.doAjax('POST', 'auth/login', {
                nombreUsuario: self.username,
                password: self.password,
            }, function (response) {
                self.authSetInfo(response.data);
                self.$router.push('/mis-tareas');
                self.loginProcess = false;
            }, function (response) {
                self.msg = response.msg;
                self.loginProcess = false;
            })
        },
        sessionStartValidation() {
            if (this.authLogged) {
                if (window.opener) {
                    window.opener.parent.postMessage({
                        k: 'ERSSO_POST_MSG',
                        token: this.authInfo.token,
                        name: this.authInfo.name,
                        email: this.authInfo.email,
                        username: this.authInfo.username,
                    }, '*');
                }
                else {
                    console.log('Window Parent Disconected');
                }
            }
            else {
                console.log('User not logged');
            }
        }
    }
}
</script>
