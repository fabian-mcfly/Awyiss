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
		$query = $this->SurveyQuestions->find()->where($this->getOverviewWhere());
		$this->Search->filterQuery($query);

		return $query;
	}


	/**
	 * Overview method
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$query = $this->getOverviewQuery();
		$surveyQuestions = $this->paginate($query);

		$this->set([
			'surveyQuestions' => $surveyQuestions,
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

		$surveyQuestion = $this->SurveyQuestions->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($surveyQuestion);
		}

		$this->set([
			'surveyQuestion' => $surveyQuestion,
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

		/**
		 * @var \Awyiss\Model\Entity\SurveyQuestion $surveyQuestion
		 * @uses \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
		 * @uses \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::findMediaElementAssignments()
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
		$surveyQuestion = $this->SurveyQuestions
			->findById($id)
			->find('translations')
			->find('mediaAssignments')
			->find('mediaElementAssignments')
			->first()
		;
		if (!$surveyQuestion) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($surveyQuestion, 'edit');
		}

		$this->set([
			'surveyQuestion' => $surveyQuestion,
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

		/** @var \Awyiss\Model\Entity\SurveyQuestion $surveyQuestion */
		$surveyQuestion = $this->SurveyQuestions->findById($id)->first();
		if (!$surveyQuestion) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->SurveyQuestions->delete($surveyQuestion)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));
				foreach ($surveyQuestion->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}

		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param \Awyiss\Model\Entity\SurveyQuestion $surveyQuestion
	 * @param string $method
	 * @return void
	 * @throws \Cake\Http\Exception\RedirectException|\Exception
	 * @noinspection DuplicatedCode
	 */
	protected function save(SurveyQuestion $surveyQuestion, string $method = 'add'): void {
		$associated = [];
		if ($this->SurveyQuestions->hasAttributes()) {
			$associated[] = $this->SurveyQuestions->getAttributesTableName(true);
			$surveyQuestion->setAccess('attributes', true);
		}

		$requestData = $this->request->getData();

		if (
			!empty($requestData['answers'])
			&& $this->Authorization->scopeIsAccessible('SurveyAnswers', [], 'create')
		) {
			$associated[] = 'SurveyAnswers';
			$surveyQuestion->setAccess('surveyAnswers', true);
			$requestData['surveyAnswers'] = $this->buildAnswersData($requestData['answers']);
		}

		$this->SurveyQuestions->patchEntity($surveyQuestion, $requestData, [
			'associated' => $associated,
			'validate' => !$this->request->getData('reloadForm'),
		]);

		if (!$this->request->getData('reloadForm')) { //reloadForm is set when we need to reload options based on current values
			$saveAsCopy = (bool)$this->request->getData('saveAsCopy');

			if ($this->SurveyQuestions->save($surveyQuestion, ['asCopy' => $saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				if ($this->request->getData('submitType') == 'submitClose') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'page' => $this->Paginate->calculateEntityPagePosition($surveyQuestion),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $surveyQuestion->id], true), 302);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__(($saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($surveyQuestion->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
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

		$languages = LocaleMiddleware::getLanguages(Awyiss::REALM_FRONTEND);
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$currentLanguage = LocaleMiddleware::getLanguage(Awyiss::REALM_FRONTEND);

		$answers = explode(PHP_EOL, $answers);
		$answers = array_map('trim', $answers);
		$answers = array_values(array_filter($answers));

		array_walk($answers, function (&$value, int $key) use ($languages, $currentLanguage) {
			$value = [
				'title' => $value,
				'systemOrder' => $key + 1,
			];

			if (count($languages) > 1) {
				$value['_translations'][ $currentLanguage->shortcode ] = [
					'title' => $value['title'],
				];
			}
		});

		return $answers;
	}
}
