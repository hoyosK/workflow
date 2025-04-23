<template>
    <CRow>
        <CCol :xs="12">
            <CCard class="mb-4">
                <CCardHeader>
                    <strong>Editar variables de sistema</strong>
                </CCardHeader>
                <CCardBody>
                    <div class="mt-4">
                        <h4>Variables de sistema</h4>
                        <div class="my-4 text-muted">
                            Atención, las variables de usuario no podrán utilizarse en formularios de visibilidad pública
                        </div>
                        <div>
                            <div class="mb-3">
                                <div class="row fw-bold mb-3">
                                    <div class="col-12 col-sm-6">
                                        Nombre
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        Valor
                                    </div>
                                </div>
                                <div v-for="(item, key) in variables">
                                    <div class="row mb-3">
                                        <div class="col-12 col-sm-4">
                                            <input class="form-control" v-model="item.slug"/>
                                            <div v-if="typeof variablesDefault[item.slug] !== 'undefined'" class="mt-2 text-muted">
                                                {{variablesDefault[item.slug]}}
                                            </div>
                                        </div>
                                        <div class="col-12 col-sm-7">
                                            <textarea class="form-control" v-model="item.contenido"></textarea>
                                        </div>
                                        <div class="col-12 col-sm-1 text-end" v-if="typeof variablesDefault[item.slug] === 'undefined'">
                                            <a @click="eliminarVariable(key)" class="btn btn-danger w-100"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <a @click="agregarVariable" class="btn btn-primary btn-sm"><i class="fas fa-plus me-2"></i> Agregar variable</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="mt-4 text-end">
                            <button @click="$router.push('/usuarios/listado')" class="btn btn-danger me-4">Cancelar</button>
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

            variablesDefault: {
                'INSPECCIONES_TIEMPO_RESERVA': 'Valor en formato de tiempo 00:00:00',
                'INSPECCIONES_TIEMPO_TRASLADO': 'Valor en formato de tiempo 00:00:00',
                'SUMA_ASEGURADA_MAXIMA': 'Número entero, 0 para ilimitado',
                'CANTIDAD_POLIZAS_MAXIMA_CLIENTE': 'Número entero, 0 para ilimitado',
                'ANTIGUEDAD_MINIMA': '(Años) número entero, 0 para ilimitado',
                'ANTIGUEDAD_MAXIMA': '(Años) número entero, 0 para ilimitado',
                'AS400_DESACTIVAR_EMISIONES': 'Número entero, 0 y 1 para ilimitado',
                'ERROR_COTIZACION_NO_SELECCIONADA': 'Número entero, 0 y 1 para ilimitado',
                'COTIZACION_VALOR_PROMEDIO_MSG': 'Número entero',
                'COTIZACION_VALOR_GARANTIZADO_MSG': 'Número entero',
                'COTIZACION_VALOR_EXCEDIDO_1_MSG': 'Texto',
                'COTIZACION_VALOR_EXCEDIDO_2_MSG': 'Texto',
                'SUMA_ASEGURADA_MAXIMA_1': 'Número decimal o entero',
                'SUMA_ASEGURADA_MAXIMA_2': 'Número decimal o entero',
                'REENVIO_ADJUNTOS_ASUNTO': 'Texto',
                'REENVIO_ADJUNTOS_CONTENIDO': 'Texto',
                'FLUJOS_SOPORTE': 'Texto',
            },

            // variables de usuario
            variables: [],
        };
    },
    mounted() {
        //this.id = (typeof this.$route.params.id !== 'undefined') ? parseInt(this.$route.params.id) : 0;
        //console.log(this.id);

        this.getData();
    },
    methods: {
        getData() {
            const self = this;

            toolbox.doAjax('POST', 'config/vars/get', {},
                function (response) {
                    self.variables = response.data || [];
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
        guardar() {

            const self = this;
            toolbox.doAjax('POST', 'config/vars/save', {
                    vars: self.variables
                },
                function (response) {
                    toolbox.alert(response.msg, 'success');
                    self.getData();
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
        agregarVariable() {
            this.variables.push({
                nombre: '',
                valor: '',
            })
        },
        eliminarVariable(key) {
            this.variables.splice(key, 1);
        },
    }
}
</script>
