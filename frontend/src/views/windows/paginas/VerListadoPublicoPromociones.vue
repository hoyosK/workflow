<template>

    <header class="masthead" style="background-image: url('https://bucket-elroble.s3.amazonaws.com/wp-content/uploads/2020/01/06113748/Banner-web-Roble-RobleMed_VF-03.jpg')">
        <div class="container position-relative px-4 px-lg-5">
            <div class="row gx-4 gx-lg-5 justify-content-center">
                <div class="col-md-10 col-lg-8 col-xl-7">
                    <div class="site-heading">
                        <h1>Centro de promociones</h1>
                        <span class="subheading">Seguros el Roble</span>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <!-- Main Content-->
    <div class="container px-4 px-lg-5">
        <div class="card">
            <div class="card-body">
                <div class="row gx-4 gx-lg-5 justify-content-center">
                    <div class="col-md-10 col-lg-10 col-xl-10">
                        <!-- Post preview-->
                        <div class="post-preview" v-for="noticia in items">
                            <div class="row">
                                <div class="col-md-6">
                                    <img :src="noticia.img" class="img img-thumbnail w-50" v-if="noticia.img">
                                </div>
                                <div class="col-md-6">
                                    <a :href="'#/promociones/' + noticia.slug" class="cursor-pointer" target="_blank">
                                        <h2 class="post-title">{{noticia.nombre}}</h2>
                                        <h3 class="post-subtitle">{{noticia.extracto}}</h3>
                                    </a>
                                    <p class="post-meta">
                                        Creado por:
                                        <i>{{noticia.author_name}}</i><br/>
                                        el {{noticia.dateCreated}}
                                    </p>
                                    <b class="post-meta" >
                                        Palabras clave: {{noticia.Tags}}
                                    </b>
                                </div>
                            </div>

                            <hr class="my-4" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import toolbox from "@/toolbox";
import './css/customBlog.scss';

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
                Tags: '',
                Options: [],
            }
        };
    },
    mounted() {
        this.getItems();
    },
    methods: {
        getItems() {

            const self = this;
            toolbox.doAjax('GET', 'paginas-promociones/post/all/show', {},
                function (response) {
                    //self.items = response.data;
                    console.log(response.data)
                    self.items = response.data;
                    if (response.data.Tags !== null && response.data.Tags !== "null") {
                        console.log(response.data.Tags);
                        self.items.Tags = response.data.Tags;
                        self.items.Options = self.page.Tags.split(',');
                    }
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
    }
}
</script>
