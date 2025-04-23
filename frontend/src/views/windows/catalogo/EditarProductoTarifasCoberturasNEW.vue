<template>
    <CRow>
        <CCol :xs="12">
            <CCard class="mb-4">
                <CCardHeader>
                    <strong>Edición de productos: {{ loadFull.p }}</strong> <button class="btn btn-primary btn-sm float-end" @click="loadCatalogoProductos">Cargar configuración</button>
                </CCardHeader>
                <CCardBody>
                    <div style="overflow: scroll; width: 100%; height: 65vh; padding-bottom: 2em">
                        <table class="ptcConfigTableTB table table-striped" style="width: max-content">
                            <thead class="rowTitle">
                                <tr class="fw-bold">
                                    <td>
                                        <h6 class="text-success m-0 p-0">
                                            <span class="cursor-pointer small" @click="newAddTarifa"><i class="fas fa-plus-circle mr-2"></i> Agregar tarifa</span>
                                        </h6>
                                        <div class="mt-3">
                                            Cobertura
                                        </div>
                                    </td>
                                    <td style="height: 110px">
                                        Condicionales
                                    </td>
                                    <td>
                                        Visibilidad condicional
                                    </td>
                                    <td>
                                        Visibilidad
                                    </td>
                                    <td>
                                        Criterio
                                    </td>
                                    <td>
                                        Moneda
                                    </td>
                                    <td>
                                        Tipo de numeración
                                    </td>
                                    <td>
                                        Valor de vehículo
                                    </td>
                                    <td>
                                        Incluye suma asegurada
                                    </td>
                                    <td>
                                        Mto. prima
                                    </td>
                                    <td>
                                        Mto. Deduc. Minimo
                                    </td>
                                    <td>
                                        Tasa Deduc.
                                    </td>
                                    <td>
                                        Suma asegurada
                                    </td>
                                    <td>
                                        Tipo
                                    </td>
                                    <td>
                                        Prima mínima
                                    </td>
                                    <td v-for="item in newTarifas" style="width: 150px">
                                        <div class="text-center">
                                            <span class="cursor-pointer small text-danger" @click="newDeleteTarifa(item.idTarifa)"><i class="fas fa-minus-circle"></i></span>
                                        </div>
                                        <select class="form-select" v-model="item.idTarifa">
                                            <option :value="tarifa.id" v-for="tarifa in loadFull.cat.tarifas">{{ tarifa.idTarifa }} - {{ tarifa.clasificacion }}</option>
                                        </select>
                                        <div class="text-center">
                                            <div style="font-size: 0.7em">
                                                Valor tarifa
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </thead>
                            <tbody>
                            <tr class="ptcConfigTableTBRow" v-for="(cober, keyCober) in newCoberturas">
                                <td style="padding-left: 2em; position: relative">
                                    <div style="left: 10px; top: 15px; position: absolute;">
                                        <div class="cursor-pointer small text-danger" @click="deleteCobertura(cober.id)">
                                            <i class="fas fa-minus-circle"></i>
                                        </div>
                                    </div>
                                    <select class="form-select" v-model="cober.idCobertura">
                                        <option :value="cobertura.id" v-for="cobertura in loadFull.cat.cobertura">{{ cobertura.codigo }} - {{ cobertura.nombre }}</option>
                                    </select>
                                </td>
                                <td>
                                    <input class="form-control width-large" type="text" v-model="cober.condicionales">
                                    <div class="stickyHTitle">{{searchCobertura(cober.idCobertura)}}</div>
                                </td>
                                <td>
                                    <input class="form-control width-large" type="text" v-model="cober.condicionalesVisibilidad">
                                    <div class="stickyHTitle">{{searchCobertura(cober.idCobertura)}}</div>
                                </td>
                                <td>
                                    <select class="form-select" v-model="cober.tipoVisibilidad">
                                        <option value="cobertura">Solo cobertura</option>
                                        <option value="monto">Cobertura y monto</option>
                                        <option value="nada">Nada</option>
                                    </select>
                                    <div class="stickyHTitle">{{searchCobertura(cober.idCobertura)}}</div>
                                </td>
                                <td>
                                    <select class="form-select" v-model="cober.obligatorio">
                                        <option :value="0">Opcional</option>
                                        <option :value="1">Obligatorio</option>
                                    </select>
                                    <div class="stickyHTitle">{{searchCobertura(cober.idCobertura)}}</div>
                                </td>
                                <td>
                                    <select class="form-select" v-model="cober.moneda">
                                        <option value="Q">Q</option>
                                        <option value="USD">USD</option>
                                    </select>
                                    <div class="stickyHTitle">{{searchCobertura(cober.idCobertura)}}</div>
                                </td>
                                <td>
                                    <select class="form-select" v-model="cober.tipoNumeracion">
                                        <option value="GEN">General</option>
                                        <option value="ESP">Específico</option>
                                    </select>
                                    <div class="stickyHTitle">{{searchCobertura(cober.idCobertura)}}</div>
                                </td>
                                <td>
                                    <select class="form-select" v-model="cober.llevaValorVehiculo">
                                        <option :value="1">Sí</option>
                                        <option :value="0">No</option>
                                    </select>
                                    <div class="stickyHTitle">{{searchCobertura(cober.idCobertura)}}</div>
                                </td>
                                <td>
                                    <select class="form-select" v-model="cober.sumaAsegurada">
                                        <option :value="1">Sí</option>
                                        <option :value="0">No</option>
                                    </select>
                                    <div class="stickyHTitle">{{searchCobertura(cober.idCobertura)}}</div>
                                </td>
                                <td>
                                    <input class="form-control width-small" type="text" v-model="cober.montoPrima">
                                    <div class="stickyHTitle">{{searchCobertura(cober.idCobertura)}}</div>
                                </td>
                                <td>
                                    <input class="form-control width-medium" type="text" v-model="cober.montoDeduMin">
                                    <div class="stickyHTitle">{{searchCobertura(cober.idCobertura)}}</div>
                                </td>
                                <td>
                                    <input class="form-control width-small" type="text" v-model="cober.tasaDedu">
                                    <div class="stickyHTitle">{{searchCobertura(cober.idCobertura)}}</div>
                                </td>
                                <td>
                                    <input class="form-control width-medium" type="text" v-model="cober.montoSumaAsegurada">
                                    <div class="stickyHTitle">{{searchCobertura(cober.idCobertura)}}</div>
                                </td>
                                <!--<td>
                                    <vue3-tags-input :tags="cober.rango"
                                                     class="form-control p-0"
                                                     @on-tags-changed="addTag($event, keyCober)"/>
                                </td>-->
                                <td>
                                    <select class="form-select" v-model="cober.tipo">
                                        <option :value="'t'">% Tasa</option>
                                        <option :value="'m'">Monto</option>
                                        <option :value="'rt'">Rango tasa</option>
                                    </select>
                                    <div class="stickyHTitle">{{searchCobertura(cober.idCobertura)}}</div>
                                </td>
                                <td>
                                    <input class="form-control width-medium" type="text" v-model="cober.primaMinima">
                                    <div class="stickyHTitle">{{searchCobertura(cober.idCobertura)}}</div>
                                </td>
                                <td v-for="item in newTarifas" class="position-relative" style="padding-left: 20px">

                                    <div class="cursor-pointer small" style="position: absolute; left: 0; top: 15px" @click="() => {
                                        if (typeof cober.rangos[item.idTarifa] === 'undefined') {
                                            cober.rangos[item.idTarifa] = [];
                                        }
                                        cober.showDetails[item.idTarifa] = true;
                                    }"><i class="fas fa-pencil"></i></div>

                                    <div v-if="cober.showDetails[item.idTarifa]" @close="() => { cober.showDetails[item.idTarifa] = false }" class="globalModal">
                                        <div class="globalModalContainer position-relative">
                                            <div @click="cober.showDetails[item.idTarifa] = false" class="globalModalClose mt-3" style="position: absolute">
                                                <i class="fas fa-times-circle"></i></div>
                                            <div>
                                                <strong>Rangos</strong>
                                                <hr>
                                                <div v-for="(rangoTmp, keyRango) in cober.rangos[item.idTarifa]">
                                                    <div class="mt-4">
                                                        <h5>No. {{ keyRango + 1 }}</h5>
                                                        <div class="row">
                                                            <div class="col-12 col-sm-3">
                                                                <div>
                                                                    <label>Suma asegurada desde</label>
                                                                    <input type="text" class="form-control" v-model="rangoTmp.suma_asegurada_desde">
                                                                </div>
                                                            </div>
                                                            <div class="col-12 col-sm-3">
                                                                <div>
                                                                    <label>Suma asegurada hasta</label>
                                                                    <input type="text" class="form-control" v-model="rangoTmp.suma_asegurada_hasta">
                                                                </div>
                                                            </div>
                                                            <div class="col-12 col-sm-3">
                                                                <div>
                                                                    <label>Suma asegurada</label>
                                                                    <input type="text" class="form-control" v-model="rangoTmp.suma_asegurada">
                                                                </div>
                                                            </div>
                                                            <div class="col-12 col-sm-3">
                                                                <div>
                                                                    <label>Tasa</label>
                                                                    <input type="text" class="form-control" v-model="rangoTmp.tasa">
                                                                </div>
                                                            </div>
                                                            <div class="col-12 col-sm-3">
                                                                <div>
                                                                    <label>Prima</label>
                                                                    <input type="text" class="form-control" v-model="rangoTmp.prima">
                                                                </div>
                                                            </div>
                                                            <div class="col-12 col-sm-3">
                                                                <div>
                                                                    <label>Tasa deducible</label>
                                                                    <input type="text" class="form-control" v-model="rangoTmp.tasa_deducible">
                                                                </div>
                                                            </div>
                                                            <div class="col-12 col-sm-3">
                                                                <div>
                                                                    <label>Valor deducible</label>
                                                                    <input type="text" class="form-control" v-model="rangoTmp.valor_deducible">
                                                                </div>
                                                            </div>
                                                            <div class="col-12 col-sm-3">
                                                                <div>
                                                                    <label>Prima mínima</label>
                                                                    <input type="text" class="form-control" v-model="rangoTmp.prima_minima">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="text-end" @click="cober.rangos[item.idTarifa].splice(keyRango, 1);">
                                                            Eliminar rango
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mt-3 text-end">
                                                    <button @click="cober.rangos[item.idTarifa].push({
                                                           suma_asegurada_desde: '',
                                                           suma_asegurada_hasta: '',
                                                           suma_asegurada: '',
                                                           tasa: '',
                                                           prima: '',
                                                           tasa_deducible: '',
                                                           valor_deducible: '',
                                                           prima_minima: '',
                                                    })" class="btn btn-primary btn-sm">Agregar rango
                                                    </button>
                                                </div>
                                                <div class="mt-5 text-end">
                                                    <button @click="guardarDetalle" class="btn btn-success">Guardar detalle</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <vue3-tags-input :tags="newCoberturas[keyCober]['det'][item.id]['valor']"
                                                     v-if="!!cober?.det && !!cober.det[item.id]"
                                                     class="form-control p-0 width-x-large"
                                                     @on-tags-changed="addTagTarifaCobertura($event, keyCober, item.id )"
                                                     :limit="(cober['tipo'] === 't' ? 1 : 30)"
                                                     :read-only="!cober['tipo'] || cober['tipo'] === ''"
                                    />
                                    <div class="stickyHTitle">{{searchCobertura(cober.idCobertura)}}</div>
                                </td>
                            </tr>
                            </tbody>
                            <tr>
                                <td class="floatCol">
                                    <h6 class="text-success">
                                        <span class="cursor-pointer small" @click="newAddCobertura"><i class="fas fa-plus-circle ms-2"></i> Agregar cobertura</span>
                                    </h6>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="mt-3 text-end text-muted">
                        La configuración se guarda automáticamente al realizar cambios*
                    </div>
                    <div class="text-end mt-4">
                        <button class="btn btn-primary  me-4" @click="saveAS400">Enviar configuración para AS400</button>
                        <button class="btn btn-success" @click="saveAll">Guardar configuración</button>
                    </div>
                    <div>
                        <h5>Glosario de configuración</h5>
                        <div style="font-size: 0.8em">
                            <div>
                                Los condicionales permiten evaluar operaciones lógicas variar el comportamiento de una cobertura.
                                <br><br>
                                <div class="mb-2">
                                    <b>Columna valor tarifa:</b> Permite configurar los valores asociados a una tarifa, esta columna se comporta distinto en función del tipo de tarifa (tasa o monto).
                                    <div class="ps-3">
                                        <div>
                                            <b>En función de tasa:</b> Solo un único valor es permitido, este será %.
                                        </div>
                                        <div>
                                            <b>En función de monto:</b> Permite ingresar uno o múltiples valores.
                                            <div class="ps-3">
                                                <div>
                                                    <b class="text-secondary">Monto único:</b> En caso la configuración sea monto único, no se mostrará selector de opciones al cliente y dicho monto será predeterminado.
                                                </div>
                                                <div>
                                                    <b class="text-secondary">Varios montos:</b> En caso existan varios montos, se mostrará un selector al usuario, el cual podrá elegir el monto deseado. Los montos tienen la siguiente estructura: "100-50".
                                                    Siendo el primer número el monto a mostrar al cliente en cotizador, separado de un guión medio, el monto a cobrar por esa selección internamente.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <b>Columna condicionales:</b> Permite activar o desactivar la cobertura en función de su evaluación condicional.
                                </div>
                                <div>
                                    <b>Columna visibilidad condicional:</b> Permite mostrar u ocultar una cobertura en función de su evaluación condicional, si una cobertura se oculta, seguirá estando activa.
                                </div>
                                <div class="mt-3">
                                    <b class="text-muted">Operadores permitidos:</b>
                                    <table class="table table-striped mt-1">
                                        <thead>
                                        <tr>
                                            <th>
                                                Operador
                                            </th>
                                            <th>
                                                Descripción
                                            </th>
                                            <th>
                                                Ejemplo
                                            </th>
                                            <th style="min-width: 200px">
                                                Condicional
                                            </th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <td>
                                                &lbrace;{SA}}
                                            </td>
                                            <td>
                                                Representa la suma asegurada en el cotizador
                                            </td>
                                            <td>
                                                Si la suma asegurada es mayor que 0
                                            </td>
                                            <td>
                                                &lbrace;{SA}} &gt; 0
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                &lbrace;{TC}}
                                            </td>
                                            <td>
                                                Representa la tasa de una cobertura, si la cobertura no tiene tasa, este valor será cero.
                                            </td>
                                            <td>
                                                Si la multiplicación de la suma asegurada por la tasa de la cobertura es mayor a 10.
                                            </td>
                                            <td>
                                                &lbrace;{SA}} * &lbrace;{TC}} &gt; 10
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                &lbrace;{VC}}
                                            </td>
                                            <td>
                                                Representa el valor fijo de una cobertura, este puede ser una selección (en caso de varias opciones) o un monto fijo. Si la cobertura no tiene un monto, este valor será cero.
                                            </td>
                                            <td>
                                                Si el monto de cobertura más la suma asegurada es mayor a 50.
                                            </td>
                                            <td>
                                                &lbrace;{VC}} + &lbrace;{SA}} &gt; 50
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                &&
                                            </td>
                                            <td>
                                                "Y" ó "AND", permite evaluar una expresión lógica, si todas se cumplen, el resultado será verdadero.
                                            </td>
                                            <td>
                                                Si la suma asegurada es mayor que 0 Y es menor que 100.
                                            </td>
                                            <td>
                                                &lbrace;{SA}} &gt; 0 && &lbrace;{SA}} &lt; 100
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                ||
                                            </td>
                                            <td>
                                                "O" u "OR", permite evaluar una expresión lógica, si al menos una se cumplen, el resultado será verdadero.
                                            </td>
                                            <td>
                                                Si la suma asegurada es mayor que 100 Ó el valor de cobertura es menor que 200.
                                            </td>
                                            <td>
                                                &lbrace;{SA}} &gt; 100 || &lbrace;{VC}} &lt; 200
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h3>Validación AS400 (momentáneo)</h3>
                        <div v-for="item in as400Debug">
                            <textarea class="w-100" rows="10" v-model="item.dtainput"></textarea>
                        </div>
                        <div v-for="item in as400DebugLOG" class="mb-4">
                            {{item}}
                            <hr>
                        </div>
                    </div>
                    <div v-if="loadConfigModal" @close="() => { loadConfigModal = false }" class="globalModal">
                        <div class="globalModalContainer position-relative">
                            <div @click="loadConfigModal = false" class="globalModalClose mt-3" style="position: absolute">
                                <i class="fas fa-times-circle"></i></div>
                            <div>
                                <strong>Cargar configuración desde otro producto</strong>
                                <hr>
                                <div>
                                    <div class="row">
                                        <div class="col-12 col-sm-6 mb-3"  v-for="item in productos">
                                            <div class="productCloneItem">
                                                <div class="row">
                                                    <div class="col-9">
                                                        {{item.nombre}}
                                                    </div>
                                                    <div class="col-3 text-end">
                                                        <button class="btn btn-primary btn-sm" @click="cloneConfig(item.id)">Seleccionar</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

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
import Button from "@/views/forms/form_elements/FormElementButton.vue";
import Multiselect from '@vueform/multiselect'
import Vue3TagsInput from 'vue3-tags-input';
import Select from "@/views/forms/Select.vue";
import InputGroup from "@/views/forms/InputGroup.vue";
import {forEach} from "lodash";


