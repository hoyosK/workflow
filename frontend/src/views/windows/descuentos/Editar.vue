<template>
    <CRow>
        <CCol :xs="12">
            <CCard class="mb-4">
                <CCardHeader>
                    <strong>Editar descuento</strong>
                </CCardHeader>
                <CCardBody>
                    <h5>Datos generales</h5>
                    <hr>
                    <div class="row">
                        <div class="col-12 col-sm-4">
                            <div class="mb-3">
                                <label class="form-label">Nombre</label>
                                <input type="text" class="form-control" placeholder="Escribe aquí" v-model="nombre">
                            </div>
                        </div>
                        <div class="col-12 col-sm-4" v-if="tipo === 'q'">
                            <div class="mb-3">
                                <label class="form-label">Monto</label>
                                <input type="number" class="form-control" placeholder="Escribe aquí" v-model="monto">
                            </div>
                        </div>
                        <div class="col-12 col-sm-4" v-if="tipo === 'p'">
                            <div class="mb-3">
                                <label class="form-label">Valor mínimo</label>
                                <input type="number" class="form-control" placeholder="Escribe aquí" v-model="valormin"
                                    :disabled="true"
                                    :readonly="true"
                                >
                            </div>
                        </div>
                        <div class="col-12 col-sm-4" v-if="tipo === 'p'">
                            <div class="mb-3">
                                <label class="form-label">Valor máximo</label>
                                <input type="number" class="form-control" placeholder="Escribe aquí" v-model="valormax">
                            </div>
                        </div>
                        <div class="col-12 col-sm-4">
                            <div class="mb-3">
                                <label class="form-label">Tipo</label>
                                <select class="form-control" v-model="tipo">
                                    <option value="p">%</option>
                                    <!--<option value="q">Q</option>-->
                                    <!--<option value="u">USD</option>-->
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-sm-4">
                            <div class="mb-3">
                                <label class="form-label">Flujo</label>
                                <div>
                                    <multiselect
                                        v-model="flujos"
                                        :options="flujosOptions"
                                        :mode="'tags'"
                                        :searchable="true"
                                        @select="getCampos"
                                    />
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-4">
                            <div class="mb-3">
                                <label class="form-label">Estado</label>
                                <select class="form-control" v-model="activo">
                                    <option value="1">Activo</option>
                                    <option value="0">Desactivado</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <h6 class="text-primary">Configuración de visibilidad</h6>
                        <div class="row">
                            <div class="col-12 col-sm-4">
                                <div class="mb-3">
                                    <span>Selecciona el canal de usuarios</span>
                                    <multiselect
                                        v-model="canales_assign"
                                        :options="canales"
                                        :mode="'tags'"
                                        :searchable="true"/>
                                </div>
                            </div>
                            <div class="col-12 col-sm-4">
                                <div class="mb-3">
                                    <span>Selecciona los distribuidores</span>
                                    <multiselect
                                        v-model="grupos_assign"
                                        :options="grupos"
                                        :mode="'tags'"
                                        :searchable="true"/>
                                </div>
                            </div>
                            <div class="col-12 col-sm-4">
                                <div class="mb-3">
                                    <span>Selecciona los roles</span>
                                    <multiselect
                                        v-model="roles_assign"
                                        :options="roles"
                                        :mode="'tags'"
                                        :searchable="true"/>
                                </div>
                            </div>
                            <div class="col-12 col-sm-4">
                                <div class="mb-3">
                                    <span>Selecciona los usuarios</span>
                                    <multiselect
                                        v-model="users_assign"
                                        :options="users"
                                        :mode="'tags'"
                                        :searchable="true"/>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="text-muted">
                                    * Atención, si selecciona accesos se sobreescribirán en cascada Canales > Grupos > Roles
                                </div>
                            </div>
                            <div class="col-12 mt-4">
                                <div class="mb-3">
                                    <span>Productos</span>
                                    <multiselect
                                        v-model="productos_assign"
                                        :options="productos"
                                        :mode="'tags'"
                                        :searchable="true"/>
                                    <div class="text-muted">
                                        * Atención, si no configura ningún producto, el descuento será global (solo tomará en cuenta permisos por usuario).
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="mt-4 text-end">
                            <button @click="$router.push('/admin/descuentos')" class="btn btn-danger me-4">Cancelar</button>
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
import Multiselect from '@vueform/multiselect'
import Select from "@/views/forms/Select.vue";
import Vue3TagsInput from 'vue3-tags-input';

