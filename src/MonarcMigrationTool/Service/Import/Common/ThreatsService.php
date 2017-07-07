<?php
namespace MonarcMigrationTool\Service\Import\Common;

use Zend\Console\ColorInterface;

class ThreatsService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = []){
        $res = $this->adapter->query('SELECT * FROM `dims_mod_smile_threat_models`')->execute();
        $threatsModels = array();
        foreach($res as $r){
            if(!isset($threatsModels[$r['threat_id']])){
                $threatsModels[$r['threat_id']] = array();
            }
            $threatsModels[$r['threat_id']][] = $r['model_id'];
        }

        $res = $this->adapter->query('SELECT * FROM `dims_mod_smile_threat`')->execute();
        $table = $this->serviceLocator->get('\MonarcCore\Model\Table\ThreatTable');
        $modelTable = $this->serviceLocator->get('\MonarcCore\Model\Table\ModelTable');
        $threatService = $this->serviceLocator->get('MonarcCore\Service\ThreatService');
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
            $threatService->set('entity',$entity);
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
                'c' => $r['c'],
                'i' => $r['i'],
                'd' => $r['d'],
                'status' => 1,
                'isAccidental' => $r['is_accidental'],
                'isDeliberate' => $r['is_deliberate'],
                'descAccidental1' => $r['desc_accidental'],
                'descAccidental2' => '',
                'descAccidental3' => '',
                'descAccidental4' => '',
                'exAccidental1' => $r['ex_accidental'],
                'exAccidental2' => '',
                'exAccidental3' => '',
                'exAccidental4' => '',
                'descDeliberate1' => $r['desc_deliberate'],
                'descDeliberate2' => '',
                'descDeliberate3' => '',
                'descDeliberate4' => '',
                'exDeliberate1' => $r['ex_deliberate'],
                'exDeliberate2' => '',
                'exDeliberate3' => '',
                'exDeliberate4' => '',
                'typeConsequences1' => $r['type_consequences'],
                'typeConsequences2' => '',
                'typeConsequences3' => '',
                'typeConsequences4' => '',
                'trend' => 0,
                'comment' => '',
                'qualification' => '-1',
                'models' => array(),
                'theme' => null,
            );
            if (!empty($threatsModels[$r['id']])) {
                foreach ($threatsModels[$r['id']] as $key => $modelId) {
                    if (!empty($corresp['models'][$modelId])) {
                        $data['models'][] = $corresp['models'][$modelId];
                    }
                }
            }
            if(isset($corresp['themes'][$r['id_theme']])){
                $data['theme'] = $corresp['themes'][$r['id_theme']];
            }
            $corresp['menaces'][$r['id']] = $threatService->create($data);

            $compt++;
            $this->updateProgressbar($compt);
        }
        $this->finishProgressbar();
        $this->console->write("\t\tOK",ColorInterface::GREEN);
        $this->console->write(" $compt/".count($res)." elements\n");
    }
}