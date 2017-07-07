<?php
namespace MonarcMigrationTool\Service\Import\Common;

use Zend\Console\ColorInterface;

class AnrInstancesService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = []){
        if(!empty($corresp['anrs'])){
            $res = $this->adapter->query('
                SELECT i.id, i.anr_id, i.asset_id, i.scope, i.disponibility, i.level, i.asset_mode, i.exportable,
                    a.id as aid, a.father_id, a.child_id, a.c, a.i, a.d, a.ch, a.ih, a.dh,
                    o.source_bdc_object_id
                FROM `dims_mod_smile_anr_instance` i
                INNER JOIN `dims_mod_smile_biblio_object` o
                ON o.id = i.biblio_id
                INNER JOIN `dims_mod_smile_anr_assoc` a
                ON i.id = a.child_id
                AND i.anr_id = a.anr_id
                WHERE i.anr_id IN ('.implode(',', array_keys($corresp['anrs'])).')
                ORDER BY i.anr_id ASC, i.root_reference ASC, a.father_id ASC, a.position ASC
            ')->execute();

            $table = $this->serviceLocator->get('\MonarcCore\Model\Table\InstanceTable');
            $objTable = $this->serviceLocator->get('\MonarcCore\Model\Table\ObjectTable');

            $instanceService = $this->serviceLocator->get('MonarcCore\Service\InstanceService');
            $c = $table->getClass();

            $this->console->write("\t- ".$c.":\n");

            if(!class_exists($c)){
                $this->console->write("\t\tERR\n",ColorInterface::RED);
                return false;
            }

            $compt = $limit =0;
            $nbPassLimit = 8; // C'est complétement arbitraire, l'arbo des instances n'a pas une profondeur énorme, cela devrait suffire
            $datas = [];
            foreach($res as $r){
                $datas[] = $r;
            }
            unset($res);
            $res = $datas;
            unset($datas);
            $nbElems = count($res);
            $this->createProgressbar($nbElems);
            while(!empty($res) && $limit <= $nbPassLimit*$nbElems){
                $r = array_shift($res);
                if(isset($corresp['anrs'][$r['anr_id']]) &&
                    isset($corresp['objects'][$r['source_bdc_object_id']]) &&
                    (empty($r['father_id']) || !empty($corresp['instances'][$r['father_id']]) || $limit >= ($nbPassLimit-1)*$nbElems)){
                    /*
                    Cas particulier, on ne peux pas passer par InstanceService::instantiateObjectToAnr car dans cette
                    fonction, on parcourt les fils de l'objet instancié et on les instancie aussi.
                    Du coup, on va dupliquer l'instanciation dans le cas où on a des fils.
                    Il faut donc reprendre l'algo ici ...
                    */

                    $object = $objTable->getEntity($corresp['objects'][$r['source_bdc_object_id']]);

                    $authorized = false;
                    foreach($object->anrs as $anr) {
                        if ($anr->id == $corresp['anrs'][$r['anr_id']]) {
                            $authorized = true;
                            break;
                        }
                    }
                    if (!$authorized) {
                        // on ne va pas renvoyer d'erreur mais on arrête là
                        break;
                    }

                    $data = [
                        'anr' => $corresp['anrs'][$r['anr_id']],
                        'object' => $object->get('id'),
                        'name1' => $object->get('name1'),
                        'name2' => $object->get('name2'),
                        'name3' => $object->get('name3'),
                        'name4' => $object->get('name4'),
                        'label1' => $object->get('label1'),
                        'label2' => $object->get('label2'),
                        'label3' => $object->get('label3'),
                        'label4' => $object->get('label4'),
                        'parent' => (empty($corresp['instances'][$r['father_id']])?null:$corresp['instances'][$r['father_id']]),
                        'asset' => (empty($corresp['assets'][$r['asset_id']])?null:$corresp['assets'][$r['asset_id']]),
                        'c' => $r['c'],
                        'i' => $r['i'],
                        'd' => $r['d'],
                        'ch' => $r['ch'], // on reprend tel quel les valeurs ici donc pas besoin de faire InstanceService::updateImpactsInherited
                        'ih' => $r['ih'],
                        'dh' => $r['dh'],
                        'disponibility' => $r['disponibility'],
                        'level' => $r['level'], // InstanceService::updateInstanceLevels
                        'asset_type' => ($r['asset_mode']==3?2:$r['asset_mode']),
                        'exportable' => $r['exportable'],
                        'implicitPosition' => 2,
                    ];

                    $entity = new $c();
                    $entity->setDbAdapter($this->serviceLocator->get($this->dbUsed));
                    $entity->initParametersChanges();
                    $entity->exchangeArray($data);
                    $instanceService->setDependencies($entity,['anr', 'parent', 'root', 'asset', 'object']);

                    /*
                    On met l'id à la fois dans "instances" & dans "assocs" car dans les InstanceRisk on a le
                    lien uniquement sur l'id_assoc dans l'ancienne base
                    */
                    $corresp['assocs'][$r['aid']] = $corresp['instances'][$r['id']] = $table->save($entity);
                    $corresp['instancescid'][$corresp['instances'][$r['id']]] = [ // on prend directement le nouvel id de l'instance
                        'c' => $r['c'],
                        'i' => $r['i'],
                        'd' => $r['d'],
                    ];
                    $compt++;
                    $this->updateProgressbar($compt);
                    /*
                    On traite les:
                        - risks
                        - risksop
                        - consequences
                    ultérieurement.
                    */
                }else{
                    array_push($res, $r);
                }
                $limit++;
            }
            $this->finishProgressbar();
            $this->console->write("\t\tOK",ColorInterface::GREEN);
            $this->console->write(" $compt/".$nbElems." elements\n");
        }else{
            $this->console->write("\t\tNo ANR imported\n",ColorInterface::YELLOW);
            return false;
        }
    }
}
