<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\SurveyAnswer;
use Awyiss\Routing\Router;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;


/**
 * SurveyAnswers Controller
 *
 * @property \Awyiss\Model\Table\SurveyAnswersTable $SurveyAnswers
 */
class SurveyAnswersController extends Controller {
	/**
	 * @inheritDoc
	 */
	protected array $categories = [
		'uriParam' => 'question-id',
	];


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		$query = $this->SurveyAnswers->find()->where($this->getOverviewWhere());
		$this->Categories->filterQuery($query);
		$this->Search->filterQuery($query);

		return $query;
	}


	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$query = $this->getOverviewQuery();
		$surveyAnswers = $query->all();

		$question = $this
			->fetchTable('SurveyQuestions')
			->findById($this->Categories->getSelectedCategory())
			->first()
		;

		$this->set([
			'surveyAnswers' => $surveyAnswers,
			'surveyQuestion' => $question,
			'disabledSurveyQuestions' => $this->SurveyAnswers->getDisabledQuestions(),
			'attributes' => $this->SurveyAnswers->getAttributes(),
		]);
	}


	/**
	 * Add method
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function add(): void {
		$this->Authorization->ensure('create');

		$surveyAnswer = $this->SurveyAnswers->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($surveyAnswer);
		}

		$this->setViewVars($surveyAnswer);
	}


	/**
	 * Edit method
	 *
	 * @param int $id
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $id) {
		$this->Authorization->ensure('update');

		/**
		 * @var \Awyiss\Model\Entity\SurveyAnswer $surveyAnswer
		 * @uses \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
		 * @uses \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::findMediaElementAssignments()
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
		$surveyAnswer = $this->SurveyAnswers
			->findById($id)
			->find('translations')
			->find('mediaAssignments')
			->find('mediaElementAssignments')
			->first()
		;
		if (!$surveyAnswer) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($surveyAnswer, 'edit');
		}

		$this->setViewVars($surveyAnswer);
	}


	/**
	 * Delete method
	 *
	 * @param int $id
	 * @return \Cake\Http\Response
	 * @throws \Exception
	 */
	public function delete(int $id): Response {
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var \Awyiss\Model\Entity\SurveyAnswer $surveyAnswer */
		$surveyAnswer = $this->SurveyAnswers->findById($id)->first();
		if (!$surveyAnswer) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->SurveyAnswers->delete($surveyAnswer)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));
				foreach ($surveyAnswer->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}

		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param SurveyAnswer $surveyAnswer
	 * @param string $method
	 * @return void
	 * @throws \Cake\Http\Exception\RedirectException
	 */
	protected function save(SurveyAnswer $surveyAnswer, string $method = 'add'): void {
		$associated = [];
		if ($this->SurveyAnswers->hasAttributes()) {
			$associated[] = $this->SurveyAnswers->getAttributesTableName(true);
			$surveyAnswer->setAccess('attributes', true);
		}

		$this->SurveyAnswers->patchEntity($surveyAnswer, $this->request->getData(), [
			'associated' => $associated,
			'validate' => !$this->request->getData('reloadForm'),
		]);

		//reloadForm is set when we need to reload options based on current values
		if (!$this->request->getData('reloadForm')) {
			$saveAsCopy = (bool)$this->request->getData('saveAsCopy');

			if ($this->SurveyAnswers->save($surveyAnswer, ['asCopy' => $saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				if ($this->request->getData('submitType') == 'submitClose') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'questionId' => $surveyAnswer->surveyQuestionId,
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $surveyAnswer->id], true), 302);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__(($saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($surveyAnswer->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}

		$this->Categories->ensurePossibleCategory($surveyAnswer);
	}


	/**
	 * @param \Awyiss\Model\Entity\SurveyAnswer $surveyAnswer
	 * @return void
	 */
	protected function setViewVars(SurveyAnswer $surveyAnswer): void {
		$this->set([
			'surveyAnswer' => $surveyAnswer,
			'disabledSurveyQuestions' => $this->SurveyAnswers->getDisabledQuestions(),
		]);
	}
}
