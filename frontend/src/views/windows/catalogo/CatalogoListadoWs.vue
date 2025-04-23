<template>
    <CRow>
        <CCol :xs="12">
            <CCard class="mb-4">
                <CCardHeader>
                    <strong>Listado de catálogos</strong>
<!--                    <button @click="$router.push('/'+urlOpt+'/0')" class="btn btn-primary float-end"><i class="fas fa-plus me-2"></i>Crear nuevo</button>-->
                    <!--<button @click="sync" class="btn btn-primary float-end me-2"><i class="fas fa-sync me-2"></i>Sincronizar con AS400</button>-->
                </CCardHeader>
                <CCardBody>
                    <div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Buscar por</label>
                            <div class="row">
                                <div class="col-3">
                                    <select class="form-select" v-model="typeSearch">
                                        <option value="nombre">Nombre</option>
                                    </select>
                                </div>
                                <div class="col-3">
                                    <input type="text" v-model="searchValue" class="form-control" placeholder="Escribe aquí tu búsqueda">
                                </div>
                            </div>
                        </div>
                    </div>
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

export default {
    name: 'Tables',
    data() {
        return {
            urlOpt: 'admin/catalogo',
            logSync: '',
            typeSearch: 'nombre',
            searchValue: '',
            headers: [
                {text: "Nombre", value: "nombre"},
                {text: "Operación", value: "operation", width: 150 },
            ],
            items: [
                {
                    nombre: 'Marcas',
                    slug: 'marcas',
                },
                {
                    nombre: 'Líneas',
                    slug: 'lineas',
                },
                {
                    nombre: 'Líneas no asegurables',
                    slug: 'lineas_no_asegurables',
                },
                {
                    nombre: 'Tipo de línea',
                    slug: 'tipo_linea',
                },
                {
                    nombre: 'Tipo de placa',
                    slug: 'tipo_placa',
                },
                {
                    nombre: 'Tipo de vehículo',
                    slug: 'tipo_vehiculo',
                },
                {
                    nombre: 'Tipo de cuenta o tarjeta',
                    slug: 'tipo_cuenta_tarjeta',
                },
                {
                    nombre: 'Tipo de movimiento',
                    slug: 'tipo_movimiento',
                },
                {
                    nombre: 'Tipo de documentos',
                    slug: 'tipo_documento',
                },
                {
                    nombre: 'Tarifas',
                    slug: 'tarifas',
                },
                {
                    nombre: 'Tipo de tarifas',
                    slug: 'tipo_tarifas',
                },
                {
                    nombre: 'Código de agentes',
                    slug: 'codigo_agente',
                },
                {
                    nombre: 'Tarifas de agentes',
                    slug: 'agente_tarifas',
                },
                {
                    nombre: 'Productos',
                    slug: 'productos',
                },
                {
                    nombre: 'Tipo de productos',
                    slug: 'tipo_productos',
                },
                {
                    nombre: 'Beneficiarios',
                    slug: 'beneficiarios',
                },
                {
                    nombre: 'Formas de pago',
                    slug: 'formas_pago',
                },
                {
                    nombre: 'Estado civil',
                    slug: 'estado_civil',
                },
                {
                    nombre: 'Profesión',
                    slug: 'profesion',
                },
                {
                    nombre: 'Zona',
                    slug: 'zona',
                },
                {
                    nombre: 'Medio de cobro',
                    slug: 'medio_cobro',
                },
                {
                    nombre: 'Clase Tarjeta',
                    slug: 'clase_tarjeta',
                },
                {
                    nombre: 'Tipo Cuenta Bancaria',
                    slug: 'tipo_cuenta_bancaria',
                },
                {
                    nombre: 'Banco Emisor',
                    slug: 'banco_emisor',
                },
                {
                    nombre: 'Tipo Licencia',
                    slug: 'tipo_licencia',
                },
                {
                    nombre: 'Sexo',
                    slug: 'sexo',
                },
                {
                    nombre: 'Zona Emisión',
                    slug: 'zona_emision',
                },
                {
                    nombre: 'Nacionalidad',
                    slug: 'nacionalidad',
                },
                {
                    nombre: 'Tipo Cliente',
                    slug: 'tipo_cliente',
                },
                {
                    nombre: 'Tipo Sociedad',
                    slug: 'tipo_sociedad',
                },
                {
                    nombre: 'Actividad Economica',
                    slug: 'actividad_economica',
                },
                {
                    nombre: 'Tipo uso',
                    slug: 'tipo_uso',
                },
                {
                    nombre: 'Tipo combustible',
                    slug: 'tipo_combustible',
                },
                {
                    nombre: 'Tipo Tecnologia',
                    slug: 'tipo_tecnologia',
                },
                {
                    nombre: 'Tipo cartera',
                    slug: 'tipo_cartera',
                },
                {
                    nombre: 'Subtipo movimiento',
                    slug: 'subtipo_movimiento',
                },
                {
                    nombre: 'Departamento',
                    slug: 'departamento',
                },
                {
                    nombre: 'Municipio',
                    slug: 'municipio',
                },
                {
                    nombre: 'Codigo Alarma',
                    slug: 'codigo_alarma',
                },
                {
                    nombre: 'Promociones',
                    slug: 'promociones',
                },
                {
                    nombre: 'Selección',
                    slug: 'seleccion',
                },
                {
                    nombre: 'Tipo Asignación',
                    slug: 'tipo_asignacion',
                },
                {
                    nombre: 'Tipo Usuario',
                    slug: 'tipo_usuario',
                },
                {
                    nombre: 'Coberturas',
                    slug: 'coberturas',
                },
                {
                    nombre: 'Línea por intermediario',
                    slug: 'linea_por_intermediario',
                },
            ]
        };
    },
    mounted() {
        this.getItems();
    },
    methods: {
        getItems() {

            const self = this;
            /*toolbox.doAjax('GET', 'admin/marcas/list', {},
                function (response) {
                    //self.items = response.data;
                    self.items = toolbox.prepareForTable(response.data);
                    //console.log(self.items);
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })*/
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
            this.$router.push('/' + this.urlOpt + '/' + item.slug);
        },
        /*sync() {
            const self = this;
            toolbox.confirm('Se realizará una sincronización con AS400, esto puede demorar unos minutos, ¿desea continuar?', function () {
                toolbox.doAjax('POST', 'admin/marcas/sync', {},
                    function (response) {
                        toolbox.alert(response.msg, 'success');
                        self.logSync = response.data;
                        self.getItems();
                    },
                    function (response) {
                        toolbox.alert(response.msg, 'danger');
                    })
            })
        },*/
    }
}
</script>
