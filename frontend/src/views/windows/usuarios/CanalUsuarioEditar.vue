<template>
    <CRow>
        <CCol :xs="12">
            <CCard class="mb-4">
                <CCardHeader>
                    <strong>Editar canal</strong>
                </CCardHeader>
                <CCardBody>
                    <div class="row">
                        <div class="col-12 col-sm-6">
                            <div class="mb-3">
                                <label for="Nombre" class="form-label">Nombre del canal</label>
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
                                <label for="password" class="form-label">Código interno</label>
                                <input type="text" class="form-control" id="Codigo" placeholder="Código interno" v-model="codigoInterno">
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="mb-3">
                                <label for="password" class="form-label">Tipo Linea</label>
                                <input type="text" class="form-control" id="Codigo" placeholder="Tipo Linea" v-model="tipoLinea">
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="mb-3">
                                <label for="password" class="form-label">Tipo Cartera</label>
                                <input type="text" class="form-control" id="Codigo" placeholder="Tipo Cartera" v-model="tipoCartera">
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="mb-3">
                                <label for="password" class="form-label">Tipo produccion</label>
                                <input type="text" class="form-control" id="Codigo" placeholder="Tipo produccion" v-model="tipoProduccion">
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="mb-3">
                                <label for="password" class="form-label">Tipo movimiento</label>
                                <input type="text" class="form-control" id="Codigo" placeholder="Tipo movimiento" v-model="tipoMovimiento">
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="mb-3">
                                <label for="password" class="form-label">Subtipo movimiento</label>
                                <input type="text" class="form-control" id="Codigo" placeholder="Subtipo movimiento" v-model="subtipoMovimiento">
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="mb-3">
                                <label for="password" class="form-label">Tipo documento</label>
                                <input type="text" class="form-control" id="Codigo" placeholder="Tipo documento" v-model="tipoDocumento">
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="mb-3">
                                <label for="password" class="form-label">Tipo usuario</label>
                                <input type="text" class="form-control" id="Codigo" placeholder="Tipo usuario" v-model="tipoUsuario">
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="mb-3">
                                <label for="password" class="form-label">Tipo asignacion</label>
                                <input type="text" class="form-control" id="Codigo" placeholder="Tipo asignacion" v-model="tipoAsignacion">
                            </div>
                        </div>
                        <div class="col-12">
                            <hr>
                            <div class="mb-3">
                                <h6 class="text-muted mb-3">Distribuidores</h6>
                                <label for="password" class="form-label">Selecciona los distribuidores asignados</label>
                                <multiselect
                                 v-model="grupos"
                                 :options="gruposOptions"
                                 :mode="'tags'"
                                 :searchable="true"/>
                            </div>
                        </div>
<!--                        <div class="col-12">
                            <div class="mb-3">
                                <h6 class="text-muted mb-3">Usuarios específicos en grupo</h6>
                                <label for="password" class="form-label">Selecciona los usuarios del grupo</label>
                                <multiselect
                                    v-model="usuarios"
                                    :options="usuariosOptions"
                                    :mode="'tags'"
                                    :searchable="true"/>
                            </div>
                        </div>-->
                    </div>
                    <div>
                        <div class="col-12 mt-4 text-end">
                            <button @click="$router.push('/usuarios/canal/listado')" class="btn btn-danger me-4">Cancelar</button>
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
import Multiselect from '@vueform/multiselect'

export default {
    name: 'Tables',
    components: {Select, Multiselect},
    data() {
        return {
            id: 0,
            nombre: '',
            activo: 0,
            grupos: [],
            gruposOptions: [],
            codigoInterno: '',
            tipoLinea: '',
            tipoCartera: '',
            tipoProduccion: '',
            tipoMovimiento: '',
            subtipoMovimiento: '',
            tipoDocumento: '',
            tipoUsuario: '',
            tipoAsignacion: '',

            /*usuarios: [],
            usuariosOptions: [],*/

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

            // this.getUsers();

            toolbox.doAjax('GET', 'users/grupo/list', {},
                function (response) {

                    self.gruposOptions = [];
                    Object.keys(response.data).map(function (a, b) {
                        self.gruposOptions.push({
                            value: response.data[a].id,
                            label: response.data[a].nombre,
                        })
                    })

                    if (self.id > 0) {

                        toolbox.doAjax('GET', 'users/canal/get/' + self.id, {},
                            function (response) {
                                self.id = response.data.id;
                                self.nombre = response.data.nombre;
                                self.activo = response.data.activo;
                                self.codigoInterno = response.data.codigoInterno;
                                self.tipoLinea = response.data.tipoLinea;
                                self.tipoCartera = response.data.tipoCartera;
                                self.tipoProduccion = response.data.tipoProduccion;
                                self.tipoMovimiento = response.data.tipoMovimiento;
                                self.subtipoMovimiento = response.data.subtipoMovimiento;
                                self.tipoDocumento = response.data.tipoDocumento;
                                self.tipoUsuario = response.data.tipoUsuario;
                                self.tipoAsignacion = response.data.tipoAsignacion;

                                self.grupos = [];
                                Object.keys(response.data.grupoList).map(function (a, b) {
                                    self.grupos.push(response.data.grupoList[a]);
                                })

                                /*self.usuarios = [];
                                Object.keys(response.data.userList).map(function (a, b) {
                                    self.usuarios.push(response.data.userList[a]);
                                })*/
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
        /*getUsers() {

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
                    console.log(self.usuariosOptions);
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },*/
        guardar() {

            const self = this;

            const data = {
                id: self.id,
                nombre: self.nombre,
                activo: self.activo,
                grupos: self.grupos,
                codigoInterno: self.codigoInterno,
                tipoLinea: self.tipoLinea,
                tipoCartera: self.tipoCartera,
                tipoProduccion: self.tipoProduccion,
                tipoMovimiento: self.tipoMovimiento,
                subtipoMovimiento: self.subtipoMovimiento,
                tipoDocumento: self.tipoDocumento,
                tipoUsuario: self.tipoUsuario,
                tipoAsignacion: self.tipoAsignacion,
                //usuarios: self.usuarios,
            }

            let errors = false;
            if (toolbox.isEmpty(this.nombre)) {
                toolbox.alert('Debe ingresar un nombre de grupo', 'danger');
                errors = true;
            }

            if (!errors) {
                toolbox.doAjax('POST', 'users/canal/save-user', data,
                    function (response) {
                        toolbox.alert(response.msg, 'success');
                        self.$router.push('/usuarios/canal/listado');
                        /*if (self.id === 0) {
                            self.id = response.data;
                            self.$router.push('/usuarios/canal/edit/' + response.data);
                        }*/
                        self.getData();
                    },
                    function (response) {
                        toolbox.alert(response.msg, 'danger');
                    })
            }
        }
    }
}
</script>
