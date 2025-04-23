<template>
    <CRow>
        <CCol :xs="12">
            <CCard class="mb-4">
                <CCardHeader>
                    <strong>Edición de tarifas y coberturas por producto</strong>
                </CCardHeader>
                <CCardBody>
                    <div>
                        <div class="mt-4">
                            <div class="mt-3">
                                <div class="row">
                                    <div class="col-5">
                                        <strong>Tarifas del producto</strong>
                                        <div class="mt-4">
                                            <div class="row">
                                                <div class="col-12">
                                                    <label>Listado de tarifas</label>
                                                    <multiselect
                                                        v-model="tarifaSelected"
                                                        :options="tarifasOptions"
                                                        :searchable="true"
                                                        @select="asociarTarifa"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                        <ul class="list-group mt-3">
                                            <li class="list-group-item" v-for="item in productoTarifas">
                                                <div class="row">
                                                    <div class="col-10">
                                                        {{item.tarifaId}} - {{item.desc}}
                                                    </div>
                                                    <div class="col-2 text-end" v-if="!item.new">
                                                        <i @click="deleteTarifa(item.tarifaId)" class="fas fa-trash me-3 text-danger"></i>
                                                        <i @click="editTarifa(item.tarifaId)" class="fas fa-edit"></i>
                                                    </div>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="col-7">
                                        <strong>Coberturas del producto</strong>
                                        <div class="mt-4">
                                            <div class="row">
                                                <div class="col-12">
                                                    <label>Listado de coberturas</label>
                                                    <multiselect
                                                        v-model="coberturaSelected"
                                                        :options="coberturasOptions"
                                                        :searchable="true"
                                                        @select="asociarCobertura"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                        <ul class="list-group mt-3">
                                            <li class="list-group-item" v-for="item in productoCoberturas">
                                                <div class="row">
                                                    <div class="col-10">
                                                        {{item.coberturaId}} - {{item.desc}}
                                                    </div>
                                                    <div class="col-2 text-end">
                                                        <i @click="deleteCobertura(item.coberturaId)" class="fas fa-trash me-3 text-danger" v-if="!item.new"></i>
                                                        <i @click="editCobertura(item.coberturaId)" class="fas fa-edit" v-if="!lockSave && !item.new"></i>
                                                    </div>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="showEditTarifa" @close="() => { showEditTarifa = false }" class="globalModal">
                            <div class="globalModalContainer position-relative">
                                <div @click="showEditTarifa = false" class="globalModalClose mt-3" style="position: absolute"><i class="fas fa-times-circle"></i></div>
                                <div>
                                    <div>
                                        <strong>Edición de Tarifa</strong>
                                        <div class="mt-4">
                                            <div class="row">
                                                <div class="col-12 col-sm-4 mt-3">
                                                    <div>
                                                        <label>Nombre de tarifa</label>
                                                        <input type="text" class="form-control" v-model="tarifaEditing.nombre" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-12 mt-3 mt-3">
                                                    <strong>Visibilidad de tarifa</strong>
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <label>Distribuidor asociado</label>
                                                            <multiselect
                                                                v-model="gruposSup"
                                                                :options="gruposOptions"
                                                                :mode="'tags'"
                                                                :searchable="true"/>
                                                        </div>
                                                    </div>
                                                    <div class="text-muted">
                                                        Permite asociar la tarifa a un distribuidor
                                                    </div>
                                                </div>
                                            </div>
                                            <div v-if="coberturaEditing.obligatorio" class="mt-5">
                                                <h6>Condicionales</h6>
                                                <div class="row">
                                                    <div class="col-12 col-sm-4 mt-3">
                                                        <label>Identificador de variable a evaluar</label>
                                                        <input class="form-control" type="text" v-model="coberturaEditing.condicionVar"/>
                                                    </div>
                                                    <div class="col-12 col-sm-4 mt-3">
                                                        <label for="llevaValor">Operación</label>
                                                        <select class="form-select" v-model="coberturaEditing.condicionOperacion">
                                                            <option value="=">Igual a</option>
                                                            <option value="<>">Diferente a</option>
                                                            <option value=">">Mayor que</option>
                                                            <option value="<">Menor que</option>
                                                            <option value=">=">Mayor o igual que</option>
                                                            <option value="<=">Menor o igual que</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-12 col-sm-4 mt-3">
                                                        <label>Valor de comparación</label>
                                                        <input class="form-control" type="text" v-model="coberturaEditing.condicionValor"/>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-5">
                                            <h6 class="text-muted mb-3">Descuentos</h6>
                                            <div>
                                                <div class="row mb-4" v-for="(item, key) in tarifaEditing.descuento_recargo">
                                                    <div class="col-12">
                                                        <h5 v-if="item.tipo === 'desc'">Descuento No.{{key+1}}</h5>
                                                        <h5 v-else-if="item.tipo === 'recar'">Recargo No.{{key+1}}</h5>
                                                        <h5 v-else>Descuento o recargo No.{{key+1}}</h5>
                                                    </div>
                                                    <div class="col-12 col-sm-3">
                                                        <label>Nombre</label>
                                                        <input type="text" v-model="item.nombre" class="form-control"/>
                                                    </div>
                                                    <div class="col-12 col-sm-3">
                                                        <label>Tipo</label>
                                                        <select class="form-control" v-model="item.tipo">
                                                            <option value="desc">Descuento</option>
                                                            <option value="recar">Recargo</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-12 col-sm-3">
                                                        <label>Monto</label>
                                                        <input type="text" v-model="item.monto" class="form-control" disabled/>
                                                    </div>
                                                    <div class="col-12 col-sm-3">
                                                        <label>Porcentaje</label>
                                                        <input type="text" v-model="item.porcentaje" class="form-control"/>
                                                    </div>
                                                    <div class="col-12 col-sm-3">
                                                        <label>Tipo secundario</label>
                                                        <select class="form-control" v-model="item.tipo">
                                                            <option value="tipo">Contado</option>
                                                            <option value="recar">Crédito</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-12 col-sm-3">
                                                        <label>Desde</label>
                                                        <input type="text" v-model="item.desde" class="form-control"/>
                                                    </div>
                                                    <div class="col-12 col-sm-3">
                                                        <label>Hasta</label>
                                                        <input type="text" v-model="item.hasta" class="form-control"/>
                                                    </div>
                                                    <div class="col-12 mt-4">
                                                        <h6 class="text-muted">Acceso a descuento</h6>
                                                        <div class="row">
                                                            <div class="col-12 col-sm-4">
                                                                <div class="mb-3">
                                                                    <label>Canales</label>
                                                                    <multiselect
                                                                        v-model="item.canales"
                                                                        :options="canalesOptions"
                                                                        :mode="'tags'"
                                                                        :searchable="true"/>
                                                                </div>
                                                            </div>
                                                            <div class="col-12  col-sm-4">
                                                                <div class="mb-3">
                                                                    <label>Distribuidores</label>
                                                                    <multiselect
                                                                        v-model="item.grupos"
                                                                        :options="gruposOptions"
                                                                        :mode="'tags'"
                                                                        :searchable="true"/>
                                                                </div>
                                                            </div>
                                                            <div class="col-12  col-sm-4">
                                                                <div class="mb-3">
                                                                    <label>Roles</label>
                                                                    <multiselect
                                                                        v-model="item.roles"
                                                                        :options="rolesOptions"
                                                                        :mode="'tags'"
                                                                        :searchable="true"/>
                                                                </div>
                                                            </div>
                                                            <div class="col-12 col-sm-12">
                                                                <div class="mb-3">
                                                                    <label>Usuarios específicos</label>
                                                                    <multiselect
                                                                        v-model="item.usuarios"
                                                                        :options="usuariosOptions"
                                                                        :mode="'tags'"
                                                                        :searchable="true"/>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="text-end">
                                                            <button @click="deleteDiscount(key)" class="btn btn-danger btn-sm">Eliminar</button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="text-muted">
                                                    Si utiliza monto, se sobreescribirá el porcentaje
                                                </div>
                                            </div>
                                            <div class="mt-3">
                                                <button @click="addDescuentoRecargo" class="btn btn-success btn-sm">Agregar descuento o recargo</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3 text-end">
                                        <button @click="guardar" class="btn btn-success">Guardar cobertura</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="showEditCobertura" @close="() => { showEditCobertura = false }" class="globalModal">
                            <div class="globalModalContainer position-relative">
                                <div @click="showEditCobertura = false" class="globalModalClose mt-3" style="position: absolute"><i class="fas fa-times-circle"></i></div>
                                <div>
                                    <div>
                                        <strong>Edición de cobertura</strong>
                                        <div class="mt-4">
                                            <div class="row">
                                                <div class="col-12 col-sm-4 mt-3">
                                                    <label>Nombre de cobertura</label>
                                                    <input type="text" class="form-control" v-model="coberturaEditing.nombre" disabled>
                                                </div>
                                                <div class="col-12 col-sm-4 mt-3">
                                                    <label>Antigüedad máxima de vehículo</label>
                                                    <input type="text" class="form-control" v-model="coberturaEditing.antiguedadMax">
                                                </div>
                                                <div class="col-12 col-sm-4 mt-3 text-center">
                                                    <label for="obligatorio">Es obligatorio</label>
                                                    <input id="obligatorio" type="checkbox" v-model="coberturaEditing.obligatorio" :checked="coberturaEditing.obligatorio" class="ms-2">
                                                </div>
                                                <div class="col-12 col-sm-4 mt-3 text-center">
                                                    <label for="llevaValor">¿Lleva valor del vehículo?</label>
                                                    <input id="llevaValor" type="checkbox" v-model="coberturaEditing.llevaValorVehiculo" :checked="coberturaEditing.llevaValorVehiculo" class="ms-2">
                                                </div>
                                            </div>
                                            <div v-if="coberturaEditing.obligatorio" class="mt-5">
                                                <h6>Condicionales</h6>
                                                <div class="row">
                                                    <div class="col-12 col-sm-4 mt-3">
                                                        <label>Identificador de variable a evaluar</label>
                                                        <input class="form-control" type="text" v-model="coberturaEditing.condicionVar"/>
                                                    </div>
                                                    <div class="col-12 col-sm-4 mt-3">
                                                        <label for="llevaValor">Operación</label>
                                                        <select class="form-select" v-model="coberturaEditing.condicionOperacion">
                                                            <option value="=">Igual a</option>
                                                            <option value="<>">Diferente a</option>
                                                            <option value=">">Mayor que</option>
                                                            <option value="<">Menor que</option>
                                                            <option value=">=">Mayor o igual que</option>
                                                            <option value="<=">Menor o igual que</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-12 col-sm-4 mt-3">
                                                        <label>Valor de comparación</label>
                                                        <input class="form-control" type="text" v-model="coberturaEditing.condicionValor"/>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3 text-end">
                                        <button @click="guardar" class="btn btn-success">Guardar cobertura</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 text-end" v-if="!tarifaEditing.idTarifa">
                            <button @click="guardar" class="btn btn-success">Guardar configuración</button>
                        </div>
                    </div>
                </CCardBody>
            </CCard>
        </CCol>
    </CRow>
