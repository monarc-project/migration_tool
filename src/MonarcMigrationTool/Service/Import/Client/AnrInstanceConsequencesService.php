<?php
namespace MonarcMigrationTool\Service\Import\Client;

use Zend\Console\ColorInterface;

class AnrInstanceConsequencesService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = [], $oldAnr = null){
        if(!empty($corresp['instances'])){
            $res = $this->adapter->query('
                SELECT c.*
                FROM `dims_mod_smile_assoc_consequences` c
                WHERE c.instance_id IN ('.implode(',', array_keys($corresp['instances'])).')
                ORDER BY instance_id
            ')->execute();

            $table = $this->serviceLocator->get('\MonarcFO\Model\Table\InstanceConsequenceTable');
            $instanceService = $this->serviceLocator->get('MonarcFO\Service\AnrInstanceConsequenceService');
            $c = $table->getClass();

            $this->console->write("\t- ".$c.":\n");

            if(!class_exists($c)){
                $this->console->write("\t\tERR\n",ColorInterface::RED);
                return false;
            }

            $compt = 0;
            $prevInstance = $firstConseq = null;
            $cMax = [
                'c' => -1,
                'i' => -1,
                'd' => -1,
            ];
            $this->createProgressbar(count($res));
            foreach($res as $r){
                if(isset($corresp['anrs'][$r['anr_id']]) && 
                    isset($corresp['instances'][$r['instance_id']]) && 
                    isset($corresp['objects'][$r['biblio_id']]) && 
                    isset($corresp['scaleTypes'][$r['type_id']])){
                    /*
                    Cas particulier, comme on n'est pas passé par InstanceService::instantiateObjectToAnr pour créer les instances,
                    on reprend tel quel les données pour les risks, riskops & consequences.
                    On suppose que toutes les données fournies par l'ancienne version sont valides.
                    
                    ------------------------------------------------------------

                    Pré-traitement:
                    - Il faut mettre à jour la 1er consequence visible dans le cas ou le max C/I/D
                    est inférieur au CID de l'instance
                    - Il faut mettre à jour toutes les consequences visibles dans le cas ou le
                    C/I/D de l'instance vaut -1

                    */
                    if($prevInstance != $corresp['instances'][$r['instance_id']]){
                        // On test avec ce que l'on a enregistré sur l'instance
                        if(!empty($corresp['instancescid'][$prevInstance])){
                            $toUpdate = [];
                            foreach($cMax as $k => $v){
                                if($v < $corresp['instancescid'][$prevInstance][$k]){
                                    $toUpdate[$k] = $corresp['instancescid'][$prevInstance][$k];
                                }
                            }
                            if(!empty($toUpdate)){
                                $conseq = $table->getEntity($firstConseq);
                                foreach($toUpdate as $k => $v){
                                    $conseq->set($k,$v);
                                }
                                $table->save($conseq);
                            }
                        }

                        // On ré-initialise les données de test
                        $prevInstance = $corresp['instances'][$r['instance_id']];
                        $firstConseq = null;
                        $cMax = [
                            'c' => -1,
                            'i' => -1,
                            'd' => -1,
                        ];
                    }
                    if(!$r['is_hidden']){
                        foreach($cMax as $k => $v){
                            // Si le C/I/D de l'instance == -1 alors les C/I/D des consequences valent -1
                            if(isset($corresp['instancescid'][$prevInstance][$k]) && $corresp['instancescid'][$prevInstance][$k] == -1){
                                $r[$k] = -1;
                            }
                            // on récupère le max CID des consequences de l'instance
                            if($r[$k] > $v){
                                $cMax[$k] = $v;
                            }
                        }
                    }

                    $data = [
                        'anr' => $corresp['anrs'][$r['anr_id']],
                        'instance' => $corresp['instances'][$r['instance_id']],
                        'object' => $corresp['objects'][$r['biblio_id']],
                        'scaleImpactType' => $corresp['scaleTypes'][$r['type_id']],
                        'isHidden' => $r['is_hidden'],
                        'locallyTouched' => $r['locally_touched'],
                        'c' => $r['c'],
                        'i' => $r['i'],
                        'd' => $r['d'],
                    ];

                    $entity = new $c();
                    $db = $this->serviceLocator->get($this->dbUsed);
                    $db->getEntityManager();
                    $entity->setDbAdapter($db);
                    $entity->exchangeArray($data);
                    $instanceService->setDependencies($entity,['anr', 'instance', 'object', 'scaleImpactType']); // 'object'

                    $idC = $table->save($entity);

                    if(!$r['is_hidden'] && is_null($firstConseq)){
                        $firstConseq = $idC;
                    }

                    $compt++;
                    $this->updateProgressbar($compt);
                    $db->getEntityManager()->detach($entity);
                    unset($entity,$data,$db);
                }            
            }

            $this->finishProgressbar();
            $this->console->write("\t\tOK",ColorInterface::GREEN);
            $this->console->write(" $compt/".count($res)." elements\n");
            unset($table,$instanceService,$c,$compt,$res);
        }else{
            $this->console->write("\t\tNo Instance imported\n",ColorInterface::YELLOW);
            return false;
        }
    }
}