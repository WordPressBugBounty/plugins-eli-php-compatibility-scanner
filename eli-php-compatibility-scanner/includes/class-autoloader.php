<?php

/**
 * Autoloader Class for PHP Compatibility Checker
 *
 * Handles automatic class loading for the PHP compatibility checker plugin
 * using PSR-4 autoloading standards with WordPress naming conventions.
 */

namespace EH\PHPCompatibilityChecker;

/**
 * Autoloader class for handling class file loading.
 */
class Autoloader
{

    /**
     * Register the autoloader with PHP.
     *
     * @return void
     */
    public static function register()
    {
        spl_autoload_register([__CLASS__, 'autoload']);
    }

    /**
     * Autoload callback function.
     *
     * @param string $class The fully-qualified class name.
     * @return void
     */
    public static function autoload($class)
    {
        $prefix   = 'EH\\PHPCompatibilityChecker\\';
        $base_dir = __DIR__ . '/';

        if (strncmp($prefix, $class, strlen($prefix)) === 0) {
            $relative_class = substr($class, strlen($prefix));

            $file_mappings = array(
                'Plugin' => 'class-plugin.php',
                'Admin\\AdminPage' => 'Admin/class-adminpage.php',
                'Admin\\Promo' => 'Admin/class-promo.php',
                'Ajax\\AjaxHandler' => 'Ajax/class-ajaxhandler.php',
                'Core\\CommandBuilder' => 'Core/class-commandbuilder.php',
                'Core\\FileManager' => 'Core/class-filemanager.php',
                'Core\\Scanner' => 'Core/class-scanner.php',
                'Core\\ScanSession' => 'Core/class-scansession.php',
            );

            if (isset($file_mappings[$relative_class])) {
                $file = $base_dir . $file_mappings[$relative_class];
                if (file_exists($file)) {
                    require $file;
                    return;
                }
            }

            $file = $base_dir . 'class-' . strtolower(str_replace('\\', '-', $relative_class)) . '.php';
            if (file_exists($file)) {
                require $file;
                return;
            }

            $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
            if (file_exists($file)) {
                require $file;
                return;
            }
        }
    }
}
