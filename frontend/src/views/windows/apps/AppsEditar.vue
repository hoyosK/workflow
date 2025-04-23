<template>
    <CRow>
        <CCol :xs="12">
            <CCard class="mb-4">
                <CCardHeader>
                    <strong>Editar aplicación</strong>
                </CCardHeader>
                <CCardBody>
                    <div>
                        <div>
                            <h5>Datos de aplicación</h5>
                            <hr>
                        </div>
                        <div class="row">
                            <div class="col-12 col-sm-6">
                                <div class="mb-3">
                                    <label class="form-label">Nombre</label>
                                    <input type="text" class="form-control" placeholder="Escribe aquí" v-model="nombre">
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="row">
                                    <div class="col-12 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label">Logotipo</label>
                                            <input class="form-control" type="file" id="logotipo">
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <div v-if="logo !== ''" class="appLogoEdit">
                                            <img :src="'data:image/png;base64,' + logo">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="mb-3">
                                    <label class="form-label">Descripción</label>
                                    <textarea class="form-control" v-model="descripcion"></textarea>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 pt-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" id="appActiva" v-model="activa" :checked="activa">
                                    <label class="form-check-label" for="appActiva">
                                        Aplicación activa
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div>
                            <h5>Información de integración</h5>
                            <hr>
                        </div>
                        <div class="row">
                            <div class="col-12 col-sm-6">
                                <div class="mb-3">
                                    <label class="form-label">Url de redirección (después de iniciar de sesión)</label>
                                    <input class="form-control" type="text" v-model="urlLogin">
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="mb-3">
                                    <label class="form-label">Url de redirección (después de cerrar de sesión)</label>
                                    <input class="form-control" type="text" v-model="urlLogout">
                                </div>
                            </div>
                            <div class="col-12 col-sm-6" v-if="token !== ''">
                                <label class="form-label">Token</label>
                                <div class="input-group mb-3">
                                    <input type="text" class="form-control" ref="token" :value="token" readonly>
                                    <button class="btn btn-dark" @click="copyToken"><i class="fa fa-copy me-1"></i>Copiar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div>
                            <h5>Scripts de integración automática</h5>
                            <hr>
                        </div>
                        <div class="row">
                            <div class="col-12 col-sm-6" v-if="integrationScriptHtml !== ''">
                                <label class="form-label">HTML</label>
                                <textarea v-model="integrationScriptHtml" class="form-control scriptAutoIntegration" ref="scriptIntegracionHtml" readonly></textarea>
                                <div class="row mt-2">
                                    <div class="col-12 col-sm-8">
                                        Coloca este código en el header de tu aplicación.
                                    </div>
                                    <div class="col-12 col-sm-4">
                                        <div class="mt-2 text-end">
                                            <button class="btn btn-dark" @click="copyScriptHtml"><i class="fa fa-copy me-1"></i>Copiar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6" v-if="integrationScriptVue !== ''">
                                <label class="form-label">Vue JS</label>
                                <textarea v-model="integrationScriptVue" class="form-control scriptAutoIntegration" ref="scriptIntegracionVue" readonly></textarea>
                                <div class="row mt-2">
                                    <div class="col-12 col-sm-8">
                                        Coloca este código en el mounted() de tu aplicación o página del login.
                                    </div>
                                    <div class="col-12 col-sm-4">
                                        <div class="mt-2 text-end">
                                            <button class="btn btn-dark" @click="copyScriptVue"><i class="fa fa-copy me-1"></i>Copiar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr>
                    </div>
                    <div class="mt-5 text-end">
                        <button @click="$router.push('/apps/listado')" class="btn btn-danger me-4">Cancelar</button>
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
    components: {Select},
    data() {
        return {
            id: 0,
            nombre: '',
            descripcion: '',
            logo: '',
            token: '',
            integrationScriptHtml: '',
            integrationScriptVue: '',
            urlLogin: '',
            urlLogout: '',
            activa: 1,
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
                toolbox.doAjax('GET', 'apps/load/' + self.id, {},
                    function (responseRole) {
                        self.id = responseRole.data.id;
                        self.nombre = responseRole.data.nombre;
                        self.descripcion = responseRole.data.descripcion;
                        self.logo = responseRole.data.logo;
                        self.urlLogin = responseRole.data.urlLogin;
                        self.urlLogout = responseRole.data.urlLogout;
                        self.token = responseRole.data.token;
                        self.integrationScriptHtml = responseRole.data.integrationScriptHtml;
                        self.integrationScriptVue = responseRole.data.integrationScriptVue;
                        self.activa = !!responseRole.data.activa;
                    },
                    function (responseRole) {
                        toolbox.alert(responseRole.msg, 'danger');
                    });
            }
        },
        guardar() {

            const self = this;

            const logo = document.getElementById('logotipo');

            const data = new FormData()
            data.append('id', self.id)
            data.append('logo', logo.files[0])
            data.append('nombre', self.nombre);
            data.append('descripcion', self.descripcion);
            data.append('loginUrl', self.urlLogin);
            data.append('logoutUrl', self.urlLogout);
            data.append('activa', (self.activa === 1 || self.activa));

            toolbox.doAjax('FILE', 'apps/save', data,
                function (response) {
                    toolbox.alert(response.msg, 'success');
                    if (self.id === 0) {
                        self.id = response.data;
                        /*self.$router.push('/apps/edit/' + response.data);
                        self.getData();*/
                    }
                    self.$router.push('/apps/listado');
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
        deleteItem(item) {
            console.log(item);
        },
        editItem(item) {
            this.$router.push('/apps/edit/' + item.id);
        },
        copyToken() {
            this.$refs.token.select();
            document.execCommand('copy');
            toolbox.alert('Token copiado a portapapeles', 'success');
        },
        copyScriptHtml() {
            this.$refs.scriptIntegracionHtml.select();
            document.execCommand('copy');
            toolbox.alert('Script copiado a portapapeles', 'success');
        },
        copyScriptVue() {
            this.$refs.scriptIntegracionVue.select();
            document.execCommand('copy');
            toolbox.alert('Script copiado a portapapeles', 'success');
        }
    }
}
</script>
