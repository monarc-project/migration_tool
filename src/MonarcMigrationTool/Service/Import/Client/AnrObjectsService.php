<?php
namespace MonarcMigrationTool\Service\Import\Client;

use Zend\Console\ColorInterface;

class AnrObjectsService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = [], $oldAnr = null){
        if(!empty($corresp['anrs'][$oldAnr])){
            $res = $this->adapter->query('
                SELECT o.id, o.anr_id
                FROM `dims_mod_smile_biblio_object` o
                LEFT JOIN `dims_mod_smile_biblio_object_categories` oc
                ON oc.id = o.category_id
                LEFT JOIN `dims_mod_smile_anr_categ_positions` c
                ON c.categ_id = oc.root_reference
                AND c.anr_id = o.anr_id
                WHERE o.type = \'anr\'
                AND o.anr_id = '.$oldAnr.'
                ORDER BY ISNULL(c.position), c.position ASC
            ')->execute();

            $objectService = $this->serviceLocator->get('MonarcFO\Service\ObjectService');
            $c = $objectService->get('table')->getClass();

            $this->console->write("\t- anrs_objects:\n");

            if(!class_exists($c)){
                $this->console->write("\t\tERR\n",ColorInterface::RED);
                return false;
            }

            $compt = 0;
            $this->createProgressbar(count($res));
            foreach($res as $r){
                if(isset($corresp['anrs'][$r['anr_id']]) && 
                    isset($corresp['objects'][$r['id']])){
                    $objectService->attachObjectToAnr($corresp['objects'][$r['id']], $corresp['anrs'][$r['anr_id']],null,null,\MonarcCore\Model\Entity\AbstractEntity::FRONT_OFFICE);
                    $compt++;
                    $this->updateProgressbar($compt);
                }   
            }
            $this->finishProgressbar();
            $this->console->write("\t\tOK",ColorInterface::GREEN);
            $this->console->write(" $compt/".count($res)." elements\n");
            unset($res,$compt,$objectService,$c);
        }else{
            $this->console->write("\t\tNo ANR imported\n",ColorInterface::YELLOW);
            return false;
        }
    }
}