export default {
    name: 'Tables',
    components: {Select, Multiselect, Vue3TagsInput},
    data() {
        return {
            id: 0,
            nombre: '',
            activo: 1,
            monto: 0,
            valormin: 0,
            valormax: 0,
            tipo: 'p',
            producto: 0,
            flujos: [],
            flujosOptions: [],

            roles: [],
            users: [],
            grupos: [],
            canales: [],
            productos: [],

            apiKey: 'n8ab72lgcjz7weqad287mk9pgjg0acg88z7xzhdf0y0hc9zn',
            canales_assign: [],
            grupos_assign: [],
            users_assign: [],
            roles_assign: [],
            productos_assign: [],
        };
    },
    mounted() {
        this.id = (typeof this.$route.params.id !== 'undefined') ? parseInt(this.$route.params.id) : 0;
        const self = this;

        self.getRoles();
        self.getCanales();
        self.getGrupos();
        self.getUsers();
        self.getFlujos();
        self.getProductos();
    },
    methods: {
        getData() {
            const self = this;

            toolbox.doAjax('POST', 'descuentos/get', {
                    id: self.id,
                },
                function (response) {
                    self.id = response.data.id;
                    self.activo = response.data.activo;
                    self.nombre = response.data.nombre;
                    self.monto = response.data.monto;
                    self.tipo = response.data.tipo;
                    self.valormin = response.data.valormin;
                    self.valormax = response.data.valormax;

                    self.flujos = [];
                    Object.keys(response.data.c.p).map(function (a, b) {
                        self.flujos.push(response.data.c.p[a]);
                    })

                    self.roles_assign = response?.data?.c?.visibilidad?.roles;
                    self.users_assign = response?.data?.c?.visibilidad?.users;
                    self.grupos_assign = response?.data?.c?.visibilidad?.grupos;
                    self.canales_assign = response?.data?.c?.visibilidad?.canales;
                    self.productos_assign = response?.data?.c?.visibilidad?.productos;

            })
        },
        getFlujos() {
            const self = this;

            toolbox.doAjax('GET', 'descuentos/get-flujos', {},
                function (response) {
                    self.flujosOptions = [];
                    Object.keys(response.data).map(function (a, b) {
                        self.flujosOptions.push({
                            value: response.data[a].id,
                            label: response.data[a].nombreProducto,
                        })
                    })

                    self.getData();
            })
        },
        getProductos() {
            const self = this;
            toolbox.doAjax('POST', 'admin/catalogo/load', {
                    slug: 'productos',
                    opt: 'get',
                },
                function (response) {
                    Object.keys(response.data).map(function (a, b) {
                        self.productos.push({
                            value: response.data[a].id,
                            label: '('+response.data[a].codigoProducto+') ' + response.data[a].descripcion,
                        })
                    })
                    //console.log(self.items);
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
        guardar() {

            const self = this;

            let errors = false;
            if (toolbox.isEmpty(this.nombre)) {
                toolbox.alert('Debe ingresar un nombre de usuario', 'danger');
                errors = true;
            }

            if (!errors) {
                toolbox.doAjax('POST', 'descuentos/save', {
                        id: self.id,
                        nombre: self.nombre,
                        activo: self.activo,
                        flujos: self.flujos,
                        productos: self.productos,
                        tipo: self.tipo,
                        monto: self.monto,
                        valormin: self.valormin,
                        valormax: self.valormax,
                        visibilidad: {
                            roles: self.roles_assign,
                            users: self.users_assign,
                            grupos: self.grupos_assign,
                            canales: self.canales_assign,
                            productos: self.productos_assign,
                        },
                    },
                    function (response) {
                        toolbox.alert(response.msg, 'success');
                        if (self.id === 0) {
                            self.id = response.data.id;
                            self.$router.push('/admin/descuentos/' + response.data.id);
                        }
                        self.getData();
                    },
                    function (response) {
                        toolbox.alert(response.msg, 'danger');
                    })
            }
        },
        //###Roles######
        getUsers() {
            const self = this;
            toolbox.doAjax('GET', 'users/list', {},
                function (response) {
                    Object.keys(response.data).map(function (a, b) {
                        self.users.push({
                            value: response.data[a].id,
                            label: response.data[a].name,
                        })
                    })
                },
                function (response) {
                    //toolbox.alert(response.msg, 'danger');
                })
        },
        getRoles() {
            const self = this;
            toolbox.doAjax('GET', 'users/role/list', {},
                function (response) {
                    Object.keys(response.data).map(function (a, b) {
                        self.roles.push({
                            value: response.data[a].id,
                            label: response.data[a].name,
                        })
                    })
                },
                function (response) {
                    //toolbox.alert(response.msg, 'danger');
                })
        },
        getGrupos() {
            const self = this;
            toolbox.doAjax('GET', 'users/grupo/list', {},
                function (response) {
                    Object.keys(response.data).map(function (a, b) {
                        self.grupos.push({
                            value: response.data[a].id,
                            label: response.data[a].nombre,
                        })
                    })
                },
                function (response) {
                    //toolbox.alert(response.msg, 'danger');
                })
        },
        getCanales() {
            const self = this;
            toolbox.doAjax('GET', 'users/canal/list', {},
                function (response) {
                    Object.keys(response.data).map(function (a, b) {
                        self.canales.push({
                            value: response.data[a].id,
                            label: response.data[a].nombre,
                        })
                    })
                },
                function (response) {
                    //toolbox.alert(response.msg, 'danger');
                })
        },
    }
}
</script>
