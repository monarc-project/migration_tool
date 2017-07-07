<?php
namespace MonarcMigrationTool\Service\Import\Client;

use Zend\Console\ColorInterface;

class AnrDeliveriesService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = [], $oldAnr = null){
        if(!empty($corresp['anrs'][$oldAnr])){
            $res = $this->adapter->query('
                SELECT *
                FROM `dims_mod_smile_anr_delivery`
                WHERE anr_id = '.$oldAnr.'
                AND typedoc != 0
                ORDER BY id DESC, anr_id, typedoc
                ')->execute();
            $table = $this->serviceLocator->get('\MonarcFO\Model\Table\DeliveryTable');
            $interviewService = $this->serviceLocator->get('MonarcFO\Service\DeliverableGenerationService');
            $c = $table->getClass();

            /*
            Une seule par typedoc-anr & la dernière (order by id)
            */
            $this->console->write("\t- ".$c.":\n");

            if(!class_exists($c)){
                $this->console->write("\t\tERR\n",ColorInterface::RED);
                return false;
            }

            $tmp = [];
            $compt = 0;
            $this->createProgressbar(count($res));
            foreach($res as $r){
                if(!isset($tmp[$r['anr_id']][$r['typedoc']])){
                    $tmp[$r['anr_id']][$r['typedoc']] = $r['typedoc'];
                    $data = array(
                        'anr' => $corresp['anrs'][$r['anr_id']],
                        'typedoc' => $r['typedoc'],
                        'name' => $r['name'],
                        'version' => $r['version'],
                        'status' => $r['state'],
                        'classification' => $r['classification'],
                        'respCustomer' => $r['resp_customer'],
                        'respSmile' => $r['resp_smile'],
                        'summaryEvalRisk' => $r['summary_eval_risk'],
                    );

                    $entity = new $c();
                    $db = $this->serviceLocator->get($this->dbUsed);
                    $db->getEntityManager();
                    $entity->setDbAdapter($db);
                    $entity->exchangeArray($data);
                    $interviewService->setDependencies($entity,['anr']);
                    $table->save($entity);
                    $db->getEntityManager()->detach($entity);
                    unset($entity,$data,$db);
                }

                $compt++;
                $this->updateProgressbar($compt);
            }
            $this->finishProgressbar();
            $this->console->write("\t\tOK",ColorInterface::GREEN);
            $this->console->write(" $compt/".count($res)." elements\n");
            unset($tmp,$table,$interviewService,$c,$compt,$res);
        }else{
            $this->console->write("\t\tNo ANR imported\n",ColorInterface::YELLOW);
            return false;
        }
    }
}