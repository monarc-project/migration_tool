<?php
namespace MonarcMigrationTool\Service\Import\Client;

use Zend\Console\ColorInterface;

class QuestionsService extends \MonarcMigrationTool\Service\Import\AbstractService{

    public function import(&$corresp = [], $oldAnr = null){
        if(!empty($corresp['anrs'][$oldAnr])){
            $res = $this->adapter->query('
                SELECT *
                FROM `dims_mod_smile_eval_ask`
                WHERE anr_id  = '.$oldAnr.'
                ORDER BY position ASC
            ')->execute();
            $table = $this->serviceLocator->get('\MonarcFO\Model\Table\QuestionTable');
            $questionService = $this->serviceLocator->get('MonarcFO\Service\AnrQuestionService');
            $c = $table->getClass();

            $tableChoice = $this->serviceLocator->get('\MonarcFO\Model\Table\QuestionChoiceTable');
            $questionChoiceService = $this->serviceLocator->get('MonarcFO\Service\AnrQuestionChoiceService');
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
                        'anr' => $corresp['anrs'][$r['anr_id']],
                        'label1' => $r['label'],
                        'label2' => '',
                        'label3' => '',
                        'label4' => '',
                        'type' => ($r['type'] == 2 ? 2 : 1),
                        'multichoice' => 0,
                        'implicitPosition' => \MonarcCore\Model\Entity\AbstractEntity::IMP_POS_END,
                        'response' => $r['answer'],
                    );

                    $idq = $questionService->create($data);
                    unset($data);
                    if($r['type'] == 2){
                        // choices
                        $choices = explode("\n", $r['options']);
                        if(!empty($choices)){
                            $p = 1;
                            foreach($choices as $choice){
                                $entity = new $cc();
                                $db = $this->serviceLocator->get($this->dbUsed);
                                $db->getEntityManager();
                                $entity->setDbAdapter($db);

                                // $questionChoiceService->set('entity',$entity);

                                $data = array(
                                    'anr' => $corresp['anrs'][$r['anr_id']],
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
                                $questionService->setDependencies($entity,['anr','question']);
                                $tableChoice->save($entity);
                                // END: To delete

                                // $questionChoiceService->create($data); // TODO: not good > service QuestionsChoices not used
                                $db->getEntityManager()->detach($entity);
                                unset($entity,$data,$db);
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
            unset($compt,$res,$questionService,$table,$tableChoice,$c,$cc,$questionChoiceService);
        }else{
            $this->console->write("\t\tNo ANR imported\n",ColorInterface::YELLOW);
            return false;
        }
    }
}