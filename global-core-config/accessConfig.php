<?php
// apps
$accessConfigModule[] = [
    'module' => 'Panel',
    'access' => [
        [
            'name' => 'Panel de productos',
            'slug' => 'panel-productos',
        ],
    ],
];

$accessConfigModule[] = [
    'module' => 'Inspecciones',
    'access' => [
        [
            'name' => 'Agendar',
            'slug' => 'inpecciones/agendar',
        ],
        [
            'name' => 'Ver mis inspecciones',
            'slug' => 'inpecciones/ver',
        ],
        [
            'name' => 'Ver y agendar inspección en flujo',
            'slug' => 'inpecciones/agendar-en-flujo',
        ],
    ],
];

$accessConfigModule[] = [
    'module' => 'Tareas',
    'access' => [
        [
            'name' => 'Mis tareas',
            'slug' => 'tareas/mis-tareas',
        ],
        [
            'name' => 'Listar flujos',
            'slug' => 'tareas/listar/flujo',
        ],
        [
            'name' => 'Iniciar cotizaciones nuevas',
            'slug' => 'tareas/admin/start-cot',
        ],
        [
            'name' => 'Subir archivos en tarea',
            'slug' => 'tareas/admin/uploadfiles',
        ],
        [
            'name' => 'Edición de usuario asignado',
            'slug' => 'tareas/admin/usuario-asignado',
        ],
        [
            'name' => 'Edición de estado manual',
            'slug' => 'tareas/admin/estado-manual',
        ],
        [
            'name' => 'Cambiar tarea de paso',
            'slug' => 'tareas/admin/cambio-paso',
        ],
        [
            'name' => 'Reactivar cotizaciones expiradas',
            'slug' => 'tareas/admin/revivir-cot',
        ],
        [
            'name' => 'Operar cotizaciones expiradas',
            'slug' => 'tareas/admin/operar-expirado',
        ],
        [
            'name' => 'Ver bitácora de flujo',
            'slug' => 'admin/show-bitacora',
        ],
        [
            'name' => 'Ver bitácora de proceso',
            'slug' => 'admin/show-bitacora-process',
        ],
        [
            'name' => 'Ver datos en modo pruebas',
            'slug' => 'admin/show-test-mode',
        ],
        [
            'name' => 'Ver resumen de flujo',
            'slug' => 'admin/show-resumen',
        ],
        [
            'name' => 'Ver archivos generados',
            'slug' => 'admin/show-adgen',
        ],
        [
            'name' => 'Ver adjuntos subidos',
            'slug' => 'admin/show-adj',
        ],
        [
            'name' => 'Ver campos ocultos en resumen',
            'slug' => 'admin/show-hidden-fields',
        ],
        [
            'name' => 'Habilitar área informativa de asignación de usuario',
            'slug' => 'admin/show-assi-usr',
        ],
        [
            'name' => 'Ver tareas sin usuario asignado',
            'slug' => 'tareas/non/user',
        ],
        [
            'name' => 'Ver codigo de intermediario en tareas',
            'slug' => 'tareas/show-cod-ag',
        ],
        [
            'name' => 'Ver estado de tareas',
            'slug' => 'tareas/show-status',
        ],
        [
            'name' => 'Habilitar botón de reiniciar',
            'slug' => 'tareas/show-button-re-start',
        ],
        [
            'name' => 'Habilitar modo pruedas',
            'slug' => 'tareas/modo-pruebas',
        ],
        [
            'name' => 'Habilitar botón de copiar cotización',
            'slug' => 'tareas/show-button-copy-coti',
        ],
        [
            'name' => 'Habilitar botón "ver en workflow" en barra de soporte',
            'slug' => 'tareas/show-in-wk',
        ],
       /*
        [
            'name' => 'Habilitar botón de ver progresión',
            'slug' => 'tareas/show-button-progression',
        ],
        [
            'name' => 'Habilitar botón de ver adjuntos',
            'slug' => 'tareas/show-button-adj',
        ], */
    ],
];


$accessConfigModule[] = [
    'module' => 'Adjuntos',
    'access' => [
        [
            'name' => 'Forzar descarga de adjuntos mediante proxy',
            'slug' => 'adj/force/proxy',
        ],
    ],
];

