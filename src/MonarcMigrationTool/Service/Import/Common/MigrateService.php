<?php
namespace MonarcMigrationTool\Service\Import\Common;

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
            '\MonarcCore\Model\Table\InstanceConsequenceTable',
            '\MonarcCore\Model\Table\InstanceRiskOpTable',
            '\MonarcCore\Model\Table\InstanceRiskTable',
            '\MonarcCore\Model\Table\ScaleCommentTable',
            '\MonarcCore\Model\Table\ScaleImpactTypeTable',
            '\MonarcCore\Model\Table\ScaleTable',
            '\MonarcCore\Model\Table\InstanceTable',
            '\MonarcCore\Model\Table\AnrTable',
            '\MonarcCore\Model\Table\AssetTable',
            '\MonarcCore\Model\Table\ThreatTable',
            '\MonarcCore\Model\Table\ThemeTable',
            '\MonarcCore\Model\Table\VulnerabilityTable',
            '\MonarcCore\Model\Table\MeasureTable',
            '\MonarcCore\Model\Table\AmvTable',
            '\MonarcCore\Model\Table\ModelTable',
            '\MonarcCore\Model\Table\ObjectCategoryTable',
            '\MonarcCore\Model\Table\RolfTagTable',
            '\MonarcCore\Model\Table\RolfRiskTable',
            '\MonarcCore\Model\Table\ObjectTable',
            '\MonarcCore\Model\Table\ObjectObjectTable',
            '\MonarcCore\Model\Table\QuestionTable',
            '\MonarcCore\Model\Table\QuestionChoiceTable',
            '\MonarcCore\Model\Table\GuideTable',
            '\MonarcCore\Model\Table\GuideItemTable',
            '\MonarcCore\Model\Table\HistoricalTable',
        ];
    }

    protected function listServicesImport(){
        return [
            '\MonarcMigrationTool\Service\Import\Common\ModelsService',
            '\MonarcMigrationTool\Service\Import\Common\AssetsService',
            '\MonarcMigrationTool\Service\Import\Common\ThemesService',
            '\MonarcMigrationTool\Service\Import\Common\ThreatsService',
            '\MonarcMigrationTool\Service\Import\Common\VulnerabilitiesService',
            '\MonarcMigrationTool\Service\Import\Common\MeasuresService',
            '\MonarcMigrationTool\Service\Import\Common\AMVsService',
            '\MonarcMigrationTool\Service\Import\Common\ObjectCategService',
            '\MonarcMigrationTool\Service\Import\Common\RolfTagsService',
            '\MonarcMigrationTool\Service\Import\Common\RolfRisksService',
            '\MonarcMigrationTool\Service\Import\Common\ObjectsService',
            '\MonarcMigrationTool\Service\Import\Common\ObjectObjectsService',
            '\MonarcMigrationTool\Service\Import\Common\QuestionsService',
            '\MonarcMigrationTool\Service\Import\Common\AnrObjectsService',
            '\MonarcMigrationTool\Service\Import\Common\AnrInstancesService',
            '\MonarcMigrationTool\Service\Import\Common\AnrInstanceRisksService',
            '\MonarcMigrationTool\Service\Import\Common\AnrInstanceRiskOpsService',
            '\MonarcMigrationTool\Service\Import\Common\ScalesService',
            '\MonarcMigrationTool\Service\Import\Common\ScaleImpactTypesService',
            '\MonarcMigrationTool\Service\Import\Common\ScaleCommentsService',
            '\MonarcMigrationTool\Service\Import\Common\AnrInstanceConsequencesService',
            '\MonarcMigrationTool\Service\Import\Common\GuidesService',
            '\MonarcMigrationTool\Service\Import\Common\GuideItemsService',
        ];
    }
}