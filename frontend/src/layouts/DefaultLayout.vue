<template>
    <div>
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
        <AppSidebar/>
        <div class="wrapper d-flex flex-column min-vh-100 bg-light">
            <AppHeader/>
            <div class="body flex-grow-1 px-3">
                <CContainer lg>
                    <router-view/>
                </CContainer>
            </div>
            <AppFooter/>
        </div>
    </div>
</template>
<script>
import {CContainer} from '@coreui/vue'
import AppFooter from '@/components/AppFooter.vue'
import AppHeader from '@/components/AppHeader.vue'
import AppSidebar from '@/components/AppSidebar.vue'
import {mapGetters, useStore} from "vuex";

import loadingImg from '@/assets/images/loading.gif'

export default {
    name: 'DefaultLayout',
    components: {
        AppFooter,
        AppHeader,
        AppSidebar,
        CContainer,
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
    mounted() {
        //console.log(this.authInfo);
    },
    methods: {}
}
</script>
