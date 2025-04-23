<template>
    <div class="container inspeccionAgenda">
        <CCard class="mb-4 p-4">
            <header class="masthead">
                <img src="../../../assets/images/logo-dark.png" style="max-width: 150px; margin: auto;">
                <div class="post-heading text-center">
                    <h3>Agendamiento de inspecciones</h3>
                </div>
            </header>
            <CCardBody class="mt-4">
                <div class="p-3 text-center" v-if="inspeccionType === ''">
                    <div>
                        <h5 class="text-primary mb-4">Selecciona el tipo de inspección a realizar</h5>
                    </div>
                    <button class="btn btn-primary mx-2" style="font-size: 1.2em" @click="inspeccionType = 'domicilio'">
                        <div>
                            Inspección a domicilio
                        </div>
                        <i class="fas fa-car"></i>
                    </button>
                    <button class="btn btn-primary mx-2" style="font-size: 1.2em" @click="inspeccionType = 'auto'; iniciarAgendamiento()">
                        <div>
                            Auto inspección
                        </div>
                        <i class="fas fa-user-check"></i>
                    </button>
                </div>
                <div v-else class="text-end mb-5">
                    <button class="btn btn-primary btn-sm" @click="inspeccionType = ''"><i class="fas fa-arrow-left me-2"></i>Cambiar tipo de inspección</button>
                </div>
                <div class="row" v-if="inspeccionType === 'auto'">
                    <div class="mt-3 text-center">
                        Una autoinspección permite al interesado realizar la inspección del vehículo en modalidad autoservicio.
                        <div class="mt-3">
                            <button class="btn btn-primary" @click="iniciarAgendamiento"><i class="fas fa-check-circle me-2"></i>Continuar con el agendamiento</button>
                        </div>
                    </div>
                </div>
                <div class="row" v-if="inspeccionType === 'domicilio'">
                    <div class="col-12 col-sm-6">
                        <h5>Selecciona la fecha de tu agendamiento</h5>
                        <date-picker v-model="date" mode="date" :min-date='new Date()' :attributes="datePickerParams" style="width: 100%"></date-picker>
                    </div>
                    <div class="col-12 col-sm-6">
                        <h5>Selecciona el horario deseado</h5>
                        <div v-if="horarios && Object.keys(horarios).length > 0" class="mt-3 text-center">
                            <ul class="list-group">
                                <li :class="{'list-group-item horarioItem active': horarioSelected === item, 'list-group-item horarioItem': horarioSelected !== item}" v-for="item in horarios" @click="horarioSelected = item">{{item}}</li>
                            </ul>
                            <div class="mt-5">
                                <button class="btn btn-primary" @click="iniciarAgendamiento"><i class="fas fa-check-circle me-2"></i>Iniciar agendamiento de inspección</button>
                            </div>
                        </div>
                        <div v-else class="mt-3 text-center text-danger">
                            No hay horarios disponibles
                        </div>
                    </div>
                </div>
            </CCardBody>
        </CCard>
    </div>

</template>

<script>
import 'v-calendar/dist/style.css';
import toolbox from "@/toolbox";
import {useRoute} from "vue-router";
import {DatePicker} from 'v-calendar';
import Select from "@/views/forms/Select.vue";
import Multiselect from "@vueform/multiselect";
import Button from "@/views/forms/form_elements/FormElementButton.vue";

export default {
    name: 'Tables',
    components: {Button, Select, DatePicker},
    data() {
        return {
            date: '',
            time: '',
            datePickerParams: [
                {
                    dot: true,
                    key: 'today',
                    highlight: false,

                },
            ],
            inspeccionType: '',

            // horarios
            horarioSelected: '',
            horarios: {},

            // formulario
            form: {},
            cToken: '',
        };
    },
    watch: {
        date: function (value) {
            const self = this;
            if (value) {
                self.getHorarios();
            }
        },
    },
    mounted() {
        this.slug = (typeof this.$route.params.slug !== 'undefined' ? this.$route.params.slug : '');
        //this.getItem();
    },
    methods: {
        getHorarios() {
            const self = this;
            toolbox.doAjax('POST', 'inspecciones/get-horarios', {
                    date: self.date,
                },
                function (response) {
                    self.horarios = response.data.horario;
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
        iniciarAgendamiento() {
            const self = this;
            toolbox.confirm('Se generará una inspección, ¿desea continuar?', function () {

                toolbox.doAjax('POST', 'inspecciones/start-agenda', {
                        date: self.date,
                        time: self.horarioSelected,
                        type: self.inspeccionType,
                    },
                    function (response) {
                        if (response.status) {

                            if (self.isPublic) {
                                //window.location.href = '/f/' + response.data.ptoken + '/' + response.data.token;
                                self.$router.push('/f/' + response.data.ptoken + '/' + response.data.token);
                            }
                            else {
                                self.$router.push('/cotizar/producto/' + response.data.ptoken + '/' + response.data.token);
                                //window.location.href = '/cotizar/producto/' + response.data.ptoken + '/' + response.data.token;
                            }
                            console.log('test');
                            /*setTimeout(function () {
                                location.reload();
                            }, 800);*/

                        } else {
                            toolbox.alert('Ha ocurrido un error obteniendo el producto', 'danger');
                        }
                    },
                    function (response) {
                        toolbox.alert(response.msg, 'danger');
                    })
            })
        }
    }
}
</script>
