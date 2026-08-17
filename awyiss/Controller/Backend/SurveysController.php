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

		$this->surveyQuestions = $this
			->fetchTable('SurveyQuestions')
			->find('all')
			->contain(['SurveyAnswers'])
			->all()
			->indexBy('id')
			->toArray()
		;

		Arrays::naturalSort($this->surveyQuestions, 'title');
	}


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		$query = $this->Surveys->find()->where($this->getOverviewWhere());
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
		$surveys = $this->paginate($query);

		$this->set([
			'surveys' => $surveys,
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

		$survey = $this->Surveys->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($survey);
		}

		$this->setViewVars($survey);
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
		 * @var \Awyiss\Model\Entity\Survey $survey
		 * @uses \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
		 * @uses \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::findMediaElementAssignments()
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
		$survey = $this->Surveys
			->findById($id)
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
			->first()
		;

		if (!$survey) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($survey, 'edit');
		}

		$this->setViewVars($survey);
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

		/** @var \Awyiss\Model\Entity\Survey $survey */
		$survey = $this->Surveys->findById($id)->first();

		if (!$survey) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Surveys->delete($survey)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));
				foreach ($survey->getError('_general') as $error) {
					$this->Flash->error($error);
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

		$id = (int)$this->request->getParam('id');

		if (!$id) {
			$this->Flash->error(__('record_not_found'));
			throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
		}

		/**
		 * @var \Awyiss\Model\Entity\Survey $survey
		 * @uses \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
		 * @uses \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::findMediaElementAssignments()
		 */
		$survey = $this->Surveys
			->findById($id)
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
			->first()
		;

		if (!$survey) {
			$this->Flash->error(__('record_not_found'));
			throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
		}

		$entryId = (int)$this->request->getParam('entryId');
		if ($entryId) {
			$surveyEntriesTable = $this->fetchTable('SurveyEntries');
			/** @var \Awyiss\Model\Entity\SurveyEntry $entry */
			$entry = $surveyEntriesTable
				->find()
				->where([
					'id' => $entryId,
					'surveyId' => $id,
				])
				->first()
			;

			if ($entry) {
				$postData = $this->decodeEntryData($entry->data);
				$this->set('entryData', $postData);
			}
		}

		$this->setViewVars($survey);
	}


	/**
	 * Analysis method - Shows detailed analysis and statistics for a survey
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function analyze(): void {
		$this->Authorization->ensure('analyze');

		$id = (int)$this->request->getParam('id');

		if (!$id) {
			$this->Flash->error(__('record_not_found'));
			throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
		}

		/**
		 * @var \Awyiss\Model\Entity\Survey $survey
		 * @uses \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
		 * @uses \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::findMediaElementAssignments()
		 */
		$survey = $this->Surveys
			->findById($id)
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
			->first()
		;

		if (!$survey) {
			$this->Flash->error(__('record_not_found'));
			throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
		}

		// Fetch all entries for this survey
		$surveyEntriesTable = $this->fetchTable('SurveyEntries');
		$entries = $surveyEntriesTable
			->find()
			->where([
				'surveyId' => $id,
			])
			->orderBy(['createdOn' => 'DESC'])
			->all()
		;

		// Analyze the data
		$analysis = $this->analyzeEntries($survey, $entries);

		/** @var class-string<\Awyiss\Model\Enum\Survey\QuestionType> $questionTypeEnum */
		$questionTypeEnum = App::className('QuestionType', 'Model/Enum/Survey');

		$entries = $this->paginate(
			$surveyEntriesTable
				->find()
				->where([
					'surveyId' => $id,
				]),
			[
				'limit' => 20,
				'order' => [
					'createdOn' => 'DESC',
				],
			]
		);

		$this->set([
			'survey' => $survey,
			'entries' => $entries,
			'analysis' => $analysis,
			'questionTypeEnum' => $questionTypeEnum,
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
		$analysis = [
			'questions' => [],
			'customAnswers' => [],
		];

		// Initialize question statistics
		foreach ($survey->surveySurveyQuestions as $question) {
			$identifier = $question->identifier;

			$analysis['questions'][ $identifier ] = [
				'question' => $question,
				'title' => $question->title ?? $question->surveyQuestion->title,
				'type' => $question->surveyQuestion->type,
				'answers' => [],
				'customAnswerCount' => 0,
				'totalResponses' => 0,
			];

			// Initialize answer counts
			foreach ($question->surveySurveyAnswers as $surveyAnswer) {
				$analysis['questions'][ $identifier ]['answers'][ $surveyAnswer->id ] = [
					'answer' => $surveyAnswer,
					'title' => $surveyAnswer->title ?? $surveyAnswer->surveyAnswer->title,
					'count' => 0,
				];
			}
		}

		// Process each entry
		foreach ($entries as $surveyEntry) {
			// Decode the entry data
			$postData = $this->decodeEntryData($surveyEntry->data);

			if (!$postData || !isset($postData['progress'])) {
				continue;
			}

			// Ensure customAnswers key exists
			$postData['customAnswers'] ??= [];

			// Process each answer in the progress data
			foreach ($postData['progress'] as $identifier => $answer) {
				// Skip if the question is unknown, e.g., deleted question
				if (!isset($analysis['questions'][ $identifier ])) {
					continue;
				}

				$analysis['questions'][ $identifier ]['totalResponses']++;

				// Unify answers to an array to simplify processing
				$answer = !is_array($answer) ? [$answer] : $answer;

				foreach ($answer as $answerId) {
					if ($answerId === 'custom') {
						$analysis['questions'][ $identifier ]['customAnswerCount']++;

						// Store custom answers
						if (isset($postData['customAnswers'][ $identifier ])) {
							$analysis['customAnswers'][ $identifier ] ??= [];
							$analysis['customAnswers'][ $identifier ][] = $postData['customAnswers'][ $identifier ];
						}

						continue;
					}

					$answerId = (int)$answerId;
					if (isset($analysis['questions'][ $identifier ]['answers'][ $answerId ])) {
						$analysis['questions'][ $identifier ]['answers'][ $answerId ]['count']++;
					}
				}
			}
		}

		// Calculate percentages
		foreach ($analysis['questions'] as &$questionData) {
			$total = $questionData['totalResponses'];

			if ($total === 0) {
				continue;
			}

			foreach ($questionData['answers'] as &$answerData) {
				$answerData['percentage'] = round($answerData['count'] / $total * 100, 2);
			}
			unset($answerData);

			$questionData['customAnswerPercentage'] = round($questionData['customAnswerCount'] / $total * 100, 2);
		}
		unset($questionData);

		return $analysis;
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
			$decompressed = gzuncompress(base64_decode($encodedData));
			if ($decompressed === false) {
				return null;
			}

			$data = json_decode($decompressed, true);
			if (json_last_error() !== JSON_ERROR_NONE) {
				return null;
			}

			return $data ?: [];
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
		$associated = [
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
			$associated[] = $this->Surveys->getAttributesTableName(true);
			$survey->setAccess('attributes', true);
		}

		$requestData = $this->buildQuestionsData($this->request->getData());

		$this->Surveys->patchEntity($survey, $requestData, [
			'associated' => $associated,
			'validate' => !$this->request->getData('reloadForm'),
		]);

		if (!$this->request->getData('reloadForm')) { //reloadForm is set when we need to reload options based on current values
			$saveAsCopy = (bool)$this->request->getData('saveAsCopy');

			if ($this->Surveys->save($survey, ['asCopy' => $saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				if ($this->request->getData('submitType') == 'submitClose') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'page' => $this->Paginate->calculateEntityPagePosition($survey),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $survey->id], true), 302);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__(($saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($survey->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}

		if ($survey->surveySurveyQuestions) {
			$surveyQuestionIds = array_column($survey->surveySurveyQuestions, 'surveyQuestionId');

			/** @var class-string<\Awyiss\Model\Enum\Survey\Type> $surveyTypeEnum */
			$surveyTypeEnum = App::className('Type', 'Model/Enum/Survey');
			foreach ($survey->surveySurveyQuestions as $key => $surveySurveyQuestion) {
				$survey->surveySurveyQuestions[ $key ]->surveyQuestion = $this->surveyQuestions[ $surveySurveyQuestion->surveyQuestionId ]
					?? null;

				// If any question is repeated in a linear survey, set an error.
				if (
					$survey->type === $surveyTypeEnum::Linear
					&& array_count_values($surveyQuestionIds)[ $surveySurveyQuestion->surveyQuestionId ] > 1
				) {
					$survey->setError('surveySurveyQuestions', __('error_no_repeated_questions_in_linear_survey'));
					$surveySurveyQuestion->setError('surveyQuestionId', __('error_no_repeated_questions_in_linear_survey'));
				}

				// Build the answers array for each question
				$answers = $survey->surveySurveyQuestions[ $key ]->surveyQuestion?->surveyAnswers ?? [];
				$answers = array_column($answers, null, 'id');

				// Ensure that each surveySurveyAnswer has a surveyAnswer set. It gets lost in the patchEntity process.
				// since it is not part of the request data.
				foreach ($surveySurveyQuestion->surveySurveyAnswers as $surveySurveyAnswer) {
					$surveySurveyAnswer->surveyAnswer = $answers[ $surveySurveyAnswer->surveyAnswerId ] ?? null;
				}
			}

			$errors = $survey->getError('surveySurveyQuestions');

			// Remove empty questions
			$survey->surveySurveyQuestions = array_filter($survey->surveySurveyQuestions ?? []);

			// Sort the questions by system order as the order of the entities might be based on the old db order
			usort($survey->surveySurveyQuestions, function (SurveySurveyQuestion $a, SurveySurveyQuestion $b) {
				return $a->systemOrder <=> $b->systemOrder;
			});

			if ($errors) {
				$survey->setError('surveySurveyQuestions', $errors);
			}
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\Survey $survey
	 * @return void
	 */
	protected function setViewVars(Survey $survey): void {
		$specifyQuestionOptions = [];
		if ($survey->surveySurveyQuestions) {
			foreach ($survey->surveySurveyQuestions as $question) {
				$specifyQuestionOptions[] = [
					'text' => $question->label,
					'value' => $question->identifier,
				];

				if (
					!$question->surveySurveyAnswers
					|| !$question->surveyQuestion?->surveyAnswers
				) {
					continue;
				}

				$this->sortAnswers($question);
			}

			/** @var class-string<\Awyiss\Model\Enum\Survey\Type> $surveyTypeEnum */
			$surveyTypeEnum = App::className('Type', 'Model/Enum/Survey');

			if ($survey->type !== $surveyTypeEnum::Linear) {
				$this->markUnreachableQuestions($survey->surveySurveyQuestions, $survey);
			}
		}

		/** @var class-string<\Awyiss\Model\Enum\Survey\NextAction> $surveyNextActionEnum */
		$surveyNextActionEnum = App::className('NextAction', 'Model/Enum/Survey');

		/** @var class-string<\Awyiss\Model\Enum\Survey\QuestionType> $questionTypeEnum */
		$questionTypeEnum = App::className('QuestionType', 'Model/Enum/Survey');

		$this->set([
			'survey' => $survey,
			'availableQuestions' => $this->surveyQuestions,
			'availableActions' => $this->Surveys->availableNextActions(),
			'availableForms' => $this
				->fetchTable('Forms')
				->find()
				->all()
				->indexBy('id')
				->toArray(),
			'finalActions' => $this->Surveys->availableFinalActions(),
			'specifyQuestionOptions' => $specifyQuestionOptions,
			'questionTypeEnum' => $questionTypeEnum,
			'nextActionEnum' => $surveyNextActionEnum,
		]);
	}


	/**
	 * @param array $data
	 * @return array
	 */
	protected function buildQuestionsData(array $data): array {
		$count = 0;

		if (
			!isset($data['surveySurveyQuestions'])
			|| !is_array($data['surveySurveyQuestions'])
		) {
			$data['surveySurveyQuestions'] = [];
		}

		foreach ($data['surveySurveyQuestions'] as $key => $questionData) {
			if (
				!is_array($questionData)
				|| !isset($questionData['surveyQuestionId'])
			) {
				unset($data['surveySurveyQuestions'][ $key ]);
				continue;
			}

			$questionData = $this->buildAnswersData($questionData);

			$identifier = $questionData['identifier'] ?? null;
			if (!$identifier) {
				// Create a random hexadecimal identifier with 8 characters
				$identifier = Security::randomBytes(4);
				$identifier = bin2hex($identifier);
			}

			$data['surveySurveyQuestions'][ $key ] = [
				'id' => $questionData['id'] ?? null,
				'active' => $questionData['active'] ?? true,
				'surveyQuestionId' => $questionData['surveyQuestionId'],
				'identifier' => $identifier,
				'nextAction' => $questionData['nextAction'] ?? null,
				'nextActionTarget' => $questionData['nextActionTarget'] ?? null,
				'allowCustomAnswer' => $questionData['allowCustomAnswer'] ?? null,
				'systemOrder' => $count + 1,
				'surveySurveyAnswers' => $questionData['surveySurveyAnswers'] ?? [],
			];

			$count++;
		}

		return $data;
	}


	/**
	 * @param array $data
	 * @return array
	 */
	protected function buildAnswersData(array $data): array {
		$count = 0;

		if (
			!isset($data['surveySurveyAnswers'])
			|| !is_array($data['surveySurveyAnswers'])
		) {
			$data['surveySurveyAnswers'] = [];
		}

		foreach ($data['surveySurveyAnswers'] as $key => $answerData) {
			if (
				!is_array($answerData)
				|| !isset($answerData['surveyAnswerId'])
			) {
				unset($data['surveySurveyAnswers'][ $key ]);
				continue;
			}

			$data['surveySurveyAnswers'][ $key ] = [
				'id' => $answerData['id'] ?? null,
				'surveyAnswerId' => $answerData['surveyAnswerId'],
				'nextAction' => $answerData['nextAction'] ?? null,
				'nextActionTarget' => $answerData['nextActionTarget'] ?? null,
				'systemOrder' => $count + 1,
				'active' => $answerData['active'] ?? false,
			];

			$count++;
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

		$visited[] = $startId;
		$reachable[] = $startId;

		// If this node has outgoing edges
		if (!isset($graph[ $startId ])) {
			return;
		}

		foreach ($graph[ $startId ] as $nextId) {
			$this->findReachableNodes($nextId, $graph, $reachable, $visited);
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

		$answerKeys = array_keys($question->surveySurveyAnswers);
		$defaultAnswers = $question->surveyQuestion->surveyAnswers;

		usort($defaultAnswers, function (SurveyAnswer $a, SurveyAnswer $b) use ($answerKeys): int {
			$posA = array_search($a->id, $answerKeys);
			$posB = array_search($b->id, $answerKeys);

			return match (true) {
				$posA === false && $posB === false => 0,
				$posA === false => 1,
				$posB === false => -1,
				default => $posA <=> $posB,
			};
		});

		$question->surveyQuestion->surveyAnswers = $defaultAnswers;
	}


	/**
	 * @param array<SurveySurveyQuestion> $surveySurveyQuestions
	 * @param Survey $survey
	 * @return void
	 */
	protected function markUnreachableQuestions(array $surveySurveyQuestions, Survey $survey): void {
		$entryPoints = $unusedQuestions = [];
		$questionsGraph = $survey->buildQuestionsGraph();

		foreach ($surveySurveyQuestions as $key => $question) {
			if ($key === 0) {
				$entryPoints[] = $question->identifier;
			}
			else {
				// Check if the question is somewhere used in the graph
				$usedInGraph = false;

				foreach ($questionsGraph as $nextQuestions) {
					if (in_array($question->identifier, $nextQuestions, true)) {
						$usedInGraph = true;
						break;
					}
				}

				$question->set('unused', !$usedInGraph);
				$question->setVirtual(['unused'], true);

				if (!$usedInGraph) {
					$unusedQuestions[] = $question->identifier;
				}
			}
		}

		$reachableFromEntryPoints = [];
		$reachableFromUnused = [];

		// Find all nodes reachable from valid entry points
		foreach ($entryPoints as $entryPoint) {
			$this->findReachableNodes($entryPoint, $questionsGraph, $reachableFromEntryPoints);
		}

		// Find all nodes reachable from unused questions
		foreach ($unusedQuestions as $unusedId) {
			$this->findReachableNodes($unusedId, $questionsGraph, $reachableFromUnused);
		}

		// Find nodes only reachable from unused questions
		$onlyReachableFromUnused = array_diff($reachableFromUnused, $reachableFromEntryPoints);

		// Mark questions that are only reachable from unused questions
		foreach ($survey->surveySurveyQuestions as $question) {
			if (in_array($question->identifier, $onlyReachableFromUnused, true)) {
				$question->set('onlyReachableFromUnused', true);
				$question->setVirtual(['onlyReachableFromUnused'], true);
			}
		}
	}
}
