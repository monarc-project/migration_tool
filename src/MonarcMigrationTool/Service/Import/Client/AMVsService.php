<?php
namespace MonarcMigrationTool\Service\Import\Client;

use Zend\Console\ColorInterface;

class AMVsService extends \MonarcMigrationTool\Service\Import\AbstractService{

	public function import(&$corresp = [], $oldAnr = null){
        if(!empty($corresp['anrs'][$oldAnr])){
    		$res = $this->adapter->query('
                SELECT *
                FROM `dims_mod_smile_amv_link`
                WHERE anr_id = '.$oldAnr.'
                ORDER BY anr_id ASC, id_asset ASC, position ASC
            ')->execute();
            $table = $this->serviceLocator->get('\MonarcFO\Model\Table\AmvTable');
            $amvService = $this->serviceLocator->get('MonarcFO\Service\AmvService');
            $c = $table->getClass();

            $this->console->write("\t- ".$c.":\n");

            if(!class_exists($c)){
                $this->console->write("\t\tERR\n",ColorInterface::RED);
                return false;
            }

            $compt = 0;
            $this->createProgressbar(count($res));
            foreach($res as $r){
                $entity = new $c();
                $db = $this->serviceLocator->get($this->dbUsed);
                $db->getEntityManager();
                $entity->setDbAdapter($db);
                $data = array(
                    'anr' => $corresp['anrs'][$r['anr_id']],
                    'asset' => (!empty($r['id_asset']) && isset($corresp['assets'][$r['id_asset']]) ? $corresp['assets'][$r['id_asset']] : null),
                    'threat' => (!empty($r['id_threat']) && isset($corresp['menaces'][$r['id_threat']]) ? $corresp['menaces'][$r['id_threat']] : null),
                    'vulnerability' => (!empty($r['id_vulnerability']) && isset($corresp['vuls'][$r['id_vulnerability']]) ? $corresp['vuls'][$r['id_vulnerability']] : null),
                    'measure1' => (!empty($r['measure1']) && isset($corresp['measures'][$r['measure1']][$r['anr_id']]) ? $corresp['measures'][$r['measure1']][$r['anr_id']] : null),
                    'measure2' => (!empty($r['measure2']) && isset($corresp['measures'][$r['measure2']][$r['anr_id']]) ? $corresp['measures'][$r['measure2']][$r['anr_id']] : null),
                    'measure3' => (!empty($r['measure3']) && isset($corresp['measures'][$r['measure3']][$r['anr_id']]) ? $corresp['measures'][$r['measure3']][$r['anr_id']] : null),
                    'position' => $r['position'],
                    'status' => 1,
                    'implicitPosition' => \MonarcCore\Model\Entity\AbstractEntity::IMP_POS_END,
                );

                $entity->exchangeArray($data);
                $amvService->setDependencies($entity,['anr', 'asset', 'threat', 'vulnerability', 'measure1', 'measure2', 'measure3']);
                $corresp['amvs'][$r['id']] = $table->save($entity);

                $compt++;
                $this->updateProgressbar($compt);
                $db->getEntityManager()->detach($entity);
                unset($entity,$data, $db);
            }
            $this->finishProgressbar();
            $this->console->write("\t\tOK",ColorInterface::GREEN);
            $this->console->write(" $compt/".count($res)." elements\n");
            unset($table,$amvService,$c,$compt,$res);
        }else{
            $this->console->write("\t\tNo ANR imported\n",ColorInterface::YELLOW);
            return false;
        }
	}
}
