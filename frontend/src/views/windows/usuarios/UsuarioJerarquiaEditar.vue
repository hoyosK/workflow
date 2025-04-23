<template>
    <CRow>
        <CCol :xs="12">
            <CCard class="mb-4">
                <CCardHeader>
                    <strong>Editar distribuidor</strong>
                </CCardHeader>
                <CCardBody>
                    <div class="row">
                        <div class="col-12 col-sm-6">
                            <div class="mb-3">
                                <label for="Nombre" class="form-label">Nombre de jerarquía</label>
                                <input type="text" class="form-control" id="Nombre" placeholder="Escribe aquí" v-model="nombre">
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="mb-3">
                                <label class="form-label">Activo</label>
                                <select class="form-select" v-model="activo">
                                    <option value="1">Activo</option>
                                    <option value="0">Desactivado</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h4>Configuración de supervisión</h4>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <h6 class="text-muted mb-3">Distribuidores</h6>
                                <multiselect
                                    v-model="gruposSup"
                                    :options="gruposOptions"
                                    :mode="'tags'"
                                    :searchable="true"/>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <h6 class="text-muted mb-3">Roles</h6>
                                <multiselect
                                    v-model="rolesSup"
                                    :options="rolesOptions"
                                    :mode="'tags'"
                                    :searchable="true"/>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <h6 class="text-muted mb-3">Usuarios específicos</h6>
                                <multiselect
                                    v-model="usuariosSup"
                                    :options="usuariosOptions"
                                    :mode="'tags'"
                                    :searchable="true"/>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h4>Asignación de supervisión</h4>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <h6 class="text-muted mb-3">Distribuidores</h6>
                                <multiselect
                                    v-model="groupsD"
                                    :options="gruposOptions"
                                    :mode="'tags'"
                                    :searchable="true"/>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <h6 class="text-muted mb-3">Tiendas</h6>
                                <multiselect
                                    v-model="canalD"
                                    :options="canalesOptions"
                                    :mode="'tags'"
                                    :searchable="true"/>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <h6 class="text-muted mb-3">Roles</h6>
                                <multiselect
                                    v-model="rolesD"
                                    :options="rolesOptions"
                                    :mode="'tags'"
                                    :searchable="true"/>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <h6 class="text-muted mb-3">Usuarios específicos</h6>
                                <multiselect
                                    v-model="usuariosD"
                                    :options="usuariosOptions"
                                    :mode="'tags'"
                                    :searchable="true"/>
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="col-12 mt-4 text-end">
                            <button @click="guardar" class="btn btn-primary me-4">Guardar</button>
                            <button @click="$router.push('/usuarios/jerarquia/listado')" class="btn btn-danger">Cancelar</button>
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

