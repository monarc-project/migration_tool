<?php
namespace MonarcMigrationTool\Service\Import\Client;

use Zend\Console\ColorInterface;

class AnrRecommandationMeasuresService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = [], $oldAnr = null){
        if(!empty($corresp['anrs'][$oldAnr])){
            $res = $this->adapter->query('
                SELECT *
                FROM `dims_mod_smile_anr_recommandations_isos`
                WHERE anr_id = '.$oldAnr.'
            ')->execute();
            $table = $this->serviceLocator->get('\MonarcFO\Model\Table\RecommandationMeasureTable');
            $recoService = $this->serviceLocator->get('MonarcFO\Service\AnrRecommandationMeasureService');
            $c = $table->getClass();

            $this->console->write("\t- ".$c.":\n");

            if(!class_exists($c)){
                $this->console->write("\t\tERR\n",ColorInterface::RED);
                return false;
            }

            $compt = 0;
            $this->createProgressbar(count($res));
            foreach($res as $r){
                if(isset($corresp['recos'][$r['recommandation_id']]) &&
                    isset($corresp['measures'][$r['iso_id']][$r['anr_id']])){
                    
                    $data = array(
                        'anr' => $corresp['anrs'][$r['anr_id']],
                        'recommandation' => $corresp['recos'][$r['recommandation_id']],
                        'measure' => $corresp['measures'][$r['iso_id']][$r['anr_id']],
                    );

                    $entity = new $c();
                    $db = $this->serviceLocator->get($this->dbUsed);
                    $db->getEntityManager();
                    $entity->setDbAdapter($db);
                    $entity->exchangeArray($data);
                    $recoService->setDependencies($entity,['anr', 'recommandation', 'measure']);
                    $table->save($entity);

                    $compt++;
                    $this->updateProgressbar($compt);
                    $db->getEntityManager()->detach($entity);
                    unset($entity,$data,$db);
                }
            }
            $this->finishProgressbar();
            $this->console->write("\t\tOK",ColorInterface::GREEN);
            $this->console->write(" $compt/".count($res)." elements\n");
            unset($c,$compt,$res,$table,$recoService);
        }else{
            $this->console->write("\t\tNo ANR imported\n",ColorInterface::YELLOW);
            return false;
        }
    }
}