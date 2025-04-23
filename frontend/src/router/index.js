import {h, resolveComponent} from 'vue'
import {createRouter, createWebHashHistory} from 'vue-router'

import DefaultLayout from '@/layouts/DefaultLayout'
import store from "@/store";
import {config} from "@/config";

const routes = [
    {
        path: '/',
        name: 'Home',
        component: DefaultLayout,
        redirect: '/panel-productos',
        children: [
            {
                path: '/dashboard',
                name: 'Dashboard',
                component: () =>
                    import(/* webpackChunkName: "dashboard" */ '@/views/windows/apps/Directorio.vue'),
            },
            {
                name: 'Panel',
                path: '/panel-productos',
                component: () => import('@/views/windows/tareas/PanelProductos.vue'),
            },
            {
                name: 'Ver noticia',
                path: '/ver/noticia/:id?',
                component: () => import('@/views/windows/paginas/VerPost.vue'),
            },
            {
                name: 'Ver listado',
                path: '/ver/noticias',
                component: () => import('@/views/windows/paginas/VerListadoPublico.vue'),
            },
            {
                name: 'Ver ayuda',
                path: '/ver/ayuda-p/:id?',
                component: () => import('@/views/windows/paginas/VerPostAyuda.vue'),
            },
            {
                name: 'Ver listado ayuda',
                path: '/ver/ayuda',
                component: () => import('@/views/windows/paginas/VerListadoPublicoAyuda.vue'),
            },
            {
                name: 'Ver promociones',
                path: '/ver/promociones-p/:id?',
                component: () => import('@/views/windows/paginas/VerPostPromociones.vue'),
            },
            {
                name: 'Ver listado promociones',
                path: '/ver/promociones',
                component: () => import('@/views/windows/paginas/VerListadoPublicoPromociones.vue'),
            },
            {
                name: 'Ver centro de atención',
                path: '/ver/centro-atencion/:id?',
                component: () => import('@/views/windows/paginas/VerPost.vue'),
            },
            {
                name: 'Ver listado centro de atención',
                path: '/ver/centro-atencion',
                component: () => import('@/views/windows/paginas/VerListadoPublicoCentroAten.vue'),
            }
        ],
    },
    {
        path: '/inpecciones',
        name: 'Inspecciones',
        component: DefaultLayout,
        redirect: '',
        children: [
            {
                name: 'Agendar inspeccion',
                path: '/inpecciones/agendar',
                component: () => import('@/views/windows/inspecciones/Agendamiento.vue'),
            },
            {
                name: 'Listado inspecciones',
                path: '/inpecciones/ver',
                component: () => import('@/views/windows/inspecciones/ListadoInspecciones.vue'),
            },
        ],
    },
    {
        path: '/admin',
        name: 'Administración',
        component: DefaultLayout,
        redirect: '',
        children: [
            {
                name: 'Tareas',
                path: '/admin/tareas',
                component: () => import('@/views/windows/tareas/ListadoTareas.vue'),

            },
            {
                name: 'Cotizar',
                path: '/cotizar/producto/:tokenProducto/:tokenCotizacion',
                component: () => import('@/views/windows/tareas/CotizarProducto.vue'),
            },
            {
                name: 'Crear tarea',
                path: '/admin/tareas/edit/:id',
                component: () => import('@/views/windows/tareas/Editar.vue'),
            },
            {
                name: 'Plantillas de tarea',
                path: '/admin/tareas-templates',
                component: () => import('@/views/windows/tareas/TemplatesListado.vue'),
            },
            {
                name: 'Crear plantilla',
                path: '/admin/tareas-templates/edit/:id',
                component: () => import('@/views/windows/tareas/TemplatesEditar.vue'),
            },
            {
                name: 'Archivadores Listado',
                path: '/admin/archivadores',
                component: () => import('@/views/windows/archivadores/Listado.vue'),
            },
            {
                name: 'Editar archivador',
                path: '/admin/archivador/edit/:id',
                component: () => import('@/views/windows/archivadores/Editar.vue'),
            },
            {
                name: 'Formularios Listado',
                path: '/admin/formularios',
                component: () => import('@/views/windows/formularios/Listado.vue'),
            },
            {
                name: 'Productos Listado',
                path: '/admin/productos',
                component: () => import('@/views/windows/productos/Listado.vue'),
            },
            {
                name: 'Editar producto',
                path: '/admin/productos/edit/:id',
                component: () => import('@/views/windows/productos/Editar.vue'),
            },
            {
                name: 'Editar formulario',
                path: '/admin/formulario/edit/:id',
                component: () => import('@/views/windows/formularios/Editar.vue'),
            },
            {
                name: 'Productos',
                path: '/admin/flujos',
                component: () => import('@/views/windows/flujos/Listado.vue'),
            },
            {
                name: 'Editar página',
                path: '/admin/blog/editar/:id?',
                component: () => import('@/views/windows/paginas/Editar.vue'),
            },
            {
                name: 'Listado de páginas',
                path: '/admin/blog/list',
                component: () => import('@/views/windows/paginas/Listado.vue'),
            },
            {
                name: 'Editar página de ayuda',
                path: '/admin/ayuda/editar/:id?',
                component: () => import('@/views/windows/paginas/EditarAyuda.vue'),
            },
            {
                name: 'Listado de páginas ayuda',
                path: '/admin/ayuda/list',
                component: () => import('@/views/windows/paginas/ListadoAyuda.vue'),
            },
            {
                name: 'Editar página de promociones',
                path: '/admin/promociones/editar/:id?',
                component: () => import('@/views/windows/paginas/EditarPromociones.vue'),
            },
            {
                name: 'Listado de páginas promociones',
                path: '/admin/promociones/list',
                component: () => import('@/views/windows/paginas/ListadoPromociones.vue'),
            },
            {
                name: 'Blog Listado',
                path: '/ver/noticias/listado',
                component: () => import('@/views/windows/paginas/Listado.vue'),
            },

            {
                name: 'Editar producto',
                path: '/admin/flujo/edit/:id',
                component: () => import('@/views/windows/flujos/Editar.vue'),
            },
            {
                name: 'Editar plantilla PDF',
                path: '/admin/plantillas-pdf/:id',
                component: () => import('@/views/windows/tareas/PlantillasPdfEditar.vue'),
            },
            {
                name: 'Listado de plantillas PDF',
                path: '/admin/plantillas-pdf',
                component: () => import('@/views/windows/tareas/PlantillasPdfListado.vue'),
            },
            {
                name: 'Variables de sistema',
                path: '/admin/system-vars',
                component: () => import('@/views/windows/configuracion/VariablesSistema.vue'),
            },

            // catalogos
            {
                name: 'Editar catálogo',
                path: '/admin/catalogo/:id',
                component: () => import('@/views/windows/catalogo/CatalogoWs.vue'),
            },
            {
                name: 'Listado de catálogos',
                path: '/admin/catalogo',
                component: () => import('@/views/windows/catalogo/CatalogoListadoWs.vue'),
            },

            // catalogos
            {
                name: 'Editar tarifas y coberturas',
                path: '/admin/tarifas/coberturas/:id',
                component: () => import('@/views/windows/catalogo/EditarProductoTarifasCoberturasNEW.vue'),
            },
            {
                name: 'Listado de tarifas y coberturas',
                path: '/admin/tarifas/coberturas',
                component: () => import('@/views/windows/catalogo/EditarProductoTarifasCoberturasListado.vue'),
            },

            // descuentos
            {
                name: 'Editar descuentos',
                path: '/admin/descuentos/:id',
                component: () => import('@/views/windows/descuentos/Editar.vue'),
            },
            {
                name: 'Listado de descuentos',
                path: '/admin/descuentos',
                component: () => import('@/views/windows/descuentos/Listado.vue'),
            },
            {
                name: 'Editar recargo de siniestralidad',
                path: '/admin/recargos',
                component: () => import('@/views/windows/recargas/Editar.vue'),
            },
            {
                name: 'Sistema',
                path: '/admin/system',
                component: () => import('@/views/windows/configuracion/Sistema.vue'),
            },
        ],
    },
    {
        path: '/usuarios',
        name: 'Usuarios',
        component: DefaultLayout,
        redirect: '/usuarios/listado',
        children: [
            {
                name: 'Listado usuarios',
                path: '/usuarios/listado',
                component: () => import('@/views/windows/usuarios/UsuariosListado.vue'),
            },
            {
                name: 'Estado de Usuarios',
                path: '/usuarios/listado/estado',
                component: () => import('@/views/windows/usuarios/UsuariosListadoFueraOficina.vue'),
            },
            {
                name: 'Editar usuario',
                path: '/usuarios/edit/:id',
                component: () => import('@/views/windows/usuarios/UsuariosEditar.vue'),
            },
            {
                name: 'Nuevo usuario',
                path: '/usuarios/new',
                component: () => import('@/views/windows/usuarios/UsuariosNuevo.vue'),
            },
            {
                name: 'Listado roles',
                path: '/usuarios/roles/listado',
                component: () => import('@/views/windows/usuarios/RolesListado.vue'),
            },
            {
                name: 'Editar rol',
                path: '/usuarios/roles/edit/:id',
                component: () => import('@/views/windows/usuarios/RolesEditar.vue'),
            },
            {
                name: 'Canales de usuarios',
                path: '/usuarios/canal/listado',
                component: () => import('@/views/windows/usuarios/CanalUsuarioListado.vue'),
            },
            {
                name: 'Editar canal',
                path: '/usuarios/canal/edit/:id',
                component: () => import('@/views/windows/usuarios/CanalUsuarioEditar.vue'),
            },
            {
                name: 'Distribuidores',
                path: '/usuarios/grupo/listado',
                component: () => import('@/views/windows/usuarios/GruposUsuarioListado.vue'),
            },
            {
                name: 'Editar distribuidor',
                path: '/usuarios/grupo/edit/:id',
                component: () => import('@/views/windows/usuarios/UsuariosGrupoEditar.vue'),
            },
            {
                name: 'Tiendas',
                path: '/usuarios/tiendas',
                component: () => import('@/views/windows/usuarios/UsuarioTiendasListado.vue'),
            },
            {
                name: 'Editar tienda',
                path: '/usuarios/tiendas/edit/:id',
                component: () => import('@/views/windows/usuarios/UsuarioTiendasEditar.vue'),
            },
            {
                name: 'Jerarquias de usuario',
                path: '/usuarios/jerarquia/listado',
                component: () => import('@/views/windows/usuarios/UsuarioJerarquiaListado.vue'),
            },
            {
                name: 'Jerarquias editar',
                path: '/usuarios/jerarquia/edit/:id',
                component: () => import('@/views/windows/usuarios/UsuarioJerarquiaEditar.vue'),
            },
        ],
    },
    {
        path: '/reportes',
        name: 'Reportes',
        component: DefaultLayout,
        redirect: '/reportes/configuracion',
        children: [
            {
                name: 'Configuración de reportes',
                path: '/reportes/configuracion',
                component: () => import('@/views/windows/reportes/Listado.vue'),
            },
            {
                name: 'Editar reporte',
                path: '/reportes/configuracion/:id',
                component: () => import('@/views/windows/reportes/Editar.vue'),
            },
            {
                name: 'Generar reporte',
                path: '/reportes/generar',
                component: () => import('@/views/windows/reportes/Generar.vue'),
            },
            {
                name: 'Tableros',
                path: '/reportes/tableros',
                component: () => import('@/views/windows/reportes/Tableros.vue'),
            },
        ],
    },
    {
        path: '/configuration',
        name: 'Configuración',
        component: DefaultLayout,
        redirect: '/configuration/system',
        children: [
            {
                name: 'Configuración de sistema',
                path: '/configuration/sistema',
                component: () => import('@/views/windows/configuracion/NotificacionEditar.vue'),
            },
        ],
    },
    {
        path: '/control-calidad',
        name: 'Control Calidad',
        component: DefaultLayout,
        redirect: '/control-calidad/listado',
        children: [
            {
                name: 'Ver tareas',
                path: '/control-calidad/listado',
                component: () => import('@/views/windows/controlCalidad/Listado.vue'),
            },
            {
                name: 'Ficha de control',
                path: '/control-calidad/ficha/:id',
                component: () => import('@/views/windows/controlCalidad/Editar.vue'),
            },
        ],
    },
    {
        path: '/',
        redirect: '/404',
        component: {
            render() {
                return h(resolveComponent('router-view'))
            },
        },
        children: [
            {
                path: '/404',
                name: 'Page404',
                component: () => import('@/views/pages/Page404'),
                meta: {
                    public: true
                }
            },
            {
                path: '/f/:tokenProducto/:tokenCotizacion',
                name: 'Formulario',
                component: () => import('@/views/pages/Formularios.vue'),
                meta: {
                    public: true
                }
            },
            {
                path: '/login',
                name: 'Login',
                component: () => import('@/views/pages/Login.vue'),
                meta: {
                    public: true
                }
            },
            {
                path: '/blog/:slug',
                name: 'Blog',
                component: () => import('@/views/windows/paginas/VerPost.vue'),
                meta: {
                    public: true
                }
            },
            {
                path: '/ayuda/:slug',
                name: 'Ayuda',
                component: () => import('@/views/windows/paginas/VerPostAyuda.vue'),
                meta: {
                    public: true
                }
            },
            {
                path: '/promociones/:slug',
                name: 'Promociones',
                component: () => import('@/views/windows/paginas/VerPostPromociones.vue'),
                meta: {
                    public: true
                }
            },
            {
                name: 'Agendar',
                path: '/inpecciones/agendar/public',
                component: () => import('@/views/windows/inspecciones/Agendamiento.vue'),
            },
            /*{
                path: '/reset-password',
                name: 'ResetPassword',
                component: () => import('@/views/pages/RecuperarPass.vue'),
                meta: {
                    public: true
                }
            },*/
            /*{
                path: '/reset-my-password/:token',
                name: 'ResetPasswordWToken',
                component: () => import('@/views/pages/RecuperarPass.vue'),
                meta: {
                    public: true
                }
            },
            {
                path: '/auth/manager',
                name: 'AuthManager',
                component: () => import('@/views/pages/AuthManager'),
                meta: {
                    public: true
                }
            }*/
            /*{
                path: '/register',
                name: 'Register',
                component: () => import('@/views/pages/Register'),
                meta: {
                    public: true
                }
            },
            ,*/
        ],
    },
]

const router = createRouter({
    history: createWebHashHistory(process.env.BASE_URL),
    routes,
    scrollBehavior() {
        // always scroll to top
        return {top: 0}
    },
})

router.beforeEach((to, from, next) => {
    const rutaPublica = to.matched.some(record => record.meta.public);

    /*console.log(to);
    console.log(rutaPublica);*/
    if (to.path === '/404' || to.path === '/login' || rutaPublica) {
        next();
    }
    else {
        store.dispatch('ValidateLogin', {
            callback: (response) => {

                if (typeof response.logged === 'undefined') {
                    next('/login');
                }
                else {
                    if (!rutaPublica) {
                        // valido si estoy logueado
                        //console.log(store.getters.authLogged);
                        if (store.getters.authLogged) {
                            next();
                        }
                        else {
                            window.location.href = '/#/login';
                            //next('login');
                        }
                    }
                    else {
                        next();
                    }
                }
            }
        });
    }

    /*console.log(rutaPublica);
    return false;*/
    //const isLoginClient = to.matched.some(record => record.meta.loginClient);


});

export default router
