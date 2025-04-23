<template>
    <CRow>
        <CCol :xs="12">
            <CCard class="mb-4">
                <CCardHeader>
                    <strong>Listado de usuarios</strong>
                    <button @click="$router.push('/usuarios/new')" class="btn btn-primary float-end">Crear nuevo</button>
                    <button @click="userSync" class="btn btn-primary float-end me-2"><i class="fas fa-sync me-2"></i>Sincronizar usuarios</button>
                </CCardHeader>
                <CCardBody>
                    <div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Buscar por</label>
                            <div class="row">
                                <div class="col-3">
                                    <select class="form-select" v-model="typeSearch">
                                        <option value="email">Correo electrónico</option>
                                        <option value="name">Nombre</option>
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
import {config} from "@/config";

export default {
    name: 'Tables',
    data() {
        return {
            typeSearch: 'email',
            searchValue: '',
            headers: [
                {text: "Nombre de usuario", value: "nombreUsuario"},
                {text: "Correo electrónico", value: "email"},
                {text: "Nombre", value: "name"},
                {text: "Corporativo", value: "corporativo"},
                {text: "Estado", value: "estado"},
                {text: "Rol", value: "rolUsuario"},
                {text: "Operación", value: "operation"},
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
            toolbox.doAjax('GET', 'users/list', {},
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
                toolbox.doAjax('POST', 'users/user/delete', {
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
            this.$router.push('/usuarios/edit/' + item.id);
        },
        userSync() {
            const self = this;
            toolbox.doAjax('POST', 'users/sync', {},
                function (response) {
                    toolbox.alert(response.msg, 'success');
                    self.getItems();
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
        goToSso() {
            const self = this;
            window.open(config.ssoUrl + '/#/usuarios/edit/0');
        },
    }
}
</script>
