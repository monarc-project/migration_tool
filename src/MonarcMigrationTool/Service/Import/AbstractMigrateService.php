<?php
namespace MonarcMigrationTool\Service\Import;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Console\ColorInterface;
use Zend\Console\Prompt\Confirm;
use Zend\Console\Prompt\Line;
use Zend\Console\Prompt\Password;
use Zend\Db\Adapter\Adapter;

abstract class AbstractMigrateService {
    protected $serviceLocator;
    protected $console;
    protected $dbUsed = 'MonarcCore\Model\Db';

    protected $optionDB = array(
        'driver' => 'Pdo_Mysql',
        'charset' => 'utf8',
        'host' => 'localhost',
        'user' => 'root',
        'dbname' => '',
        'password' => '',
    );
    protected $adapter = null;
    protected $confirmAll = false;

    protected $optionDBCommon = array(
        'driver' => 'Pdo_Mysql',
        'charset' => 'utf8',
        'host' => 'localhost',
        'user' => 'root',
        'dbname' => '',
        'password' => '',
    );
    protected $adapterCommon = null;
    protected $useCommonAdapter = false;

    public function __construct(ServiceLocatorInterface $serviceLocator){
        $this->serviceLocator = $serviceLocator;
        $this->console = $serviceLocator->get('console');

        $this->setUser();
    }

    public function setConfirmAll($confirmAll = false){
        $this->confirmAll = $confirmAll;
        return $this;
    }

    public function getOptionDB(){
        return $this->optionDB;
    }
    public function setOptionDB($opts = []){
        $this->optionDB = array_merge($this->optionDB,$opts);
        // on force > on considère que toutes les infos nécessaires sont présentes et que l'on a pas besoin d'une action utilisateur
        $this->confirmAll = true;
        return $this;
    }

    public function getOptionDBCommon(){
        return $this->optionDBCommon;
    }
    public function setOptionDBCommon($opts = []){
        $this->optionDBCommon = array_merge($this->optionDBCommon,$opts);
        // on force > on considère que toutes les infos nécessaires sont présentes et que l'on a pas besoin d'une action utilisateur
        $this->confirmAll = true;
        return $this;
    }
    public function setUseCommonAdapter($v = false){
        $this->useCommonAdapter = $v;
        return $this;
    }

    public function migrate(){
        if(!$this->checkNewConnection()){
            return;
        }
        if(!$this->getOldConnection()){
            return;
        }
        if($this->useCommonAdapter){
            if(!$this->getOldCommonConnection()){
                return;
            }
        }

        $this->deleteDatas();

        $this->importDatas();
    }

    protected function title(){
        $this->console->write("Migrate old Monarc BDD to new version\n\n",ColorInterface::BLUE);
    }

    abstract protected function setUser();

    // check new DB connection
    protected function checkNewConnection(){
        $this->console->write('Check new DB connection: ');
        if($this->useCommonAdapter){
            $dbUse = 'orm_cli';
            try{
                $this->serviceLocator->get('doctrine.entitymanager.orm_cli')->getConnection()->connect();
                $this->dbUsed = 'MonarcCli\Model\Db'; // C'est bien le service porte le même nom pour le FO & BO
            }catch(\Exception $e){
                try{
                    $dbUse = 'orm_default';
                    $this->serviceLocator->get('doctrine.entitymanager.orm_default')->getConnection()->connect();
                }catch(\Exception $e){
                    $this->console->write("ERR\n",ColorInterface::RED);
                    return false;
                }
            }
        }else{
            $dbUse = 'orm_default';
            try{
                $this->serviceLocator->get('doctrine.entitymanager.orm_default')->getConnection()->connect();
            }catch(\Exception $e){
                $this->console->write("ERR\n",ColorInterface::RED);
                return false;
            }
        }

        $this->console->write("OK\n",ColorInterface::GREEN);
        $config = $this->serviceLocator->get('config');
        $this->console->write("Host: ".$config['doctrine']['connection'][$dbUse]['params']['host']."\n");
        $this->console->write("User: ".$config['doctrine']['connection'][$dbUse]['params']['user']."\n");
        $this->console->write("DB name: ".$config['doctrine']['connection'][$dbUse]['params']['dbname']."\n");

        if($this->confirmAll){
            return true;
        }else{
            return Confirm::prompt('Continue ? [y/n]');
        }
    }

