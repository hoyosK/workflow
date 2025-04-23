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
                                <label for="Nombre" class="form-label">Nombre del distribuidor</label>
                                <input type="text" class="form-control" id="Nombre" placeholder="Escribe aquí" v-model="nombre">
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="mb-3">
                                <label for="Nombre" class="form-label">Activo</label>
                                <select class="form-select" v-model="activo">
                                    <option value="1">Activo</option>
                                    <option value="0">Desactivado</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="mb-3">
                                <label for="codigo" class="form-label">Código interno</label>
                                <input type="text" class="form-control" id="Codigo" placeholder="Código interno" v-model="codigoInterno">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <div v-if="imagen">
                                    <CCardImage :src="imagen" rounded thumbnail orientation="top" style="max-width: 200px; max-height: 100px"/>
                                    <hr>
                                </div>
                                <label for="Nombre" class="form-label">Imagen</label>
                                <CFormInput type="file" @change="onMainImageChange" class="form-control form-control-file" accept="image/*"/>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <div v-if="cintillo">
                                    <CCardImage :src="cintillo" rounded thumbnail orientation="top" style="max-width: 200px; max-height: 100px"/>
                                    <hr>
                                </div>
                                <label for="Nombre" class="form-label">Cintillo</label>
                                <CFormInput type="file" @change="onMainCintilloChange" class="form-control form-control-file" accept="image/*"/>
                            </div>
                        </div>
                        <div class="col-12">
                            <hr>
                            <div class="mb-3">
                                <h6 class="text-muted mb-3">Roles en grupo</h6>
                                <label for="password" class="form-label">Selecciona los roles</label>
                                <multiselect
                                 v-model="roles"
                                 :options="rolesOptions"
                                 :mode="'tags'"
                                 :searchable="true"/>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <h6 class="text-muted mb-3">Usuarios específicos en grupo</h6>
                                <label for="password" class="form-label">Selecciona los usuarios del grupo</label>
                                <multiselect
                                    v-model="usuarios"
                                    :options="usuariosOptions"
                                    :mode="'tags'"
                                    :searchable="true"/>
<!--                                <select class="form-select" v-model="roles">
                                    <option v-for="rol in roleList" :value="rol.id">{{rol.name}}</option>
                                </select>-->
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="col-12 mt-4 text-end">
                            <button @click="guardar" class="btn btn-primary me-4">Guardar</button>
                            <button @click="$router.push('/usuarios/grupo/listado')" class="btn btn-danger">Cancelar</button>
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
            imagen: '',
            activo: 0,
            roles: [],
            rolesOptions: [],
            usuarios: [],
            usuariosOptions: [],
            cintillo: '',
            codigoInterno: '',

            // temporal en lo que el ajax de usuarios termina
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

            this.getUsers();

            toolbox.doAjax('GET', 'users/role/list', {},
                function (response) {

                    self.rolesOptions = [];
                    Object.keys(response.data).map(function (a, b) {
                        self.rolesOptions.push({
                            value: response.data[a].id,
                            label: response.data[a].name,
                        })
                    })

                    if (self.id > 0) {

                        toolbox.doAjax('GET', 'users/grupo/get/' + self.id, {},
                            function (response) {
                                self.id = response.data.id;
                                self.nombre = response.data.nombre;
                                self.activo = response.data.activo;
                                self.imagen = response.data.imagen;
                                self.cintillo = response.data.cintillo;
                                self.codigoInterno = response.data.codigoInterno;

                                self.roles = [];
                                Object.keys(response.data.rolList).map(function (a, b) {
                                    self.roles.push(response.data.rolList[a]);
                                })

                                self.usuarios = [];
                                Object.keys(response.data.userList).map(function (a, b) {
                                    self.usuarios.push(response.data.userList[a]);
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
                    //console.log(self.usuariosOptions);
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
        guardar() {

            const self = this;

            const data = {
                id: self.id,
                nombre: self.nombre,
                activo: self.activo,
                roles: self.roles,
                imagen: self.imagen,
                usuarios: self.usuarios,
                codigoInterno: self.codigoInterno,
            }

            let errors = false;
            if (toolbox.isEmpty(this.nombre)) {
                toolbox.alert('Debe ingresar un nombre de grupo', 'danger');
                errors = true;
            }

            if (!errors) {
                toolbox.doAjax('POST', 'users/grupo/save-user', data,
                    function (response) {
                        toolbox.alert(response.msg, 'success');
                        self.$router.push('/usuarios/grupo/listado');
                        /*if (self.id === 0) {
                            self.id = response.data;
                            self.$router.push('/usuarios/grupo/edit/' + response.data);
                        }*/
                        self.getData();
                    },
                    function (response) {
                        toolbox.alert(response.msg, 'danger');
                    })
            }
        },
        onMainImageChange(event) {
            const file = event.target.files[0];
            const reader = new FileReader();
            const self = this;
            reader.onload = () => {
                self.imagen = reader.result;
            };
            reader.readAsDataURL(file);
        },
        onMainCintilloChange(event) {
            const file = event.target.files[0];
            const reader = new FileReader();
            const self = this;

            // creo la data
            const formData = new FormData();
            formData.append('file', file);

            if(!self.id){
                formData.append('id', self.id);
                const nombre = self.nombre? self.nombre : 'Sin nombre';
                const data = {
                    id: self.id,
                    nombre,
                    activo: self.activo,
                    roles: self.roles,
                    imagen: self.imagen,
                    usuarios: self.usuarios,
                }

                toolbox.doAjax('POST', 'users/grupo/save-user', data,
                    function (response) {
                        toolbox.alert(response.msg, 'success');
                        self.id = response.data;

                        formData.append('id', self.id);
                        toolbox.doAjax('FILE', 'users/grupo/save-cintillo', formData,
                            function (response) {
                                self.cintillo = response.data;
                                toolbox.alert(response.msg);
                            },
                            function (response) {
                                toolbox.alert(response.msg, 'danger');
                            })
                    },
                    function (response) {
                        toolbox.alert(response.msg, 'danger');
                    })

            } else {
                formData.append('id', self.id);
                toolbox.doAjax('FILE', 'users/grupo/save-cintillo', formData,
                    function (response) {
                        self.cintillo = response.data;
                        toolbox.alert(response.msg);
                    },
                    function (response) {
                        toolbox.alert(response.msg, 'danger');
                    })
            }
        },
    }
}
</script>
