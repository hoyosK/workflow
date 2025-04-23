<template>
    <CRow>
        <CCol :xs="12">
            <CCard class="mb-4">
                <CCardBody>
                    <div class="row">
                        <div class="col-12 col-sm-4" v-for="item in apps">
                            <div class="appListContainer">
                                <div class="mb-4 logoContainer">
                                    <img :src="'data:image/png;base64,' + item.logo" class="logo">
                                </div>
                                <hr>
                                <div>
                                    <h4>{{item.nombre}}</h4>
                                </div>
                                <div class="description mt-3">
                                    {{item.descripcion}}
                                </div>
                                <div class="mt-5">
                                    <button class="btn btn-primary" @click="startSession(item)">Acceder</button>
                                </div>
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
import {mapMutations} from "vuex";
import {config} from "@/config";

export default {
    name: 'MisApps',
    data() {
        return {
            apps: {}
        };
    },
    mounted() {
        this.getItems();
    },
    methods: {
        getItems() {

            const self = this;
            toolbox.doAjax('GET', 'apps/my/list', {},
                function (response) {
                    self.apps = response.data;
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
        startSession(app) {
            if (app.urlLogin !== '') {
                const appLogin = (app.urlLogin.indexOf("?") > -1) ? app.urlLogin + '&ersso=true' : app.urlLogin + '?ersso=true';
                window.open(appLogin);
            }
            else {
                toolbox.alert('La aplicación no se encuentra configurada correctamente', 'danger');
            }
        }
    }
}
</script>
