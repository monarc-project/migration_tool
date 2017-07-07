<?php
namespace MonarcMigrationTool\Service\Import\Client;

use Zend\Console\ColorInterface;

class ThreatsService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = [], $oldAnr = null){
        if(!empty($corresp['anrs'][$oldAnr])){
            $res = $this->adapter->query('
                SELECT *
                FROM `dims_mod_smile_threat`
                WHERE anr_id = '.$oldAnr.'
                ')->execute();
            $table = $this->serviceLocator->get('\MonarcFO\Model\Table\ThreatTable');
            $threatService = $this->serviceLocator->get('MonarcFO\Service\AnrThreatService');
            $c = $table->getClass();

            $this->console->write("\t- ".$c.":\n");

            if(!class_exists($c)){
                $this->console->write("\t\tERR\n",ColorInterface::RED);
                return false;
            }

            $compt = 0;
            $this->createProgressbar(count($res));
            foreach($res as $r){
                $data = array(
                    'mode' => ($r['io_level'] == 1 ? 0 : 1),
                    'code' => $r['code'],
                    'label1' => $r['label'],
                    'label2' => '',
                    'label3' => '',
                    'label4' => '',
                    'description1' => $r['description'],
                    'description2' => '',
                    'description3' => '',
                    'description4' => '',
                    'c' => $r['c'],
                    'i' => $r['i'],
                    'd' => $r['d'],
                    'status' => $r['status'],
                    'isAccidental' => $r['is_accidental'],
                    'isDeliberate' => $r['is_deliberate'],
                    'descAccidental1' => $r['desc_accidental'],
                    'descAccidental2' => '',
                    'descAccidental3' => '',
                    'descAccidental4' => '',
                    'exAccidental1' => $r['ex_accidental'],
                    'exAccidental2' => '',
                    'exAccidental3' => '',
                    'exAccidental4' => '',
                    'descDeliberate1' => $r['desc_deliberate'],
                    'descDeliberate2' => '',
                    'descDeliberate3' => '',
                    'descDeliberate4' => '',
                    'exDeliberate1' => $r['ex_deliberate'],
                    'exDeliberate2' => '',
                    'exDeliberate3' => '',
                    'exDeliberate4' => '',
                    'typeConsequences1' => $r['type_consequences'],
                    'typeConsequences2' => '',
                    'typeConsequences3' => '',
                    'typeConsequences4' => '',
                    'trend' => $r['trend'],
                    'comment' => $r['comment'],
                    'qualification' => $r['qualification'],
                    'anr' => $corresp['anrs'][$r['anr_id']],
                    'theme' => null,
                );
                if(isset($corresp['themes'][$r['id_theme']])){
                    $data['theme'] = $corresp['themes'][$r['id_theme']];
                }

                $entity = new $c();
                $db = $this->serviceLocator->get($this->dbUsed);
                $db->getEntityManager();
                $entity->setDbAdapter($db);
                $entity->exchangeArray($data);
                $threatService->setDependencies($entity,['anr', 'theme']);
                $corresp['menaces'][$r['id']] = $table->save($entity);

                $compt++;
                $this->updateProgressbar($compt);
                $db->getEntityManager()->detach($entity);
                unset($entity,$data,$db);
            }
            $this->finishProgressbar();
            $this->console->write("\t\tOK",ColorInterface::GREEN);
            $this->console->write(" $compt/".count($res)." elements\n");
            unset($compt,$res,$table,$threatService,$c);
        }else{
            $this->console->write("\t\tNo ANR imported\n",ColorInterface::YELLOW);
            return false;
        }
    }
}