    // get/check old DB connection
    protected function getOldConnection(){
        $this->console->write("\nInformations of old DB connection:\n");
        if(!empty($this->optionDB['dbname'])){
            $this->console->write("Host: ".$this->optionDB['host']."\n");
            $this->console->write("User: ".$this->optionDB['user']."\n");
            $this->console->write("DB name: ".$this->optionDB['dbname']."\n");
            $this->console->write("Password: ".str_repeat("*", strlen($this->optionDB['password']))."\n");
        }else{
            $host = Line::prompt('Host (default=localhost): ',true);
            if(!empty($host)){
                $this->optionDB['host'] = $host;
            }
            $user = Line::prompt('User (default=root): ',true);
            if(!empty($user)){
                $this->optionDB['user'] = $user;
            }

            $dbname = Line::prompt('DB name: ',false);
            if(!empty($dbname)){
                $this->optionDB['dbname'] = $dbname;
            }else{
                return false;
            }

            $password = Password::prompt('Password: ',true);
            $this->optionDB['password'] = $password;
        }

        $this->console->write("Check old DB connection: ");
        try{
            $this->adapter = new Adapter($this->optionDB);
            $res = $this->adapter->query('SELECT id FROM `dims_mod_smile_asset` LIMIT 1')->execute();
            $this->console->write("OK\n",ColorInterface::GREEN);
        }catch(\Exception $e){
            $this->console->write("ERR\n",ColorInterface::RED);
            return false;
        }

        return true;
    }

    // get/check old DB Common connection (client & backoffice)
    protected function getOldCommonConnection(){
        $this->console->write("\nInformations of old DB Common connection:\n");
        if(!empty($this->optionDBCommon['dbname'])){
            $this->console->write("Host: ".$this->optionDBCommon['host']."\n");
            $this->console->write("User: ".$this->optionDBCommon['user']."\n");
            $this->console->write("DB name: ".$this->optionDBCommon['dbname']."\n");
            $this->console->write("Password: ".str_repeat("*", strlen($this->optionDBCommon['password']))."\n");
        }else{
            $host = Line::prompt('Host (default=localhost): ',true);
            if(!empty($host)){
                $this->optionDBCommon['host'] = $host;
            }
            $user = Line::prompt('User (default=root): ',true);
            if(!empty($user)){
                $this->optionDBCommon['user'] = $user;
            }

            $dbname = Line::prompt('DB name: ',false);
            if(!empty($dbname)){
                $this->optionDBCommon['dbname'] = $dbname;
            }else{
                return false;
            }

            $password = Password::prompt('Password: ',true);
            $this->optionDBCommon['password'] = $password;
        }

        $this->console->write("Check old DB Common connection: ");
        try{
            $this->adapterCommon = new Adapter($this->optionDBCommon);
            $res = $this->adapterCommon->query('SELECT id FROM `dims_mod_smile_asset` LIMIT 1')->execute();
            $this->console->write("OK\n",ColorInterface::GREEN);
        }catch(\Exception $e){
            $this->console->write("ERR\n",ColorInterface::RED);
            return false;
        }

        return true;
    }

    // List data tables to delete
    abstract protected function listTablesDelete();
    // Delete datas
    protected function deleteDatas(){
        $this->console->write("\nDelete datas:\n");
        $db = $this->serviceLocator->get($this->dbUsed);
        $entityManager = $db->getEntityManager();
        $entityManager->getConnection()->prepare('SET foreign_key_checks = 0')->execute();

        $lst = $this->listTablesDelete();

        foreach($lst as $v){
            $table = $this->serviceLocator->get($v);
            $class = $table->getClass();
            $this->console->write("\t- ".$class." ");
            try{
                $entities = $table->fetchAllObject();
                foreach($entities as $entity){
                    $entityManager->remove($entity);
                }
                $entityManager->flush();
                $this->console->write("OK\n",ColorInterface::GREEN);
            }catch(\Exception $e){
                $this->console->write("ERR\n",ColorInterface::RED);
            }
        }
        $entityManager->getConnection()->prepare('SET foreign_key_checks = 1')->execute();
    }
    
    // List services for import
    abstract protected function listServicesImport();
    // Import datas
    protected function importDatas(){
        $this->console->write("\nImport datas:\n");

        $lst = $this->listServicesImport();
        $corresp = array();
        ini_set('memory_limit','-1');
        foreach($lst as $l){
            if(class_exists($l)){
                set_time_limit(0);
                $migr = new $l($this->serviceLocator, $this->adapter, $this->console, $this->dbUsed, $this->adapterCommon);
                $migr->import($corresp);
                // certains traitements sont très longs, on relance la connexion
                $this->adapter = new Adapter($this->optionDB);
                if($this->useCommonAdapter){
                    // certains traitements sont très longs, on relance la connexion
                    $this->adapterCommon = new Adapter($this->optionDBCommon);
                }
            }
        }
    }
}
