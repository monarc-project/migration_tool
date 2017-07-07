<?php
namespace MonarcMigrationTool\Service\Import\Client;

use Zend\Console\ColorInterface;

class VulnerabilitiesService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = [], $oldAnr = null){
        if(!empty($corresp['anrs'][$oldAnr])){
            $res = $this->adapter->query('
                SELECT *
                FROM `dims_mod_smile_vulnerability`
                WHERE anr_id = '.$oldAnr.'
            ')->execute();
            $table = $this->serviceLocator->get('\MonarcFO\Model\Table\VulnerabilityTable');
            $vulService = $this->serviceLocator->get('MonarcFO\Service\AnrVulnerabilityService');
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
                    'status' => 1,
                    'anr' => $corresp['anrs'][$r['anr_id']],
                );
                $entity = new $c();
                $db = $this->serviceLocator->get($this->dbUsed);
                $db->getEntityManager();
                $entity->setDbAdapter($db);
                $entity->exchangeArray($data);
                $vulService->setDependencies($entity,['anr']);
                $corresp['vuls'][$r['id']] = $table->save($entity);

                $compt++;
                $this->updateProgressbar($compt);
                $db->getEntityManager()->detach($entity);
                unset($entity,$data,$db);
            }
            $this->finishProgressbar();
            $this->console->write("\t\tOK",ColorInterface::GREEN);
            $this->console->write(" $compt/".count($res)." elements\n");
            unset($compt,$res,$table,$vulService,$c);
        }else{
            $this->console->write("\t\tNo ANR imported\n",ColorInterface::YELLOW);
            return false;
        }
    }
}