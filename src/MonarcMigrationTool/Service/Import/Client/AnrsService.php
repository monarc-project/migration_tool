<?php
namespace MonarcMigrationTool\Service\Import\Client;

use Zend\Console\ColorInterface;

class AnrsService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = []){
        $res = $this->adapter->query("
            SELECT a.*, s.anr_reference sref, s.comment scomment, s.timestp_create as stc
            FROM `dims_mod_smile_anr` a
            INNER JOIN `dims_project` p
            ON p.id = a.project_id
            LEFT JOIN `dims_mod_smile_anr_snapshots` s
            ON a.id = s.anr_id
            AND a.snapshot_id = s.id
            WHERE p.state = 1
            AND p.type = 2
            ORDER BY a.snapshot_id, a.id
        ")->execute();
        // p.state = 1 (actif)
        // p.type = 2 (2 = anr / 1 = micro eval)
        // s.id IS NULL (on n'importe pas les snapshots)
        $table = $this->serviceLocator->get('\MonarcFO\Model\Table\AnrTable');
        $anrService = $this->serviceLocator->get('MonarcFO\Service\AnrService');
        $c = $table->getClass();
        $snapshotTable = $this->serviceLocator->get('\MonarcFO\Model\Table\SnapshotTable');
        $snapC = $snapshotTable->getClass();
        $userAnrTable = $this->serviceLocator->get('\MonarcFO\Model\Table\UserAnrTable');
        $uaC = $userAnrTable->getClass();

        $this->console->write("\t- ".$c.":\n");

        if(!class_exists($c)){
            $this->console->write("\t\tERR\n",ColorInterface::RED);
            return false;
        }

        $compt = 0;
        $this->createProgressbar(count($res));
        foreach($res as $r){
            /*
            On ne doit pas passer par le service car cela créé automatiquement:
            - scales
            - scales_impact_types
            Et il faut reprendre ces données depuis l'ancienne version.
            */
            $dataAnr = [
                'label1' => $r['label'],
                'label2' => '',
                'label3' => '',
                'label4' => '',
                'description1' => $r['description'],
                'description2' => '',
                'description3' => '',
                'description4' => '',
                'seuil1' => $r['seuil1'],
                'seuil2' => $r['seuil2'],
                'seuilRolf1' => $r['seuil_rolf1'],
                'seuilRolf2' => $r['seuil_rolf2'],
                'seuilTraitement' => $r['seuil_traitement'],
                'initAnrContext' => $r['init_anr_context'],
                'initEvalContext' => $r['init_eval_context'],
                'initRiskContext' => $r['init_risk_context'],
                'initDefContext' => $r['init_def_context'],
                'initLivrableDone' => $r['init_livrable_done'],
                'modelSummary' => $r['model_summary'],
                'modelLivrableDone' => $r['model_livrable_done'],
                'evalRisks' => $r['eval_risks'],
                'evalPlanRisks' => $r['eval_plan_risks'],
                'evalLivrableDone' => $r['eval_livrable_done'],
                'manageRisks' => $r['manage_risks'],
                'contextAnaRisk' => $r['context_ana_risk'],
                'contextGestRisk' => $r['context_gest_risk'],
                'synthThreat' => $r['synth_threat'],
                'synthAct' => $r['synth_act'],
                'cacheModelShowRolfBrut' => $r['cache_model_show_rolf_brut'],
                'showRolfBrut' => $r['show_rolf_brut'],
                'model' => (isset($corresp['models'][$r['model_id']]) && $corresp['models'][$r['model_id']] > 0 ?$corresp['models'][$r['model_id']]:null),
                'cacheModelIsScalesUpdatable' => (isset($corresp['models'][$r['model_id']]) && isset($corresp['modelsIsScalesUpdatable'][$corresp['models'][$r['model_id']]])?$corresp['modelsIsScalesUpdatable'][$corresp['models'][$r['model_id']]]:1),
                'modelImpacts' => $r['model_impacts'],
            ];


            $anr = new $c();
            $anr->setDbAdapter($this->serviceLocator->get($this->dbUsed));
            $anr->exchangeArray($dataAnr);
            $corresp['anrs'][$r['id']] = $table->save($anr);

            /*
            On vérifie si c'est un snapshot, si c'est le cas on le crée.
            Les données liées aux snapshots sont identiques aux anrs classiques, le traitement sera donc le même
            */
            if(!empty($r['sref']) && isset($corresp['anrs'][$r['sref']])){
                $snap = new $snapC();
                $snap->setDbAdapter($this->serviceLocator->get($this->dbUsed));
                $snap->exchangeArray([
                    'anr' => $corresp['anrs'][$r['id']],
                    'anrReference' => $corresp['anrs'][$r['sref']],
                    'comment' => $r['scomment'],
                    'createdAt' => substr($r['stc'], 0, 4).'-'.substr($r['stc'], 4, 2).'-'.substr($r['stc'], 6, 2).' '.substr($r['stc'], 8, 2).':'.substr($r['stc'], 10, 2).':'.substr($r['stc'], 12, 2),
                    'createdBy' => 'Migration Tool',
                ]);
                $anrService->setDependencies($snap,['anr', 'anrReference']);
                $snapshotTable->save($snap);
                $this->serviceLocator->get($this->dbUsed)->getEntityManager()->detach($snap);
                unset($snap);
            }else{
                // On donne tous les droits à l'utilisateur par défaut sur cette ANR
                $link = new $uaC();
                $link->setDbAdapter($this->serviceLocator->get($this->dbUsed));
                $link->exchangeArray([
                    'anr' => $corresp['anrs'][$r['id']],
                    'user' => $corresp['user'],
                    'rwd' => 1,
                ]);
                $anrService->setDependencies($link,['anr', 'user']);
                $userAnrTable->save($link);
                $this->serviceLocator->get($this->dbUsed)->getEntityManager()->detach($link);
                unset($link);
            }

            $compt++;
            $this->updateProgressbar($compt);
            $this->serviceLocator->get($this->dbUsed)->getEntityManager()->detach($anr);
            unset($anr,$dataAnr);
        }

        unset($corresp['modelsIsScalesUpdatable']);

        $this->finishProgressbar();
        $this->console->write("\t\tOK",ColorInterface::GREEN);
        $this->console->write(" $compt/".count($res)." elements\n");
        unset($compt,$res,$anrService,$snapC,$uaC,$c,$table,$snapshotTable,$userAnrTable);
    }
}