<?php

/**
 * This file is part of DSJAS
 * Written and maintained by the DSJAS project.
 *
 * Copyright (C) 2020 - Ethan Marshall
 *
 * DSJAS is free software which is licensed and distributed under
 * the terms of the MIT software licence.
 * Exact terms can be found in the LICENCE file.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * above mentioned licence for specific details.
 */

require_once "Customization.php";
require_once "Util.php";

require_once "vendor/hooks/src/gburtini/Hooks/Hooks.php";


define("MODULE_PATH", "/admin/site/modules/");
define("DEFAULT_MODULE", "example");

define("MODULE_CONFIG_FILE_NAME", "config.json");


class ModuleManager
{
    private $currentFile;

    private $loadedModules = [];
    private $loadedModuleInfo = [];

    private $loadedModuleRoutes = [];
    private $loadedModuleText = [];

    private $configuration;

    function __construct($file = "index")
    {
        $this->currentFile = $file;

        $this->configuration = new Configuration(true, false, false, true);

        $modules = scandir(ABSPATH . MODULE_PATH);

        foreach ($modules as $module) {
            $this->loadModule($module);
        }
    }

    function __destruct()
    {
    }

    function getModules()
    {
        foreach ($this->loadedModules as $module) {
            $this->getModule($module);
        }
    }

    function getModule($moduleName)
    {
        foreach ($this->loadedModuleRoutes[$moduleName] as $route) {
            if (!$this->shouldLoadModule($moduleName)) {
                continue;
            }

            if ($this->loadedModuleInfo[$moduleName]["hooks"][$route]["loadCSS"]) {
                echo ("<style>\n");

                echo ($this->loadedModuleText[$moduleName][$route]["style"]);

                echo ("\n</style>");
            }

            if ($this->loadedModuleInfo[$moduleName]["hooks"][$route]["loadJS"]) {
                echo ("<script>\n");

                echo ($this->loadedModuleText[$moduleName][$route]["JS"]);

                echo ("\n</script>");
            }

            if ($this->loadedModuleInfo[$moduleName]["hooks"][$route]["loadHTML"]) {
                echo ($this->loadedModuleText[$moduleName][$route]["HTML"]);
            }
        }
    }

    function getModuleRoute($moduleName, $route)
    {
        if (!$this->shouldLoadModule($moduleName)) {
            return;
        }

        if ($this->loadedModuleInfo[$moduleName]["hooks"][$route]["loadCSS"]) {
            echo ("<style>\n");

            echo ($this->loadedModuleText[$moduleName][$route]["style"]);

            echo ("\n</style>");
        }

        if ($this->loadedModuleInfo[$moduleName]["hooks"][$route]["loadJS"]) {
            echo ("<script>\n");

            echo ($this->loadedModuleText[$moduleName][$route]["JS"]);

            echo ("\n</script>");
        }

        if ($this->loadedModuleInfo[$moduleName]["hooks"][$route]["loadHTML"]) {
            echo ($this->loadedModuleText[$moduleName][$route]["HTML"]);
        }
    }

    function getAllByCallback($callbackName)
    {
        foreach ($this->loadedModules as $module) {
            foreach ($this->loadedModuleRoutes[$module] as $route) {
                if ($this->loadedModuleInfo[$module]["hooks"][$route]["triggerEvent"] == $callbackName) {
                    $this->getModuleRoute($module, $route);
                }
            }
        }
    }

    function processModules(callable $callback)
    {
        $displayEvent = "module_hook_event";
        \gburtini\Hooks\Hooks::bind($displayEvent, $callback);
    }

    function getLoadedModules()
    {
        return $this->loadedModules;
    }

    function getModuleInfo($moduleName, $infoKey)
    {
        if (!in_array($moduleName, $this->loadedModules)) {
            return false;
        }

        return $this->loadedModuleInfo[$moduleName][$infoKey];
    }

    private function loadModule($moduleName)
    {
        if (is_file(ABSPATH . MODULE_PATH . $moduleName)) {
            return false;
        }

        if ($moduleName == "." || $moduleName == "..") {
            return false;
        }

        if (!$this->configuration->getKey(ID_MODULE_CONFIG, "active_modules", $moduleName)) {
            return false;
        }

        array_push($this->loadedModules, $moduleName);

        $moduleConfigText = file_get_contents(ABSPATH . MODULE_PATH . $moduleName . "/" . MODULE_CONFIG_FILE_NAME);
        $this->loadedModuleInfo[$moduleName] = json_decode($moduleConfigText, true);

        $this->loadedModuleRoutes[$moduleName] = $this->loadRoutes($moduleName);

        foreach ($this->loadedModuleRoutes[$moduleName] as $route) {
            if ($this->loadedModuleInfo[$moduleName]["hooks"][$route]["loadCSS"]) {
                $this->loadedModuleText[$moduleName][$route]["style"] =
                    file_get_contents(ABSPATH . MODULE_PATH . $moduleName . "/" . $route . "/content.css");
            }

            if ($this->loadedModuleInfo[$moduleName]["hooks"][$route]["loadJS"]) {
                $this->loadedModuleText[$moduleName][$route]["JS"] =
                    file_get_contents(ABSPATH . MODULE_PATH . $moduleName . "/" . $route . "/content.js");
            }

            if ($this->loadedModuleInfo[$moduleName]["hooks"][$route]["loadHTML"]) {
                $this->loadedModuleText[$moduleName][$route]["HTML"] =
                    file_get_contents(ABSPATH . MODULE_PATH . $moduleName . "/" . $route . "/content.html");
            }
        }

        return true;
    }

    private function loadRoutes($moduleName)
    {
        $routes = scandir(ABSPATH . MODULE_PATH . $moduleName . "/");

        $result = [];

        foreach ($routes as $route) {
            if (is_file(ABSPATH . MODULE_PATH . $moduleName . "/" . $route)) {
                continue;
            }

            if ($route == "." || $route == "..") {
                continue;
            }

            array_push($result, $route);
        }

        return $result;
    }

    private function shouldLoadModule($moduleName)
    {
        if (isset($this->loadedModuleInfo[$moduleName]["fileFilter"])) {
            $wantedFiles = $this->loadedModuleInfo[$moduleName]["fileFilter"];

            if (!in_array($this->currentFile, $wantedFiles)) {
                return false;
            }
        }

        return true;
    }
}