</template>

<script>
import toolbox from "@/toolbox";
import {useRoute} from "vue-router";
import Button from "@/views/forms/form_elements/FormElementButton.vue";
import Multiselect from '@vueform/multiselect'
import Vue3TagsInput from 'vue3-tags-input';
import Select from "@/views/forms/Select.vue";


export default {
    name: 'Tables',
    components: {Select, Button, Multiselect, Vue3TagsInput},
    data() {
        return {
            tarifas: {},
            tarifaSelected: false,
            tarifasOptions: [],
            tarifaEditing: {},

            coberturas: {},
            coberturaSelected: false,
            coberturasOptions: [],
            coberturaEditing: {},

            gruposSup: [],
            gruposOptions: [],
            rolesOptions: [],
            usuariosOptions: [],
            canalesOptions: [],

            productoTarifas: [],
            productoCoberturas: [],

            // visualización
            lockSave: false,
            showEditTarifa: false,
            showEditCobertura: false,

        };
    },
    mounted() {
        this.productoId = (typeof this.$route.params.id !== 'undefined') ? parseInt(this.$route.params.id) : 0;
        this.loadCatalogo();
        this.getCanales();
    },
    methods: {
        loadCatalogo() {

            const self = this;

            toolbox.doAjax('POST', 'admin/catalogo/load', {
                    slug: 'coberturas',
                    opt: 'get',
                },
                function (response) {
                    self.coberturasOptions = [];
                    Object.keys(response.data).map(function (a, b) {
                        self.coberturasOptions.push({
                            value: response.data[a].id,
                            label: response.data[a].nombre,
                        })
                    })
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })

            toolbox.doAjax('POST', 'admin/catalogo/load', {
                    slug: 'tarifas',
                    opt: 'get',
                },
                function (response) {

                    self.tarifasOptions = [];
                    Object.keys(response.data).map(function (a, b) {
                        self.tarifasOptions.push({
                            value: response.data[a].id,
                            label: response.data[a].descripcion,
                        })
                    })

                    // traigo los productos y tarifas guardados
                    toolbox.doAjax('POST', 'admin/load/productos-coberturas-tarifas', {
                            productoId: self.productoId
                        },
                        function (response) {

                            self.productoTarifas = [];
                            Object.keys(response.data.t).map(function (a, b) {

                                let descTarifa = '';
                                self.tarifasOptions.forEach(function (c){
                                    if (parseInt(c.value) === parseInt(response.data.t[a].idTarifa)) {
                                        descTarifa = c.label;
                                    }
                                })

                                self.productoTarifas.push({
                                    tarifaId: response.data.t[a].idTarifa,
                                    desc: descTarifa,
                                })
                            })


                            console.log(response.data.c);
                            self.productoCoberturas = [];
                            Object.keys(response.data.c).map(function (a, b) {

                                let descCobertura = '';
                                self.coberturasOptions.forEach(function (c){
                                    if (parseInt(c.value) === parseInt(response.data.c[a].idCobertura)) {
                                        descCobertura = c.label;
                                    }
                                })

                                self.productoCoberturas.push({
                                    coberturaId: response.data.c[a].idCobertura,
                                    desc: descCobertura,
                                })
                            })
                        },
                        function (response) {
                            toolbox.alert(response.msg, 'danger');
                        })
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
        asociarTarifa () {
            const self = this;
            let descTarifa = '';
            this.tarifasOptions.forEach(function (a){
                if (a.value === self.tarifaSelected) {
                    descTarifa = a.label;
                }
            })
            this.productoTarifas.push({
                tarifaId: self.tarifaSelected,
                desc: descTarifa,
                new: true,
            });

            setTimeout(function () {
                self.tarifaSelected = 0;
            }, 50);
        },
        editTarifa(idTarifa) {
            const self = this;
            toolbox.doAjax('POST', 'admin/load/producto-tarifa', {
                    productoId: self.productoId,
                    tarifaId: idTarifa,
                },
                function (response) {

                    self.gruposSup = [];
                    Object.keys(response.data.gu).map(function (a, b) {
                        self.gruposSup.push(response.data.gu[a].grupoUsuarioId);
                    })

                    let descTarifa = '';
                    self.tarifasOptions.forEach(function (c){
                        if (parseInt(c.value) === parseInt(response.data.idTarifa)) {
                            descTarifa = c.label;
                        }
                    })

                    self.tarifaEditing = response.data;
                    self.tarifaEditing.nombre = descTarifa;
                    self.showEditTarifa = true;
                    self.tarifaEditing.descuento_recargo = [];

                    Object.keys(response.data.descRec).map(function (a, b) {

                        const jsonAccess = JSON.parse(response.data.descRec[a].accesos);

                        self.tarifaEditing.descuento_recargo.push({
                            nombre: response.data.descRec[a].nombre,
                            tipo: response.data.descRec[a].tipo,
                            monto: response.data.descRec[a].monto,
                            porcentaje: response.data.descRec[a].porcentaje,
                            grupos: jsonAccess.grupos,
                            roles: jsonAccess.roles,
                            canales: jsonAccess.canales,
                        });
                    })

                    toolbox.alert(response.msg, 'success');
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
        deleteTarifa(idTarifa) {
            const self = this;
            toolbox.confirm('¿Está seguro de eliminar la tarifa?', function () {
                toolbox.doAjax('POST', 'admin/delete/productos-tarifa', {
                        productoId: self.productoId,
                        tarifaId: idTarifa,
                    },
                    function (response) {
                        self.loadCatalogo();
                    },
                    function (response) {
                        toolbox.alert(response.msg, 'danger');
                    })
            })
        },
        editCobertura(idCobertura) {
            const self = this;

            toolbox.doAjax('POST', 'admin/load/producto-cobertura', {
                    productoId: self.productoId,
                    coberturaId: idCobertura,
                },
                function (response) {

                    self.coberturaEditing = response.data;

                    let descTarifa = '';
                    self.coberturasOptions.forEach(function (a){
                        if (a.value === idCobertura) {
                            descTarifa = a.label;
                        }
                    })

                    self.coberturaEditing = response.data;
                    self.coberturaEditing.nombre = descTarifa;
                    self.coberturaEditing.obligatorio = (!!self.coberturaEditing.obligatorio);
                    self.coberturaEditing.llevaValorVehiculo = (!!self.coberturaEditing.llevaValorVehiculo);
                    /*self.coberturaEditing.condicionVar = self.coberturaEditing.condicionVar;
                    self.coberturaEditing.condicionOperacion = self.coberturaEditing.condicionOperacion;
                    self.coberturaEditing.condicionValor = self.coberturaEditing.condicionValor;
                    self.coberturaEditing.descuentos = self.coberturaEditing.descuentos;*/

                    self.showEditCobertura = true;
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
        asociarCobertura () {
            const self = this;
            let desc = '';
            this.coberturasOptions.forEach(function (a){
                if (a.value === self.coberturaSelected) {
                    desc = a.label;
                }
            })
            this.productoCoberturas.push({
                coberturaId: self.coberturaSelected,
                desc: desc,
            });

            setTimeout(function () {
                self.coberturaSelected = 0;
            }, 50);
        },
        addDescuentoRecargo() {
            this.tarifaEditing.descuento_recargo.push({
                nombre: '',
                tipo: '',
                monto: '',
                porcentaje: '',
            })
        },
        guardar() {
            const self = this;
            toolbox.doAjax('POST', 'admin/productos-tarifas/save', {
                productoId: self.productoId,
                tarifaId: self.tarifaEditing.idTarifa,
                descuentosTarifa: self.tarifaEditing.descuento_recargo,
                /*
                coberturaId: self.coberturaEditing.idCobertura,*/
                tarifas: self.productoTarifas,
                coberturas: self.productoCoberturas,
                coberturaEditing: self.coberturaEditing,
                gruposSup: self.gruposSup,
            },
            function (response) {
                if (self.tarifaEditing.idTarifa) {
                    self.lockSave = false;
                }
                self.loadCatalogo();
                toolbox.alert(response.msg, 'success');
            },
            function (response) {
                toolbox.alert(response.msg, 'danger');
            })
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
        getCanales() {

            const self = this;
            toolbox.doAjax('GET', 'users/canal/list', {},
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
        deleteDiscount(key) {
            const self = this;
            toolbox.confirm('¿Está seguro de eliminar?', function () {
                self.tarifaEditing.descuento_recargo.splice(key, 1);
            })
        }
    }
}
</script>
