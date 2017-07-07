<?php
namespace MonarcMigrationTool\Service\Import\Common;

use Zend\Console\ColorInterface;

class AMVsService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = []){
        $res = $this->adapter->query('SELECT * FROM `dims_mod_smile_amv_link` ORDER BY id_asset ASC, position ASC')->execute();
        $table = $this->serviceLocator->get('\MonarcCore\Model\Table\AmvTable');
        $amvService = $this->serviceLocator->get('MonarcCore\Service\AmvService');
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
            $amvService->set('entity',$entity);
            $corresp['amvs'][$r['id']] = $amvService->create(array(
                'asset' => (!empty($r['id_asset']) && isset($corresp['assets'][$r['id_asset']]) ? $corresp['assets'][$r['id_asset']] : null),
                'threat' => (!empty($r['id_threat']) && isset($corresp['menaces'][$r['id_threat']]) ? $corresp['menaces'][$r['id_threat']] : null),
                'vulnerability' => (!empty($r['id_vulnerability']) && isset($corresp['vuls'][$r['id_vulnerability']]) ? $corresp['vuls'][$r['id_vulnerability']] : null),
                'measure1' => (!empty($r['measure1']) && isset($corresp['measures'][$r['measure1']]) ? $corresp['measures'][$r['measure1']] : null),
                'measure2' => (!empty($r['measure2']) && isset($corresp['measures'][$r['measure2']]) ? $corresp['measures'][$r['measure2']] : null),
                'measure3' => (!empty($r['measure3']) && isset($corresp['measures'][$r['measure3']]) ? $corresp['measures'][$r['measure3']] : null),
                'position' => $r['position'],
                'status' => 1,
                'implicitPosition' => \MonarcCore\Model\Entity\AbstractEntity::IMP_POS_END,
            ));

            $compt++;
            $this->updateProgressbar($compt);
        }
        $this->finishProgressbar();
        $this->console->write("\t\tOK",ColorInterface::GREEN);
        $this->console->write(" $compt/".count($res)." elements\n");
    }
}
