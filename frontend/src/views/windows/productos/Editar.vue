<template>
    <CRow>
        <CCol :xs="12">
            <CCard class="mb-4">
                <CCardHeader>
                    <strong>Editar Producto</strong>
                </CCardHeader>
                <CCardBody>
                    <div class="card-body">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="homeIcon-tab" data-bs-toggle="tab" href="#homeIcon"
                                   aria-controls="home" role="tab" aria-selected="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                         stroke-linejoin="round" class="feather feather-home">
                                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                                    </svg>
                                    Contenido</a>
                            </li>
                            <li class="nav-item" @click="getPlanes()">
                                <a class="nav-link" id="profileIcon-tab" data-bs-toggle="tab" href="#profileIcon"
                                   aria-controls="profile" role="tab" aria-selected="false">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                         stroke-linejoin="round" class="feather feather-tool">
                                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                                    </svg>
                                    Planes</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="aboutIcon-tab" data-bs-toggle="tab" href="#aboutIcon"
                                   aria-controls="about" role="tab" aria-selected="false">
                                    <i class="fa fa-cogs"></i>
                                    Flujos</a>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane active" id="homeIcon" aria-labelledby="homeIcon-tab" role="tabpanel">
                                <div class="row mb-3">
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label class="mb-1">Nombre</label>
                                            <input type="text" v-model="producto.nombreProducto" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label class="mb-1">Descripción</label>
                                            <textarea  v-model="producto.descripcion" class="form-control"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label class="mb-1">Código Interno</label>
                                            <input  type="text" v-model="producto.codigoInterno" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 mb-2">
                                    <div class="border rounded p-2">
                                        <h4 class="mb-1">Imagen Principal o Logo:</h4>
                                        <div class="d-flex flex-column flex-md-row">
                                            <div v-if="producto.mainImage" class="d-flex flex-column flex-md-row">
                                                <img :src="producto.mainImage" alt="Imagen Principal"  class="rounded me-2 mb-1 mb-md-0"  width="170" height="110">
                                            </div>
                                            <div class="featured-info">
                                                <div class="d-inline-block">
                                                    <small class="text-muted">Se recomienda imágenes optimizadas para web</small>
                                                    <input type="file" @change="onMainImageChange" class="form-control form-control-file" accept="image/*">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 mb-2">
                                    <div class="border rounded p-2">
                                        <h4 class="mb-1">Imagen Secundaria:</h4>
                                        <div class="d-flex flex-column flex-md-row">
                                            <div v-if="producto.secondaryImage" class="d-flex flex-column flex-md-row">
                                                <img :src="producto.secondaryImage" alt="Imagen Secundaria" class="rounded me-2 mb-1 mb-md-0" width="170" height="110">
                                            </div>
                                            <div class="featured-info">

                                                <div class="d-inline-block">
                                                    <small class="text-muted">Se recomienda imágenes optimizadas para web</small>
                                                    <input type="file" @change="onSecondaryImageChange" class="form-control form-control-file" accept="image/*">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div v-for="(block, index) in blocks" :key="index" class="block-container">
                                        <h2 class="block-title">Bloque {{ index + 1 }}</h2>
                                        <div class="form-group">
                                            <label class="block-label">Título:</label>
                                            <input v-model="block.titulo" type="text" class="form-control">
                                        </div>

                                        <div class="form-group">
                                            <label class="block-label">Icono:</label>
                                            <input type="file" @change="onFileChange(index, 'bloques')" class="form-control form-control-file">
                                            <div v-if="block.icono" class="icon-preview">
                                                <img :src="block.icono" alt="Icono" class="icon-image">
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label class="block-label">Bullets:</label>
                                            <ul class="bullet-list">
                                                <li v-for="(bullet, bulletIndex) in block.bullets" :key="bulletIndex" class="bullet-item">
                                                    <input type="text" v-model="block.bullets[bulletIndex]" class="bullet-input form-control">
                                                </li>
                                            </ul>
                                        </div>
                                        <button @click="addBullet(index)" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-plus"></i> Agregar Bullet
                                        </button>
                                    </div>
                                    <button @click="addBlock" class="btn btn-sm btn-outline-primary mb-5">
                                        <i class="fas fa-plus"></i> Agregar Bloque
                                    </button>
                                </div>
                                <div class="row">
                                    <div v-for="(block, index) in coberturas" :key="index" class="block-container">
                                        <h2 class="block-title">Coberturas y no coberturas {{ index + 1 }}</h2>
                                        <div class="form-group">
                                            <label class="block-label">Título:</label>
                                            <input v-model="block.titulo" type="text" class="form-control">
                                        </div>

                                        <div class="form-group">
                                            <label class="block-label">Icono:</label>
                                            <input type="file" @change="onFileChange(index, 'coberturas')" class="form-control form-control-file">
                                            <div v-if="block.icono" class="icon-preview">
                                                <img :src="block.icono" alt="Icono" class="icon-image">
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label class="block-label">Bullets:</label>
                                            <ul class="bullet-list">
                                                <li v-for="(bullet, bulletIndex) in block.bullets" :key="bulletIndex" class="bullet-item">
                                                    <input type="text" v-model="block.bullets[bulletIndex]" class="bullet-input form-control">
                                                </li>
                                            </ul>
                                        </div>
                                        <button @click="addBulletCober(index)" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-plus"></i> Agregar Bullet
                                        </button>
                                    </div>

                                    <button @click="addCobertura" class="btn btn-sm btn-outline-primary mb-5">
                                        <i class="fas fa-plus"></i> Agregar Bloque
                                    </button>
                                </div>

                            </div>
                            <div class="tab-pane" id="profileIcon" aria-labelledby="profileIcon-tab" role="tabpanel">
                                <div class="row">
                                    <div v-for="(plan, indexPlan) in planes" :key="indexPlan" class="block-container">
                                        <h3 class="block-title">{{ plan.nombrePlan }}</h3>
                                        <div class="form-group pt-2">
                                            <label class="block-label">Código del plan</label>
                                            <select class="form-control" v-model="plan.codigoPlan">
                                                <option v-for="(planCodigo, indexPlan) in listadoPlan" @click="plan.nombrePlan = planCodigo.nombreplanprod;">
                                                    {{planCodigo.codplan}}-{{planCodigo.nombreplanprod}}
                                                </option>
                                            </select>
                                        </div>
                                        <div class="form-group pt-2">
                                            <label class="block-label">Nombre del Plan:</label>
                                            <input v-model="plan.nombrePlan" type="text" class="form-control">
                                        </div>
                                        <div class="form-group pt-2">
                                            <label class="block-label">Fraccionamientos:</label>
                                            <ul class="bullet-list">
                                                <li v-for="(fraccionamiento, fraccionamientoIndex) in plan.planFraccionamiento" :key="fraccionamientoIndex" class="bullet-item row">
                                                    <div class="col-md-6">
                                                        <label for="codigo{{ fraccionamientoIndex }}" class="form-label">Código</label>
                                                        <input type="text" v-model="fraccionamiento.codigo" class="bullet-input form-control" id="codigo{{ fraccionamientoIndex }}">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label for="descripcion{{ fraccionamientoIndex }}" class="form-label">Descripción</label>
                                                        <input type="text" v-model="fraccionamiento.descripcion" class="bullet-input form-control" id="descripcion{{ fraccionamientoIndex }}">
                                                    </div>
                                                </li>
                                            </ul>
                                        </div>
                                        <button @click="addFraccionamiento(indexPlan)" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-plus"></i> Agregar Fraccionamiento
                                        </button>
                                    </div>
                                    <button @click="addPlan" class="btn btn-sm btn-outline-primary mb-5">
                                        <i class="fas fa-plus"></i> Agregar plan
                                    </button>
                                </div>
                            </div>

                            <div class="tab-pane" id="aboutIcon" aria-labelledby="aboutIcon-tab" role="tabpanel">
                                <div class="card-datatable table-responsive p-2">
                                    <div class="row">
                                        <label for="Formulario">Formulario Inicial</label>
                                        <select class="form-control">
                                            <option value="">Cotización datos iniciales</option>
                                            <option value="">Subir DPI</option>
                                            <option value="">Comentario individual</option>
                                        </select>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group pt-2">
                                                <label>Etapa actual:</label>
                                                <select class="form-select" v-model="selectedStage" @change="cargarTareas">
                                                    <option v-for="stage in stages" :value="stage">{{ stage.title }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group pt-2">
                                                <label>Tarea:</label>
                                                <select class="form-select" v-model="selectedTask" @change="cargarSiguientesEtapa">
                                                    <option v-for="task in tasks" :value="task">{{ task.title }}</option>
                                                </select>
                                                <span v-if="loading" class="loading-spinner"><i class="fas fa-spinner fa-spin"></i></span>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group pt-2">
                                                <label>Etapa siguiente:</label>
                                                <select class="form-select" v-model="selectedNextStage">
                                                    <option v-for="stage in stages" :value="stage">{{ stage.title }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3 my-4">
                                            <button class="btn btn-sm btn-outline-primary" @click="agregarConexion">
                                                <i v-if="!loading" class="fas fa-plus"></i>
                                                <span v-else class="loading-spinner"><i class="fas fa-spinner fa-spin"></i></span>
                                            </button>
                                        </div>
                                    </div>

                                    <h5 v-if="!isEmptyObject(stateMachineConfig.states)">Configuración actual:</h5>
                                    <div class="state-nodes overflow-auto">
                                        <div class="state-node border-end-light border-start-light border-top-light" v-for="(state, stateName) in stateMachineConfig.states" :key="stateName">
                                            <div class="state-name">{{ stateName }}</div>
                                            <div class="state-transitions mx-25">
                                                <ul class="list-group">
                                                    <li class="list-group-item small" v-for="(nextState, task) in state.on" :key="task">
                                                        <div class="task" style="max-width: 130px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" v-if="tareas[task]">{{ tareas[task].title}}</div>
                                                        <div class="arrow-down">&#8595;</div>
                                                        <div class="next-state">{{ nextState.title }}</div>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-4">
                                <button class="btn btn-primary" @click="save">Guardar producto</button>
                                <button class="btn btn-danger ms-2" onclick="window.location.href='/productos'">Cancelar</button>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 text-end">
                        <button @click="$router.push('/admin/productos')" class="btn btn-danger me-4">Cancelar</button>
                        <button @click="guardar" class="btn btn-primary">Guardar</button>
                    </div>
                </CCardBody>
            </CCard>
        </CCol>
    </CRow>
</template>

<script>
import toolbox from "@/toolbox";
import {config} from "/src/config";
import Select from "@/views/forms/Select.vue";
import login from "@/views/pages/Login.vue";
import Multiselect from '@vueform/multiselect'

export default {
    name: 'Tables',
    computed: {
        login() {
            return login
        }
    },
    components: {Multiselect},
    data() {
        return {
            id: 0,
            nombre: '',
            urlApp: '',
            urlAmigable: '',
            activo: 1,

            // agregar campo
            agregarCampo: false,
            //archivadorSelected: null,
            campo: {
                id: 0,
                archivadorCampo: '',
                nombre: '',
                layoutSizePc: '',
                layoutSizeMobile: '',
                cssClass: '',
                requerido: 0,
                deshabilitado: 0,
                visible: 0,
                activo: 0,
                archivadorDetalleId: 0,
            },
            campos: [],

            // sección
            secciones: [],

            // formulario valores
            optionsArchivadores: [],
        };
    },
    mounted() {
        this.id = (typeof this.$route.params.id !== 'undefined') ? parseInt(this.$route.params.id) : 0;
        this.urlApp = config.appUrl;
        //console.log(this.id);
        this.getData();
        this.getArchivadores();
    },
    methods: {
        getData() {

            const self = this;
            if (self.id > 0) {
                toolbox.doAjax('GET', 'admin/formulario/load/' + self.id, {},
                    function (response) {
                        self.id = response.data.id;
                        self.nombre = response.data.nombre;
                        self.urlAmigable = response.data.urlAmigable;
                        self.activo = !!response.data.activo;

                        if (typeof response.data.seccion !== 'undefined') {
                            Object.keys(response.data.seccion).map(function (a) {
                                const section = {
                                    id: response.data.seccion[a].id,
                                    nombre: response.data.seccion[a].nombre,
                                    campos: [],
                                }
                                Object.keys(response.data.seccion[a].campos).map(function (b) {

                                    const campo = {
                                        id: response.data.seccion[a].campos[b].id,
                                        nombre: response.data.seccion[a].campos[b].nombre,
                                        archivadorDetalleId: response.data.seccion[a].campos[b].archivadorDetalleId,
                                        archivadorCampo: response.data.seccion[a].campos[b].archivador_detalle.archivador.nombre + ' / ' + response.data.seccion[a].campos[b].archivador_detalle.nombre,
                                    }
                                    section.campos.push(campo);
                                })

                                self.secciones.push(section);
                            })
                        }
                    },
                    function (responseRole) {
                        toolbox.alert(responseRole.msg, 'danger');
                    });
            }
        },
        guardar() {

            const self = this;
            this.ordenarSecciones();

            toolbox.doAjax('POST', 'admin/formulario/save', {
                    id: self.id,
                    nombre: self.nombre,
                    activo: self.activo,
                    urlAmigable: self.urlAmigable,
                    campos: self.secciones,
                },
                function (response) {
                    toolbox.alert(response.msg, 'success');
                    /*if (self.id === 0) {
                        self.id = response.data;
                    }*/
                    self.$router.push('/admin/formularios');
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
        addSection() {
            const section = {
                nombre: '',
                campos: [],
            }
            this.secciones.push(section);
        },
        resetCampo() {
            this.campo = {
                id: 0,
                nombre: '',
                tipoCampo: '',
                mascara: '',
                longitudMin: 0,
                longitudMax: 0,
            };
        },
        saveCampo(seccionKey) {
            this.secciones[seccionKey].agregarCampo = false;

            // traigo el archivador desc
            const self = this;
            this.optionsArchivadores.forEach(function (a) {
                if (a.value === self.campo.archivadorDetalleId) {
                    self.campo.archivadorCampo = a.label;
                }
            });

            if (typeof this.secciones[seccionKey].campos === 'undefined') {
                this.secciones[seccionKey].campos = [];
            }

            if (parseInt(this.campo.id) === 0) {
                this.secciones[seccionKey].campos.push(this.campo);
            }
            this.resetCampo();
        },
        deleteCampo(key, item) {
            const self = this;
            toolbox.confirm('Si elimina un campo que esté asociado a información de formularios, este únicamente se desactivará. ¿Desea continuar?', function () {
                self.campos.splice(key, 1);

                toolbox.doAjax('POST', 'admin/formulario/delete-field', {
                        id: item.id,
                    },
                    function (response) {
                        toolbox.alert(response.msg, 'success');
                    },
                    function (response) {
                        toolbox.alert(response.msg, 'danger');
                    })
            });
        },
        editCampo(item) {
            this.agregarCampo = true;
            this.campo = item;
        },
        changeTipoCampo(tipo) {
            this.campo.tipoCampo = tipo;
        },
        getArchivadores() {
            const self = this;
            toolbox.doAjax('GET', 'admin/archivador/fields', {},
                function (response) {
                    Object.keys(response.data).map(function (a, b) {
                        self.optionsArchivadores.push({
                            value: response.data[a].id,
                            label: response.data[a].nombre,
                        })
                    })
                },
                function (responseRole) {
                    toolbox.alert(responseRole.msg, 'danger');
                });
        },
        friendlyUrl() {
            this.urlAmigable = this.nombre.replace(/[^a-z0-9_]+/gi, '-').replace(/^-|-$/g, '').toLowerCase();
        },
        ordenarSecciones() {
            const self = this;
            this.secciones.forEach(function (a, b) {
                self.secciones[b].orden = b;
            })
        },
        moverSeccionArriba(old_index) {
            const new_index = old_index-1;
            if (new_index >= this.secciones.length) {
                var k = new_index - this.secciones.length + 1;
                while (k--) {
                    this.secciones.push(undefined);
                }
            }
            this.secciones.splice(new_index, 0, this.secciones.splice(old_index, 1)[0]);
        },
        moverSeccionAbajo(old_index) {
            const new_index = old_index+1;
            if (new_index >= this.secciones.length) {
                var k = new_index - this.secciones.length + 1;
                while (k--) {
                    this.secciones.push(undefined);
                }
            }
            this.secciones.splice(new_index, 0, this.secciones.splice(old_index, 1)[0]);
        },
    }
}
</script>
