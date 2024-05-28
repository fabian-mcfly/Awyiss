<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\MediaComposite;
use Awyiss\Routing\Router;
use Awyiss\Utility\Content\ColumnInterface;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;


/**
 * MediaComposites Controller
 *
 * @property \Awyiss\Model\Table\MediaCompositesTable $MediaComposites
 * @method MediaComposite[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class MediaCompositesController extends Controller {
	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		return $this->MediaComposites->find()->where($this->getOverviewWhere());
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
			$lo_mediaComposites = $this->paginate($lo_query);
		}
		else {
			$lo_mediaComposites = $lo_query->all();
		}

		$this->set([
			'mediaComposites' => $lo_mediaComposites,
			'attributes' => $this->MediaComposites->getAttributes(),
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

		$lo_mediaComposite = $this->MediaComposites->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($lo_mediaComposite);
		}

		$this->setViewVars($lo_mediaComposite);
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

		/** @var MediaComposite $lo_mediaComposite */
		$lo_mediaComposite = $this->MediaComposites->findById($id)->find('translations')->contain([
			'MediaCompositeAssignments',
			'MediaCompositeSelectors' => [
				'queryBuilder' => function (SelectQuery $query) {
					return $query->find('translations');
				},
			],
		])->first();
		if (!$lo_mediaComposite) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_mediaComposite, 'edit');
		}

		$this->setViewVars($lo_mediaComposite);
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

		/** @var MediaComposite $lo_mediaComposite */
		$lo_mediaComposite = $this->MediaComposites->findById($id)->first();
		if (!$lo_mediaComposite) {
			$this->Flash->error(__('record_not_found'));

			return $this->redirect(['action' => 'overview']);
		}

		if ($this->MediaComposites->delete($lo_mediaComposite)) {
			$this->Flash->success(__('delete_succeeded'));
		}
		else {
			$this->Flash->error(__('delete_failed'));

			foreach ($lo_mediaComposite->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}

		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param MediaComposite $mediaComposite
	 * @param string $method
	 * @return void
	 * @throws \Cake\Http\Exception\RedirectException
	 */
	protected function save(MediaComposite $mediaComposite, string $method = 'add'): void {
		$la_associated = [];
		if ($this->MediaComposites->hasAttributes()) {
			$la_associated[] = $this->MediaComposites->getAttributesTableName(true);
			$mediaComposite->setAccess('attributes', true);
		}

		$la_requestData = $this->request->getData();

		if (!empty($la_requestData['media_composite_selectors'])) {
			$la_requestData['media_composite_selectors'] = array_filter($la_requestData['media_composite_selectors'], function ($element) {
				if (empty($element['media_selector_id']) || empty($element['identifier'])) {
					return false;
				}

				$lo_currentLanguage = LocaleMiddleware::getLanguage('Backend');
				if (empty($element['title']) && empty($element['_translations'][ $lo_currentLanguage->shortcode ]['title'])) {
					return false;
				}

				return true;
			});

			$la_associated[] = 'MediaCompositeSelectors';
		}

		if (!empty($la_requestData['media_composite_assignments'])) {
			$la_requestData['media_composite_assignments'] = array_filter($la_requestData['media_composite_assignments'], function ($element) {
				if (empty($element['scope'])) {
					return false;
				}

				return true;
			});

			$la_associated[] = 'MediaCompositeAssignments';
		}

		$this->MediaComposites->patchEntity($mediaComposite, $la_requestData, [
			'associated' => $la_associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->MediaComposites->save($mediaComposite, ['asCopy' => (bool)$this->request->getData('save_as_copy')])) {
				$this->Flash->success(__($method . '_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url(['action' => 'overview'], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $mediaComposite->id], true), 302);
			}

			$this->Flash->error(__($method . '_failed'));
			foreach ($mediaComposite->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}
	}


	/**
	 * @param \Awyiss\Model\Entity\MediaComposite $mediaComposite
	 * @return void
	 */
	protected function setViewVars(MediaComposite $mediaComposite): void {
		$la_mediaSelectors = $this->fetchTable('MediaSelectors')->find()->all()->indexBy('id')->toArray();

		$la_columnSpans = $this->MediaComposites->getColumnSpans();
		$la_columnSpans = array_map(function (ColumnInterface $column): string {
			return $column->getLabel();
		}, $la_columnSpans);

		$la_assignableModels = $this->MediaComposites->getAssignableModels(true);

		$this->set([
			'mediaComposite' => $mediaComposite,
			'mediaSelectors' => $la_mediaSelectors,
			'assignableModels' => $la_assignableModels,
			'columnSpans' => $la_columnSpans,
		]);
	}
}
