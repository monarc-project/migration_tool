<?php
namespace MonarcMigrationTool\Service\Import\Backoffice;

use Zend\Console\ColorInterface;

class ServersService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = []){
    	// !!! les servers sont sur l'ancienne base Common
        $res = $this->adapterCommon->query('SELECT * FROM `dims_server`')->execute();

        $table = $this->serviceLocator->get('\MonarcBO\Model\Table\ServerTable');
        $clientService = $this->serviceLocator->get('MonarcBO\Service\ServerService');
        $c = $table->getClass();

        $this->console->write("\t- ".$c.":\n");

        if(!class_exists($c)){
            $this->console->write("\t\tERR\n",ColorInterface::RED);
            return false;
        }

        $compt = 0;
        $this->createProgressbar(count($res));
        foreach($res as $r){
            /*
            On ne passe pas par le service, car on ne veux pas générer le JSON associé au client
            */
            $data = [
                'label' => $r['label'],
                'ip_address' => $r['address'],
                'fqdn' => $r['fqdn'],
                'status' => $r['status'],
            ];
            $entity = new $c();
            $entity->setDbAdapter($this->serviceLocator->get($this->dbUsed));
            $entity->exchangeArray($data);
            $corresp['servers'][$r['id']] = $table->save($entity);

            $compt++;
            $this->updateProgressbar($compt);
        }
        $this->finishProgressbar();
        $this->console->write("\t\tOK",ColorInterface::GREEN);
        $this->console->write(" $compt/".count($res)." elements\n");
    }
}
