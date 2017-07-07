<?php
namespace MonarcMigrationTool\Service\Import\Common;

use Zend\Console\ColorInterface;

class RolfTagsService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = []){
        $res = $this->adapter->query('SELECT * FROM `dims_mod_smile_rolf_tags`')->execute();
        $table = $this->serviceLocator->get('\MonarcCore\Model\Table\RolfTagTable');
        $rolfTagService = $this->serviceLocator->get('MonarcCore\Service\RolfTagService');
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
            $rolfTagService->set('entity',$entity);
            $corresp['tags'][$r['id']] = $rolfTagService->create(array(
                'code' => $r['code'],
                'label1' => $r['label'],
                'label2' => '',
                'label3' => '',
                'label4' => '',
            ));

            $compt++;
            $this->updateProgressbar($compt);
        }
        $this->finishProgressbar();
        $this->console->write("\t\tOK",ColorInterface::GREEN);
        $this->console->write(" $compt/".count($res)." elements\n");
    }
}