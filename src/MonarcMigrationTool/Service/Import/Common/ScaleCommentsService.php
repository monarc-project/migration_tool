<?php
namespace MonarcMigrationTool\Service\Import\Common;

use Zend\Console\ColorInterface;

class ScaleCommentsService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = []){
        if(!empty($corresp['scales'])){
            $res = $this->adapter->query("
                SELECT id, id_scale, type_impact_id, anr_id, val , comment
                FROM `dims_mod_smile_biblio_scales_comments`
                WHERE id_scale IN (".implode(',', array_keys($corresp['scales'])).")
                AND type_scale = 'scale'
            ")->execute();

            $table = $this->serviceLocator->get('\MonarcCore\Model\Table\ScaleCommentTable');
            $scaleTable = $this->serviceLocator->get('\MonarcCore\Model\Table\ScaleTable');
            $scaleService = $this->serviceLocator->get('MonarcCore\Service\ScaleCommentService');
            $c = $table->getClass();

            $this->console->write("\t- ".$c.":\n");

            if(!class_exists($c)){
                $this->console->write("\t\tERR\n",ColorInterface::RED);
                return false;
            }

            $compt = 0;
            $this->createProgressbar(count($res));
            foreach($res as $r){
                if(isset($corresp['scales'][$r['id_scale']])){
                    $scaleC = new $c();
                    $scaleC->setDbAdapter($this->serviceLocator->get($this->dbUsed));
                    $scale = $scaleTable->getEntity($corresp['scales'][$r['id_scale']]);
                    $scaleC->setScale($scale);
                    $scaleC->exchangeArray([
                        'anr' => $corresp['scale_anr'][$r['id_scale']],
                        'scale' => $corresp['scales'][$r['id_scale']],
                        'scaleImpactType' => (isset($corresp['scaleTypes'][$r['type_impact_id']])?$corresp['scaleTypes'][$r['type_impact_id']]:null),
                        'val' => $r['val'],
                        'comment1' => $r['comment'],
                        'comment2' => '',
                        'comment3' => '',
                        'comment4' => '',
                    ]);
                    $scaleService->setDependencies($scaleC,['anr', 'scale', 'scaleImpactType']);
                    $table->save($scaleC);

                    $compt++;
                    $this->updateProgressbar($compt);
                }
            }
            $this->finishProgressbar();
            $this->console->write("\t\tOK",ColorInterface::GREEN);
            $this->console->write(" $compt/".count($res)." elements\n");
        }else{
            $this->console->write("\t\tNo Scale imported\n",ColorInterface::YELLOW);
            return false;
        }
    }
}
