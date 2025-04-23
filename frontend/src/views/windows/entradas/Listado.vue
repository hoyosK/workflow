<template>
    <div>
        <div class="mb-3">
            <div class="d-flex justify-content-end align-items-center">
                <div class="p-1">
                    <input v-model="searchTerm" @keyup="currentPage = 1; loadData()" class="form-control" type="text"
                           placeholder="Buscar">
                </div>
                <div >
                    <button class="add-new btn btn-primary mx-1"
                            data-bs-toggle="modal"
                            data-bs-target="#modals-slide-in">
                        <i class="fa fa-plus-circle"></i> Agregar nuevo
                    </button>

                    <button class="btn btn-primary" @click="exportToExcel" :disabled="loading">
                            <span v-if="loading">
                                <i class="fas fa-spinner fa-spin"></i>...
                            </span>
                        <span v-else>
                                <i class="fa fa-file-excel"></i>
                            </span>
                    </button>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-4 col-sm-12" v-for="row in rows" :key="row.token">
                <div class="card-header"><h5 class="card-title">{{ row.nombres }}</h5></div>
                <div class="card-body">

                    <p class="card-text"><strong>Canal:</strong> {{ row.canal }}</p>
                    <p class="card-text"><strong>Correo:</strong> {{ row.correo }}</p>
                    <p class="card-text"><strong>Contacto:</strong> {{ row.contacto }}</p>
                    <p class="card-text"><strong>NIT:</strong> {{ row.nit }}</p>
                    <p class="card-text"><strong>Identificación:</strong> {{ row.identificacion }}</p>
                    <p class="card-text"><strong>Fecha actualización:</strong> {{ row.dateUpdated }}</p>
                    <p class="card-text"><strong>Estado General:</strong> {{ row.estadoGeneral }}</p>

                </div>
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <div>
                        <a class="btn btn-outline-primary me-2" :href="'/formularios?cliente=' + row.token">
                            <i class="fa fa-pencil"></i>
                        </a>
                        <div v-if="row.rol === 'Super Administrador'" class="btn btn-outline-danger me-2" @click="deleteCliente(row.id)">
                            <i class="fa fa-trash"></i>
                        </div>
                        <a class="btn btn-outline-info" :href="'/timeline?cliente=' + row.token">
                            <i class="fa fa-link"></i>
                        </a>
                    </div>
                </div>

            </div>

        </div>
        <nav v-if="totalPages > 1">
            <ul class="pagination">
                <li class="page-item" :class="{ disabled: currentPage === 1 }">
                    <a class="page-link" href="#" @click="changePage(1)">&lt;</a>
                </li>
                <li class="page-item" :class="{ active: page === currentPage }" v-for="page in visiblePages"
                    :key="page">
                    <a class="page-link" href="#" @click="changePage(page)">{{ page }}</a>
                </li>
                <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                    <a class="page-link" href="#" @click="changePage(totalPages)">&gt;</a>
                </li>
            </ul>
        </nav>
        <div class="modal modal-slide-in new-user-modal fade" id="modals-slide-in">
            <div class="modal-dialog">
                <form class="add-new-user modal-content pt-0" id="newChannelForm">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">×</button>
                    <div class="modal-header mb-1">
                        <h5 class="modal-title" id="exampleModalLabel">Agregar nuevo</h5>
                    </div>
                    <div class="modal-body flex-grow-1">

                        <div class="mb-1">
                            <label class="form-label" for="basic-icon-default-correo">Correo</label>
                            <input class="form-control dt-full-name" id="basic-icon-default-correo" placeholder="Ingrese el correo" name="correo"/>
                        </div>
                        <div class="col-md-12">
                            <label class="custom-option-item p-1" for="Express">
                                        <span class="d-flex justify-content-between flex-wrap mb-50">
                                            <span class="fw-bolder">Seleccione un canal</span>
                                        </span>
                            </label>
                            <select class="form-control" v-model="canal" @change="loadSegmentos" name="canal">
                                <option v-for="(channel, index) in channelList" :value="channel.canales.id" :key="index">{{channel.canales.nombre}}</option>
                            </select>

                        </div>
                        <div class="divider"  v-if="segmentos.length">
                            <div class="divider-text">Seleccione un segmento:
                                <a  ref="linkDescarga" style="display: none;"></a>
                            </div>
                        </div>
                        <div class="row custom-options-checkable g-1">
                            <div class="col-md-6" v-for="(segmento, index) in segmentos" :key="index">
                                <input class="custom-option-item-check" type="radio" name="segmento" v-model="segSelected" :id="segmento.id" :value="segmento.id" >
                                <label class="custom-option-item p-1" :for="segmento.id">
                            <span class="d-flex justify-content-between flex-wrap mb-50">
                                <span class="fw-bolder">{{segmento.nombre}}</span>
                            </span>
                                </label>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button v-if="canal" type="button" id="newChannel" @click="newChannel" class="btn btn-primary me-1 data-submit" data-bs-dismiss="modal">Generar Link</button>
                        <button type="reset" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>

        </div>
    </div>

</template>


<script>
import toolbox from "@/toolbox";

