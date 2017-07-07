<?php
namespace MonarcMigrationTool\Service\Import\Client;

use Zend\Console\ColorInterface;

class ObjectObjectsService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = [], $oldAnr = null){
        if(!empty($corresp['anrs'][$oldAnr])){
            $res = $this->adapter->query('
                SELECT c.father_id, c.child_id, o.anr_id
                FROM `dims_mod_smile_biblio_object_components` c
                INNER JOIN `dims_mod_smile_biblio_object` o
                ON c.father_id = o.id
                WHERE o.anr_id = '.$oldAnr.'
                ORDER BY c.father_id ASC, c.position ASC
            ')->execute();
            $table = $this->serviceLocator->get('\MonarcFO\Model\Table\ObjectObjectTable');
            $objectObjectService = $this->serviceLocator->get('MonarcFO\Service\ObjectObjectService');
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
                $objectObjectService->set('entity',$entity);

                try{
                    $data = array(
                        'father' => isset($corresp['objects'][$r['father_id']])?$corresp['objects'][$r['father_id']]:null,
                        'child' => isset($corresp['objects'][$r['child_id']])?$corresp['objects'][$r['child_id']]:null,
                        'implicitPosition' => \MonarcCore\Model\Entity\AbstractEntity::IMP_POS_END,
                        'anr' => isset($corresp['anrs'][$r['anr_id']])?$corresp['anrs'][$r['anr_id']]:null,
                    );

                    $objectObjectService->create($data,true,\MonarcCore\Model\Entity\AbstractEntity::FRONT_OFFICE);
                    $compt++;
                    $this->updateProgressbar($compt);
                    unset($data);
                }catch(\Exception $e){

                }
                unset($entity,$db);
            }
            $this->finishProgressbar();
            $this->console->write("\t\tOK",ColorInterface::GREEN);
            $this->console->write(" $compt/".count($res)." elements\n");
            unset($compt,$res,$table,$objectObjectService,$c);
        }else{
            $this->console->write("\t\tNo ANR imported\n",ColorInterface::YELLOW);
            return false;
        }
    }
}