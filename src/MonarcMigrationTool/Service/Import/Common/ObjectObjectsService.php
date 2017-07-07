<?php
namespace MonarcMigrationTool\Service\Import\Common;

use Zend\Console\ColorInterface;

class ObjectObjectsService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = []){
        $res = $this->adapter->query('SELECT * FROM `dims_mod_smile_biblio_object_components` ORDER BY father_id ASC, position ASC')->execute();
        $table = $this->serviceLocator->get('\MonarcCore\Model\Table\ObjectObjectTable');
        $objectObjectService = $this->serviceLocator->get('MonarcCore\Service\ObjectObjectService');
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
            $objectObjectService->set('entity',$entity);

            try{
                $data = array(
                    'father' => isset($corresp['objects'][$r['father_id']])?$corresp['objects'][$r['father_id']]:null,
                    'child' => isset($corresp['objects'][$r['child_id']])?$corresp['objects'][$r['child_id']]:null,
                    'implicitPosition' => \MonarcCore\Model\Entity\AbstractEntity::IMP_POS_END,
                );

                $objectObjectService->create($data);
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