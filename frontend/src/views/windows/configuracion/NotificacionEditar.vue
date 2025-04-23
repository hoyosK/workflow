<template>
    <CRow>
        <CCol :xs="12">
            <CCard class="mb-4">
                <CCardHeader>
                    <strong>Configuración de sistema</strong>
                </CCardHeader>
                <CCardBody>
                    <div class="mb-5">
                        <h5>Configuración de llaves o credenciales</h5>
                        <hr>
                        <div>
                            <!--<div class="row">
                                <div class="col-12 col-sm-6">
                                    <h6 class="text-primary">Whatsapp</h6>
                                    <div class="mb-3">
                                        <label class="form-label">Token</label>
                                        <input type="text" class="form-control" placeholder="Escribe aquí" v-model="whatsapp.token">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Url de servicio</label>
                                        <input type="text" class="form-control" placeholder="Escribe aquí" v-model="whatsapp.url">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Type</label>
                                        <input type="text" class="form-control" placeholder="Escribe aquí" v-model="whatsapp.type">
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <h6 class="text-primary">Salida Mailgun</h6>
                                    <div class="mb-3">
                                        <label class="form-label">API Key</label>
                                        <input type="text" class="form-control" placeholder="Escribe aquí" v-model="mailgun.apiKey">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Dominio</label>
                                        <input type="text" class="form-control" placeholder="Escribe aquí" v-model="mailgun.domain">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Asunto (subject)</label>
                                        <input type="text" class="form-control" placeholder="Escribe aquí" v-model="mailgun.subject">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Enviar desde (from)</label>
                                        <input type="text" class="form-control" placeholder="Escribe aquí" v-model="mailgun.from">
                                    </div>
                                </div>
                            </div>-->
                        </div>
                    </div>
                    <div class="mb-5">
                        <h5>Configuración de plantillas</h5>
                        <hr>
                        <div>
                            <!--<div class="row">
                                <div class="col-12 col-sm-12">
                                    <h6 class="text-primary">Envío de credenciales de usuario</h6>
                                    <div class="mb-3">
                                        <div class="row">
                                            <div class="col-12 col-sm-6">
                                                <label class="form-label">Campos disponibles</label>
                                                Los campos disponibles a utilizar en la plantilla son:
                                                <table class="table table-striped">
                                                    <thead>
                                                    <tr>
                                                        <td>Campo</td>
                                                        <td>Descripción</td>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td>::URL_LOGIN::</td>
                                                            <td>Enlace para inicio de sesión</td>
                                                        </tr>
                                                        <tr>
                                                            <td>::USERNAME::</td>
                                                            <td>Nombre de usuario generado por el sistema</td>
                                                        </tr>
                                                        <tr>
                                                            <td>::PASSWORD::</td>
                                                            <td>Contraseña del usuario generada por el sistema</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="col-12 col-sm-6">
                                                <label class="form-label">HTML de plantilla</label>
                                                <textarea type="text" class="form-control" placeholder="Escribe aquí" v-model="templateHtml" style="min-height: 500px"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-12">
                                    <h6 class="text-primary">Envío de enlace para recuperación de contraseña</h6>
                                    <div class="mb-3">
                                        <div class="row">
                                            <div class="col-12 col-sm-6">
                                                <label class="form-label">Campos disponibles</label>
                                                Los campos disponibles a utilizar en la plantilla son:
                                                <table class="table table-striped">
                                                    <thead>
                                                    <tr>
                                                        <td>Campo</td>
                                                        <td>Descripción</td>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr>
                                                        <td>::URL_RECUPERACION::</td>
                                                        <td>Enlace para inicio de sesión</td>
                                                    </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="col-12 col-sm-6">
                                                <label class="form-label">HTML de plantilla</label>
                                                <textarea type="text" class="form-control" placeholder="Escribe aquí" v-model="userResetTemplateHtml" style="min-height: 500px"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>-->
                        </div>
                    </div>
                    <div>
                        <div class="col-12 mt-4 text-end">
                            <button @click="$router.push('/usuarios/listado')" class="btn btn-danger me-4">Cancelar</button>
                            <button @click="save" class="btn btn-primary">Guardar</button>
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

export default {
    name: 'Tables',
    components: {Select},
    data() {
        return {
            id: 0,
            whatsapp: {
                token: '',
                url: '',
                type: '',
            },
            mailgun: {
                apiKey: '',
                domain: '',
                from: '',
                subject: '',
            },
            templateHtml: '',
            userResetTemplateHtml: '',
        };
    },
    mounted() {
        //this.id = (typeof this.$route.params.id !== 'undefined') ? parseInt(this.$route.params.id) : 0;
        //console.log(this.id);

        this.getData();
    },
    methods: {
        getData() {
            const self = this;

            toolbox.doAjax('GET', 'configuration/load', {},
                function (response) {
                    self.whatsapp = response.data.whatsappNotifyConfig;
                    self.mailgun = response.data.mailgunNotifyConfig;
                    self.templateHtml = response.data.userCreateTemplateHtml;
                    self.userResetTemplateHtml = response.data.userResetTemplateHtml;
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                });
        },
        save() {
            const self = this;

            toolbox.doAjax('POST', 'configuration/save', {
                    whatsapp: self.whatsapp,
                    mailgun: self.mailgun,
                    templateHtml: self.templateHtml,
                    userResetTemplateHtml: self.userResetTemplateHtml,
                },
                function (response) {
                    toolbox.alert(response.msg, 'success');
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                });
        },
    }
}
</script>
