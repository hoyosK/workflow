export default [
    {
        component: 'CNavItem',
        name: 'Panel',
        access: 'panel-productos',
        to: '/panel-productos',
        icon: 'fas fa-check',
    },
    {
        component: 'CNavGroup',
        name: 'Tareas',
        access: 'Tareas',
        to: '',
        icon: 'fas fa-tasks',
        items: [
            {
                component: 'CNavItem',
                name: 'Listado de tareas',
                access: 'tareas/mis-tareas',
                to: '/admin/tareas',
            },
        ],
    },

    {
        component: 'CNavGroup',
        name: 'Inspecciones',
        access: 'Inspecciones',
        to: '',
        icon: 'fas fa-clock',
        items: [
            {
                component: 'CNavItem',
                name: 'Agendar inspección',
                access: 'inpecciones/agendar',
                to: '/inpecciones/agendar',
            },
            /*{
                component: 'CNavItem',
                name: 'Listado de inspecciones',
                access: 'inpecciones/ver',
                to: '/inpecciones/ver',
            },*/
        ],
    },
    {
        component: 'CNavGroup',
        name: 'Administración',
        access: 'Administración',
        to: '',
        icon: 'fas fa-th-large',
        items: [
            {
                component: 'CNavItem',
                name: 'Flujos',
                access: 'admin/flujos',
                to: '/admin/flujos',
            },
            {
                component: 'CNavItem',
                name: 'Páginas',
                access: 'admin/paginas',
                to: '/admin/blog/list',
            },
            {
                component: 'CNavItem',
                name: 'Páginas de ayuda',
                access: 'admin/paginas-ayuda',
                to: '/admin/ayuda/list',
            },
            {
                component: 'CNavItem',
                name: 'Páginas de promociones',
                access: 'admin/paginas-promociones',
                to: '/admin/promociones/list',
            },
            {
                component: 'CNavItem',
                name: 'Plantillas PDF',
                access: 'admin/plantillas-pdf',
                to: '/admin/plantillas-pdf',
            },
            {
                component: 'CNavItem',
                name: 'Variables de sistema',
                access: 'admin/system-vars',
                to: '/admin/system-vars',
            },
            {
                component: 'CNavItem',
                name: 'Catálogos',
                access: 'admin/catalogo/list',
                to: '/admin/catalogo',
            },
            {
                component: 'CNavItem',
                name: 'Tarifas y coberturas',
                access: 'admin/tarifas/coberturas',
                to: '/admin/tarifas/coberturas',
            },
            {
                component: 'CNavItem',
                name: 'Descuentos',
                access: 'admin/descuentos',
                to: '/admin/descuentos',
            },
            {
                component: 'CNavItem',
                name: 'Recargos',
                access: 'admin/recargas/siniestralidad',
                to: '/admin/recargos',
            },
            {
                component: 'CNavItem',
                name: 'Sistema',
                access: 'admin/system',
                to: '/admin/system',
            },
        ],
    },
    {
        component: 'CNavGroup',
        name: 'Usuarios',
        to: '',
        icon: 'fas fa-user',
        access: 'Usuarios',
        items: [
            {
                component: 'CNavItem',
                access: 'users/admin',
                name: 'Administrar usuarios',
                to: '/usuarios/listado',
            },
            {
                component: 'CNavItem',
                access: 'users/admin/estado',
                name: 'Estado de usuarios',
                to: '/usuarios/listado/estado',
            },
            {
                component: 'CNavItem',
                access: 'users/admin/grupos',
                name: 'Canales de usuarios',
                to: '/usuarios/canal/listado',
            },
            {
                component: 'CNavItem',
                access: 'users/admin/grupos',
                name: 'Distribuidores',
                to: '/usuarios/grupo/listado',
            },
            {
                component: 'CNavItem',
                access: 'usuarios/tiendas',
                name: 'Tiendas',
                to: '/usuarios/tiendas',
            },
            {
                component: 'CNavItem',
                access: 'users/role/admin',
                name: 'Administrar roles',
                to: '/usuarios/roles/listado',
            },
            {
                component: 'CNavItem',
                access: 'users/jerarquia/admin',
                name: 'Jerarquía de acceso',
                to: '/usuarios/jerarquia/listado',
            },
        ],
    },
    {
        component: 'CNavGroup',
        name: 'Páginas',
        access: 'Páginas',
        to: '',
        icon: 'fas fa-newspaper',
        items: [
            {
                component: 'CNavItem',
                name: 'Centro de atención',
                access: 'paginas/ver/noti',
                to: '/admin/blog/list',
            },
            {
                component: 'CNavItem',
                name: 'Listado de ayuda',
                access: 'paginas/ver/ayuda',
                to: '/admin/ayuda/list',
            },
            {
                component: 'CNavItem',
                name: 'Listado de promociones',
                access: 'paginas/ver/promociones',
                to: '/admin/promociones/list',
            },
        ],
    },
    {
        component: 'CNavGroup',
        name: 'Reportes',
        access: 'reportes',
        to: '',
        icon: 'fas fa-file-download',
        items: [
            {
                component: 'CNavItem',
                name: 'Generar reporte',
                access: 'reportes/generar',
                to: '/reportes/generar',
            },
            {
                component: 'CNavItem',
                name: 'Tableros',
                access: 'reportes/tableros',
                to: '/reportes/tableros',
            },
            {
                component: 'CNavItem',
                name: 'Configuración',
                access: 'reportes/admin',
                to: '/reportes/configuracion',
            },
        ],
    },
    {
        component: 'CNavGroup',
        name: 'Control Calidad',
        access: 'control-calidad/listado',
        to: '',
        icon: 'fas fa-eye',
        items: [
            {
                component: 'CNavItem',
                name: 'Ver tareas',
                access: 'control-calidad/listado',
                to: '/control-calidad/listado',
            },
        ],
    },
    /*{
        component: 'CNavGroup',
        name: 'Configuración',
        access: 'Configuración',
        to: '',
        icon: 'fas fa-cog',
        items: [
            {
                component: 'CNavItem',
                access: 'configuration',
                name: 'Administrar',
                to: '/configuration/sistema',
            },
        ],
    },*/
]
