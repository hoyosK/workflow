<template>
    <CSidebar
        position="fixed"
        :unfoldable="sidebarUnfoldable"
        :visible="sidebarVisible"
        @visible-change="
      (event) =>
        $store.commit({
          type: 'updateSidebarVisible',
          value: event,
        })
    "
    >
        <CSidebarBrand>
            <img :src="logoNegative" class="sidebar-brand-full">
        </CSidebarBrand>
        <CSidebarBrand v-if="!!authInfo.cintillo">
            <img :src="authInfo.cintillo" class="sidebar-brand-full">
        </CSidebarBrand>
        <div class="p-2 text-center">
            Bienvenido {{authInfo.name}}
            <div class="small">
                {{authInfo.username}}
            </div>
        </div>
        <hr class="mb-2 mt-1"/>
        <ContainerMenu/>
        <CSidebarToggler
            class="d-none d-lg-flex"
            @click="$store.commit('toggleUnfoldable')"
        />
    </CSidebar>
</template>

<script>
import {computed} from 'vue'
import {mapGetters, useStore} from 'vuex'
import ContainerMenu from './ContainerMenu.vue'
import logoNegative from '@/assets/images/logo.png'
import {sygnet} from '@/assets/brand/sygnet'

export default {
    name: 'AppSidebar',
    components: {
        ContainerMenu,
    },
    setup() {
        const store = useStore()
        return {
            logoNegative,
            sygnet,
            sidebarUnfoldable: computed(() => store.state.sidebarUnfoldable),
            sidebarVisible: computed(() => store.state.sidebarVisible),
        }
    },
    computed: {
        ...mapGetters({
            loading: 'loading',
            authInfo: 'authInfo',
        })
    },
}
</script>
