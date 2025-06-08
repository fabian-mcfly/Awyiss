<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Awyiss;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\SurveyQuestion;
use Awyiss\Routing\Router;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;


/**
 * SurveyQuestions Controller
 *
 * @property \Awyiss\Model\Table\SurveyQuestionsTable $SurveyQuestions
 */
class SurveyQuestionsController extends Controller {
	/**
	 * @inheritDoc
	 */
	protected array $paginate = [
		'enabled' => true,
	];


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		$lo_query = $this->SurveyQuestions->find()->where($this->getOverviewWhere());
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
		$lo_surveyQuestions = $this->paginate($lo_query);

		$this->set([
			'surveyQuestions' => $lo_surveyQuestions,
			'attributes' => $this->SurveyQuestions->getAttributes(),
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

		$lo_surveyQuestion = $this->SurveyQuestions->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($lo_surveyQuestion);
		}

		$this->set([
			'surveyQuestion' => $lo_surveyQuestion,
		]);
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

		/** @var SurveyQuestion $lo_surveyQuestion */
		$lo_surveyQuestion = $this->SurveyQuestions->findById($id)->find('translations')->find('mediaAssignments')->find('mediaElementAssignments')->first();
		if (!$lo_surveyQuestion) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_surveyQuestion, 'edit');
		}

		$this->set([
			'surveyQuestion' => $lo_surveyQuestion,
		]);
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

		/** @var SurveyQuestion $lo_surveyQuestion */
		$lo_surveyQuestion = $this->SurveyQuestions->findById($id)->first();
		if (!$lo_surveyQuestion) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->SurveyQuestions->delete($lo_surveyQuestion)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));
				foreach ($lo_surveyQuestion->getError('_general') as $ls_error) {
					$this->Flash->error($ls_error);
				}
			}
		}

		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param SurveyQuestion $surveyQuestion
	 * @param string $method
	 * @return void
	 * @throws \Cake\Http\Exception\RedirectException|\Exception
	 * @noinspection DuplicatedCode
	 */
	protected function save(SurveyQuestion $surveyQuestion, string $method = 'add'): void {
		$la_associated = [];
		if ($this->SurveyQuestions->hasAttributes()) {
			$la_associated[] = $this->SurveyQuestions->getAttributesTableName(true);
			$surveyQuestion->setAccess('attributes', true);
		}

		$la_data = $this->request->getData();

		if (
			!empty($la_data['answers']) &&
			$this->Authorization->scopeIsAccessible('SurveyAnswers', [], 'create')
		) {
			$la_associated[] = 'SurveyAnswers';
			$surveyQuestion->setAccess('surveyAnswers', true);
			$la_data['survey_answers'] = $this->buildAnswersData($la_data['answers']);
		}

		$this->SurveyQuestions->patchEntity($surveyQuestion, $la_data, [
			'associated' => $la_associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			$lb_saveAsCopy = (bool)$this->request->getData('save_as_copy');

			if ($this->SurveyQuestions->save($surveyQuestion, ['asCopy' => $lb_saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($lb_saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				if ($this->request->getData('submit_type') == 'submit_close') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'page' => $this->Paginate->calculateEntityPagePosition($surveyQuestion),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $surveyQuestion->id], true), 302);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__(($lb_saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($surveyQuestion->getError('_general') as $ls_error) {
					$this->Flash->error($ls_error);
				}
			}
		}
		elseif ($this->SurveyQuestions->hasBehavior('SystemOrder')) {
			if ($this->SurveyQuestions->hasDirtyRelatedSystemOrderColumns($surveyQuestion)) {
				/** @noinspection PhpUndefinedFieldInspection */
				$surveyQuestion->systemOrder = null;
			}
			else {
				/** @noinspection PhpUndefinedFieldInspection */
				$surveyQuestion->systemOrder = $surveyQuestion->hasOriginal('systemOrder') ? $surveyQuestion->getOriginal('systemOrder') : $surveyQuestion->get('systemOrder');
			}

			// Update the request data. Otherwise, the SystemOrderHelper would use the outdated request data
			$lo_request = $this->request->withData('system_order', $surveyQuestion->systemOrder);
			$this->setRequest($lo_request);
		}
	}


	/**
	 * @param mixed $answers
	 * @return array
	 * @throws \Exception
	 */
	protected function buildAnswersData(mixed $answers): array {
		if (!is_string($answers)) {
			return [];
		}

		$la_languages = LocaleMiddleware::getLanguages(Awyiss::REALM_FRONTEND);
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$lo_currentLanguage = LocaleMiddleware::getLanguage(Awyiss::REALM_FRONTEND);

		$la_answers = explode(PHP_EOL, $answers);
		$la_answers = array_map('trim', $la_answers);
		$la_answers = array_values(array_filter($la_answers));

		array_walk($la_answers, function (&$value, int $key) use ($la_languages, $lo_currentLanguage) {
			/** @noinspection PhpVariableNamingConventionInspection */
			$value = [
				'title' => $value,
				'system_order' => $key + 1,
			];

			if (count($la_languages) > 1) {
				/** @noinspection PhpVariableNamingConventionInspection */
				$value['_translations'][ $lo_currentLanguage->shortcode ] = [
					'title' => $value['title'],
				];
			}
		});

		return $la_answers;
	}
}
