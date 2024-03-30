<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Awyiss;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Model\Entity\Media;
use Awyiss\Routing\Router;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;


/**
 * Media Controller
 *
 * @property \Awyiss\Model\Table\MediaTable $Media
 */
class MediaController extends Controller {
	/**
	 * Overview method
	 *
	 * @throws \Exception
	 */
	public function overview(): void {
		$this->Authorization->ensure('read');

		$lo_media = $this->Media->find()->where($this->getOverviewWhere());
		$this->Categories->filterQuery($lo_media, null, !$this->paginate['enabled']);

		$this->set([
			'ao_media' => $lo_media,
			'as_languageRealm' => Awyiss::REALM_FRONTEND,
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

		$lo_media = $this->Media->newDefaultEntity();

		if ($this->request->is('post')) {
			$this->save($lo_media);
		}

		$this->set([
			'ao_media' => $lo_media,
			'as_languageRealm' => Awyiss::REALM_FRONTEND,
		]);
	}


	/**
	 * Edit method
	 *
	 * @return \Cake\Http\Response|void
	 * @throws \Exception
	 */
	public function edit(int $ai_id) {
		$this->Authorization->ensure('update');

		/** @var \Awyiss\Model\Entity\Media $lo_media */
		$lo_media = $this->Media->findById($ai_id)->find('translations')->first();

		if (!$lo_media) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->request->is(['patch', 'post', 'put'])) {
			$this->save($lo_media);
		}

		$this->set([
			'ao_media' => $lo_media,
			'as_languageRealm' => Awyiss::REALM_FRONTEND,
		]);
	}


	/**
	 * Delete method
	 *
	 * @param int $ai_id
	 * @return Response
	 * @throws \Exception
	 */
	public function delete(int $ai_id): Response {
		$this->Authorization->ensure('delete');

		$this->request->allowMethod(['get', 'delete']);

		/** @var Media $lo_media */
		$lo_media = $this->Media->findById($ai_id)->find('translations')->first();
		if (!$lo_media) {
			$this->Flash->error(__('record_not_found'));


			return $this->redirect(['action' => 'overview']);
		}

		if ($this->Media->delete($lo_media)) {
			$this->Flash->success(__('delete_succeeded'));
		}
		else {
			$this->Flash->error(__('delete_failed'));

			foreach ($lo_media->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}


		return $this->redirect(['action' => 'overview']);
	}


	/**
	 * @param \Awyiss\Model\Entity\MediaFolder $ao_mediaFolder
	 * @param string $as_method
	 * @return void
	 */
	protected function save(Media $ao_media, string $as_method = 'add'): void {
		$la_associated = [];
		if ($this->Media->hasAttributes()) {
			$la_associated[] = $this->Media->getAttributesTableName(true);
			$ao_media->setAccess('attributes', true);
		}

		$la_data = $this->request->getData();

		/** @var \Laminas\Diactoros\UploadedFile $lo_uploadedFile */
		$lo_uploadedFile = $this->request->getData('file');

		$ls_extension = null;
		if ($lo_uploadedFile && !$lo_uploadedFile->getError()) {
			if (empty($la_data['name'])) {
				$la_data['name'] = $lo_uploadedFile->getClientFilename();
			}
			else {
				$li_dotPos = strrpos($lo_uploadedFile->getClientFilename(), '.');
				$ls_extension = substr($lo_uploadedFile->getClientFilename(), $li_dotPos + 1);
			}
		}
		elseif (!empty($la_data['name'])) {
			$ls_extension = $ao_media->extension;
		}

		if ($ls_extension && !str_ends_with($la_data['name'], $ls_extension)) {
			$la_data['name'] .= '.' . $ls_extension;
		}

		$this->Media->patchEntity($ao_media, $la_data, [
			'associated' => $la_associated,
			'validate' => !$this->request->getData('reload_form'),
		]);

		if (
			!$lo_uploadedFile ||
			$lo_uploadedFile->getError() === UPLOAD_ERR_INI_SIZE ||
			$lo_uploadedFile->getError() === UPLOAD_ERR_FORM_SIZE
		) {
			$ao_media->setError('file', __df(
				strtolower($this->getName()),
				'validation',
				'error_media_file_size_within_limit'
			), true);
		}

		if (!$this->request->getData('reload_form')) { //reload_form is set when we need to reload options based on current values
			if ($this->Media->save($ao_media)) {
				$this->Flash->success(__($as_method . '_succeeded'));

				if ($this->request->getData('submit') == 'submit_close') {
					throw new RedirectException(Router::url(['action' => 'overview', 'mediaFolderId' => $ao_media->mediaFolderId], true), 302);
				}

				throw new RedirectException(Router::url(['action' => 'edit', 'id' => $ao_media->id], true), 302);
			}

			$this->Flash->error(__($as_method . '_failed'));
			foreach ($ao_media->getError('_general') as $ls_error) {
				$this->Flash->error($ls_error);
			}
		}
		else {
			if ($this->Media->getSystemOrderRelatedColumns($ao_media)) {
				$ao_media->systemOrder = null;
			}
			else {
				$ao_media->systemOrder = $ao_media->hasOriginal('systemOrder') ? $ao_media->getOriginal('systemOrder') : $ao_media->get('systemOrder');
			}
		}

		$this->Categories->ensurePossibleCategory($ao_media);
	}
}
