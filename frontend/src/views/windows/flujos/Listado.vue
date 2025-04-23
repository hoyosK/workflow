<template>
    <CRow>
        <CCol :xs="12">
            <CCard class="mb-4">
                <CCardHeader>
                    <strong>Listado de flujos</strong>
                    <button @click="$router.push('/admin/flujo/edit/0')" class="btn btn-primary float-end"><i class="fa fa-plus me-2"></i> Crear nuevo flujo</button>
                </CCardHeader>
                <CCardBody>
                    <EasyDataTable :headers="headers" :items="items" alternating >
                        <template #item-operation="item">
                            <div>
                                <i class="fa fa-clone icon me-3" @click="copyItem(item)"></i>
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
                {text: "Nombre", value: "nombreProducto"},
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
            toolbox.doAjax('POST', 'productos/get', {
                    rc: true
                },
                function (response) {
                    //self.items = response.data;
                    console.log(response.data)
                    self.items = toolbox.prepareForTable(response.data);
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
        deleteItem(item) {
            const self = this;
            toolbox.confirm('¿Está seguro de eliminar?', function () {
                toolbox.doAjax('POST', 'productos/delete', {
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
            this.$router.push('/admin/flujo/edit/' + item.id);
        },
        copyItem(item) {
            const self = this;
            toolbox.confirm('¿Está seguro de generar una copia de este flujo?', function () {
                toolbox.doAjax('POST', 'productos/copy', {
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
    }
}
</script>
