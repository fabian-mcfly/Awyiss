<?php declare(strict_types=1);


namespace Awyiss\Controller\Frontend;


use Awyiss\Controller\AppController;


/**
 * ThirdPartyConsentController handles the third party consent
 *
 * @property \Awyiss\Model\Table\ThirdPartyConsentsTable $ThirdPartyConsents
 */
class ThirdPartyConsentsController extends AppController {
	/**
	 * Saves the third party consent
	 *
	 * @return void
	 */
	public function track(): void {
		$la_requestData = $this->request->getData();

		$this->viewBuilder()
			->setClassName('Json')
			->setOption('serialize', ['status']);

		// Check if the required fields exist
		if (
			!isset($la_requestData['consentId']) ||
			!isset($la_requestData['acceptType']) ||
			!isset($la_requestData['acceptedCategories']) ||
			!isset($la_requestData['rejectedCategories'])
		) {
			// Set the response data
			$this->set([
				'status' => 'error',
			]);

			$this->response = $this->response->withStatus(400);

			return;
		}

		$lo_track = $this->ThirdPartyConsents->newDefaultEntity();
		$this->ThirdPartyConsents->patchEntity($lo_track, $la_requestData);

		if ($this->ThirdPartyConsents->save($lo_track, ['allowFrontendSave' => true])) {
			$lb_error = false;
			$this->response = $this->response->withStatus(201);
		}
		else {
			$this->response = $this->response->withStatus(500);
			$lb_error = true;
		}

		// Set the response data
		$this->set([
			'status' => $lb_error ? 'error' : 'success',
		]);
	}
}
