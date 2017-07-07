<?php
namespace MonarcMigrationTool\Controller;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use \MonarcMigrationTool\Service\MigrateService;
use \MonarcMigrationTool\Controller\MigrateController;

class MigrateControllerFactory implements FactoryInterface{
    public function createService(ServiceLocatorInterface $serviceLocator){
        return new MigrateController($serviceLocator->getServiceLocator()->get(MigrateService::class));
    }
}
