<template>
    <div class="loading" v-if="loading">
        <div class="loadingBox text-center">
            <div>
                <img :src="loadingImg">
            </div>
            <div class="mt-2">
                Cargando
            </div>
        </div>
    </div>
    <div class="container">
        <CotizarProducto :tokenProducto="tokenProducto" :tokenCotizacion="tokenCotizacion" :isPublic="true"></CotizarProducto>
    </div>
</template>

<script>
import toolbox from "@/toolbox";
import {config} from "@/config";
import loadingImg from '@/assets/images/loading.gif'

import CotizarProducto from "../windows/tareas/CotizarComponent.vue";
import {mapGetters, useStore} from "vuex";

export default {
    name: 'CotizarProductoWindow',
    components: {
        CotizarProducto
    },
    computed: {
        ...mapGetters({
            loading: 'loading',
            authInfo: 'authInfo',
        })
    },
    setup() {
        const store = useStore()
        return {
            loadingImg,
        }
    },
    data() {
        return {
            tokenProducto: '',
            tokenCotizacion: '',
        };
    },
    mounted() {
        this.tokenProducto = (typeof this.$route.params.tokenProducto !== 'undefined') ? this.$route.params.tokenProducto : '';
        this.tokenCotizacion = (typeof this.$route.params.tokenCotizacion !== 'undefined') ? this.$route.params.tokenCotizacion : '';
    },
    methods: {
    }
}
</script>
