<?php
// incluyo las configuraciones del global core
if (file_exists('../global-core-config/appConfig.php')) {
    include_once ('../global-core-config/appConfig.php');
    include_once ('../global-core-config/accessConfig.php');
}

// Include vue submodules
$modulesDirectory = "_customVue/modules";

$vueComponents = [];
if ( is_dir($modulesDirectory)) {
    if ($modules = opendir($modulesDirectory)) {
        while ($module = readdir($modules)) {
            if ($module == ".." || $module == ".") continue;

            if ( is_dir("{$modulesDirectory}/{$module}")) {
                if ($views = opendir("{$modulesDirectory}/{$module}")) {
                    while ($view = readdir($views)) {
                        if ($view == ".." || $view == ".") continue;

                        $name = str_replace('.vue', '', $view);
                        $componentName = "{$module}-{$name}";
                        $componentPath = "/{$modulesDirectory}/{$module}/{$view}";
                        $vueComponents[$componentName] = $componentPath;
                    }
                    closedir($views);
                }
            }
        }
        closedir($modules);
    }
}

define('LgcVueComponents', $vueComponents);
