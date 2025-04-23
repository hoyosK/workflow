<template>
    <CCard class="mb-4">
        <CCardHeader>
            <h1 class="float-start">Listado de páginas de ayuda</h1>
            <a class="btn btn-primary float-end mt-2" @click="$router.push('/admin/ayuda/editar/0')"><i class="fas fa-plus me-2"></i>Agregar nueva nota</a>
        </CCardHeader>
        <CCardBody>
            <div v-for="noticia in items">
                <!-- Post preview-->
                <div class="post-preview">
                    <div>
                        <h3 class="post-title">{{noticia.nombre}}</h3>
                        <h6 class="post-subtitle">{{noticia.extracto}}</h6>
                    </div>
                    <p class="post-meta">
                        Creado por:
                        <i>{{noticia.author_name}}</i>
                        el {{noticia.dateCreated}}
                    </p>
                    <button v-if="typeof authInfo.m['paginas/admin/ayuda'] !== 'undefined' && authInfo.m['paginas/admin/ayuda']" class="btn btn-outline-primary m-1" @click="$router.push('/admin/ayuda/editar/'+noticia.id)"><i class="fa fa-edit"></i></button>
                    <button v-if="typeof authInfo.m['paginas/admin/ayuda'] !== 'undefined' && authInfo.m['paginas/admin/ayuda']" class="btn btn-outline-danger m-1" @click="deleteItem(noticia)"><i class="fa fa-trash"></i></button>
                    <a class="btn btn-outline-info m-1" target="_blank" :href="'#/ayuda/' + noticia.slug"><i class="fa fa-eye"></i></a>
                    <hr class="my-4" />
                </div>
            </div>
        </CCardBody>
    </CCard>
</template>

<script>
import toolbox from "@/toolbox";
import './css/customBlog.scss';
import {mapGetters} from "vuex";

export default {
    name: 'Tables',
    data() {
        return {
            headers: [
                {text: "Nombre", value: "nombreProducto"},
                {text: "Operación", value: "operation",  width: 150},
            ],
            items: [],
            cliente:{},
            noticia:{
                titulo: '',
                subtitulo: '',
                autor: '',
                fecha: '',
                contenido: '',
            }
        };
    },
    computed: {
        ...mapGetters({
            authLogged: 'authLogged',
            authInfo: 'authInfo',
        }),

    },
    mounted() {
        this.getItems();
    },
    methods: {
        getItems() {

            const self = this;
            toolbox.doAjax('GET', 'paginas-ayuda/post/all', {},
                function (response) {
                    //self.items = response.data;
                    console.log(response.data)
                    self.items = response.data;
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
        deleteItem(item) {
            const self = this;
            toolbox.confirm('¿Está seguro de eliminar?', function () {
                toolbox.doAjax('DELETE', 'paginas-ayuda/borrar/'+ item.id, {},
                    function (response) {
                        toolbox.alert(response.msg, 'success');
                        self.getItems();
                    },
                    function (response) {
                        toolbox.alert(response.msg, 'danger');
                    })
            })
        }
    }
}
</script>
