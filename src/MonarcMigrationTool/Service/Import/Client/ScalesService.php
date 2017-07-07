<?php
namespace MonarcMigrationTool\Service\Import\Client;

use Zend\Console\ColorInterface;

class ScalesService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = [], $oldAnr = null){
        if(!empty($corresp['anrs'][$oldAnr])){
            $res = $this->adapter->query("
                SELECT id, model_id, anr_id, type, min , max
                FROM `dims_mod_smile_biblio_scales`
                WHERE anr_id = '.$oldAnr.'
            ")->execute();

            $table = $this->serviceLocator->get('\MonarcFO\Model\Table\ScaleTable');
            $scaleService = $this->serviceLocator->get('MonarcFO\Service\AnrScaleService');
            $c = $table->getClass();

            $this->console->write("\t- ".$c.":\n");

            if(!class_exists($c)){
                $this->console->write("\t\tERR\n",ColorInterface::RED);
                return false;
            }

            $compt = 0;
            $this->createProgressbar(count($res));
            foreach($res as $r){
                if(isset($corresp['anrs'][$r['anr_id']])){
                    $scale = new $c();
                    $db = $this->serviceLocator->get($this->dbUsed);
                    $db->getEntityManager();
                    $scale->setDbAdapter($db);
                    $scale->exchangeArray([
                        'anr' => $corresp['anrs'][$r['anr_id']],
                        'type' => $r['type'],
                        'min' => $r['min'],
                        'max' => $r['max'],
                    ]);
                    $scaleService->setDependencies($scale,['anr']);
                    $corresp['scales'][$r['id']] = $table->save($scale);

                    // On ajoute une correspondace entre les anciens id_scale & les nouveaux anr_id
                    $corresp['scale_anr'][$r['id']] = $corresp['anrs'][$r['anr_id']];

                    $compt++;
                    $this->updateProgressbar($compt);
                    $db->getEntityManager()->detach($scale);
                    unset($scale,$db);
                }
            }
            $this->finishProgressbar();
            $this->console->write("\t\tOK",ColorInterface::GREEN);
            $this->console->write(" $compt/".count($res)." elements\n");
            unset($compt,$res,$table,$scaleService,$c);
        }else{
            $this->console->write("\t\tNo ANR imported\n",ColorInterface::YELLOW);
            return false;
        }
    }
}
