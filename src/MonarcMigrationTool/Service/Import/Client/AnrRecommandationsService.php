<?php
namespace MonarcMigrationTool\Service\Import\Client;

use Zend\Console\ColorInterface;

class AnrRecommandationsService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = [], $oldAnr = null){
        if(!empty($corresp['anrs'][$oldAnr])){
            $res = $this->adapter->query('
            	SELECT *
            	FROM `dims_mod_smile_anr_recommandations`
            	WHERE anr_id = '.$oldAnr.'
            	ORDER BY anr_id ASC, position ASC
            ')->execute();
            $table = $this->serviceLocator->get('\MonarcFO\Model\Table\RecommandationTable');
            $recoService = $this->serviceLocator->get('MonarcFO\Service\AnrRecommandationService');
            $c = $table->getClass();

            $this->console->write("\t- ".$c.":\n");

            if(!class_exists($c)){
                $this->console->write("\t\tERR\n",ColorInterface::RED);
                return false;
            }

            $compt = 0;
            $this->createProgressbar(count($res));
            foreach($res as $r){
                if($r['importance'] <= 0){
                    $r['importance'] = 1;
                }elseif($r['importance'] > 3){
                    $r['importance'] = 3;
                }
                $data = array(
                	'anr' => $corresp['anrs'][$r['anr_id']],
					'code' => $r['code'],
					'description' => $r['description'],
					'importance' => $r['importance'],
					'position' => $r['position'],
					'comment' => $r['comment'],
					'responsable' => $r['responsable'],
					'duedate' => ((empty($r['duedate']) || $r['duedate'] == '0000-00-00')?null:new \DateTime($r['duedate'])),
					'counterTreated' => $r['counter_treated'],
					'originalCode' => $r['original_code'],
					'tokenImport' => $r['token_import'],
                );

                $entity = new $c();
                $db = $this->serviceLocator->get($this->dbUsed);
                $db->getEntityManager();
                $entity->setDbAdapter($db);
                $entity->exchangeArray($data);
                $recoService->setDependencies($entity,['anr']);
                $corresp['recos'][$r['id']] = $table->save($entity);

                $compt++;
                $this->updateProgressbar($compt);
                $db->getEntityManager()->detach($entity);
                unset($entity,$data,$db);
            }
            $this->finishProgressbar();
            $this->console->write("\t\tOK",ColorInterface::GREEN);
            $this->console->write(" $compt/".count($res)." elements\n");
            unset($compt,$res,$c,$recoService,$table);
        }else{
            $this->console->write("\t\tNo ANR imported\n",ColorInterface::YELLOW);
            return false;
        }
    }
}