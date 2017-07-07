<?php
namespace MonarcMigrationTool\Service\Import\Common;

use Zend\Console\ColorInterface;

class GuidesService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = []){
        $res = $this->adapter->query('SELECT * FROM `dims_mod_smile_anr_guide` ORDER BY id ASC')->execute();
        $table = $this->serviceLocator->get('\MonarcCore\Model\Table\GuideTable');
        $guideService = $this->serviceLocator->get('MonarcCore\Service\GuideService');
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
            $corresp['guides'][$r['id']] = $guideService->create(array(
                'type' => $r['id_categ'],
                'description1' => $r['description'],
                'description2' => '',
                'description3' => '',
                'description4' => '',
                'isWithItems' => $r['mode'],
            ));

            $compt++;
            $this->updateProgressbar($compt);
        }
        $this->finishProgressbar();
        $this->console->write("\t\tOK",ColorInterface::GREEN);
        $this->console->write(" $compt/".count($res)." elements\n");
    }
}
