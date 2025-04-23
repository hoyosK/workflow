<?php
// apps
$accessConfigModule[] = [
    'module' => 'Panel',
    'access' => [
        [
            'name' => 'Panel de productos',
            'slug' => 'panel',
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
            'name' => 'Plantillas PDF',
            'slug' => 'admin/plantillas-pdf',
        ],
        [
            'name' => 'Variables de sistema',
            'slug' => 'admin/system-vars',
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
    ],
];

// apps
$accessConfigModule[] = [
    'module' => 'Páginas',
    'access' => [
        [
            'name' => 'Ver ',
            'slug' => 'paginas/ver/noti',
        ],
        [
            'name' => 'Ver páginas de noticias',
            'slug' => 'paginas/ver/noti',
        ],
        [
            'name' => 'Administrar noticias',
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
            'name' => 'Administrar canales de usuario',
            'slug' => 'users/admin/canales',
        ],
        [
            'name' => 'Administrar distribuidores',
            'slug' => 'users/admin/grupos',
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
            'name' => 'Jerarquia de usuarios',
            'slug' => 'users/jerarquia/admin',
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
