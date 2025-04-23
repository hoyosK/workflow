<template>
    <CHeader position="sticky" class="mb-4">
        <CContainer fluid>
            <CHeaderToggler class="ps-1" @click="$store.commit('toggleSidebar')">
                <CIcon icon="cil-menu" size="lg"/>
            </CHeaderToggler>
            <CHeaderBrand class="mx-auto d-lg-none" to="/">
                <img src="../assets/images/logo-dark.png" style="max-width: 100px; margin: auto;">
            </CHeaderBrand>
            <CHeaderNav class="d-none d-md-flex me-auto">
                <!--<CNavItem>
                    <CNavLink href="/dashboard"> Dashboard</CNavLink>
                </CNavItem>
                <CNavItem>
                    <CNavLink href="#">Users</CNavLink>
                </CNavItem>
                <CNavItem>
                    <CNavLink href="#">Settings</CNavLink>
                </CNavItem>-->
            </CHeaderNav>
            <CHeaderNav>
                <AppHeaderDropdownAccnt />
            </CHeaderNav>
            <CHeaderNav>
                <!--<CNavItem>
                    <CNavLink href="#">
                        <CIcon class="mx-2" icon="cil-bell" size="lg"/>
                    </CNavLink>
                </CNavItem>
                <CNavItem>
                    <CNavLink href="#">
                        <CIcon class="mx-2" icon="cil-list" size="lg"/>
                    </CNavLink>
                </CNavItem>-->
                <CNavItem>
                    <CNavLink @click="$router.push('/ver/noticias')" class="text-success">
                        <i class="far fa-lightbulb fa-lg"></i>
                    </CNavLink>
                </CNavItem>
                <CNavItem>
                    <CNavLink   @click="$router.push('/ver/ayuda')">
                        <i class="far fa-question-circle fa-lg"></i>
                    </CNavLink>
                </CNavItem>
                <CNavItem>
                    <CNavLink  @click="$router.push('/ver/promociones')">
                        <i class="fa-solid fa-comment-dollar"></i>
                    </CNavLink>
                </CNavItem>
                <button class="btn btn-secondary" @click="logout">
                    <i class="fas fa-lock"/>
                    <span class="d-none d-sm-inline-block ms-2">Cerrar sesión</span>
                </button>
            </CHeaderNav>
        </CContainer>
        <CHeaderDivider/>
        <CContainer fluid>
            <AppBreadcrumb/>
        </CContainer>
    </CHeader>
</template>

<script>
import AppBreadcrumb from './AppBreadcrumb'
import AppHeaderDropdownAccnt from './AppHeaderDropdownAccnt'
import {logo} from '@/assets/brand/logo'
import {mapMutations} from "vuex";
import toolbox from "@/toolbox";
import Swal from "sweetalert2";
import {mapGetters} from "vuex";


export default {
    name: 'AppHeader',
    components: {
        AppBreadcrumb,
        AppHeaderDropdownAccnt,
    },
    setup() {
        return {
            logo,
        }
    },
    computed: {
        ...mapGetters({
            authLogged: 'authLogged',
            authInfo: 'authInfo',
        }),

    },
    methods: {
        ...mapMutations(["authSetInfo"]),
        logout() {

            const self = this;

                Swal.fire({
                title: '¿Deseas cerrar sesión en todas las aplicaciones?',
                showCancelButton: true,
                confirmButtonText: 'Solo en esta aplicación',
                cancelButtonText: `Cerrar todo`,
                }).then((result) => {
                toolbox.doAjax('POST', 'auth/logout', {
                        type: (!result.isConfirmed ? 'all' : '')
                    },
                    function (response) {
                        self.authSetInfo({});
                        self.$router.push('/login');
                    },
                    function (response) {
                        toolbox.alert(response.msg, 'danger');
                    })
            })

        },
        help() {

            const self = this;
            self.$router.push('/help');
            //loginClose

        }
    }
}
</script>
