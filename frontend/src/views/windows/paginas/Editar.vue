<template>
    <div class="card">
        <div class="card-header">
            <h2 class="mb-4" v-if="page.id === 0">Crear nota</h2>
            <h2 class="mb-4" v-if="page.id !== 0">Editando nota</h2>
        </div>
        <div class="card-body">

            <div class="row">
                <div class="form-group col-12 col-md-6 pt-2">
                    <label for="nombre" class="form-label">Nombre</label>
                    <input v-model="page.nombre" type="text" class="form-control" id="nombre" />
                </div>
                <div class="form-group col-12 col-md-6 pt-2">
                    <label for="subtitle" class="form-label">Subtítulo</label>
                    <input v-model="page.subtitle" type="text" class="form-control" id="subtitle" />
                </div>
                <div class="form-group col-12 pt-2">
                    <label for="slug" class="form-label">Url amigable</label>
                    <div class="input-group mb-3">
                        <input v-model="page.slug" type="text" class="form-control" id="slug" />
                        <a :href="'#/blog/' + page.slug" class="btn btn-primary" target="_blank"><i class="fas fa-external-link me-2"></i>Ver nota</a>
                    </div>
                </div>
                <div class="form-group col-12 col-md-6 pt-2">
                    <div class="mb-3">
                        <span>Configurar acceso por distribuidores</span>
                        <multiselect
                            v-model="page.grupos_assign"
                            :options="page.grupos"
                            :mode="'tags'"
                            :searchable="true"/>
                    </div>
                </div>
                <div class="form-group col-12 col-md-6 pt-2">
                    <div class="mb-3">
                        <span>Visibilidad de página</span>
                        <select class="form-select" v-model="page.publica">
                            <option value="0">Privada</option>
                            <option value="1">Pública</option>
                        </select>
                    </div>
                </div>
                <div class="form-group col-12 mt-2">
                    <label for="img" class="form-label">Imagen destacada</label>
                    <div class="row">
                        <div class="col-12 col-md-6 pt-2">
                            <file-pond type="file"
                                       class="filepond"
                                       name="img"
                                       label-idle="Puedes arrastrar tu imagen aquí"
                                       credits="false"
                                       allow-file-size-validation="true"
                                       max-file-size="3MB"
                                       ref="filepondInput"
                                       :server="{
                                process: (fieldName, file, metadata, load, error, progress, abort) => {
                                    handleFileUpload(fieldName, file, metadata, load, error, progress, abort, page);
                                }
                            }"
                            >
                            </file-pond>
                        </div>
                        <div class="col-12 col-md-4 pt-2 text-center" v-if="page.img">
                            <img :src="page.img" class="img img-thumbnail" style="max-width: 150px">
                        </div>
                    </div>
                    <div class="form-group col-12 col-md-12 pt-2">
                        <label for="Tags" class="form-label">Tags</label>

                        <vue3-tags-input :tags="page.Options"
                                         placeholder="Ingresa palabras clave"
                                         class="form-control"
                                         @on-tags-changed="addTag"/>
                    </div>

                </div>

                <div class="form-group col-12 col-md-12 pt-2">
                    <label for="contenido" class="form-label">Contenido</label>
                    <editor
                        :init="{
                                plugins: plugins,
                                toolbar: toolbar,
                                language: language,
                                promotion: false,
                                branding: false
                        }"
                        v-model="page.contenido"
                        tinymce-script-src="https://tinyroble.s3.amazonaws.com/tinymce/tinymce.min.js"
                    />
                </div>
                <div class="form-group col-12 col-md-12 pt-2">
                    <label for="extracto" class="form-label">Extracto</label>
                    <textarea v-model="page.extracto" class="form-control" id="extracto"></textarea>
                </div>

            </div>
        </div>
        <div class="card-footer text-end">
            <button @click="$router.push('/admin/blog/list')" class="btn btn-danger me-2">Cancelar</button>
            <button type="button" @click="guardarBlog" class="btn btn-primary">Guardar</button>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import Editor from '@tinymce/tinymce-vue';
import Multiselect from '@vueform/multiselect';
import Vue3TagsInput from 'vue3-tags-input';
// Import FilePond
import vueFilePond from 'vue-filepond';
import {AccordionList, AccordionItem} from "vue3-rich-accordion";
import "vue3-rich-accordion/accordion-library-styles.css";
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview';
import 'filepond/dist/filepond.min.css';
import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.css';
import ExcelJS from "exceljs";
import toolbox from "@/toolbox";
import {useRouter, useRoute} from 'vue-router';
import Select from "@/views/forms/Select.vue";

const FilePond = vueFilePond(FilePondPluginFileValidateType, FilePondPluginImagePreview);



