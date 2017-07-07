<?php
namespace MonarcMigrationTool\Service\Import\Client;

use Zend\Console\ColorInterface;

class AnrInterviewsService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = [], $oldAnr = null){
        if(!empty($corresp['anrs'][$oldAnr])){
            $res = $this->adapter->query('
                SELECT *
                FROM `dims_mod_smile_anr_interviews`
                WHERE anr_id = '.$oldAnr.'
                ')->execute();
            $table = $this->serviceLocator->get('\MonarcFO\Model\Table\InterviewTable');
            $interviewService = $this->serviceLocator->get('MonarcFO\Service\AnrInterviewService');
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
                    'anr' => $corresp['anrs'][$r['anr_id']],
                    'date' => $r['date'],
                    'service' => $r['service'],
                    'content' => $r['content'],
                );

                $entity = new $c();
                $db = $this->serviceLocator->get($this->dbUsed);
                $db->getEntityManager();
                $entity->setDbAdapter($db);
                $entity->exchangeArray($data);
                $interviewService->setDependencies($entity,['anr']);
                $table->save($entity);

                $compt++;
                $this->updateProgressbar($compt);
                $db->getEntityManager()->detach($entity);
                unset($entity,$data,$db);
            }
            $this->finishProgressbar();
            $this->console->write("\t\tOK",ColorInterface::GREEN);
            $this->console->write(" $compt/".count($res)." elements\n");
            unset($compt,$c,$res,$table,$interviewService);
        }else{
            $this->console->write("\t\tNo ANR imported\n",ColorInterface::YELLOW);
            return false;
        }
    }
}