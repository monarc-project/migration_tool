<?php
namespace MonarcMigrationTool\Service\Import\Common;

use Zend\Console\ColorInterface;

class ModelsService extends \MonarcMigrationTool\Service\Import\AbstractService{

	public function import(&$corresp = []){
		$res = $this->adapter->query("
            SELECT m.*, a.seuil1, a.seuil2, a.seuil_rolf1, a.seuil_rolf2, a.id as anrid
            FROM `dims_mod_smile_inventory_model` m
            INNER JOIN `dims_mod_smile_anr` a
            ON m.id = a.model_id
            WHERE m.deleted = 0
        ")->execute();
        $table = $this->serviceLocator->get('\MonarcCore\Model\Table\ModelTable');
        $modelService = $this->serviceLocator->get('MonarcCore\Service\ModelService');
        $c = $table->getClass();
        $anrtable = $this->serviceLocator->get('\MonarcCore\Model\Table\AnrTable');
        $cAnr = $anrtable->getClass();

        $this->console->write("\t- ".$c.":\n");

        if(!class_exists($c)){
            $this->console->write("\t\tERR\n",ColorInterface::RED);
            return false;
        }

        $compt = 0;
        $this->createProgressbar(count($res));
        foreach($res as $r){
            /*
            On ne doit pas passer par le service car cela créé automatiquement:
            - scales
            - scales_impact_types
            Et il faut reprendre ces données depuis l'ancienne version.
            */
            $dataM = $dataAnr = [
                'isScalesUpdatable' => $r['scales_modifiable'],
                'isDefault' => $r['default'],
                'isDeleted' => 0,
                'status' => $r['status'],
                'label1' => $r['label'],
                'label2' => '',
                'label3' => '',
                'label4' => '',
                'description1' => $r['description'],
                'description2' => '',
                'description3' => '',
                'description4' => '',
                'isGeneric' => $r['generic'],
                'isRegulator' => $r['regulator'],
                'showRolfBrut' => 0,
                'seuil1' => $r['seuil1'],
                'seuil2' => $r['seuil2'],
                'seuilRolf1' => $r['seuil_rolf1'],
                'seuilRolf2' => $r['seuil_rolf2'],
            ];


            $anr = new $cAnr();
            $anr->setDbAdapter($this->serviceLocator->get($this->dbUsed));
            $anr->exchangeArray($dataAnr);
            $corresp['anrs'][$r['anrid']] = $dataM['anr'] = $anrtable->save($anr);

            $model = new $c();
            $model->setDbAdapter($this->serviceLocator->get($this->dbUsed));
            $model->exchangeArray($dataM);
            $modelService->setDependencies($model,['anr']);
            $corresp['models'][$r['id']] = $table->save($model);

            // On ajoute une correspondace entre les anciens id_model & les nouveaux anr_id
            $corresp['model_anr'][$r['id']] = $corresp['anrs'][$r['anrid']];

            $compt++;
            $this->updateProgressbar($compt);
        }
        $this->finishProgressbar();
        $this->console->write("\t\tOK",ColorInterface::GREEN);
        $this->console->write(" $compt/".count($res)." elements\n");
	}
}