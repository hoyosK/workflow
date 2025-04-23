<template>
    <CRow>
        <CCol :xs="12">
            <CCard class="mb-4">
                <CCardHeader>
                    <strong>Edición de recargo: {{ loadFull.p }}</strong>
                </CCardHeader>
                <CCardBody>
                    <div style="overflow: auto; width: 100%; padding-bottom: 2em">
                        <table class="ptcConfigTableTB table table-striped" style="width: max-content">
                            <tr class="fw-bold rowTitle">
                                <td>
                                </td>
                                <td>
                                    N.
                                </td>
                                <td>
                                    Desde (%)
                                </td>
                                <td>
                                    Hasta (%)
                                </td>
                                <td>
                                    Recargo (%)
                                </td>
                                <td>
                                    Renovar
                                </td>
                            </tr>
                            <tbody>
                            <tr class="ptcConfigTableTBRow" v-for="(cober, keyCober) in newRecargas">
                                <td>
                                    <div class="cursor-pointer small text-danger" @click="deleteRecarga(keyCober)"><i class="fas fa-minus-circle"></i></div>
                                </td>
                                <td>
                                    <div>{{ keyCober + 1 }}</div>
                                </td>
                                <td>
                                    <input class="form-control" type="number" v-model="cober.valormin">
                                </td>
                                <td>
                                    <input class="form-control" type="number" v-model="cober.valormax">
                                </td>
                                <td>
                                    <input class="form-control" type="number" v-model="cober.recargo">
                                </td>
                                <td>
                                    <select class="form-select" v-model="cober.renovar">
                                        <option :value="1">Sí</option>
                                        <option :value="0">No</option>
                                    </select>
                                </td>
                            </tr>
                            </tbody>
                            <tr>
                                <td class="floatCol">
                                    <h6 class="text-success">
                                        <span class="cursor-pointer small" @click="newAddRecarga"><i class="fas fa-plus-circle ms-2"></i> Agregar recargo</span>
                                    </h6>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="text-end mb-2 mt-5">
                        <CButton @click="saveAll" class="btn btn-primary">
                            Guardar
                        </CButton>
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


export default {
    name: 'Tables',
    components: {InputGroup, Select, Button, Multiselect, Vue3TagsInput},
    data()
    {
        return {
            loadFull: {},
            newRecargas: [],

            coberturas: {},
            coberturaSelected: false,
            coberturasOptions: [],
            coberturaEditing: {},

            gruposSup: [],
            gruposOptions: [],
            rolesOptions: [],
            usuariosOptions: [],
            canalesOptions: [],

            productoCoberturas: [],

            // visualización
            lockSave: false,
            showEditTarifa: false,
            showEditCobertura: false,
        };
    },
    mounted()
    {
        this.load();
    },
    methods: {
        load()
        {
            const self = this;
           toolbox.doAjax('GET', 'recargas/siniestralidad/listado', {
                },
                function (response)
                {
                    self.newRecargas = response.data;
                },
                function (response)
                {
                    toolbox.alert(response.msg, 'danger');
                })
        },
        newAddRecarga()
        {
            const self = this;
            const recarga = {id: 0, valormin: 0, valormax: 0, recargo: 0, renovar: 1};
            self.newRecargas.push({...recarga});
        },
        deleteRecarga(index)
        {
            const self = this;
            toolbox.confirm('¿Está seguro de eliminar?', function () {
                if (index > -1 && index < self.newRecargas.length) {
                    self.newRecargas.splice(index, 1);
                }
            })
        },
        saveAll()
        {
            const self = this;
            toolbox.doAjax('POST', 'recargas/siniestralidad/save', {
                    recargas: self.newRecargas,
                },
                function (response)
                {
                    toolbox.alert(response.msg, 'success');
                    self.newRecargas = response.data;
                },
                function (response)
                {
                    toolbox.alert(response.msg, 'danger');
                })
        }
    }
}
</script>
