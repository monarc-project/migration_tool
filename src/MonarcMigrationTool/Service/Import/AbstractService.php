<?php
namespace MonarcMigrationTool\Service\Import;

abstract class AbstractService {
    protected $serviceLocator;
    protected $adapter = null;
    protected $adapterCommon = null;
    protected $console;
    protected $dbUsed = 'MonarcCore\Model\Db';
    protected $progressBar = null;

    public function __construct($serviceLocator, $adapter, $console, $dbUsed = 'MonarcCore\Model\Db', $adapterCommon = null){
    	$this->serviceLocator = $serviceLocator;
    	$this->adapter = $adapter;
    	$this->console = $console;
    	$this->dbUsed = $dbUsed;
        $this->adapterCommon = $adapterCommon;
    }

    abstract function import(&$corresp = []);

    protected function createProgressbar($nb){
        $adapter  = new \Zend\ProgressBar\Adapter\Console([
            'finishAction' => \Zend\ProgressBar\Adapter\Console::FINISH_ACTION_CLEAR_LINE,
        ]);
        $this->progressBar = new \Zend\ProgressBar\ProgressBar($adapter,0,$nb);
    }
    protected function updateProgressbar($nb){
        $this->progressBar->update($nb);
    }
    protected function finishProgressbar(){
        $this->progressBar->finish();
    }
}
