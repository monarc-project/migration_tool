<?php
namespace MonarcMigrationTool\Service\Import\Client;

use Zend\Console\ColorInterface;

class AnrRecommandationRisksService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = [], $oldAnr = null){
        if(!empty($corresp['anrs'][$oldAnr])){
            $res = $this->adapter->query('
                SELECT *
                FROM `dims_mod_smile_anr_recommandations_risks`
                WHERE anr_id = '.$oldAnr.'
            ')->execute();
            $table = $this->serviceLocator->get('\MonarcFO\Model\Table\RecommandationRiskTable');
            $recoService = $this->serviceLocator->get('MonarcFO\Service\AnrRecommandationRiskService');
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
                    isset($corresp['instancerisks'][$r['risk_id']]) &&
                    isset($corresp['assets'][$r['a_id']]) &&
                    isset($corresp['menaces'][$r['m_id']]) &&
                    isset($corresp['vuls'][$r['v_id']])){
                    $data = array(
                        'anr' => $corresp['anrs'][$r['anr_id']],
                        'recommandation' => $corresp['recos'][$r['recommandation_id']],
                        'instanceRisk' => $corresp['instancerisks'][$r['risk_id']],
                        'instanceRiskOp' => null,
                        'instance' => $corresp['instancerisksinstance'][$r['risk_id']],
                        'objectGlobal' => (isset($corresp['objects'][$r['biblio_global_id']])?$corresp['objects'][$r['biblio_global_id']]:null),
                        'asset' => $corresp['assets'][$r['a_id']],
                        'threat' => $corresp['menaces'][$r['m_id']],
                        'vulnerability' => $corresp['vuls'][$r['v_id']],
                        'commentAfter' => null,
                        'risk' => $corresp['instancerisks'][$r['risk_id']],
                        'op' => 0,
                    );

                    try{
                        $recoService->create($data);

                        $compt++;
                        $this->updateProgressbar($compt);
                    }catch(\MonarcCore\Exception\Exception $e){
                        // On peux avoir une exception si le lien existe déjà
                    }
                    unset($data);
                }
            }
            $this->finishProgressbar();
            $this->console->write("\t\tOK",ColorInterface::GREEN);
            $this->console->write(" $compt/".count($res)." elements\n");
            unset($compt,$res,$table,$recoService,$c);
        }else{
            $this->console->write("\t\tNo ANR imported\n",ColorInterface::YELLOW);
            return false;
        }
    }
}