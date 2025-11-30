<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Core\App;
use Awyiss\Model\Entity\Survey;
use Awyiss\Model\Entity\SurveyAnswer;
use Awyiss\Model\Entity\SurveySurveyAnswer;
use Awyiss\Model\Entity\SurveySurveyQuestion;
use Awyiss\Routing\Router;
use Awyiss\Utility\Arrays;
use Cake\Collection\CollectionInterface;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Security;
use Exception;


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
	 * All available survey questions
	 * indexed by their ID
	 *
	 * @var array<int, \Awyiss\Model\Entity\SurveyQuestion>
	 */
	protected array $surveyQuestions = [];


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function initialize(): void {
		parent::initialize();

		$this->surveyQuestions = $this->fetchTable('SurveyQuestions')
			->find('all')
			->contain(['SurveyAnswers'])
			->all()
			->indexBy('id')
			->toArray();

		Arrays::naturalSort($this->surveyQuestions, 'title');
	}


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

		/**
		 * @var \Awyiss\Model\Entity\Survey $lo_survey
		 * @uses \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
		 * @uses \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::findMediaElementAssignments()
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
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
	 * Add method
	 *
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function diagram(): void {
		$this->Authorization->ensure('read');

		$li_id = (int)$this->request->getParam('id');

		if (!$li_id) {
			$this->Flash->error(__('record_not_found'));
			throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
		}

		/**
		 * @var \Awyiss\Model\Entity\Survey $lo_survey
		 * @uses \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
		 * @uses \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::findMediaElementAssignments()
		 */
		$lo_survey = $this->Surveys->findById($li_id)
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
			throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
		}

		$li_entryId = (int)$this->request->getParam('entryId');
		if ($li_entryId) {
			$lo_surveyEntriesTable = $this->fetchTable('SurveyEntries');
			$lo_entry = $lo_surveyEntriesTable->find()->where([
				'id' => $li_entryId,
				'survey_id' => $li_id,
			])->first();

			if ($lo_entry) {
				$la_postData = $this->decodeEntryData($lo_entry->data);
				$this->set('entryData', $la_postData);
			}
		}

		$this->setViewVars($lo_survey);
	}


	/**
	 * Analysis method - Shows detailed analysis and statistics for a survey
	 *
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function analyze() {
		$this->Authorization->ensure('analyze');

		$li_id = (int)$this->request->getParam('id');

		if (!$li_id) {
			$this->Flash->error(__('record_not_found'));
			throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
		}

		/**
		 * @var \Awyiss\Model\Entity\Survey $lo_survey
		 * @uses \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
		 * @uses \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::findMediaElementAssignments()
		 */
		$lo_survey = $this->Surveys->findById($li_id)
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
			])->first();

		if (!$lo_survey) {
			$this->Flash->error(__('record_not_found'));
			throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
		}

		// Fetch all entries for this survey
		$lo_surveyEntriesTable = $this->fetchTable('SurveyEntries');
		$lo_entries = $lo_surveyEntriesTable->find()->where([
			'survey_id' => $li_id,
		])->orderBy(['created_on' => 'DESC'])->all();

		// Analyze the data
		$la_analysis = $this->analyzeEntries($lo_survey, $lo_entries);

		/** @var class-string<\Awyiss\Model\Enum\Survey\QuestionType> $ls_questionTypeEnum */
		$ls_questionTypeEnum = App::className('QuestionType', 'Model/Enum/Survey');

		$lo_entries = $this->paginate($lo_surveyEntriesTable->find()->where([
			'survey_id' => $li_id,
		]), [
			'limit' => 20,
			'order' => [
				'created_on' => 'DESC',
			],
		]);

		$this->set([
			'survey' => $lo_survey,
			'entries' => $lo_entries,
			'analysis' => $la_analysis,
			'questionTypeEnum' => $ls_questionTypeEnum,
		]);
	}


	/**
	 * Analyze survey entries and calculate statistics
	 *
	 * @param \Awyiss\Model\Entity\Survey $survey
	 * @param \Cake\Collection\CollectionInterface $entries
	 * @return array
	 */
	protected function analyzeEntries(Survey $survey, CollectionInterface $entries): array {
		$la_analysis = [
			'questions' => [],
			'customAnswers' => [],
		];

		// Initialize question statistics
		foreach ($survey->surveySurveyQuestions as $lo_question) {
			$ls_identifier = $lo_question->identifier;

			$la_analysis['questions'][ $ls_identifier ] = [
				'question' => $lo_question,
				'title' => $lo_question->title ?? $lo_question->surveyQuestion->title,
				'type' => $lo_question->surveyQuestion->type,
				'answers' => [],
				'customAnswerCount' => 0,
				'totalResponses' => 0,
			];

			// Initialize answer counts
			foreach ($lo_question->surveySurveyAnswers as $lo_surveyAnswer) {
				$la_analysis['questions'][ $ls_identifier ]['answers'][ $lo_surveyAnswer->id ] = [
					'answer' => $lo_surveyAnswer,
					'title' => $lo_surveyAnswer->title ?? $lo_surveyAnswer->surveyAnswer->title,
					'count' => 0,
				];
			}
		}

		// Process each entry
		foreach ($entries as $lo_surveyEntry) {
			// Decode the entry data
			$la_postData = $this->decodeEntryData($lo_surveyEntry->data);

			if (!$la_postData || !isset($la_postData['progress'])) {
				continue;
			}

			// Ensure customAnswers key exists
			$la_postData['customAnswers'] ??= [];

			// Process each answer in the progress data
			foreach ($la_postData['progress'] as $ls_identifier => $lx_answer) {
				// Skip if the question is unknown, e.g., deleted question
				if (!isset($la_analysis['questions'][ $ls_identifier ])) {
					continue;
				}

				$la_analysis['questions'][ $ls_identifier ]['totalResponses']++;

				// Unify answers to an array to simplify processing
				$la_answer = !is_array($lx_answer) ? [$lx_answer] : $lx_answer;

				foreach ($la_answer as $lx_answerId) {
					if ($lx_answerId === 'custom') {
						$la_analysis['questions'][ $ls_identifier ]['customAnswerCount']++;

						// Store custom answers
						if (isset($la_postData['customAnswers'][ $ls_identifier ])) {
							$la_analysis['customAnswers'][ $ls_identifier ] ??= [];
							$la_analysis['customAnswers'][ $ls_identifier ][] = $la_postData['customAnswers'][ $ls_identifier ];
						}

						continue;
					}

					$li_answerId = (int)$lx_answerId;
					if (isset($la_analysis['questions'][ $ls_identifier ]['answers'][ $li_answerId ])) {
						$la_analysis['questions'][ $ls_identifier ]['answers'][ $li_answerId ]['count']++;
					}
				}
			}
		}

		// Calculate percentages
		foreach ($la_analysis['questions'] as &$la_questionData) {
			$li_total = $la_questionData['totalResponses'];

			if ($li_total === 0) {
				continue;
			}

			foreach ($la_questionData['answers'] as &$la_answerData) {
				$la_answerData['percentage'] = round($la_answerData['count'] / $li_total * 100, 2);
			}
			unset($la_answerData);

			$la_questionData['customAnswerPercentage'] = round($la_questionData['customAnswerCount'] / $li_total * 100, 2);
		}
		unset($la_questionData);

		return $la_analysis;
	}


	/**
	 * Decode entry data from database format
	 *
	 * @param string|null $encodedData
	 * @return array|null
	 */
	protected function decodeEntryData(?string $encodedData): ?array {
		if (empty($encodedData)) {
			return null;
		}

		try {
			$ls_decompressed = gzuncompress(base64_decode($encodedData));
			if ($ls_decompressed === false) {
				return null;
			}

			$la_data = json_decode($ls_decompressed, true);
			if (json_last_error() !== JSON_ERROR_NONE) {
				return null;
			}

			return $la_data ?: [];
		}
		catch (Exception) {
			return null;
		}
	}

	/**
	 * @param Survey $survey
	 * @param string $method
	 * @return void
	 * @throws \Cake\Http\Exception\RedirectException
	 * @noinspection DuplicatedCode
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

		if ($survey->surveySurveyQuestions) {
			$la_surveyQuestionIds = array_column($survey->surveySurveyQuestions, 'surveyQuestionId');

			/** @var class-string<\Awyiss\Model\Enum\Survey\Type> $ls_surveyTypeEnum */
			$ls_surveyTypeEnum = App::className('Type', 'Model/Enum/Survey');
			foreach ($survey->surveySurveyQuestions as $li_key => $lo_surveySurveyQuestion) {
				$survey->surveySurveyQuestions[ $li_key ]->surveyQuestion = $this->surveyQuestions[ $lo_surveySurveyQuestion->surveyQuestionId ] ?? null;

				// If any question is repeated in a linear survey, set an error.
				if (
					$survey->type === $ls_surveyTypeEnum::Linear &&
					array_count_values($la_surveyQuestionIds)[ $lo_surveySurveyQuestion->surveyQuestionId ] > 1
				) {
					$survey->setError('surveySurveyQuestions', __('error_no_repeated_questions_in_linear_survey'));
					$lo_surveySurveyQuestion->setError('surveyQuestionId', __('error_no_repeated_questions_in_linear_survey'));
				}

				// Build the answers array for each question
				$la_answers = $survey->surveySurveyQuestions[ $li_key ]->surveyQuestion?->surveyAnswers ?? [];
				$la_answers = array_column($la_answers, null, 'id');

				// Ensure that each surveySurveyAnswer has a surveyAnswer set. It gets lost in the patchEntity process.
				// since it is not part of the request data.
				foreach ($lo_surveySurveyQuestion->surveySurveyAnswers as $lo_surveySurveyAnswer) {
					$lo_surveySurveyAnswer->surveyAnswer = $la_answers[ $lo_surveySurveyAnswer->surveyAnswerId ] ?? null;
				}
			}

			$la_errors = $survey->getError('surveySurveyQuestions');

			// Remove empty questions
			$survey->surveySurveyQuestions = array_filter($survey->surveySurveyQuestions ?? []);

			// Sort the questions by system order as the order of the entities might be based on the old db order
			usort($survey->surveySurveyQuestions, function (SurveySurveyQuestion $a, SurveySurveyQuestion $b) {
				return $a->systemOrder <=> $b->systemOrder;
			});

			if ($la_errors) {
				$survey->setError('surveySurveyQuestions', $la_errors);
			}
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\Survey $survey
	 * @return void
	 */
	protected function setViewVars(Survey $survey): void {
		$la_specifyQuestionOptions = [];
		if ($survey->surveySurveyQuestions) {
			foreach ($survey->surveySurveyQuestions as $lo_question) {
				$la_specifyQuestionOptions[] = [
					'text' => $lo_question->label,
					'value' => $lo_question->identifier,
				];

				if (
					!$lo_question->surveySurveyAnswers ||
					!$lo_question->surveyQuestion?->surveyAnswers
				) {
					continue;
				}

				$this->sortAnswers($lo_question);
			}

			/** @var class-string<\Awyiss\Model\Enum\Survey\Type> $ls_surveyTypeEnum */
			$ls_surveyTypeEnum = App::className('Type', 'Model/Enum/Survey');

			if ($survey->type !== $ls_surveyTypeEnum::Linear) {
				$this->markUnreachableQuestions($survey->surveySurveyQuestions, $survey);
			}
		}

		/** @var class-string<\Awyiss\Model\Enum\Survey\NextAction> $ls_surveyNextActionEnum */
		$ls_surveyNextActionEnum = App::className('NextAction', 'Model/Enum/Survey');

		/** @var class-string<\Awyiss\Model\Enum\Survey\QuestionType> $ls_questionTypeEnum */
		$ls_questionTypeEnum = App::className('QuestionType', 'Model/Enum/Survey');

		$this->set([
			'survey' => $survey,
			'availableQuestions' => $this->surveyQuestions,
			'availableActions' => $this->Surveys->availableNextActions(),
			'availableForms' => $this->fetchTable('Forms')->find()->all()->indexBy('id')->toArray(),
			'finalActions' => $this->Surveys->availableFinalActions(),
			'specifyQuestionOptions' => $la_specifyQuestionOptions,
			'questionTypeEnum' => $ls_questionTypeEnum,
			'nextActionEnum' => $ls_surveyNextActionEnum,
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

			$ls_identifier = $la_questionData['identifier'] ?? null;
			if (!$ls_identifier) {
				// Create a random hexadecimal identifier with 8 characters
				$ls_identifier = Security::randomBytes(4);
				$ls_identifier = bin2hex($ls_identifier);
			}

			/** @noinspection PhpVariableNamingConventionInspection */
			$data['survey_survey_questions'][ $lx_key ] = [
				'id' => $la_questionData['id'] ?? null,
				'active' => $la_questionData['active'] ?? true,
				'surveyQuestionId' => $la_questionData['survey_question_id'],
				'identifier' => $ls_identifier,
				'nextAction' => $la_questionData['next_action'] ?? null,
				'nextActionTarget' => $la_questionData['next_action_target'] ?? null,
				'allowCustomAnswer' => $la_questionData['allow_custom_answer'] ?? null,
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

		foreach ($data['survey_survey_answers'] as $lx_key => $la_answerData) {
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
				'nextAction' => $la_answerData['next_action'] ?? null,
				'nextActionTarget' => $la_answerData['next_action_target'] ?? null,
				'systemOrder' => $li_count + 1,
				'active' => $la_answerData['active'] ?? false,
			];

			$li_count++;
		}

		return $data;
	}


	/**
	 * Recursively finds all nodes reachable from a starting node
	 *
	 * @param string $startId The identifier of the starting node
	 * @param array $graph The graph representation
	 * @param array &$reachable Reference to array storing reachable nodes
	 * @param array $visited Optional array to track visited nodes
	 * @return void
	 */
	protected function findReachableNodes(
		string $startId,
		array $graph,
		array &$reachable,
		array $visited = []
	): void {
		// Prevent infinite loops
		if (in_array($startId, $visited, true)) {
			return;
		}

		/** @noinspection PhpVariableNamingConventionInspection */
		$visited[] = $startId;
		/** @noinspection PhpVariableNamingConventionInspection */
		$reachable[] = $startId;

		// If this node has outgoing edges
		if (!isset($graph[ $startId ])) {
			return;
		}

		foreach ($graph[ $startId ] as $ls_nextId) {
			/** @noinspection PhpVariableNamingConventionInspection */
			$this->findReachableNodes($ls_nextId, $graph, $reachable, $visited);
		}
	}


	/**
	 * @param mixed $question
	 * @return void
	 */
	protected function sortAnswers(SurveySurveyQuestion $question): void {
		// Index the SurveySurveyAnswers by their surveyAnswerId
		$question->surveySurveyAnswers = array_column($question->surveySurveyAnswers, null, 'surveyAnswerId');

		// Sort the answers by system order as the order of the entities might be based on the old db order
		uasort($question->surveySurveyAnswers, function (SurveySurveyAnswer $a, SurveySurveyAnswer $b) {
			return $a->systemOrder <=> $b->systemOrder;
		});

		$la_answerKeys = array_keys($question->surveySurveyAnswers);
		$la_defaultAnswers = $question->surveyQuestion->surveyAnswers;

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

		$question->surveyQuestion->surveyAnswers = $la_defaultAnswers;
	}


	/**
	 * @param array<SurveySurveyQuestion> $surveySurveyQuestions
	 * @param Survey $survey
	 * @return void
	 */
	protected function markUnreachableQuestions(array $surveySurveyQuestions, Survey $survey): void {
		$la_entryPoints = $la_unusedQuestions = [];
		$la_questionsGraph = $survey->buildQuestionsGraph();

		foreach ($surveySurveyQuestions as $li_key => $lo_question) {
			if ($li_key === 0) {
				$la_entryPoints[] = $lo_question->identifier;
			}
			else {
				// Check if the question is somewhere used in the graph
				$lb_usedInGraph = false;

				foreach ($la_questionsGraph as $la_nextQuestions) {
					if (in_array($lo_question->identifier, $la_nextQuestions, true)) {
						$lb_usedInGraph = true;
						break;
					}
				}

				$lo_question->set('unused', !$lb_usedInGraph);
				$lo_question->setVirtual(['unused'], true);

				if (!$lb_usedInGraph) {
					$la_unusedQuestions[] = $lo_question->identifier;
				}
			}
		}

		$la_reachableFromEntryPoints = [];
		$la_reachableFromUnused = [];

		// Find all nodes reachable from valid entry points
		foreach ($la_entryPoints as $ls_entryPoint) {
			$this->findReachableNodes($ls_entryPoint, $la_questionsGraph, $la_reachableFromEntryPoints);
		}

		// Find all nodes reachable from unused questions
		foreach ($la_unusedQuestions as $ls_unusedId) {
			$this->findReachableNodes($ls_unusedId, $la_questionsGraph, $la_reachableFromUnused);
		}

		// Find nodes only reachable from unused questions
		$la_onlyReachableFromUnused = array_diff($la_reachableFromUnused, $la_reachableFromEntryPoints);

		// Mark questions that are only reachable from unused questions
		foreach ($survey->surveySurveyQuestions as $lo_question) {
			if (in_array($lo_question->identifier, $la_onlyReachableFromUnused, true)) {
				$lo_question->set('onlyReachableFromUnused', true);
				$lo_question->setVirtual(['onlyReachableFromUnused'], true);
			}
		}
	}
}
