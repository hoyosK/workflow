<template>
    <CCard class="mb-4">
        <header class="masthead" :style="'background-image: url(' + page.img + ')'">
            <div class="container position-relative px-4 px-lg-5">
                <div class="row gx-4 gx-lg-5 justify-content-center">
                    <div class="col-md-10 col-lg-8 col-xl-7">
                        <div class="post-heading text-center">
                            <h1>{{ page.nombre }}</h1>
                            <h2 class="subheading">{{page.subtitle}}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        <CCardBody>
            <!-- Post Content-->
            <article class="mb-4">
                <div class="container px-4 px-lg-5">
                    <div class="row gx-2 gx-lg-2 justify-content-center">
                        <div class="col-md-10 col-lg-10 col-xl-10 small mb-5">
            <span class="meta" v-if="page.author_name">
                                Creado por
                                <i>{{page.author_name}}</i>
                                el {{page.dateCreated}}
            </span>
                        </div>
                        <div class="col-md-10 col-lg-10 col-xl-10 contenedorImagen" v-html="page.contenido">
                        </div>
                    </div>
                </div>
            </article>
        </CCardBody>
    </CCard>
</template>

<script>
import toolbox from "@/toolbox";
import './css/customBlog.scss';
import {useRoute} from "vue-router";

export default {
    name: 'Tables',
    data() {
        return {
            page: {
                slug: '',
                nombre: '',
                //slug: '',
                extracto: '',
                contenido: '',
                status: 1,
                subtitle: '',
                author_name: '',
                dateCreated: '',
                img: '',
                Tags: '',
                Options: []
            }
        };
    },
    mounted() {
        this.slug = (typeof this.$route.params.slug !== 'undefined' ? this.$route.params.slug : '');
        this.getItem();
    },
    methods: {
        getItem() {

            const self = this;
            toolbox.doAjax('GET', 'paginas-promociones/ver/'+self.slug, {},
                function (response) {

                    if (response.data.id !== null) {
                        self.page.id = response.data.id;
                    }
                    if (response.data.nombre !== null) {
                        self.page.nombre = response.data.nombre;
                    }
                    if (response.data.slug !== null) {
                        self.page.slug = response.data.slug;
                    }
                    if (response.data.extracto !== null) {
                        self.page.extracto = response.data.extracto;
                    }
                    if (response.data.contenido !== null) {
                        self.page.contenido = response.data.contenido;
                    }
                    if (response.data.status !== null) {
                        self.page.status = response.data.status;
                    }
                    if (response.data.img !== null) {
                        self.page.img = response.data.img;
                    }
                    if (response.data.author_name !== null) {
                        self.page.author_name = response.data.author_name;
                    }
                    if (response.data.dateCreated !== null) {
                        self.page.dateCreated = response.data.dateCreated;
                    }
                    if (response.data.subtitle !== null) {
                        self.page.subtitle = response.data.subtitle;
                    }
                    if (response.data.Tags !== null && response.data.Tags !== "null") {
                        self.page.Tags = response.data.Tags;
                        self.page.Options = self.page.Tags.split(',');
                    }
                },
                function (response) {
                    self.page.nombre = 'Página no encontrada';
                    toolbox.alert(response.msg, 'danger');
                })
        }
    }
}
</script>
