<?php
namespace MonarcMigrationTool\Service\Import\Client;

use Zend\Console\Prompt\Confirm;
use Zend\Console\ColorInterface;
use Zend\Db\Adapter\Adapter;

class MigrateService extends \MonarcMigrationTool\Service\Import\AbstractMigrateService{
    protected function setUser(){
        $user = $this->serviceLocator->get('\MonarcFO\Model\Entity\User');
        $user->set('firstname','Migration');
        $user->set('lastname','Tool');
        $this->serviceLocator->get('\MonarcCore\Service\ConnectedUserService')->setConnectedUser($user);
    }

    protected function listTablesDelete(){
        return [
            '\MonarcFO\Model\Table\UserRoleTable',
            '\MonarcFO\Model\Table\UserAnrTable',
            '\MonarcFO\Model\Table\UserTokenTable',
            '\MonarcFO\Model\Table\UserTable',
            '\MonarcFO\Model\Table\SnapshotTable',
            '\MonarcFO\Model\Table\AnrTable',
            '\MonarcFO\Model\Table\AssetTable',
            '\MonarcFO\Model\Table\ThreatTable',
            '\MonarcFO\Model\Table\ThemeTable',
            '\MonarcFO\Model\Table\VulnerabilityTable',
            '\MonarcFO\Model\Table\MeasureTable',
            '\MonarcFO\Model\Table\AmvTable',
            '\MonarcFO\Model\Table\ObjectCategoryTable',
            '\MonarcFO\Model\Table\RolfTagTable',
            '\MonarcFO\Model\Table\RolfRiskTable',
            '\MonarcFO\Model\Table\ObjectTable',
            '\MonarcFO\Model\Table\ObjectObjectTable',
            '\MonarcFO\Model\Table\QuestionTable',
            '\MonarcFO\Model\Table\QuestionChoiceTable',
            '\MonarcFO\Model\Table\RecommandationTable',
            '\MonarcFO\Model\Table\RecommandationHistoricTable',
            '\MonarcFO\Model\Table\RecommandationMeasureTable',
            '\MonarcFO\Model\Table\RecommandationRiskTable',
            '\MonarcFO\Model\Table\InterviewTable',
            '\MonarcFO\Model\Table\DeliveryTable',
            '\MonarcFO\Model\Table\ClientTable',
        ];
    }


    protected function listServicesImport(){
        return [
            '\MonarcMigrationTool\Service\Import\Client\ClientsService',
            '\MonarcMigrationTool\Service\Import\Client\AnrsService',
        ];
    }
    private function getServiceImportAnr(){
        return [
            '\MonarcMigrationTool\Service\Import\Client\AssetsService',
            '\MonarcMigrationTool\Service\Import\Client\ThemesService',
            '\MonarcMigrationTool\Service\Import\Client\ThreatsService',
            '\MonarcMigrationTool\Service\Import\Client\VulnerabilitiesService',
            '\MonarcMigrationTool\Service\Import\Client\MeasuresService',
            '\MonarcMigrationTool\Service\Import\Client\AMVsService',
            '\MonarcMigrationTool\Service\Import\Client\ObjectCategService',
            '\MonarcMigrationTool\Service\Import\Client\RolfTagsService',
            '\MonarcMigrationTool\Service\Import\Client\RolfRisksService',
            '\MonarcMigrationTool\Service\Import\Client\ObjectsService',
            '\MonarcMigrationTool\Service\Import\Client\ObjectObjectsService',
            '\MonarcMigrationTool\Service\Import\Client\QuestionsService',
            '\MonarcMigrationTool\Service\Import\Client\AnrObjectsService',
            '\MonarcMigrationTool\Service\Import\Client\AnrInstancesService',
            '\MonarcMigrationTool\Service\Import\Client\AnrInstanceRisksService',
            '\MonarcMigrationTool\Service\Import\Client\AnrInstanceRiskOpsService',
            '\MonarcMigrationTool\Service\Import\Client\ScalesService',
            '\MonarcMigrationTool\Service\Import\Client\ScaleImpactTypesService',
            '\MonarcMigrationTool\Service\Import\Client\ScaleCommentsService',
            '\MonarcMigrationTool\Service\Import\Client\AnrInstanceConsequencesService',
            '\MonarcMigrationTool\Service\Import\Client\AnrRecommandationsService',
            '\MonarcMigrationTool\Service\Import\Client\AnrRecommandationHistoricsService',
            '\MonarcMigrationTool\Service\Import\Client\AnrRecommandationMeasuresService',
            '\MonarcMigrationTool\Service\Import\Client\AnrRecommandationRisksService',
            '\MonarcMigrationTool\Service\Import\Client\AnrInterviewsService',
            '\MonarcMigrationTool\Service\Import\Client\AnrDeliveriesService',
        ];
    }

    protected function importDatas(){
        $this->console->write("\nImport datas:\n");

        // Import only user & anrs
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

        // Import datas anr
        $output = new \Symfony\Component\Console\Output\NullOutput();
        $inputMetadata = new \Symfony\Component\Console\Input\ArrayInput(array(
           'command' => 'orm:clear-cache:metadata',
        ));
        $inputQuery = new \Symfony\Component\Console\Input\ArrayInput(array(
           'command' => 'orm:clear-cache:query',
        ));
        $inputResult = new \Symfony\Component\Console\Input\ArrayInput(array(
           'command' => 'orm:clear-cache:result',
        ));
        if(!empty($corresp['anrs'])){
            gc_enable();
            $lst = $this->getServiceImportAnr();
            $i = 1;
            foreach($corresp['anrs'] as $oldId => $newId){
                $tmpSharedData = $corresp;
                $this->console->write("\nImport ANR datas $i/".count($corresp['anrs'])." (".$oldId." -> ".$newId.")\n");
                foreach($lst as $l){
                    if(class_exists($l)){
                        set_time_limit(0);
                        $migr = new $l($this->serviceLocator, $this->adapter, $this->console, $this->dbUsed, $this->adapterCommon);
                        $migr->import($tmpSharedData, $oldId);
                        // certains traitements sont très longs, on relance la connexion
                        $this->adapter = new Adapter($this->optionDB);
                        if($this->useCommonAdapter){
                            // certains traitements sont très longs, on relance la connexion
                            $this->adapterCommon = new Adapter($this->optionDBCommon);
                        }
                    }
                }
                $this->serviceLocator->get('\MonarcCli\Model\Db')->getEntityManager()->clear();
                $this->serviceLocator->get('doctrine.cli')->get('orm:clear-cache:metadata')->run($inputMetadata,$output);
                $this->serviceLocator->get('doctrine.cli')->get('orm:clear-cache:query')->run($inputQuery,$output);
                $this->serviceLocator->get('doctrine.cli')->get('orm:clear-cache:result')->run($inputResult,$output);
                $tmpSharedData = null;
                unset($tmpSharedData);
                gc_collect_cycles();
                $i++;
            }
        }
    }
}