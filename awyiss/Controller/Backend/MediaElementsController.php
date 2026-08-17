<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\MediaElement;
use Awyiss\Routing\Router;
use Awyiss\Utility\Content\ColumnInterface;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;


/**
 * MediaElements Controller
 *
 * @property \Awyiss\Model\Table\MediaElementsTable $MediaElements
 * @method \Awyiss\Model\Entity\MediaElement[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 * @noinspection PhpFullyQualifiedNameUsageInspection
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
class MediaElementsController extends Controller {
	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		$query = $this->MediaElements->find()->where($this->getOverviewWhere());
		$this->Search->filterQuery($query);

		return $query;
	}


	/**
	 * @inheritDoc
	 * @throws \Exception
	 */
	protected function initializeOverviewWhere(): void {
		$this->overviewWhere = [
			'internal' => 0,
		];

		parent::initializeOverviewWhere();
	}


	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$query = $this->getOverviewQuery();

		$paginated = $this->paginate['enabled'];
		if ($paginated) {
			$mediaElements = $this->paginate($query);
		}
		else {
			$mediaElements = $query->all();
		}

		$this->set([
			'mediaElements' => $mediaElements,
			'attributes' => $this->MediaElements->getAttributes(),
			'paginated' => $paginated,
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

		$mediaElement = $this->MediaElements->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($mediaElement);
		}

		$this->setViewVars($mediaElement);
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
		 * @var \Awyiss\Model\Entity\MediaElement $mediaElement
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
		$mediaElement = $this->MediaElements
			->findById($id)
			->find('translations')
			->contain([
				'MediaElementAssignments',
				'MediaElementSelectors' => [
					/** @uses \Awyiss\Model\Table::findTranslations() */
					'queryBuilder' => fn(SelectQuery $query) => $query->find('translations'),
				],
			])
			->first()
		;
		if (!$mediaElement) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($mediaElement, 'edit');
		}

		$this->setViewVars($mediaElement);
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

		/** @var \Awyiss\Model\Entity\MediaElement $mediaElement */
		$mediaElement = $this->MediaElements->findById($id)->first();
		if (!$mediaElement) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->MediaElements->delete($mediaElement)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));
				foreach ($mediaElement->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}

		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param MediaElement $mediaElement
	 * @param string $method
	 * @return void
	 * @throws \Cake\Http\Exception\RedirectException|\Exception
	 */
	protected function save(MediaElement $mediaElement, string $method = 'add'): void {
		$associated = [];
		if ($this->MediaElements->hasAttributes()) {
			$associated[] = $this->MediaElements->getAttributesTableName(true);
			$mediaElement->setAccess('attributes', true);
		}

		$requestData = $this->request->getData();

		if (!empty($requestData['mediaElementSelectors'])) {
			$requestData['mediaElementSelectors'] = array_map(function ($element) {
				static $systemOrder = 1;

				if (empty($element['mediaSelectorId']) || empty($element['identifier'])) {
					return false;
				}

				$currentLanguage = LocaleMiddleware::getLanguage('Backend');
				if (empty($element['title']) && empty($element['_translations'][ $currentLanguage->shortcode ]['title'])) {
					return false;
				}

				$element['systemOrder'] = $systemOrder;
				$systemOrder++;

				return $element;
			}, $requestData['mediaElementSelectors']);
			$requestData['mediaElementSelectors'] = array_filter($requestData['mediaElementSelectors']);

			// Update the request data
			$request = $this->request->withData('mediaElementSelectors', $requestData['mediaElementSelectors']);
			$this->setRequest($request);

			$associated[] = 'MediaElementSelectors';
		}

		if (!empty($requestData['mediaElementAssignments'])) {
			$requestData['mediaElementAssignments'] = array_filter($requestData['mediaElementAssignments'], function ($element) {
				if (empty($element['scope'])) {
					return false;
				}

				return true;
			});

			$this->request->withData('mediaElementAssignments', $requestData['mediaElementAssignments']);

			$associated[] = 'MediaElementAssignments';
		}

		$this->MediaElements->patchEntity($mediaElement, $requestData, [
			'associated' => $associated,
			'validate' => !$this->request->getData('reloadForm'),
		]);

		if (!$this->request->getData('reloadForm')) { //reloadForm is set when we need to reload options based on current values
			$saveAsCopy = (bool)$this->request->getData('saveAsCopy');

			if ($this->MediaElements->save($mediaElement, ['asCopy' => $saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				if ($this->request->getData('submitType') == 'submitClose') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'page' => $this->Paginate->calculateEntityPagePosition($mediaElement),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $mediaElement->id], true), 302);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__(($saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($mediaElement->getError('_general') as $error) {
					$this->Flash->error($error);
				}
			}
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\MediaElement $mediaElement
	 * @return void
	 * @throws \ReflectionException
	 */
	protected function setViewVars(MediaElement $mediaElement): void {
		$mediaSelectors = $this
			->fetchTable('MediaSelectors')
			->find()
			->all()
			->indexBy('id')
			->toArray()
		;

		$columnSpans = $this->MediaElements->getColumnSpans();
		$columnSpans = array_map(function (ColumnInterface $column): string {
			return $column->getLabel();
		}, $columnSpans);

		$assignableModels = $this->MediaElements->getAssignableModels(true);

		$this->set([
			'mediaElement' => $mediaElement,
			'mediaSelectors' => $mediaSelectors,
			'assignableModels' => $assignableModels,
			'columnSpans' => $columnSpans,
		]);
	}
}
