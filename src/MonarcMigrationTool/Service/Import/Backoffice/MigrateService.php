<?php
namespace MonarcMigrationTool\Service\Import\Backoffice;

use Zend\Console\Prompt\Confirm;
use Zend\Console\ColorInterface;

class MigrateService extends \MonarcMigrationTool\Service\Import\AbstractMigrateService{
    protected function setUser(){
        $user = $this->serviceLocator->get('\MonarcCore\Model\Entity\User');
        $user->set('firstname','Migration');
        $user->set('lastname','Tool');
        $this->serviceLocator->get('\MonarcCore\Service\ConnectedUserService')->setConnectedUser($user);
    }

    protected function listTablesDelete(){
        return [
            '\MonarcBO\Model\Table\ClientTable',
            '\MonarcBO\Model\Table\ServerTable',
        ];
    }

    protected function listServicesImport(){
        return [
        	'\MonarcMigrationTool\Service\Import\Backoffice\ServersService',
            '\MonarcMigrationTool\Service\Import\Backoffice\ClientsService',
        ];
    }
}