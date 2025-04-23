<?php
define("LgcAppTitle", 'Cotizador - El Roble');
define("LgcAppSlug", 'ELROBLE-GASTOSMEDICOS');
define("LgcAppKeywords", 'El Roble');
define("LgcAppDescription", 'Cotizador - El Roble');
define("LgcLogoPath", '_customAssets/img/logo-elroble.png');
define("LgcColorPrimary", '#8f9092');
define("LgcFooterText", ' '.date('Y'));


$menuConfig = [
    [
        'name' => 'Inicio',
        'class' => 'nav-item',
        'route' => '/',
        'icon' => 'home',
    ],
    [
        'name' => 'Reglas',
        'class' => 'nav-item',
        'route' => '#',
        'icon' => 'check-square',
        'access' => 'reglas-esquemas',
        'childs' => [
            [
                'name' => 'Esquemas',
                'route' => '/esquema/list',
                'icon' => 'settings',
                'class' => 'nav-link',
            ],
            [
                'name' => 'Reglas',
                'route' => '/rules/list',
                'icon' => 'settings',
                'class' => 'nav-link',
            ],
        ]
    ],
    [
        'name' => 'Carga de datos',
        'class' => 'nav-item',
        'route' => '#',
        'icon' => 'upload',
        'access' => 'carga-datos/role/admin',
        'childs' => [
            [
                'name' => 'Fuente de datos',
                'route' => '/files/list',
                'icon' => 'settings',
                'class' => 'nav-link',
            ],
        ]
    ],
    [
        'name' => 'Lotes',
        'class' => 'nav-item',
        'route' => '#',
        'icon' => 'box',
        'access' => 'lotes-listado',
        'childs' => [
            [
                'name' => 'Listado de lotes',
                'route' => '/lotes/list',
                'icon' => 'circle',
                'class' => 'nav-link',
            ],
            [
                'name' => 'Archivo 390',
                'route' => '/lotes/archivo-390',
                'icon' => 'circle',
                'class' => 'nav-link',
            ],
        ]
    ],
    [
        'name' => 'Usuarios',
        'class' => 'nav-group',
        'route' => '/',
        'icon' => 'users',
        'access' => 'users/admin',
        'childs' => [
            [
                'name' => 'Usuarios',
                'route' => '/users/list',
                'class' => 'nav-link',
                'icon' => 'circle',
            ],
            [
                'name' => 'Roles',
                'route' => '/users/role/list',
                'class' => 'nav-link',
                'icon' => 'circle',
            ],
        ]
    ],
    [
        'name' => 'Mis comisiones',
        'class' => 'nav-group',
        'route' => '/',
        'icon' => 'user',
        'access' => 'mis-comisiones',
        'childs' => [
            [
                'name' => 'Ver mis comisiones',
                'route' => '/miscomisiones',
                'class' => 'nav-link',
                'icon' => 'circle',
            ],
        ]
    ],
];
define("LgcMenu", $menuConfig);
