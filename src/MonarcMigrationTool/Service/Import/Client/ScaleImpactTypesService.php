<?php
namespace MonarcMigrationTool\Service\Import\Client;

use Zend\Console\ColorInterface;

class ScaleImpactTypesService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = [], $oldAnr = null){
        if(!empty($corresp['scales'])){
            $res = $this->adapter->query("
                SELECT id, scale_id, anr_id, type, label , is_sys, is_hidden
                FROM `dims_mod_smile_biblio_scales_impact_types`
                WHERE scale_id IN (".implode(',', array_keys($corresp['scales'])).")
                ORDER BY scale_id, position
            ")->execute();

            $table = $this->serviceLocator->get('\MonarcFO\Model\Table\ScaleImpactTypeTable');
            $scaleService = $this->serviceLocator->get('MonarcFO\Service\AnrScaleTypeService');
            $c = $table->getClass();

            $this->console->write("\t- ".$c.":\n");

            if(!class_exists($c)){
                $this->console->write("\t\tERR\n",ColorInterface::RED);
                return false;
            }

            $compt = 0;
            $this->createProgressbar(count($res));
            foreach($res as $r){
                if(isset($corresp['scales'][$r['scale_id']])){
                    $scaleT = new $c();
                    $db = $this->serviceLocator->get($this->dbUsed);
                    $db->getEntityManager();
                    $scaleT->setDbAdapter($db);
                    switch (strtoupper(trim($r['type']))) {
                        case 'C':
                            $r['type'] = 1;
                            $r['is_sys'] = 1;
                            $r['label1'] = 'Confidentialité';
                            $r['label2'] = 'Confidentiality';
                            $r['label3'] = 'Vertraulichkeit';
                            $r['label4'] = '';
                            break;
                        case 'I':
                            $r['type'] = 2;
                            $r['is_sys'] = 1;
                            $r['label1'] = 'Intégrité';
                            $r['label2'] = 'Integrity';
                            $r['label3'] = 'Integrität';
                            $r['label4'] = '';
                            break;
                        case 'D':
                            $r['type'] = 3;
                            $r['is_sys'] = 1;
                            $r['label1'] = 'Disponibilité';
                            $r['label2'] = 'Availability';
                            $r['label3'] = 'Verfügbarkeit';
                            $r['label4'] = '';
                            break;
                        case 'R':
                            $r['type'] = 4;
                            $r['is_sys'] = 1;
                            $r['label1'] = 'Réputation';
                            $r['label2'] = 'Reputation';
                            $r['label3'] = 'Ruf';
                            $r['label4'] = '';
                            break;
                        case 'O':
                            $r['type'] = 5;
                            $r['is_sys'] = 1;
                            $r['label1'] = 'Opérationnel';
                            $r['label2'] = 'Operational';
                            $r['label3'] = 'Einsatzbereit';
                            $r['label4'] = '';
                            break;
                        case 'L':
                            $r['type'] = 6;
                            $r['is_sys'] = 1;
                            $r['label1'] = 'Légal';
                            $r['label2'] = 'Legal';
                            $r['label3'] = 'Legal';
                            $r['label4'] = '';
                            break;
                        case 'F':
                            $r['type'] = 7;
                            $r['is_sys'] = 1;
                            $r['label1'] = 'Financier';
                            $r['label2'] = 'Financial';
                            $r['label3'] = 'Finanziellen';
                            $r['label4'] = '';
                            break;
                        case 'P':
                            $r['type'] = 8;
                            $r['is_sys'] = 1;
                            $r['label1'] = 'Personne';
                            $r['label2'] = 'Person';
                            $r['label3'] = 'Person';
                            $r['label4'] = '';
                            break;
                        case 'CUS':
                        default:
                            $r['type'] = 9;
                            $r['is_sys'] = 0;
                            $r['label1'] = $r['label'];
                            $r['label2'] = '';
                            $r['label3'] = '';
                            $r['label4'] = '';
                            break;
                    }
                    $scaleT->exchangeArray([
                        'anr' => $corresp['scale_anr'][$r['scale_id']],
                        'scale' => $corresp['scales'][$r['scale_id']],
                        'type' => $r['type'],
                        'label1' => $r['label1'],
                        'label2' => $r['label2'],
                        'label3' => $r['label3'],
                        'label4' => $r['label4'],
                        'isSys' => $r['is_sys'],
                        'isHidden' => $r['is_hidden'],
                        'implicitPosition' => \MonarcCore\Model\Entity\AbstractEntity::IMP_POS_END,
                    ]);
                    $scaleService->setDependencies($scaleT,['anr', 'scale']);
                    $corresp['scaleTypes'][$r['id']] = $table->save($scaleT);

                    $compt++;
                    $this->updateProgressbar($compt);
                    $db->getEntityManager()->detach($scaleT);
                    unset($scaleT,$db);
                }
            }
            $this->finishProgressbar();
            $this->console->write("\t\tOK",ColorInterface::GREEN);
            $this->console->write(" $compt/".count($res)." elements\n");
            unset($compt,$res,$table,$c,$scaleService);
        }else{
            $this->console->write("\t\tNo Scale imported\n",ColorInterface::YELLOW);
            return false;
        }
    }
}
