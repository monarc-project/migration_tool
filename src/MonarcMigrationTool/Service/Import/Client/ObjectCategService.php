<?php
namespace MonarcMigrationTool\Service\Import\Client;

use Zend\Console\ColorInterface;

class ObjectCategService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = [], $oldAnr = null){
        if(!empty($corresp['anrs'][$oldAnr])){
            $res = $this->adapter->query('
                SELECT *
                FROM `dims_mod_smile_biblio_object_categories`
                WHERE anr_id = '.$oldAnr.'
                ORDER BY anr_id ASC, root_reference ASC, parent_id ASC, position ASC
            ')->execute();
            $table = $this->serviceLocator->get('\MonarcFO\Model\Table\ObjectCategoryTable');
            $ocService = $this->serviceLocator->get('MonarcFO\Service\ObjectCategoryService');
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
                $db = $this->serviceLocator->get($this->dbUsed);
                $db->getEntityManager();
                $entity->setDbAdapter($db);
                $ocService->set('entity',$entity);
                $corresp['categs'][$r['id']] = $ocService->create(array(
                    'anr' => $corresp['anrs'][$r['anr_id']],
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
                $db->getEntityManager()->detach($entity);
                unset($entity,$db);
            }
            $this->finishProgressbar();
            $this->console->write("\t\tOK",ColorInterface::GREEN);
            $this->console->write(" $compt/".count($res)." elements\n");
            unset($res,$compt,$res,$table,$ocService,$c);
        }else{
            $this->console->write("\t\tNo ANR imported\n",ColorInterface::YELLOW);
            return false;
        }
    }
}