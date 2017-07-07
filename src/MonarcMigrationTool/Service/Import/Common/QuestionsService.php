<?php
namespace MonarcMigrationTool\Service\Import\Common;

use Zend\Console\ColorInterface;

class QuestionsService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = []){
        $res = $this->adapter->query('SELECT * FROM `dims_mod_smile_eval_ask` ORDER BY position ASC')->execute();
        $table = $this->serviceLocator->get('\MonarcCore\Model\Table\QuestionTable');
        $questionService = $this->serviceLocator->get('MonarcCore\Service\QuestionService');
        $c = $table->getClass();

        $tableChoice = $this->serviceLocator->get('\MonarcCore\Model\Table\QuestionChoiceTable');
        $questionChoiceService = $this->serviceLocator->get('MonarcCore\Service\QuestionChoiceService');
        $cc = $tableChoice->getClass();

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
            $questionService->set('entity',$entity);

            try{
                $data = array(
                    'label1' => $r['label'],
                    'label2' => '',
                    'label3' => '',
                    'label4' => '',
                    'type' => ($r['type'] == 2 ? 2 : 1),
                    'multichoice' => 0,
                    'implicitPosition' => \MonarcCore\Model\Entity\AbstractEntity::IMP_POS_END,
                );

                $idq = $questionService->create($data);
                if($r['type'] == 2){
                    // choices
                    $choices = explode("\n", $r['options']);
                    if(!empty($choices)){
                        $p = 1;
                        foreach($choices as $choice){
                            $entity = new $cc();
                            $entity->setDbAdapter($this->serviceLocator->get($this->dbUsed));

                            // $questionChoiceService->set('entity',$entity);

                            $data = array(
                                'question' => $idq,
                                'label1' => $choice,
                                'label2' => '',
                                'label3' => '',
                                'label4' => '',
                                'type' => 0,
                                'multichoice' => 0,
                                'position' => $p,
                            );
                            $p++;
                            // START: To delete & uncomment in "foreach"
                            $entity->squeezeAutoPositionning(true);
                            $entity->exchangeArray($data);
                            $entity->set('question',$table->getReference($entity->get('question')));
                            $tableChoice->save($entity);
                            // END: To delete

                            // $questionChoiceService->create($data); // TODO: not good > service QuestionsChoices not used
                        }
                    }
                }
                $compt++;
                $this->updateProgressbar($compt);
            }catch(\Exception $e){
                echo $e->getMessage()."\n";
            }
        }
        $this->finishProgressbar();
        $this->console->write("\t\tOK",ColorInterface::GREEN);
        $this->console->write(" $compt/".count($res)." elements\n");
    }
}