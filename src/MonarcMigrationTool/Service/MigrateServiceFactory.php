<?php
namespace MonarcMigrationTool\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class MigrateServiceFactory implements FactoryInterface{
    public function createService(ServiceLocatorInterface $serviceLocator){
        return new MigrateService($serviceLocator);
    }
}

