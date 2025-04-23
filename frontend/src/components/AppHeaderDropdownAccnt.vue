<template>
    <div class="nav-item dropdownHeaderCustom">
        <a class="nav-link dropdown-toggle py-0" @click="showTask = !showTask">
            <i class="far fa-bell fa-lg">
                <span v-if="!!items && Array.isArray(items)" class="badge rounded-circle bg-danger position-absolute top-0 start-90 translate-middle" style="font-size: 0.75rem;">
                {{ items.length }}
                </span>
            </i>
        </a>
        <div class="dropdownHeaderCustomList" v-if="showTask">
            <div class="text-muted mb-2">
                Últimas tareas
            </div>
            <div v-for="item in items" class="cursor-pointer dropdownHeaderCustomItem" @click="goTask(item)">
                <a class="text-dark text-decoration-none">
                    <div>
                        <i class="fas fa-tasks me-2"></i><b>Tarea No.{{item.id}}</b>
                    </div>
                    <div>
                        Estado: <span style="text-transform: capitalize">{{item.estado}}</span>
                    </div>
                </a>
            </div>
        </div>
    </div>
    <a v-if="(typeof authInfo.m['paginas/show-button-support'] !== 'undefined' && authInfo.m['paginas/show-button-support'])" class="nav-item py-0 pe-3" :href="link" target="_blank">
        <div class="btn btn-light btn-sm"><i class="fas fa-info-circle fa-lg me-2"></i>Soporte</div>
    </a>
</template>

<script>
import avatar from '@/assets/images/avatars/1.png'
import {mapMutations} from "vuex";
import {logo} from "@/assets/brand/logo";
import toolbox from "@/toolbox";
import {mapGetters} from "vuex";

export default {
    name: 'AppHeaderDropdownAccnt',
    setup() {
        return {
            avatar: avatar,
            itemsCount: 42,
        }
    },
    data() {
        return {
            conteo: {},
            items: {},
            showTask: false,
            link: '',
        };
    },
    mounted() {
        this.getTareas();
        //document.getElementsByClassName('userMenu').removeAttribute('href');
    },
    computed: {
        ...mapGetters({
            authLogged: 'authLogged',
            authInfo: 'authInfo',
        }),

    },
    methods: {
        getTareas() {
            const self = this;
            toolbox.doAjax('POST', 'tareas/get-fast-view', {
                },
                function (response) {
                    self.items = response.data.c;
                    self.link = response.data.l.contenido;
                    self.conteo = response.data.pc;
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                },{}, false)
        },
        filterTareas(pr) {
            this.$router.push('/admin/tareas/' + pr);
        },
        goTask(item) {
            this.$router.push('/cotizar/producto/'+item.productoTk+'/'+item.token);
            setTimeout(function () {
                location.reload();
            }, 800);

        }
    }
}
</script>
