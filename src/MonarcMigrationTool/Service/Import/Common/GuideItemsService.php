<?php
namespace MonarcMigrationTool\Service\Import\Common;

use Zend\Console\ColorInterface;

class GuideItemsService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = []){
        $res = $this->adapter->query('SELECT * FROM `dims_mod_smile_anr_guide_item` ORDER BY id_anr_guide ASC, position ASC')->execute();
        $table = $this->serviceLocator->get('\MonarcCore\Model\Table\GuideItemTable');
        $guideService = $this->serviceLocator->get('MonarcCore\Service\GuideItemService');
        $c = $table->getClass();

        $this->console->write("\t- ".$c.":\n");

        if(!class_exists($c)){
            $this->console->write("\t\tERR\n",ColorInterface::RED);
            return false;
        }

        $compt = 0;
        $this->createProgressbar(count($res));
        foreach($res as $r){
            $entity = new $c();
            $entity->setDbAdapter($this->serviceLocator->get($this->dbUsed));
            $guideService->set('entity',$entity);
            $guideService->create(array(
                'guide' => $corresp['guides'][$r['id_anr_guide']],
                'description1' => $r['description'],
                'description2' => '',
                'description3' => '',
                'description4' => '',
                'implicitPosition' => \MonarcCore\Model\Entity\AbstractEntity::IMP_POS_END,
            ));

            $compt++;
            $this->updateProgressbar($compt);
        }
        $this->finishProgressbar();
        $this->console->write("\t\tOK",ColorInterface::GREEN);
        $this->console->write(" $compt/".count($res)." elements\n");
    }
}
