<?php
namespace MonarcMigrationTool\Service\Import\Common;

use Zend\Console\ColorInterface;

class ObjectsService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = []){
        $res = $this->adapter->query('SELECT * FROM `dims_mod_smile_biblio_object` WHERE type = \'bdc\' ORDER BY category_id ASC, position ASC')->execute();
        $table = $this->serviceLocator->get('\MonarcCore\Model\Table\ObjectTable');
        $objectService = $this->serviceLocator->get('MonarcCore\Service\ObjectService');
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
            $objectService->set('entity',$entity);
            $modelService = $objectService->get('modelService');
            $entity2 = new \MonarcCore\Model\Entity\Model();
            $entity2->setDbAdapter($this->serviceLocator->get($this->dbUsed));
            $modelService->set('entity',$entity2);
            $objectService->set('modelService',$modelService);
            $objectObjectService = $objectService->get('objectObjectService');
            $entity3 = new \MonarcCore\Model\Entity\ObjectObject();
            $entity3->setDbAdapter($this->serviceLocator->get($this->dbUsed));
            $objectObjectService->set('entity',$entity3);
            $objectService->set('objectObjectService',$objectObjectService);

            try{
                $data = array(
                    'asset' => (!empty($r['asset_id']) && isset($corresp['assets'][$r['asset_id']]) ? $corresp['assets'][$r['asset_id']] : 0 ),
                    'rolfTag' => (!empty($r['rolf_tag_id']) && isset($corresp['tags'][$r['rolf_tag_id']]) ? $corresp['tags'][$r['rolf_tag_id']] : 0 ),
                    'type' => 'bdc',
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
                $corresp['objects'][$r['id']] = $objectService->create($data);
                $compt++;
                $this->updateProgressbar($compt);
            }catch(\Exception $e){

            }
        }
        $this->finishProgressbar();
        $this->console->write("\t\tOK",ColorInterface::GREEN);
        $this->console->write(" $compt/".count($res)." elements\n");
    }
}