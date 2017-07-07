<?php
namespace MonarcMigrationTool\Service;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Console\Request as ConsoleRequest;

class MigrateService {
    protected $serviceLocator;

    public function __construct(ServiceLocatorInterface $serviceLocator){
        $this->serviceLocator = $serviceLocator;
    }

    public function migrate(ConsoleRequest $request){
        $debug = $request->getParam('debug') || $request->getParam('d');
        if($debug){
            error_reporting(E_ALL | E_STRICT);
        }else{
            error_reporting(E_ERROR);
        }

        $mode = $request->getParam('mode','common');
        switch ($mode) {
            default:
            case 'common':
                $m = new \MonarcMigrationTool\Service\Import\Common\MigrateService($this->serviceLocator);
                break;
            case 'client':
                $m = new \MonarcMigrationTool\Service\Import\Client\MigrateService($this->serviceLocator);
                break;
            case 'backoffice':
                $m = new \MonarcMigrationTool\Service\Import\Backoffice\MigrateService($this->serviceLocator);
                break;
        }

        $yes = $request->getParam('yes') || $request->getParam('y');
        $m->setConfirmAll($yes);

        $dbname = $request->getParam('dbname');
        if(!empty($dbname)){
            $opts = $m->getOptionDB();
            $opts['dbname'] = $dbname;
            $opts['host'] = $request->getParam('host','localhost');
            $opts['user'] = $request->getParam('user','root');
            $opts['password'] = $request->getParam('password','');
            $m->setOptionDB($opts);
        }

        if($mode == 'client' || $mode == 'backoffice'){
            $m->setUseCommonAdapter(true);
            // On a besoin de la connexion à la common
            $dbnamec = $request->getParam('dbnamec');
            if(!empty($dbnamec)){
                $opts = $m->getOptionDB();
                $opts['dbname'] = $dbnamec;
                $opts['host'] = $request->getParam('hostc','localhost');
                $opts['user'] = $request->getParam('userc','root');
                $opts['password'] = $request->getParam('passwordc','');
                $m->setOptionDBCommon($opts);
            }
        }

        $m->migrate();
    }
}
