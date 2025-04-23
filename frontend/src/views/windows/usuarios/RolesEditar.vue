<template>
    <CRow>
        <CCol :xs="12">
            <CCard class="mb-4">
                <CCardHeader>
                    <strong>Editar Rol</strong>
                </CCardHeader>
                <CCardBody>
                    <div class="row">
                        <div class="col-12 col-sm-6">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre</label>
                                <input type="text" class="form-control" id="nombre" placeholder="Escribe aquí" v-model="rol.nombre">
                            </div>
                        </div>
                    </div>
                    <div>
                        <h5 class="mb-4 mt-4">Configuración de accesos</h5>
                        <div v-for="item in accessList" :key="item.module" class="mb-4">
                            <h6 class="text-primary">{{item.module}}</h6>
                            <hr class="my-1">
                            <div class="ps-3">
                                <div class="row">
                                    <div v-for="access in item.access" :key="access.slug" class="col-12 col-sm-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="1" :id="access.slug" v-model="access.active" :checked="access.active">
                                            <label class="form-check-label" :for="access.slug">
                                                {{access.name}}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="col-12 mt-4 text-end">
                            <button @click="$router.push('/usuarios/roles/listado')" class="btn btn-danger me-4">Cancelar</button>
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

export default {
    name: 'Tables',
    components: {Select},
    data() {
        return {
            id: 0,
            rol: {
                nombre: '',
                access: {},
            },
            accessList: {},
            appList: {},
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
            toolbox.doAjax('GET', 'users/role/access/list', {},
                function (response) {

                    self.accessList = response.data;

                    if (self.id > 0) {
                        toolbox.doAjax('GET', 'users/role/load/' + self.id, {},
                            function (responseRole) {
                                self.rol = responseRole.data;

                                Object.keys(self.accessList).map(function (z) {
                                    if (typeof self.accessList[z].access !== 'undefined') {
                                        self.accessList[z].access.forEach(function (a, b) {
                                            if (typeof self.rol.access[a.slug] !== 'undefined') {
                                                self.accessList[z].access[b].active = true;
                                            }
                                            else {
                                                self.accessList[z].access[b].active = false;
                                            }
                                        })
                                    }
                                });

                                Object.keys(self.appList).map(function (z) {
                                    if (typeof self.rol.apps[self.appList[z].id] !== 'undefined') {
                                        self.appList[z].active = true;
                                    }
                                    else {
                                        self.appList[z].active = false;
                                    }
                                });
                            },
                            function (responseRole) {
                                toolbox.alert(responseRole.msg, 'danger');
                            });
                    }
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                });

            toolbox.doAjax('GET', 'apps/list', {},
                function (response) {
                    self.appList = response.data;
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                });
        },
        guardar() {

            const self = this;
            toolbox.doAjax('POST', 'users/save-role', {
                    id: self.id,
                    nombre: self.rol.nombre,
                    access: self.accessList,
                    appList: self.appList,
                },
                function (response) {
                    toolbox.alert(response.msg, 'success');
                    if (self.id === 0) {
                        self.id = response.data;
                        //self.$router.push('/usuarios/roles/edit/' + response.data);
                    }
                    self.$router.push('/usuarios/roles/listado');
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
        deleteItem(item) {
            console.log(item);
        },
        editItem(item) {
            this.$router.push('/usuarios/listado/edit/' + item.id);
        },
        password_check: function () {
            this.has_number    = /\d/.test(this.user.password);
            this.has_lowercase = /[a-z]/.test(this.user.password);
            this.has_uppercase = /[A-Z]/.test(this.user.password);
            this.has_special   = /[!@#\$%\^\&*\)\(+=._-]/.test(this.user.password);
        }
    }
}
</script>
