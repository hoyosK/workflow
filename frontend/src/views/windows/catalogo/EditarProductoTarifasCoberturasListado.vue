<template>
    <CRow>
        <CCol :xs="12">
            <CCard class="mb-4">
                <CCardHeader>
                    <strong>Edición de tarifas y coberturas</strong>
                </CCardHeader>
                <CCardBody>
                    <div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Buscar por</label>
                            <div class="row">
                                <div class="col-3">
                                    <select class="form-select" v-model="typeSearch">
                                        <option value="nombre">Nombre</option>
                                        <option value="codigoProducto">Código</option>
                                        <option value="descripcion">Descripción</option>
                                    </select>
                                </div>
                                <div class="col-3">
                                    <input type="text" v-model="searchValue" class="form-control" placeholder="Escribe aquí tu búsqueda">
                                </div>
                            </div>
                        </div>
                    </div>
                    <h6>Seleccione un producto</h6>
                    <EasyDataTable :headers="headers" :items="items" :search-field="typeSearch" :search-value="searchValue" alternating >
                        <template #item-operation="item">
                            <div class="text-center">
                                <i class="fas fa-pencil icon me-3" @click="editItem(item)"></i>
<!--                                <i class="fas fa-trash icon" @click="deleteItem(item)"></i>-->
                            </div>
                        </template>
                    </EasyDataTable>
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


export default {
    name: 'Tables',
    components: {Button, Multiselect, Vue3TagsInput},
    data() {
        return {
            urlOpt: 'admin/catalogo',
            showConfig: false,
            idRow: 0,
            slugCatalogo: '',
            nombreCatalogo: '',
            typeSearch: 'nombre',
            searchValue: '',
            headers: [
                {text: "Nombre", value: "nombre"},
                {text: "Operación", value: "operation", width: 150 },
            ],
            items: [],
        };
    },
    mounted() {
        this.slugCatalogo = 'productos';
        this.loadCatalogo();
    },
    methods: {
        loadCatalogo() {

            this.nombreCatalogo = 'Productos';
            this.headers = [
                {text: "Código AS400", value: "codigoProducto"},
                {text: "Nombre", value: "nombre"},
                {text: "Moneda", value: "idMoneda"},
                {text: "Descripción", value: "descripcion"},
                {text: "Estado", value: "estado"},
                {text: "Rango desde", value: "rangoPolizaDesde"},
                {text: "Rango hasta", value: "rangoPolizaHasta"},
                {text: "Operación", value: "operation", width: 150 },
            ];

            const self = this;
            toolbox.doAjax('POST', 'admin/catalogo/load', {
                    slug: self.slugCatalogo,
                    opt: 'get',
                },
                function (response) {
                    //self.items = response.data;
                    self.items = toolbox.prepareForTable(response.data);
                    //console.log(self.items);
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
        deleteItem(item) {
            const self = this;
            toolbox.confirm('Se desactivará este usuario, ¿desea continuar?', function () {
                toolbox.doAjax('POST', 'admin/canales/delete', {
                        id: item.id,
                    },
                    function (response) {
                        toolbox.alert(response.msg, 'success');
                        self.getItems();
                    },
                    function (response) {
                        toolbox.alert(response.msg, 'danger');
                    })
            })
        },
        editItem(item) {
            this.$router.push('/admin/tarifas/coberturas/' + item.id);
        },
    }
}
</script>
