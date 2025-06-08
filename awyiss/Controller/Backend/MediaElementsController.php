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
 */
class MediaElementsController extends Controller {
	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		$lo_query = $this->MediaElements->find()->where($this->getOverviewWhere());
		$this->Search->filterQuery($lo_query);

		return $lo_query;
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

		$lo_query = $this->getOverviewQuery();

		$lb_paginated = $this->paginate['enabled'];
		if ($lb_paginated) {
			$lo_mediaElements = $this->paginate($lo_query);
		}
		else {
			$lo_mediaElements = $lo_query->all();
		}

		$this->set([
			'mediaElements' => $lo_mediaElements,
			'attributes' => $this->MediaElements->getAttributes(),
			'paginated' => $lb_paginated,
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

		$lo_mediaElement = $this->MediaElements->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($lo_mediaElement);
		}

		$this->setViewVars($lo_mediaElement);
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

		/** @var MediaElement $lo_mediaElement */
		$lo_mediaElement = $this->MediaElements->findById($id)->find('translations')->contain([
			'MediaElementAssignments',
			'MediaElementSelectors' => [
				'queryBuilder' => function (SelectQuery $query) {
					return $query->find('translations');
				},
			],
		])->first();
		if (!$lo_mediaElement) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_mediaElement, 'edit');
		}

		$this->setViewVars($lo_mediaElement);
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

		/** @var MediaElement $lo_mediaElement */
		$lo_mediaElement = $this->MediaElements->findById($id)->first();
		if (!$lo_mediaElement) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->MediaElements->delete($lo_mediaElement)) {
			if (!$this->request->is('ajax')) {
				$this->Flash->success(__('delete_succeeded'));
			}
		}
		else {
			if (!$this->request->is('ajax')) {
				$this->Flash->error(__('delete_failed'));
				foreach ($lo_mediaElement->getError('_general') as $ls_error) {
					$this->Flash->error($ls_error);
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
		$la_associated = [];
		if ($this->MediaElements->hasAttributes()) {
			$la_associated[] = $this->MediaElements->getAttributesTableName(true);
			$mediaElement->setAccess('attributes', true);
		}

		$la_requestData = $this->request->getData();

		if (!empty($la_requestData['media_element_selectors'])) {
			$la_requestData['media_element_selectors'] = array_map(function ($element) {
				static $li_systemOrder = 1;

				if (empty($element['media_selector_id']) || empty($element['identifier'])) {
					return false;
				}

				$lo_currentLanguage = LocaleMiddleware::getLanguage('Backend');
				if (empty($element['title']) && empty($element['_translations'][ $lo_currentLanguage->shortcode ]['title'])) {
					return false;
				}

				/** @noinspection PhpVariableNamingConventionInspection */
				$element['system_order'] = $li_systemOrder;
				$li_systemOrder++;

				return $element;
			}, $la_requestData['media_element_selectors']);
			$la_requestData['media_element_selectors'] = array_filter($la_requestData['media_element_selectors']);

			// Update the request data
			$lo_request = $this->request->withData('media_element_selectors', $la_requestData['media_element_selectors']);
			$this->setRequest($lo_request);

			$la_associated[] = 'MediaElementSelectors';
		}

		if (!empty($la_requestData['media_element_assignments'])) {
			$la_requestData['media_element_assignments'] = array_filter($la_requestData['media_element_assignments'], function ($element) {
				if (empty($element['scope'])) {
					return false;
				}

				return true;
			});

			$this->request->withData('media_element_assignments', $la_requestData['media_element_assignments']);

			$la_associated[] = 'MediaElementAssignments';
		}

		$this->MediaElements->patchEntity($mediaElement, $la_requestData, [
			'associated' => $la_associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			$lb_saveAsCopy = (bool)$this->request->getData('save_as_copy');

			if ($this->MediaElements->save($mediaElement, ['asCopy' => $lb_saveAsCopy])) {
				if (!$this->request->is('ajax')) {
					$this->Flash->success(__(($lb_saveAsCopy ? 'add' : $method) . '_succeeded'));
				}

				if ($this->request->getData('submit_type') == 'submit_close') {
					throw new RedirectException(Router::url([
						'action' => 'overview',
						'page' => $this->Paginate->calculateEntityPagePosition($mediaElement),
					], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $mediaElement->id], true), 302);
			}

			if (!$this->request->is('ajax')) {
				$this->Flash->error(__(($lb_saveAsCopy ? 'add' : $method) . '_failed'));
				foreach ($mediaElement->getError('_general') as $ls_error) {
					$this->Flash->error($ls_error);
				}
			}
		}
		else {
			if ($this->MediaElements->hasDirtyRelatedSystemOrderColumns($mediaElement)) {
				$mediaElement->systemOrder = null;
			}
			else {
				$mediaElement->systemOrder = $mediaElement->hasOriginal('systemOrder') ? $mediaElement->getOriginal('systemOrder') : $mediaElement->get('systemOrder');
			}

			// Update the request data. Otherwise, the SystemOrderHelper would use the outdated request data
			$lo_request = $this->request->withData('system_order', $mediaElement->systemOrder);
			$this->setRequest($lo_request);
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\MediaElement $mediaElement
	 * @return void
	 * @throws \ReflectionException
	 */
	protected function setViewVars(MediaElement $mediaElement): void {
		$la_mediaSelectors = $this->fetchTable('MediaSelectors')->find()->all()->indexBy('id')->toArray();

		$la_columnSpans = $this->MediaElements->getColumnSpans();
		$la_columnSpans = array_map(function (ColumnInterface $column): string {
			return $column->getLabel();
		}, $la_columnSpans);

		$la_assignableModels = $this->MediaElements->getAssignableModels(true);

		$this->set([
			'mediaElement' => $mediaElement,
			'mediaSelectors' => $la_mediaSelectors,
			'assignableModels' => $la_assignableModels,
			'columnSpans' => $la_columnSpans,
		]);
	}
}
