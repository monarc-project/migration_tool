<?php
namespace MonarcMigrationTool\Service\Import\Common;

use Zend\Console\ColorInterface;

class ThemesService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = []){
        $res = $this->adapter->query('SELECT * FROM `dims_mod_smile_threat_theme`')->execute();
        $table = $this->serviceLocator->get('\MonarcCore\Model\Table\ThemeTable');
        $themeService = $this->serviceLocator->get('MonarcCore\Service\ThemeService');
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
            $themeService->set('entity',$entity);
            $corresp['themes'][$r['id']] = $themeService->create(array(
                'label1' => $r['label'],
                'label2' => '',
                'label3' => '',
                'label4' => '',
            ));

            $compt++;
            $this->updateProgressbar($compt);
        }
        $this->finishProgressbar();
        $this->console->write("\t\tOK",ColorInterface::GREEN);
        $this->console->write(" $compt/".count($res)." elements\n");
    }
}