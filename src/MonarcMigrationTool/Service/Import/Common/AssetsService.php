<?php
namespace MonarcMigrationTool\Service\Import\Common;

use Zend\Console\ColorInterface;

class AssetsService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = []){
        $res = $this->adapter->query('SELECT * FROM `dims_mod_smile_asset_models`')->execute();
        $assetsModels = array();
        foreach($res as $r){
            if(!isset($assetsModels[$r['asset_id']])){
                $assetsModels[$r['asset_id']] = array();
            }
            $assetsModels[$r['asset_id']][] = $r['model_id'];
        }

        $res = $this->adapter->query('SELECT * FROM `dims_mod_smile_asset` WHERE is_simplified = 0 AND status = 1')->execute();
        $table = $this->serviceLocator->get('\MonarcCore\Model\Table\AssetTable');
        $modelTable = $this->serviceLocator->get('\MonarcCore\Model\Table\ModelTable');
        $assetService = $this->serviceLocator->get('MonarcCore\Service\AssetService');
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
            $assetService->set('entity',$entity);
            $data = array(
                'mode' => ($r['io_level'] == 1 ? 0 : 1),
                'type' => ($r['type']==3?2:$r['type']),
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
                'models' => array(),
            );

            if (!empty($assetsModels[$r['id']])) {
                foreach ($assetsModels[$r['id']] as $key => $modelId) {
                    if (!empty($corresp['models'][$modelId])) {
                        // $data['models'][] = $modelTable->getEntity( $corresp['models'][$modelId] );
                        $data['models'][] = [ 'id' => $corresp['models'][$modelId] ];
                    }
                }
            }
            $corresp['assets'][$r['id']] = $assetService->create($data);
            $compt++;
            $this->updateProgressbar($compt);
        }
        $this->finishProgressbar();
        $this->console->write("\t\tOK",ColorInterface::GREEN);
        $this->console->write(" $compt/".count($res)." elements\n");
    }
}