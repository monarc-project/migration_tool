<?php
namespace MonarcMigrationTool\Service\Import\Common;

use Zend\Console\ColorInterface;

class VulnerabilitiesService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = []){
        $res = $this->adapter->query('SELECT * FROM `dims_mod_smile_vul_models`')->execute();
        $vulsModels = array();
        foreach($res as $r){
            if(!isset($vulsModels[$r['vul_id']])){
                $vulsModels[$r['vul_id']] = array();
            }
            $vulsModels[$r['vul_id']][] = $r['model_id'];
        }

        $res = $this->adapter->query('SELECT * FROM `dims_mod_smile_vulnerability`')->execute();
        $table = $this->serviceLocator->get('\MonarcCore\Model\Table\VulnerabilityTable');
        $modelTable = $this->serviceLocator->get('\MonarcCore\Model\Table\ModelTable');
        $vulService = $this->serviceLocator->get('MonarcCore\Service\VulnerabilityService');
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
            $vulService->set('entity',$entity);
            $data = array(
                'mode' => ($r['io_level'] == 1 ? 0 : 1),
                'code' => $r['code'],
                'label1' => $r['label'],
                'label2' => '',
                'label3' => '',
                'label4' => '',
                'description1' => $r['description'],
                'description2' => '',
                'description3' => '',
                'description4' => '',
                'status' => 1,
                'models' => array(),
            );
            if (!empty($vulsModels[$r['id']])) {
                foreach ($vulsModels[$r['id']] as $key => $modelId) {
                    if (!empty($corresp['models'][$modelId])) {
                        $data['models'][] = $corresp['models'][$modelId];
                    }
                }
            }
            $corresp['vuls'][$r['id']] = $vulService->create($data);

            $compt++;
            $this->updateProgressbar($compt);
        }
        $this->finishProgressbar();
        $this->console->write("\t\tOK",ColorInterface::GREEN);
        $this->console->write(" $compt/".count($res)." elements\n");
    }
}