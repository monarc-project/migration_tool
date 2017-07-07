<?php
namespace MonarcMigrationTool\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Console\Request as ConsoleRequest;

use Zend\Console\Exception\RuntimeException as ConsoleException;

use \MonarcMigrationTool\Service\MigrateService;

class MigrateController extends AbstractActionController
{
    protected $migrateService;

    public function __construct(MigrateService $migrateService){
        $this->migrateService = $migrateService;
    }

    public function indexAction(){
        $request = $this->getRequest();
        if (!$request instanceof ConsoleRequest) {
            throw new RuntimeException('You can only use this action from a console!');
        }
        try {
            $this->migrateService->migrate($request);
        } catch (ConsoleException $e) {
            // Could not get console adapter - most likely we are not running inside a console window.
        }
    }
}
