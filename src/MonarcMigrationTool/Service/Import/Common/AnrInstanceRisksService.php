<?php
namespace MonarcMigrationTool\Service\Import\Common;

use Zend\Console\ColorInterface;

class AnrInstanceRisksService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = []){
        if(!empty($corresp['assocs'])){
            $res = $this->adapter->query('
                SELECT r.*
                FROM `dims_mod_smile_anr_qualif` r
                WHERE r.id_assoc IN ('.implode(',', array_keys($corresp['assocs'])).')
            ')->execute();

            $table = $this->serviceLocator->get('\MonarcCore\Model\Table\InstanceRiskTable');
            $instanceService = $this->serviceLocator->get('MonarcCore\Service\InstanceRiskService');
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
                    isset($corresp['assocs'][$r['id_assoc']]) && 
                    isset($corresp['assets'][$r['id_asset']]) && 
                    isset($corresp['menaces'][$r['id_menace']]) && 
                    isset($corresp['vuls'][$r['id_vul']]) && 
                    isset($corresp['amvs'][$r['amv_id']])){
                    /*
                    Cas particulier, comme on n'est pas passé par InstanceService::instantiateObjectToAnr pour créer les instances,
                    on reprend tel quel les données pour les risks, riskops & consequences.
                    On suppose que toutes les données fournies par l'ancienne version sont valides.
                    */

                    $data = [
                        'anr' => $corresp['anrs'][$r['anr_id']],
                        'instance' => $corresp['assocs'][$r['id_assoc']],
                        'asset' => $corresp['assets'][$r['id_asset']],
                        'threat' => $corresp['menaces'][$r['id_menace']],
                        'vulnerability' => $corresp['vuls'][$r['id_vul']],
                        'amv' => $corresp['amvs'][$r['amv_id']],
                        'specific' => $r['specific'],
                        'mh' => $r['mh'],
                        'threatRate' => $r['threat_rate'],
                        'vulnerabilityRate' => $r['vulnerability_rate'],
                        'kindOfMeasure' => $r['kind_of_measure'],
                        'reductionAmount' => $r['reduction_amount'],
                        'comment' => $r['comment'],
                        'commentAfter' => $r['comment_after'],
                        'riskC' => $r['risk_c'],
                        'riskI' => $r['risk_i'],
                        'riskD' => $r['risk_d'],
                        'cacheMaxRisk' => $r['cache_max_risk'],
                        'cacheTargetedRisk' => $r['cache_targeted_risk'],
                    ];

                    $entity = new $c();
                    $entity->setDbAdapter($this->serviceLocator->get($this->dbUsed));
                    $entity->exchangeArray($data);
                    $instanceService->setDependencies($entity,['anr', 'instance', 'amv', 'asset', 'instance', 'threat', 'vulnerability']);

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