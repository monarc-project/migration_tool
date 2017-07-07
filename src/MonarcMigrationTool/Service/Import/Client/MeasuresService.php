<?php
namespace MonarcMigrationTool\Service\Import\Client;

use Zend\Console\ColorInterface;

class MeasuresService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = [], $oldAnr = null){
        if(!empty($corresp['anrs'][$oldAnr])){
            /*
            On prend toutes les mesures actuellement présentes dans la Common.
            Pour chaque ANR du client, on ajoute toutes les mesures.
            EDIT: on fait l'import ANR par ANR
            !!! Le tableau de correspondance est différent ici => OldIdMeasure->OldIdAnr->NewIdMeasure
            */
            $res = $this->adapterCommon->query('SELECT * FROM `dims_mod_smile_27002` WHERE code != \'\'')->execute();
            $table = $this->serviceLocator->get('\MonarcFO\Model\Table\MeasureTable');
            $measureService = $this->serviceLocator->get('MonarcFO\Service\AnrMeasureService');
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
                $entity->setDbAdapter($this->serviceLocator->get($this->dbUsed));
                $measureService->set('entity',$entity);
                $data = array(
                    'code' => $r['code'],
                    'description1' => $r['description'],
                    'description2' => '',
                    'description3' => '',
                    'description4' => '',
                    'status' => $r['status'],
                    'anr' => null,
                );

                $data['anr'] = $corresp['anrs'][$oldAnr];
                $entity = new $c();
                $db = $this->serviceLocator->get($this->dbUsed);
                $db->getEntityManager();
                $entity->setDbAdapter($db);
                $entity->exchangeArray($data);
                $measureService->setDependencies($entity,['anr']);
                $corresp['measures'][$r['id']][$oldAnr] = $table->save($entity);

                $compt++;
                $this->updateProgressbar($compt);
                $db->getEntityManager()->detach($entity);
                unset($entity,$data,$db);
            }
            $this->finishProgressbar();
            $this->console->write("\t\tOK",ColorInterface::GREEN);
            $this->console->write(" $compt/".count($res)." elements\n");
            unset($compt,$res,$table,$measureService,$c);
        }else{
            $this->console->write("\t\tNo ANR imported\n",ColorInterface::YELLOW);
            return false;
        }
    }
}