export default {
    data() {
        return {
            rows: [], // Array para almacenar los datos
            currentPage: 1, // Página actual
            totalPages: 1, // Total de páginas
            visiblePages: [], // Páginas visibles en la paginación
            searchTerm: '', // Búsqueda por cualquiera de los parametros
            channelList: [],
            segmentos: [],
            canal: 0,
            segSelected: 0,
            msg: '',
            downloadExcel: false,
            loading: false,
            productoId: 0
        };
    },
    computed: {
        visiblePages() {
            const range = 3; // Cantidad de páginas visibles a cada lado de la página actual
            const ellipsisThreshold = range + 2; // Umbral para mostrar los puntos suspensivos

            // Lógica para generar el rango de páginas visibles
            let start = Math.max(1, this.currentPage - range);
            let end = Math.min(this.totalPages, this.currentPage + range);

            // Lógica para mostrar los puntos suspensivos
            let showStartEllipsis = false;
            let showEndEllipsis = false;

            if (start > ellipsisThreshold) {
                start++;
                showStartEllipsis = true;
            }

            if (end < this.totalPages - ellipsisThreshold + 1) {
                end--;
                showEndEllipsis = true;
            }

            // Generar el arreglo de páginas visibles
            const visiblePages = [];

            if (showStartEllipsis) {
                visiblePages.push(1);
                visiblePages.push("...");
            }

            for (let page = start; page <= end; page++) {
                visiblePages.push(page);
            }

            if (showEndEllipsis) {
                visiblePages.push("...");
                visiblePages.push(this.totalPages);
            }

            return visiblePages;
        },
    },
    mounted() {
        this.loadData(); // Cargar datos iniciales
        this.loadAccess();
    },
    methods: {
        newChannel() {
            const self = this;
            const formData = $('#newChannelForm').serializeArray();
            const jsonData = formData.reduce((acc, field) => {
                acc[field.name] = field.value;
                return acc;
            }, {});

            toolbox.doAjax('POST', 'clients/new', jsonData, function (response) {
                // console.log(response);
                self.searchTerm = jsonData.correo;
                self.loadData();
            }, function (response) {
                alert(response.msg);
            });
        },
        loadAccess() {

            const self = this;

            toolbox.doAjax('GET', 'channels/user', {}, function (response) {
                // console.log(response);
                self.channelList = response.data;
            }, function (response) {
                alert(response.msg);
            });
        },
        loadSegmentos(event) {
            const selectedChannel = this.channelList.find(channel => channel.canales.id === this.canal);
            if (selectedChannel) {
                // Aquí puedes cargar los segmentos del canal seleccionado
                //Reiniciar la variable por cualquier cosa.
                this.segSelected = 0;
                this.segmentos = selectedChannel.segmentos;
            }
            else {
                // Manejar el caso en que no se encuentra el canal seleccionado
                this.segmentos = [];
                this.segSelected = 0;
            }
        },
        loadData() {
            toolbox.doAjax('GET', 'clients/all?searchTerm=' + this.searchTerm + '&currentPage=' + this.currentPage, {}, response => {
                this.totalPages = response.data.totalPages;
                this.rows = response.data.clientes.map(row => ({
                    canal: row.canal,
                    nombres: row.nombres,
                    correo: row.correo,
                    contacto: row.contacto,
                    nit: row.nit,
                    identificacion: row.identificacion,
                    token: row.token,
                    estadoGeneral: row.estadoGeneral,
                    rol: row.rolName,
                    id: row.id,
                }));
            }, response => {
                alert(response.msg);
            });
        },
        deleteCliente(idCliente) {
            const confirmDelete = window.confirm("¿Estás seguro de que deseas eliminar este cliente?");

            if (confirmDelete) {
                toolbox.doAjax('DELETE', 'clients/delete?clienteId=' + idCliente, {}, response => {
                    this.loadData();
                }, response => {
                    alert(response.msg);
                });
            }
        },

        addNew() {
            // Lógica para agregar nuevo elemento
        },
        exportToExcel : function () {
            const self = this;
            self.loading = true;

            toolbox.doAjax('GET', 'clients/all?downloadExcel=' + true, {}, response => {
                if (response.status) {
                    self.loading = false;
                    // Convertir el archivo base64 a un objeto Blob
                    const byteCharacters = atob(response.data.excel);
                    const byteNumbers = new Array(byteCharacters.length);
                    for (let i = 0; i < byteCharacters.length; i++) {
                        byteNumbers[i] = byteCharacters.charCodeAt(i);
                    }
                    const byteArray = new Uint8Array(byteNumbers);
                    const blob = new Blob([byteArray], {type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'});

                    // Descargar el archivo
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = 'archivo.xlsx';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);

                }
                else {
                    self.loading = false;
                    self.msg = response.msg;
                }
            }, function (response) {
                self.loading = false;
                self.msg = response.msg;
            })
        },
        changePage(page) {
            // Verificar si el valor de page es igual a "..."
            if (page === "...") {
                return; // No realizar ningún cambio
            }

            // Cambiar la página actual y cargar los datos correspondientes
            this.currentPage = page;
            this.loadData();
        },

    },
};
</script>
