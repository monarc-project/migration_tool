<?php
namespace MonarcMigrationTool\Service\Import\Common;

use Zend\Console\ColorInterface;

class RolfRisksService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = []){
        $res = $this->adapter->query('SELECT * FROM `dims_mod_smile_rolf_risk_tags`')->execute();
        $rolfRisks = array();
        foreach($res as $r){
            if(!isset($rolfRisks[$r['risk_id']])){
                $rolfRisks[$r['risk_id']] = array();
            }
            $rolfRisks[$r['risk_id']][] = $r['tag_id'];
        }

        $res = $this->adapter->query('SELECT * FROM `dims_mod_smile_rolf_risks`')->execute();
        $table = $this->serviceLocator->get('\MonarcCore\Model\Table\RolfRiskTable');
        $tagTable = $this->serviceLocator->get('\MonarcCore\Model\Table\RolfTagTable');
        $rolfRiskService = $this->serviceLocator->get('MonarcCore\Service\RolfRiskService');
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
            $rolfRiskService->set('entity',$entity);
            $data = array(
                'code' => $r['code'],
                'label1' => $r['label'],
                'label2' => '',
                'label3' => '',
                'label4' => '',
                'description1' => $r['description'],
                'description2' => '',
                'description3' => '',
                'description4' => '',
                'tags' => array(),
            );
            if (!empty($rolfRisks[$r['id']])) {
                foreach ($rolfRisks[$r['id']] as $key => $tagId) {
                    if (!empty($corresp['tags'][$tagId])) {
                        $data['tags'][] = $corresp['tags'][$tagId];
                    }
                }
            }
            $corresp['risks'][$r['id']] = $rolfRiskService->create($data);

            $compt++;
            $this->updateProgressbar($compt);
        }
        $this->finishProgressbar();
        $this->console->write("\t\tOK",ColorInterface::GREEN);
        $this->console->write(" $compt/".count($res)." elements\n");
    }
}