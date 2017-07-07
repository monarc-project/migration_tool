<?php
namespace MonarcMigrationTool\Service\Import\Common;

use Zend\Console\ColorInterface;

class AnrInstanceRiskOpsService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = []){
        if(!empty($corresp['instances'])){
            $res = $this->adapter->query('
                SELECT r.*, o.source_bdc_object_id
                FROM `dims_mod_smile_anr_rolf_risks` r
                INNER JOIN `dims_mod_smile_biblio_object` o
                ON r.object_id = o.id
                WHERE r.instance_id IN ('.implode(',', array_keys($corresp['instances'])).')
            ')->execute();

            $table = $this->serviceLocator->get('\MonarcCore\Model\Table\InstanceRiskOpTable');
            $instanceService = $this->serviceLocator->get('MonarcCore\Service\InstanceRiskOpService');
            $c = $table->getClass();

            $this->console->write("\t- ".$c.":\n");

            if(!class_exists($c)){
                $this->console->write("\t\tERR\n",ColorInterface::RED);
                return false;
            }

            $compt = 0;
            $this->createProgressbar(count($res));
            foreach($res as $r){
                if(isset($corresp['anrs'][$r['anr_id']]) && 
                    isset($corresp['instances'][$r['instance_id']]) && 
                    isset($corresp['objects'][$r['source_bdc_object_id']]) && 
                    isset($corresp['risks'][$r['risk_id']])){
                    /*
                    Cas particulier, comme on n'est pas passé par InstanceService::instantiateObjectToAnr pour créer les instances,
                    on reprend tel quel les données pour les risks, riskops & consequences.
                    On suppose que toutes les données fournies par l'ancienne version sont valides.
                    */

                    $data = [
                        'anr' => $corresp['anrs'][$r['anr_id']],
                        'instance' => $corresp['instances'][$r['instance_id']],
                        'object' => $corresp['objects'][$r['source_bdc_object_id']],
                        'rolfRisk' => $corresp['risks'][$r['risk_id']],
                        'riskCacheCode' => $r['risk_cache_code'],
                        'riskCacheLabel1' => $r['risk_cache_label'],
                        'riskCacheLabel2' => '',
                        'riskCacheLabel3' => '',
                        'riskCacheLabel4' => '',
                        'riskCacheDescription1' => $r['risk_cache_description'],
                        'riskCacheDescription2' => '',
                        'riskCacheDescription3' => '',
                        'riskCacheDescription4' => '',
                        'brutProb' => $r['brut_prob'],
                        'brutR' => $r['brut_r'],
                        'brutO' => $r['brut_o'],
                        'brutL' => $r['brut_l'],
                        'brutF' => $r['brut_f'],
                        'cacheBrutRisk' => $r['cache_brut_risk'],
                        'netProb' => $r['net_prob'],
                        'netR' => $r['net_r'],
                        'netO' => $r['net_o'],
                        'netL' => $r['net_l'],
                        'netF' => $r['net_f'],
                        'cacheNetRisk' => $r['cache_net_risk'],
                        'targetedProb' => $r['targeted_prob'],
                        'targetedR' => $r['targeted_r'],
                        'targetedO' => $r['targeted_o'],
                        'targetedL' => $r['targeted_l'],
                        'targetedF' => $r['targeted_f'],
                        'cacheTargetedRisk' => $r['cache_targeted_risk'],
                        'kindOfMeasure' => $r['kind_of_measure'],
                        'comment' => $r['comment'],
                        'mitigation' => $r['mitigation'],
                        'specific' => $r['specific'],
                        'netP' => -1,
                        'targetedP' => -1,
                        'brutP' => -1,
                    ];

                    $entity = new $c();
                    $entity->setDbAdapter($this->serviceLocator->get($this->dbUsed));
                    $entity->exchangeArray($data);
                    $instanceService->setDependencies($entity,['anr', 'instance', 'rolfRisk', 'object']);

                    $table->save($entity);
                    $compt++;
                    $this->updateProgressbar($compt);
                }            
            }
            $this->finishProgressbar();
            $this->console->write("\t\tOK",ColorInterface::GREEN);
            $this->console->write(" $compt/".count($res)." elements\n");
        }else{
            $this->console->write("\t\tNo Instance imported\n",ColorInterface::YELLOW);
            return false;
        }
    }
}