<?php
namespace MonarcMigrationTool\Service\Import\Common;

use Zend\Console\ColorInterface;

class AnrObjectsService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = []){
        if(!empty($corresp['anrs'])){
            $res = $this->adapter->query('
                SELECT o.source_bdc_object_id, o.anr_id
                FROM `dims_mod_smile_biblio_object` o
                LEFT JOIN `dims_mod_smile_biblio_object_categories` oc
                ON oc.id = o.category_id
                LEFT JOIN `dims_mod_smile_anr_categ_positions` c
                ON c.categ_id = oc.root_reference
                AND c.anr_id = o.anr_id
                WHERE o.type = \'anr\'
                AND o.anr_id IN ('.implode(',', array_keys($corresp['anrs'])).')
                ORDER BY ISNULL(c.position), c.position ASC
            ')->execute();

            $objectService = $this->serviceLocator->get('MonarcCore\Service\ObjectService');
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
                    isset($corresp['objects'][$r['source_bdc_object_id']])){
                    $objectService->attachObjectToAnr($corresp['objects'][$r['source_bdc_object_id']], $corresp['anrs'][$r['anr_id']]);
                    $compt++;
                    $this->updateProgressbar($compt);
                }   
            }
            $this->finishProgressbar();
            $this->console->write("\t\tOK",ColorInterface::GREEN);
            $this->console->write(" $compt/".count($res)." elements\n");
        }else{
            $this->console->write("\t\tNo ANR imported\n",ColorInterface::YELLOW);
            return false;
        }
    }
}
