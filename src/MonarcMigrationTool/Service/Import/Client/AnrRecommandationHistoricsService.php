<?php
namespace MonarcMigrationTool\Service\Import\Client;

use Zend\Console\ColorInterface;

class AnrRecommandationHistoricsService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = [], $oldAnr = null){
        if(!empty($corresp['anrs'][$oldAnr])){
            $res = $this->adapter->query('
            	SELECT *
            	FROM `dims_mod_smile_anr_recommandation_historics`
            	WHERE anr_id = '.$oldAnr.'
            ')->execute();
            $table = $this->serviceLocator->get('\MonarcFO\Model\Table\RecommandationHistoricTable');
            $recoService = $this->serviceLocator->get('MonarcFO\Service\AnrRecommandationHistoricService');
            $c = $table->getClass();

            $this->console->write("\t- ".$c.":\n");

            if(!class_exists($c)){
                $this->console->write("\t\tERR\n",ColorInterface::RED);
                return false;
            }

            $compt = 0;
            $this->createProgressbar(count($res));
            foreach($res as $r){
                $data = array(
                	'anr' => $corresp['anrs'][$r['anr_id']],
					'instanceRisk' => null,
					'final' => 1,
					'implComment' => $r['impl_comment'],
					'recoCode' => $r['reco_code'],
					'recoDescription' => $r['reco_description'],
					'recoImportance' => $r['reco_importance'],
					'recoComment' => $r['reco_comment'],
					'recoResponsable' => $r['reco_responsable'],
					'recoDuedate' => ((empty($r['reco_duedate']) || $r['reco_duedate'] == '0000-00-00')?null:new \DateTime($r['reco_duedate'])),
					'riskInstance' => $r['risk_instance'],
					'riskInstanceContext' => $r['risk_instance_context'],
					'riskAsset' => $r['risk_asset'],
					'riskThreat' => $r['risk_threat'],
					'riskThreatVal' => $r['risk_threat_val'],
					'riskVul' => $r['risk_vul'],
					'riskVulValBefore' => $r['risk_vul_val_before'],
					'riskVulValAfter' => $r['risk_vul_val_after'],
					'riskKindOfMeasure' => $r['risk_kind_of_measure'],
					'riskCommentBefore' => $r['risk_comment_before'],
					'riskCommentAfter' => $r['risk_comment_after'],
					'riskMaxRiskBefore' => $r['risk_max_risk_before'],
					'riskColorBefore' => $r['risk_color_before'],
					'riskMaxRiskAfter' => $r['risk_max_risk_after'],
					'riskColorAfter' => $r['risk_color_after'],
					'cacheCommentAfter' => null,
                );

                $entity = new $c();
                $db = $this->serviceLocator->get($this->dbUsed);
                $db->getEntityManager();
                $entity->setDbAdapter($db);
                $entity->exchangeArray($data);
                $recoService->setDependencies($entity,['anr']);
                $table->save($entity);

                $compt++;
                $this->updateProgressbar($compt);
                $db->getEntityManager()->detach($entity);
                unset($entity,$data,$db);
            }
            $this->finishProgressbar();
            $this->console->write("\t\tOK",ColorInterface::GREEN);
            $this->console->write(" $compt/".count($res)." elements\n");
            unset($res,$c,$compt,$table,$recoService);
        }else{
            $this->console->write("\t\tNo ANR imported\n",ColorInterface::YELLOW);
            return false;
        }
    }
}