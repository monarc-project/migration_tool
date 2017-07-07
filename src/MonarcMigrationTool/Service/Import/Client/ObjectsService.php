<?php
namespace MonarcMigrationTool\Service\Import\Client;

use Zend\Console\ColorInterface;

class ObjectsService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = [], $oldAnr = null){
        if(!empty($corresp['anrs'][$oldAnr])){
            $res = $this->adapter->query('
                SELECT *
                FROM `dims_mod_smile_biblio_object`
                WHERE type = \'anr\'
                AND anr_id = '.$oldAnr.'
                ORDER BY category_id ASC, position ASC')->execute();
            $table = $this->serviceLocator->get('\MonarcFO\Model\Table\ObjectTable');
            $objectService = $this->serviceLocator->get('MonarcFO\Service\ObjectService');
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
                $objectService->set('entity',$entity);
                $objectObjectService = $objectService->get('objectObjectService');
                $entity3 = new \MonarcFO\Model\Entity\ObjectObject();
                $entity3->setDbAdapter($db);
                $objectObjectService->set('entity',$entity3);
                $objectService->set('objectObjectService',$objectObjectService);

                try{
                    $data = array(
                        'anr' => $corresp['anrs'][$r['anr_id']],
                        'asset' => (!empty($r['asset_id']) && isset($corresp['assets'][$r['asset_id']]) ? $corresp['assets'][$r['asset_id']] : 0 ),
                        'rolfTag' => (!empty($r['rolf_tag_id']) && isset($corresp['tags'][$r['rolf_tag_id']]) ? $corresp['tags'][$r['rolf_tag_id']] : 0 ),
                        'type' => 'anr',
                        'mode' => ($r['io_level'] == 1 ? 0 : 1),
                        'scope' => $r['scope'],
                        'name1' => $r['name'],
                        'name2' => '',
                        'name3' => '',
                        'name4' => '',
                        'label1' => $r['label'],
                        'label2' => '',
                        'label3' => '',
                        'label4' => '',
                        'disponibility' => 0,
                        'position' => $r['position'],
                        'implicitPosition' => \MonarcCore\Model\Entity\AbstractEntity::IMP_POS_END,
                    );
                    if(!empty($r['category_id']) && isset($corresp['categs'][$r['category_id']]['id'])){
                        $data['category'] = $corresp['categs'][$r['category_id']]['id'];
                    }
                    $corresp['objects'][$r['id']] = $objectService->create($data,true,\MonarcCore\Model\Entity\AbstractEntity::FRONT_OFFICE);
                    $compt++;
                    $this->updateProgressbar($compt);
                    unset($data);
                }catch(\Exception $e){

                }
                unset($entity,$entity3,$objectObjectService,$db);
            }
            $this->finishProgressbar();
            $this->console->write("\t\tOK",ColorInterface::GREEN);
            $this->console->write(" $compt/".count($res)." elements\n");
            unset($compt,$res,$objectService,$table,$c);
        }else{
            $this->console->write("\t\tNo ANR imported\n",ColorInterface::YELLOW);
            return false;
        }
    }
}