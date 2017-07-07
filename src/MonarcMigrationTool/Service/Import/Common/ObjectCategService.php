<?php
namespace MonarcMigrationTool\Service\Import\Common;

use Zend\Console\ColorInterface;

class ObjectCategService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = []){
        $res = $this->adapter->query('SELECT * FROM `dims_mod_smile_biblio_object_categories` ORDER BY root_reference ASC, parent_id ASC, position ASC')->execute();
        $table = $this->serviceLocator->get('\MonarcCore\Model\Table\ObjectCategoryTable');
        $ocService = $this->serviceLocator->get('MonarcCore\Service\ObjectCategoryService');
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
            $ocService->set('entity',$entity);
            $corresp['categs'][$r['id']] = $ocService->create(array(
                'root' => (!empty($r['root_reference']) && isset($corresp['categs'][$r['root_reference']]['id']) ? $corresp['categs'][$r['root_reference']]['id'] : null),
                'parent' => (!empty($r['parent_id']) && isset($corresp['categs'][$r['parent_id']]['id']) ? $corresp['categs'][$r['parent_id']]['id'] : null),
                'label1' => $r['label'],
                'label2' => '',
                'label3' => '',
                'label4' => '',
                'position' => $r['position'],
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