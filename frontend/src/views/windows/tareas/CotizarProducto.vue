<template>
    <div class="container">
        <CotizarProducto :tokenProducto="tokenProducto" :tokenCotizacion="tokenCotizacion" :isPublic="false"></CotizarProducto>
    </div>
</template>

<script>
import toolbox from "@/toolbox";
import {config} from "@/config";

import CotizarProducto from "./CotizarComponent.vue";

export default {
    name: 'CotizarProductoWindow',
    components: {
        CotizarProducto
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