export default {
    components:{Select, Multiselect, Editor, Vue3TagsInput, FilePond},
    data() {
        return {
            page: {
                id: 0,
                nombre: '',
                slug: '',
                extracto: '',
                contenido: '',
                status: 1,
                subtitle: '',
                img: '',
                Tags: '',
                Options: [],
                publica: 0,
                grupos: [],
                grupos_assign: [],
            },
            plugins: 'code autoresize autosave link image',
            toolbar: 'undo redo | fontsizeselect formatselect | bold italic forecolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent blockquote | link image media',
            language: 'es',
            editor: null,
        };
    },
    watch: {
        'page.nombre': function(newNombre) {
            this.page.slug = this.createSlug(newNombre);
        }
    },
    mounted() {
        this.page.id = (typeof this.$route.params.id !== 'undefined') ? parseInt(this.$route.params.id) : 0;
        this.getGrupos();
    },
    methods: {
        addTag (newTag) {
            this.page.Options = newTag;
            this.page.Tags = newTag.toString();
        },
        handleFileUpload(fieldName, file, metadata, load, error, progress, abort, page) {
            const formData = new FormData();

            // Agregar los campos de la variable 'page' al FormData
            for (const key in page) {
                if(key !== 'img'){
                    formData.append(key, page[key]);
                }
            }

            // Agregar el archivo al FormData
            formData.append(fieldName, file, file.name);

            const url = (this.page.id !== 0) ? 'paginas/new/edit/' + this.page.id : 'paginas/new/edit';

            toolbox.doAjax('FILE', url, formData, function (response) {
                if (response.status) {
                    //const responseData = response.data;
                    //page.img = responseData.img;
                    if (response.data.id !== null) {
                        page.id = response.data.id;
                    }
                    if (response.data.nombre !== null) {
                        page.nombre = response.data.nombre;
                    }
                    if (response.data.slug !== null) {
                        page.slug = response.data.slug;
                    }
                    if (response.data.extracto !== null) {
                        page.extracto = response.data.extracto;
                    }
                    if (response.data.contenido !== null) {
                        page.contenido = response.data.contenido;
                    }
                    if (response.data.status !== null) {
                        page.status = response.data.status;
                    }
                    if (response.data.img !== null) {
                        page.img = response.data.img;
                    }
                    if (response.data.subtitle !== null) {
                        page.subtitle = response.data.subtitle;
                    }
                    if (response.data.Tags !== null && response.data.Tags !== "null") {
                        console.log(response.data.Tags);
                        page.Tags = response.data.Tags;
                        page.Options = page.Tags.split(',');
                    }
                    toolbox.alert(response.msg);
                    load(response.msg);
                } else {
                    toolbox.alert(response.msg);
                }
            }, function (response) {
                toolbox.alert(response.msg);
            });

            // Devuelve un objeto con un método 'abort' que se activa si el usuario cancela la carga
            return {
                abort: () => {
                    // Si toolbox.doAjax admite cancelación de la solicitud, aquí puedes usarlo.
                    // Por ejemplo: Abortar la solicitud de toolbox.doAjax si el usuario cancela la carga.
                    // Consulta la documentación de toolbox.doAjax para obtener más detalles.
                    abort();
                },
            };
        },
        getItem() {

            const self = this;
            toolbox.doAjax('GET', 'paginas/by/'+self.page.id, {},
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
                    if (response.data.subtitle !== null) {
                        self.page.subtitle = response.data.subtitle;
                    }
                    if (response.data.subtitle !== null) {
                        self.page.publica = response.data.publica;
                    }
                    if (response.data.Tags !== null && response.data.Tags !== "null") {
                        self.page.Tags = response.data.Tags;
                        self.page.Options = self.page.Tags.split(',');
                    }
                    if (response.data.acc !== null && response.data.acc !== "null") {
                        self.page.grupos_assign = [];
                        Object.keys(response.data.acc).map(function (a, b) {
                            self.page.grupos_assign.push(response.data.acc[a]);
                        })
                    }
                    toolbox.alert(response.msg);
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
        guardarBlog() {
            const self = this;
            if(self.page.id === 0){
                toolbox.confirm('Esta noticia se publicará automáticamente en el apartado de blog del sitio', function () {
                    //self.selected.id = idCliente;
                    toolbox.doAjax('POST', 'paginas/new/edit', self.page, function (response) {
                        if (typeof response.data.id !== 'undefined') {
                            self.$router.push('/admin/blog/editar/' + response.data.id);
                        }
                        setTimeout(function () {
                            location.reload();
                        }, 500);
                        toolbox.alert(response.msg);
                        //loadProduct()
                    }, function (response) {
                        toolbox.alert(response.msg);
                    });
                });
            }
            else{
                //self.selected.id = idCliente;
                toolbox.doAjax('PUT', 'paginas/editar/'+self.page.id, self.page, function (response) {
                    toolbox.alert(response.msg);
                    //loadProduct()
                }, function (response) {
                    toolbox.alert(response.msg);
                });
            }

            //save();
        },
        createSlug(nombre) {
            return nombre
                .toLowerCase()
                .replace(/\s+/g, '-')
                .replace(/[^a-z0-9-]/g, '');
        },
        getGrupos() {

            const self = this;
            toolbox.doAjax('GET', 'users/grupo/list', {},
                function (response) {
                    //self.items = response.data;
                    self.page.grupos = [];
                    Object.keys(response.data).map(function (a, b) {
                        self.page.grupos.push({
                            value: response.data[a].id,
                            label: response.data[a].nombre,
                        })
                    })

                    if (self.page.id > 0) {
                        self.getItem();
                    }
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
    }
};
</script>

