<?php
namespace MonarcMigrationTool\Service\Import\Common;

use Zend\Console\ColorInterface;

class ScalesService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = []){
        if(!empty($corresp['models'])){
            $res = $this->adapter->query("
                SELECT id, model_id, anr_id, type, min , max
                FROM `dims_mod_smile_biblio_scales`
                WHERE model_id IN (".implode(',', array_keys($corresp['models'])).")
            ")->execute();

            $table = $this->serviceLocator->get('\MonarcCore\Model\Table\ScaleTable');
            $scaleService = $this->serviceLocator->get('MonarcCore\Service\ScaleService');
            $c = $table->getClass();

            $this->console->write("\t- ".$c.":\n");

            if(!class_exists($c)){
                $this->console->write("\t\tERR\n",ColorInterface::RED);
                return false;
            }

            $compt = 0;
            $this->createProgressbar(count($res));
            foreach($res as $r){
                if(isset($corresp['model_anr'][$r['model_id']])){
                    $scale = new $c();
                    $scale->setDbAdapter($this->serviceLocator->get($this->dbUsed));
                    $scale->exchangeArray([
                        'anr' => $corresp['model_anr'][$r['model_id']],
                        'type' => $r['type'],
                        'min' => $r['min'],
                        'max' => $r['max'],
                    ]);
                    $scaleService->setDependencies($scale,['anr']);
                    $corresp['scales'][$r['id']] = $table->save($scale);

                    // On ajoute une correspondace entre les anciens id_scale & les nouveaux anr_id
                    $corresp['scale_anr'][$r['id']] = $corresp['model_anr'][$r['model_id']];

                    $compt++;
                    $this->updateProgressbar($compt);
                }
            }
            $this->finishProgressbar();
            $this->console->write("\t\tOK",ColorInterface::GREEN);
            $this->console->write(" $compt/".count($res)." elements\n");
        }else{
            $this->console->write("\t\tNo Model imported\n",ColorInterface::YELLOW);
            return false;
        }
    }
}