// apps
$accessConfigModule[] = [
    'module' => 'Administración',
    'access' => [
        [
            'name' => 'Edición de flujos',
            'slug' => 'admin/flujos',
        ],
        [
            'name' => 'Ver noticias',
            'slug' => 'ver/paginas',
        ],
        [
            'name' => 'Plantillas PDF',
            'slug' => 'admin/plantillas-pdf',
        ],
        [
            'name' => 'Variables de sistema',
            'slug' => 'admin/system-vars',
        ],
        [
            'name' => 'Catálogos',
            'slug' => 'admin/catalogo/list',
        ],
        [
            'name' => 'Editar y sincronizar catálogos',
            'slug' => 'admin/catalogo/sync',
        ],
        [
            'name' => 'Editar tarifas y coberturas',
            'slug' => 'admin/tarifas/coberturas',
        ],
        [
            'name' => 'Descuentos',
            'slug' => 'admin/descuentos',
        ],
        [
            'name' => 'Recargos',
            'slug' => 'admin/recargas/siniestralidad',
        ],
        [
            'name' => 'Sistema',
            'slug' => 'admin/system',
        ],
    ],
];


// apps
$accessConfigModule[] = [
    'module' => 'Reportes',
    'access' => [
        [
            'name' => 'Módulo de reportes',
            'slug' => 'reportes',
        ],
        [
            'name' => 'Listar reportes',
            'slug' => 'reportes/listar',
        ],
        [
            'name' => 'Configuración',
            'slug' => 'reportes/admin',
        ],
        [
            'name' => 'Generar reporte',
            'slug' => 'reportes/generar',
        ],
        [
            'name' => 'Tableros',
            'slug' => 'reportes/tableros',
        ],
    ],
];

// usuarios
$accessConfigModule[] = [
    'module' => 'Usuarios',
    'access' => [
        [
            'name' => 'Administrar roles',
            'slug' => 'users/role/admin',
        ],
        [
            'name' => 'Administrar canales',
            'slug' => 'users/admin/canales',
        ],
        [
            'name' => 'Administrar distribuidores',
            'slug' => 'users/admin/grupos',
        ],
        [
            'name' => 'Administrar tiendas',
            'slug' => 'usuarios/tiendas',
        ],
        [
            'name' => 'Administrar usuarios',
            'slug' => 'users/admin',
        ],
        [
            'name' => 'Ver Usuarios (Listar usuarios)',
            'slug' => 'users/listar',
        ],
        [
            'name' => 'Editar estado fuera de oficina',
            'slug' => 'users/admin/estado',
        ],
        [
            'name' => 'Jerarquia de usuarios',
            'slug' => 'users/jerarquia/admin',
        ],
    ],
];

$accessConfigModule[] = [
    'module' => 'Páginas',
    'access' => [
        [
            'name' => 'Ver páginas de centro de atención',
            'slug' => 'paginas/ver/noti',
        ],
        [
            'name' => 'Administrar páginas de centro de atención',
            'slug' => 'paginas/admin/noti',
        ],
        [
            'name' => 'Ver páginas de ayuda',
            'slug' => 'paginas/ver/ayuda',
        ],
        [
            'name' => 'Administrar ayuda',
            'slug' => 'paginas/admin/ayuda',
        ],
        [
            'name' => 'Ver páginas de promociones',
            'slug' => 'paginas/ver/promociones',
        ],
        [
            'name' => 'Administrar promociones',
            'slug' => 'paginas/admin/promociones',
        ],
    ],
];

$accessConfigModule[] = [
    'module' => 'Control Calidad',
    'access' => [
        [
            'name' => 'Tareas',
            'slug' => 'control-calidad/listado',
        ],
        [
            'name' => 'Fichas de control',
            'slug' => 'control-calidad/ficha',
        ],
    ],
];


// Configuración
/*$accessConfigModule[] = [
    'module' => 'Configuración',
    'access' => [
        [
            'name' => 'Editar configuración',
            'slug' => 'configuration',
        ],
    ],
];*/

define("LgcAccessConfig", $accessConfigModule);