export default {
    name: 'Tables',
    components: {InputGroup, Select, Button, Multiselect, Vue3TagsInput},
    data() {
        return {
            loadFull: {},
            newTarifas: [],
            newCoberturas: [],


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

            // pruebas
            as400Debug: [],
            as400DebugLOG: [],

            // cargar config
            loadConfigModal: false,
            productos: {},

        };
    },
    mounted() {
        this.productoId = (typeof this.$route.params.id !== 'undefined') ? parseInt(this.$route.params.id) : 0;
        //this.loadCatalogo();
        this.load();
    },
    methods: {
        load() {
            const self = this;
            toolbox.doAjax('POST', 'config/prod-tari-cober', {
                    productoId: self.productoId,
                },
                function (response) {

                    self.loadFull = response.data;
                    self.newTarifas = response.data.t;
                    self.newCoberturas = response.data.c;
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
        cloneConfig(productoID) {
            const self = this;

            toolbox.confirm('Se eliminará la configuración actual y será reemplazada por la nueva, ¿está seguro?', function () {
                toolbox.doAjax('POST', 'config/clone-prod-tari-cober', {
                        productoId: productoID,
                        productoIdNew: self.productoId,
                    },
                    function (response) {
                        toolbox.alert(response.msg, 'success');
                        self.loadConfigModal = false;
                        self.load();
                        /*const productoName = self.loadFull.p;
                        self.loadConfigModal = false;
                        self.loadFull = response.data;
                        self.newTarifas = response.data.t;
                        self.newCoberturas = response.data.c;
                        self.loadFull.p = productoName;*/
                    },
                    function (response) {
                        toolbox.alert(response.msg, 'danger');
                    })
            })
        },
        newAddTarifa() {
            const self = this;

            this.saveAll(function () {
                toolbox.doAjax('POST', 'config/add-prod-tari', {
                        productoId: self.productoId,
                    },
                    function (response) {
                        self.load();
                    },
                    function (response) {
                        toolbox.alert(response.msg, 'danger');
                    })
            })
        },
        newDeleteTarifa(tarifaId) {
            const self = this;

            this.saveAll(function () {
                toolbox.doAjax('POST', 'config/delete-prod-tari', {
                        productoId: self.productoId,
                        tarifaId: tarifaId,
                    },
                    function (response) {
                        self.load();
                    },
                    function (response) {
                        toolbox.alert(response.msg, 'danger');
                    })
            })

        },
        newAddCobertura() {
            const self = this;
            this.saveAll(function () {
                toolbox.doAjax('POST', 'config/add-prod-tari-cober', {
                        productoId: self.productoId,
                    },
                    function (response) {
                        self.load();
                    },
                    function (response) {
                        toolbox.alert(response.msg, 'danger');
                    })
            })

        },
        openDetails() {

        },
        deleteCobertura(coberturaId) {
            const self = this;
            toolbox.confirm('¿Está seguro de eliminar?', function () {

                this.saveAll(function () {
                    toolbox.doAjax('POST', 'config/delete-prod-tari-cober', {
                            productoId: self.productoId,
                            coberturaId: coberturaId,
                        },
                        function (response) {
                            toolbox.alert(response.msg, 'success');
                            self.load();
                        },
                        function (response) {
                            toolbox.alert(response.msg, 'danger');
                        })
                })
            })
        },
        saveAll(callback) {
            const self = this;
            toolbox.doAjax('POST', 'config/save-prod-tari-cober', {
                    productoId: self.productoId,
                    tarifas: self.newTarifas,
                    coberturas: self.newCoberturas,
                },
                function (response) {
                    toolbox.alert(response.msg, 'success');
                    self.as400Debug = response.data.xmlAS.dtainput || [];

                    if (typeof callback === 'function') {
                        callback();
                    }
                    else {
                        self.load();
                    }
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
        saveAS400() {
            const self = this;
            toolbox.doAjax('POST', 'config/save-prod-tari-cober', {
                    productoId: self.productoId,
                    tarifas: self.newTarifas,
                    coberturas: self.newCoberturas,
                    sendAS400: 1,
                },
                function (response) {
                    toolbox.alert(response.msg, 'success');
                    self.as400Debug = response.data.xmlAS || [];
                    self.as400DebugLOG = response.data.log || [];
                    self.load();
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
        addTag(newTag, key) {
            const self = this;
            self.newCoberturas[key]['rango'] = newTag;
        },

        addTagTarifaCobertura(newTag, key, id) {
            const self = this;
            const cober = self.newCoberturas[key];
            self.newCoberturas[key]['det'][id]['valor'] = cober['tipo'] === 't' ? newTag.filter(e => !Number.isNaN(Number(e))) : newTag;

            //this.saveAll();
        },
        guardarDetalle() {
            this.saveAll();
        },
        searchCobertura(idCobertura) {
            let coberturaName = '';
            this.loadFull.cat.cobertura.forEach(function (a,b) {
                if (parseInt(a.id) === parseInt(idCobertura)) {
                    coberturaName = a.nombre;
                }
            })
            return coberturaName;
        },

        loadCatalogoProductos() {

            const self = this;
            toolbox.doAjax('POST', 'admin/catalogo/load', {
                    slug: 'productos',
                    opt: 'get',
                },
                function (response) {
                    self.loadConfigModal = true;
                    self.productos = response.data;
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
    }
}
</script>
