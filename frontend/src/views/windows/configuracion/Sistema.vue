<template>
    <CRow>
        <CCol :xs="12">
            <CCard class="mb-4">
                <CCardHeader>
                    <strong>Control de sistema</strong>
                    <div class="mt-3 text-muted">
                        Atención, las configuraciones realizadas en esta ventana pueden comprometer la estabilidad del sistema
                    </div>
                </CCardHeader>
                <CCardBody>
                    <div class="mt-4">
                        <h5>Caché</h5>
                        <div>
                            El sistema de caché evita el reproceso y cálculo de datos, funciona en catálogos de sistema, configuración de tarifas, etc.
                        </div>
                        <div class="mt-3">
                            <button class="btn btn-primary" @click="clearCache">Borrar caché</button>
                        </div>
                    </div>
                    <!--<div>
                        <div class="mt-4 text-end">
                            <button @click="$router.push('/usuarios/listado')" class="btn btn-danger me-4">Cancelar</button>
                            <button @click="guardar" class="btn btn-primary">Guardar</button>
                        </div>
                    </div>-->
                </CCardBody>
            </CCard>
        </CCol>
    </CRow>
</template>

<script>
import toolbox from "@/toolbox";
import Select from "@/views/forms/Select.vue";
import Button from "@/views/forms/form_elements/FormElementButton.vue";

export default {
    name: 'Tables',
    components: {Button, Select},
    data() {
        return {

        };
    },
    mounted() {

    },
    methods: {
        clearCache() {

            const self = this;

            toolbox.confirm('Se limpiará todo el caché guardado, esto puede impactar la velocidad del sitio durante unos minutos, ¿desea continuar?', function () {
                toolbox.doAjax('POST', 'config/system/cache-clear', {
                        vars: self.variables
                    },
                    function (response) {
                        toolbox.alert(response.msg, 'success');
                        self.getData();
                    },
                    function (response) {
                        toolbox.alert(response.msg, 'danger');
                    })
            })
        },
    }
}
</script>
