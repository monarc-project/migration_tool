<?php
namespace MonarcMigrationTool;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\Feature\ConsoleUsageProviderInterface;
use Zend\Console\Adapter\AdapterInterface as Console;
use Zend\Mvc\MvcEvent;

class Module implements
    AutoloaderProviderInterface,
    ConfigProviderInterface,
    ConsoleUsageProviderInterface
{

    private $serviceManager;

    public function onBootstrap(MvcEvent $e){
        $this->serviceManager = $e->getApplication()->getServiceManager();
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            // ./vendor/bin/classmap_generator.php --library module/MonarcMigrationTool/src/MonarcMigrationTool -w -s -o module/MonarcMigrationTool/autoload_classmap.php
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            /*'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),*/
        );
    }

    public function getConsoleUsage(Console $console)
    {
        return array(
            "Migration Tools for Monarc version 1.0.2\n",
            'monarc:migrate' => 'Execute migration',
            ['--debug|-d', 'Display all errors & warnings (default: false)'],
            ['--yes|-y', 'Automatically answers "yes" to questions'],
            ['--mode=client|common|backoffice', 'Import old datas from common or client (default=common)'],
            'Specify connection information for old database (optional)',
            ['--dbname', 'DB name (mandatory), set "--yes" parameter'],
            ['--host', 'Host (default=localhost & optional)'],
            ['--user', 'User (default=root & optional)'],
            ['--password', 'Password (default=<empty> & optional)'],
            'Specify connection information for old Common database use for backoffice & client migration (optional)',
            ['--dbnamec', 'DB name (mandatory), set "--yes" parameter'],
            ['--hostc', 'Host (default=localhost & optional)'],
            ['--userc', 'User (default=root & optional)'],
            ['--passwordc', 'Password (default=<empty> & optional)'],
        );
    }
}
