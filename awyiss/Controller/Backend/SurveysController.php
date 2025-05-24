<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Core\App;
use Awyiss\Model\Entity\Survey;
use Awyiss\Model\Entity\SurveyAnswer;
use Awyiss\Model\Enum\SurveyType;
use Awyiss\Routing\Router;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Security;


/**
 * Surveys Controller
 *
 * @property \Awyiss\Model\Table\SurveysTable $Surveys
 */
class SurveysController extends Controller {
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
		$lo_query = $this->Surveys->find()->where($this->getOverviewWhere());
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
		$lo_surveys = $this->paginate($lo_query);

		$this->set([
			'surveys' => $lo_surveys,
			'attributes' => $this->Surveys->getAttributes(),
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

		$lo_survey = $this->Surveys->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($lo_survey);
		}

		$this->setViewVars($lo_survey);
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

		/** @var Survey $lo_survey */
		$lo_survey = $this->Surveys->findById($id)
			->find('translations')
			->find('mediaAssignments')
			->find('mediaElementAssignments')
			->contain([
				'SurveySurveyQuestions' => [
					'SurveyQuestions' => [
						'SurveyAnswers',
					],
					'SurveySurveyAnswers' => [
						'SurveyAnswers',
					],
				],
			])
			->first();

		if (!$lo_survey) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_survey, 'edit');
		}

		$this->setViewVars($lo_survey);
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

		/** @var Survey $lo_survey */
		$lo_survey = $this->Surveys->findById($id)->first();
		if (!$lo_survey) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Surveys->delete($lo_survey)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));
				foreach ($lo_survey->getError('_general') as $ls_error) {
					$this->Flash->error($ls_error);
				}
			}
		}

		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param Survey $survey
	 * @param string $method
	 * @return void
	 * @throws \Cake\Http\Exception\RedirectException
	 */
	protected function save(Survey $survey, string $method = 'add'): void {
		$la_associated = [
			'SurveySurveyQuestions' => [
				'accessibleFields' => [
					'surveySurveyAnswers' => true,
				],
				'associated' => [
					'SurveySurveyAnswers',
				],
			],
		];
		$survey->setAccess('surveySurveyQuestions', true);
		if ($this->Surveys->hasAttributes()) {
			$la_associated[] = $this->Surveys->getAttributesTableName(true);
			$survey->setAccess('attributes', true);
		}

		$la_data = $this->request->getData();
		$la_data = $this->buildQuestionsData($la_data);

		$this->Surveys->patchEntity($survey, $la_data, [
			'associated' => $la_associated,
			'validate' => !$this->request->getData('reload_form'),
		]);


		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			$lb_saveAsCopy = (bool)$this->request->getData('save_as_copy');

			if ($this->Surveys->save($survey, ['asCopy' => $lb_saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($lb_saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				if ($this->request->getData('submit_type') == 'submit_close') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'page' => $this->Paginate->calculateEntityPagePosition($survey),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $survey->id], true), 302);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__(($lb_saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($survey->getError('_general') as $ls_error) {
					$this->Flash->error($ls_error);
				}
			}
		}
		elseif ($this->Surveys->hasBehavior('SystemOrder')) {
			if ($this->Surveys->getSystemOrderRelatedColumns($survey)) {
				$survey->systemOrder = null;
			}
			else {
				$survey->systemOrder = $survey->hasOriginal('systemOrder') ? $survey->getOriginal('systemOrder') : $survey->get('systemOrder');
			}

			// Update the request data. Otherwise, the SystemOrderHelper would use the outdated request data
			$lo_request = $this->request->withData('system_order', $survey->systemOrder);
			$this->setRequest($lo_request);
		}

		if ($survey->surveySurveyQuestions) {
			$la_surveyQuestionIds = array_column($survey->surveySurveyQuestions, 'surveyQuestionId');
			$la_surveyQuestions = $this->fetchTable('SurveyQuestions')
				->find('all')
				->contain(['SurveyAnswers'])
				->where(['SurveyQuestions.id IN' => $la_surveyQuestionIds])
				->all()
				->indexBy('id')
				->toArray();

			$lb_hasError = false;
			/** @var class-string<\Awyiss\Model\Enum\SurveyType> $ls_surveyTypeEnum */
			$ls_surveyTypeEnum = App::className('SurveyType', 'Model/Enum');
			foreach ($survey->surveySurveyQuestions as $li_key => $lo_surveySurveyQuestion) {
				$survey->surveySurveyQuestions[ $li_key ]->surveyQuestion = $la_surveyQuestions[ $lo_surveySurveyQuestion->surveyQuestionId ] ?? null;

				if (
					$survey->type === $ls_surveyTypeEnum::Linear &&
					array_count_values($la_surveyQuestionIds)[ $lo_surveySurveyQuestion->surveyQuestionId ] > 1
				) {
					$lb_hasError = true;
					$lo_surveySurveyQuestion->setError('surveyQuestionId', __('error_no_repeated_questions_in_linear_survey'));
				}
			}

			$survey->surveySurveyQuestions = array_values(array_filter($survey->surveySurveyQuestions ?? []));
			if ($lb_hasError) {
				$survey->setError('surveySurveyQuestions', __('error_no_repeated_questions_in_linear_survey'));
			}
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\Survey $survey
	 * @return void
	 */
	protected function setViewVars(Survey $survey): void {
		$la_availableQuestions = $this->fetchTable('SurveyQuestions')->find('all')
			->contain(['SurveyAnswers'])
			->all()
			->indexBy('id')
			->toArray();


		if ($survey->surveySurveyQuestions) {
			/** @var \Awyiss\Model\Entity\SurveySurveyQuestion $lo_question */
			foreach ($survey->surveySurveyQuestions as $lo_question) {
				if (
					!$lo_question->surveySurveyAnswers ||
					!$lo_question->surveyQuestion?->surveyAnswers
				) {
					continue;
				}

				$lo_question->surveySurveyAnswers = array_combine(
					array_column($lo_question->surveySurveyAnswers, 'surveyAnswerId'),
					$lo_question->surveySurveyAnswers
				);

				$la_answerKeys = array_keys($lo_question->surveySurveyAnswers);
				$la_defaultAnswers = $lo_question->surveyQuestion?->surveyAnswers;

				usort($la_defaultAnswers, function (SurveyAnswer $a, SurveyAnswer $b) use ($la_answerKeys): int {
					$li_posA = array_search($a->id, $la_answerKeys);
					$li_posB = array_search($b->id, $la_answerKeys);

					return match (true) {
						$li_posA === false && $li_posB === false => 0,
						$li_posA === false => 1,
						$li_posB === false => -1,
						default => $li_posA <=> $li_posB,
					};
				});

				$lo_question->surveyQuestion->surveyAnswers = $la_defaultAnswers;
			}
		}

		$this->set([
			'survey' => $survey,
			'availableQuestions' => $la_availableQuestions,
		]);
	}


	/**
	 * @param array $data
	 * @return array
	 */
	protected function buildQuestionsData(array &$data): array {
		$li_count = 0;

		if (
			!isset($data['survey_survey_questions']) ||
			!is_array($data['survey_survey_questions'])
		) {
			/** @noinspection PhpVariableNamingConventionInspection */
			$data['survey_survey_questions'] = [];
		}

		foreach ($data['survey_survey_questions'] as $lx_key => $la_questionData) {
			if (
				!is_array($la_questionData) ||
				!isset($la_questionData['survey_question_id'])
			) {
				/** @noinspection PhpVariableNamingConventionInspection */
				unset($data['survey_survey_questions'][$lx_key]);
				continue;
			}

			$la_questionData = $this->buildAnswersData($la_questionData);

			/** @noinspection PhpVariableNamingConventionInspection */
			$data['survey_survey_questions'][ $lx_key ] = [
				'id' => $la_questionData['id'] ?? null,
				'surveyQuestionId' => $la_questionData['survey_question_id'],
				'identifier' => $la_questionData['identifier'] ?: Security::randomString(8),
				'systemOrder' => $li_count + 1,
				'surveySurveyAnswers' => $la_questionData['survey_survey_answers'] ?? [],
			];

			$li_count++;
		}

		return $data;
	}


	/**
	 * @param array $data
	 * @return array
	 */
	protected function buildAnswersData(array &$data): array {
		$li_count = 0;

		if (
			!isset($data['survey_survey_answers']) ||
			!is_array($data['survey_survey_answers'])
		) {
			/** @noinspection PhpVariableNamingConventionInspection */
			$data['survey_survey_answers'] = [];
		}

		foreach (($data['survey_survey_answers'] ?? []) as $lx_key => $la_answerData) {
			if (
				!is_array($la_answerData) ||
				!isset($la_answerData['survey_answer_id'])
			) {
				/** @noinspection PhpVariableNamingConventionInspection */
				unset($data['survey_survey_answers'][ $lx_key ]);
				continue;
			}

			/** @noinspection PhpVariableNamingConventionInspection */
			$data['survey_survey_answers'][ $lx_key ] = [
				'id' => $la_answerData['id'] ?? null,
				'surveyAnswerId' => $la_answerData['survey_answer_id'],
				'systemOrder' => $li_count + 1,
				'active' => $la_answerData['active'] ?? false,
			];

			$li_count++;
		}

		return $data;
	}
}