export default {
    name: 'Tables',
    components: {Select, Multiselect},
    data() {
        return {
            id: 0,
            nombre: '',
            activo: 0,

            // supervisor
            loading: false,
            rolesSup: [],
            usuariosSup: [],
            gruposSup: [],

            //
            rolesD: [],
            usuariosD: [],
            groupsD: [],
            canalD: [],

            rolesOptions: [],
            usuariosOptions: [],
            gruposOptions: [],
            canalesOptions: [],
            // temporal en lo que el ajax de usuarios termina
        };
    },
    mounted() {
        const self = this;
        this.id = (typeof this.$route.params.id !== 'undefined') ? parseInt(this.$route.params.id) : 0;
        if (this.id > 0) {
            this.loading = true;
        }
        this.getCanales();
    },
    methods: {
        getJerarquia() {
            const self = this;
            if (self.id > 0) {

                toolbox.doAjax('GET', 'users/jerarquia/get/' + self.id, {},
                    function (response) {
                        self.id = response.data.id;
                        self.nombre = response.data.nombre;
                        self.activo = response.data.activo;

                        // supervisores
                        self.rolesSup = [];
                        Object.keys(response.data.rolSup).map(function (a, b) {
                            self.rolesSup.push(response.data.rolSup[a]);
                        })

                        self.usuariosSup = [];
                        Object.keys(response.data.userSup).map(function (a, b) {
                            self.usuariosSup.push(response.data.userSup[a]);
                        })

                        self.gruposSup = [];
                        Object.keys(response.data.groupSup).map(function (a, b) {
                            self.gruposSup.push(response.data.groupSup[a]);
                        })

                        // detalle
                        self.canalD = [];
                        Object.keys(response.data.canalD).map(function (a, b) {
                            self.canalD.push(response.data.canalD[a]);
                        })

                        self.groupsD = [];
                        Object.keys(response.data.groupD).map(function (a, b) {
                            self.groupsD.push(response.data.groupD[a]);
                        })

                        self.rolesD = [];
                        Object.keys(response.data.rolD).map(function (a, b) {
                            self.rolesD.push(response.data.rolD[a]);
                        })

                        self.usuariosD = [];
                        Object.keys(response.data.userD).map(function (a, b) {
                            self.usuariosD.push(response.data.userD[a]);
                        })
                    },
                    function (response) {
                        toolbox.alert(response.msg, 'danger');
                    })
            }
        },
        getRoles() {
            const self = this;

            toolbox.doAjax('GET', 'users/role/list', {},
                function (response) {

                    self.rolesOptions = [];
                    Object.keys(response.data).map(function (a, b) {
                        self.rolesOptions.push({
                            value: response.data[a].id,
                            label: response.data[a].name,
                        })
                    })
                    self.getUsers();
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                }, {noCloseLoading: self.loading});
        },
        getUsers() {

            const self = this;
            toolbox.doAjax('GET', 'users/list', {},
                function (response) {
                    self.usuariosOptions = [];
                    Object.keys(response.data).map(function (a, b) {
                        self.usuariosOptions.push({
                            value: response.data[a].id,
                            label: response.data[a].name + " ("+response.data[a].email+")",
                        })
                    })
                    self.getJerarquia();
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                }, {noCloseLoading: self.loading})
        },
        getGrupos() {
            const self = this;
            toolbox.doAjax('GET', 'users/grupo/list', {},
                function (response) {

                    self.gruposOptions = [];
                    Object.keys(response.data).map(function (a, b) {
                        self.gruposOptions.push({
                            value: response.data[a].id,
                            label: response.data[a].nombre,
                        })
                    })
                    self.getRoles();
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                }, {noCloseLoading: self.loading});
        },
        getCanales() {

            const self = this;
            toolbox.doAjax('GET', 'users/tiendas/list', {},
                function (response) {
                    self.gruposOptions = [];
                    Object.keys(response.data).map(function (a, b) {
                        self.canalesOptions.push({
                            value: response.data[a].id,
                            label: response.data[a].nombre,
                        })
                    })
                    self.getGrupos();
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                }, {noCloseLoading: self.loading})
        },
        guardar() {

            const self = this;

            const data = {
                id: self.id,
                nombre: self.nombre,
                activo: self.activo,

                gruposSup: self.gruposSup,
                rolesSup: self.rolesSup,
                usuariosSup: self.usuariosSup,

                rolesD: self.rolesD,
                usuariosD: self.usuariosD,
                groupsD: self.groupsD,
                canalD: self.canalD,
            }

            let errors = false;
            if (toolbox.isEmpty(this.nombre)) {
                toolbox.alert('Debe ingresar un nombre', 'danger');
                errors = true;
            }

            if (!errors) {
                toolbox.doAjax('POST', 'users/jerarquia/save', data,
                    function (response) {
                        toolbox.alert(response.msg, 'success');
                        self.$router.push('/usuarios/jerarquia/listado');
                        /*if (self.id === 0) {
                            self.id = response.data;
                            self.$router.push('/usuarios/grupo/edit/' + response.data);
                        }*/
                        //self.getData();
                    },
                    function (response) {
                        toolbox.alert(response.msg, 'danger');
                    })
            }
        }
    }
}
</script>
