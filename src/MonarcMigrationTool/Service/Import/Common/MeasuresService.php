<?php
namespace MonarcMigrationTool\Service\Import\Common;

use Zend\Console\ColorInterface;

class MeasuresService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = []){
        $res = $this->adapter->query('SELECT * FROM `dims_mod_smile_27002`')->execute();
        $table = $this->serviceLocator->get('\MonarcCore\Model\Table\MeasureTable');
        $measureService = $this->serviceLocator->get('MonarcCore\Service\MeasureService');
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
            $measureService->set('entity',$entity);
            $data = array(
                'code' => $r['code'],
                'description1' => $r['description'],
                'description2' => '',
                'description3' => '',
                'description4' => '',
                'status' => $r['status'],
            );
            $corresp['measures'][$r['id']] = $measureService->create($data);

            $compt++;
            $this->updateProgressbar($compt);
        }
        $this->finishProgressbar();
        $this->console->write("\t\tOK",ColorInterface::GREEN);
        $this->console->write(" $compt/".count($res)." elements\n");
    }
}