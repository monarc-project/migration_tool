<?php
namespace MonarcMigrationTool\Service\Import\Client;

use Zend\Console\ColorInterface;

class RolfRisksService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = [], $oldAnr = null){
        if(!empty($corresp['anrs'][$oldAnr])){
            $res = $this->adapter->query('
                SELECT *
                FROM `dims_mod_smile_rolf_risk_tags`
                WHERE anr_id = '.$oldAnr.'
            ')->execute();
            $rolfRisks = array();
            foreach($res as $r){
                if(!isset($rolfRisks[$r['risk_id']])){
                    $rolfRisks[$r['risk_id']] = array();
                }
                $rolfRisks[$r['risk_id']][] = $r['tag_id'];
            }

            $res = $this->adapter->query('
                SELECT *
                FROM `dims_mod_smile_rolf_risks`
                WHERE anr_id = '.$oldAnr.'
            ')->execute();
            $table = $this->serviceLocator->get('\MonarcFO\Model\Table\RolfRiskTable');
            $tagTable = $this->serviceLocator->get('\MonarcFO\Model\Table\RolfTagTable');
            $rolfRiskService = $this->serviceLocator->get('MonarcFO\Service\AnrRolfRiskService');
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
                $rolfRiskService->set('entity',$entity);
                $data = array(
                    'anr' => $corresp['anrs'][$r['anr_id']],
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
                unset($data,$entity,$db);
            }
            $this->finishProgressbar();
            $this->console->write("\t\tOK",ColorInterface::GREEN);
            $this->console->write(" $compt/".count($res)." elements\n");
            unset($res,$compt,$rable,$tagTable,$rolfRiskService,$c);
        }else{
            $this->console->write("\t\tNo ANR imported\n",ColorInterface::YELLOW);
            return false;
        }
    }
}