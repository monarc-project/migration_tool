<?php
namespace MonarcMigrationTool\Service\Import\Backoffice;

use Zend\Console\ColorInterface;

class ClientsService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = []){
        // on va commencer par charger les correspondances des modèles
        $res = $this->adapterCommon->query("
            SELECT id, label
            FROM `dims_mod_smile_inventory_model`
            WHERE deleted = 0
        ")->execute();
        $tmp = [];
        foreach($res as $r){
            $tmp[trim($r['label'])] = $r['id'];
            $corresp['models'][$r['id']] = -1;
        }
        $models = $this->serviceLocator->get('\MonarcCore\Model\Table\ModelTable')->fetchAllObject();
        foreach($models as $m){
            $id = isset($tmp[trim($m->get('label1'))])?$tmp[trim($m->get('label1'))]:null;
            if(isset($corresp['models'][$id])){
                $corresp['models'][$id] = $m->get('id');
            }
        }

        $res = $this->adapter->query('
            SELECT t.*, c.firstname, c.lastname, c.email, c.phone
            FROM `dims_mod_business_tiers` t
            LEFT JOIN `dims_mod_business_contact` c
            ON c.id_tiers = t.id
            WHERE t.profile_type = 2
            GROUP BY t.id
            ORDER BY t.intitule, c.id
        ')->execute();

        $table = $this->serviceLocator->get('\MonarcBO\Model\Table\ClientTable');
        $clientService = $this->serviceLocator->get('MonarcBO\Service\ClientService');
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
                'model_id' => (isset($corresp['models'][$r['id_smile_model']])?$corresp['models'][$r['id_smile_model']]:null),
                'server_id' => (isset($corresp['servers'][$r['id_smile_server']])?$corresp['servers'][$r['id_smile_server']]:null),
                'logo_id' => null,
                'country_id' => null,
                'city_id' => null,
                'name' => $r['intitule'],
                'proxyAlias' => $r['proxy_alias'],
                'address' => $r['adresse'],
                'postalcode' => $r['codepostal'],
                'phone' => $r['telephone'],
                'fax' => $r['telecopie'],
                'email' => $r['mel'],
                'employees_number' => $r['nb_employees'],
                'contactFullname' => '', // Champ inexistant dans l'ancienne version
                'contact_email' => '', // Champ inexistant dans l'ancienne version
                'contact_phone' => '', // Champ inexistant dans l'ancienne version
                'first_user_firstname' => $r['firstname'],
                'first_user_lastname' => $r['lastname'],
                'first_user_email' => $r['email'],
                'first_user_phone' => $r['phone'],
            ];

            // On récupère le pays
            if(!empty($r['id_country'])){
                if(!isset($corresp['countries'][$r['id_country']])){
                    $rC = $this->adapterCommon->query("
                        SELECT id, iso3
                        FROM `dims_country`
                        WHERE id = ".$r['id_country']."
                    ")->execute()->current();
                    if($rC){
                        $country = current($this->serviceLocator->get('\MonarcCore\Model\Table\CountryTable')->getEntityByFields(['iso3' => trim($rC['iso3'])]));
                        if(!empty($country)){
                            $corresp['countries'][$r['id_country']] = $country->get('id');
                        }
                    }
                }
                $data['country_id'] = isset($corresp['countries'][$r['id_country']])?$corresp['countries'][$r['id_country']]:null;
            }
            // On récupère la commune
            if(!empty($r['id_city'])){
                if(!isset($corresp['cities'][$r['id_city']])){
                    $rC = $this->adapterCommon->query("
                        SELECT id, label
                        FROM `dims_city`
                        WHERE id = ".$r['id_city']."
                        LIMIT 1
                    ")->execute()->current();
                    if($rC){
                        $city = current($this->serviceLocator->get('\MonarcCore\Model\Table\CityTable')->getEntityByFields(
                            ['label' => [
                                'op' => 'LIKE',
                                'value' => $rC['label'],
                            ]]
                        ));
                        if(!empty($city)){
                            $corresp['cities'][$r['id_city']] = $city->get('id');
                        }
                    }
                }
                $data['city_id'] = isset($corresp['cities'][$r['id_city']])?$corresp['cities'][$r['id_city']]:null;
            }

            $entity = new $c();
            $entity->setDbAdapter($this->serviceLocator->get($this->dbUsed));
            $entity->exchangeArray($data);
            $clientService->setDependencies($entity,['server', 'logo']);

            $corresp['clients'][$r['id']] = $table->save($entity);

            $compt++;
            $this->updateProgressbar($compt);
        }
        $this->finishProgressbar();
        $this->console->write("\t\tOK",ColorInterface::GREEN);
        $this->console->write(" $compt/".count($res)." elements\n");
    }
}
