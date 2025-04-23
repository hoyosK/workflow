<template>
    <CRow>
        <CCol :xs="12">
            <CCard class="mb-4">
                <CCardHeader>
                    <strong>Listado de aplicaciones</strong>
                    <button @click="$router.push('/apps/edit/0')" class="btn btn-primary float-end"><i class="fa fa-plus me-2"></i> Crear nueva aplicación</button>
                </CCardHeader>
                <CCardBody>
                    <EasyDataTable :headers="headers" :items="items" alternating >
                        <template #item-operation="item">
                            <div>
                                <i class="fas fa-pencil icon me-3" @click="editItem(item)"></i>
                                <i class="fas fa-trash icon" @click="deleteItem(item)"></i>
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
            headers: [
                {text: "Nombre de aplicación", value: "nombre"},
                {text: "Url al iniciar sesión", value: "urlLogin"},
                {text: "Operación", value: "operation",  width: 150},
            ],
            items: []
        };
    },
    mounted() {
        this.getItems();
    },
    methods: {
        getItems() {

            const self = this;
            toolbox.doAjax('GET', 'apps/list', {},
                function (response) {
                    //self.items = response.data;
                    self.items = toolbox.prepareForTable(response.data);
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
        deleteItem(item) {
            const self = this;
            toolbox.confirm('¿Está seguro de eliminar?', function () {
                toolbox.doAjax('POST', 'apps/delete', {
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
            this.$router.push('/apps/edit/' + item.id);
        }
    }
}
</script>
