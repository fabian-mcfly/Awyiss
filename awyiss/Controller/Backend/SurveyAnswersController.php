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
		$lo_query = $this->SurveyAnswers->find()->where($this->getOverviewWhere());
		$this->Categories->filterQuery($lo_query);
		$this->Search->filterQuery($lo_query);

		return $lo_query;
	}


	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$lo_query = $this->getOverviewQuery();
		$lo_surveyAnswers = $lo_query->all();

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$lo_question = $this->fetchTable('SurveyQuestions')->findById($this->Categories->getSelectedCategory())->first();

		$this->set([
			'surveyAnswers' => $lo_surveyAnswers,
			'surveyQuestion' => $lo_question,
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

		$lo_surveyAnswer = $this->SurveyAnswers->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($lo_surveyAnswer);
		}

		$this->setViewVars($lo_surveyAnswer);
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
		 * @var SurveyAnswer $lo_surveyAnswer
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
		$lo_surveyAnswer = $this->SurveyAnswers->findById($id)->find('translations')->find('mediaAssignments')->find('mediaElementAssignments')->first();
		if (!$lo_surveyAnswer) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_surveyAnswer, 'edit');
		}

		$this->setViewVars($lo_surveyAnswer);
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

		/** @var SurveyAnswer $lo_surveyAnswer */
		$lo_surveyAnswer = $this->SurveyAnswers->findById($id)->first();
		if (!$lo_surveyAnswer) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->SurveyAnswers->delete($lo_surveyAnswer)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));
				foreach ($lo_surveyAnswer->getError('_general') as $ls_error) {
					$this->Flash->error($ls_error);
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
		$la_associated = [];
		if ($this->SurveyAnswers->hasAttributes()) {
			$la_associated[] = $this->SurveyAnswers->getAttributesTableName(true);
			$surveyAnswer->setAccess('attributes', true);
		}

		$la_data = $this->request->getData();

		$this->SurveyAnswers->patchEntity($surveyAnswer, $la_data, [
			'associated' => $la_associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			$lb_saveAsCopy = (bool)$this->request->getData('save_as_copy');

			if ($this->SurveyAnswers->save($surveyAnswer, ['asCopy' => $lb_saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($lb_saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				if ($this->request->getData('submit_type') == 'submit_close') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'questionId' => $surveyAnswer->surveyQuestionId,
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $surveyAnswer->id], true), 302);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__(($lb_saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($surveyAnswer->getError('_general') as $ls_error) {
					$this->Flash->error($ls_error);
				}
			}
		}
		elseif ($this->SurveyAnswers->hasBehavior('SystemOrder')) {
			if ($this->SurveyAnswers->hasDirtyRelatedSystemOrderColumns($surveyAnswer)) {
				$surveyAnswer->systemOrder = null;
			}
			else {
				$surveyAnswer->systemOrder = $surveyAnswer->hasOriginal('systemOrder') ? $surveyAnswer->getOriginal('systemOrder') : $surveyAnswer->get('systemOrder');
			}

			// Update the request data. Otherwise, the SystemOrderHelper would use the outdated request data
			$lo_request = $this->request->withData('system_order', $surveyAnswer->systemOrder);
			$this->setRequest($lo_request);
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
