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
		$requestData = $this->request->getData();

		$this->viewBuilder()
			->setClassName('Json')
			->setOption('serialize', ['status']);

		// Check if the required fields exist
		if (
			!isset($requestData['consentId']) ||
			!isset($requestData['acceptType']) ||
			!isset($requestData['acceptedCategories']) ||
			!isset($requestData['rejectedCategories'])
		) {
			// Set the response data
			$this->set([
				'status' => 'error',
			]);

			$this->response = $this->response->withStatus(400);

			return;
		}

		$track = $this->ThirdPartyConsents->newDefaultEntity();
		$this->ThirdPartyConsents->patchEntity($track, $requestData);

		if ($this->ThirdPartyConsents->save($track, ['allowFrontendSave' => true])) {
			$status = 'success';
			$this->response = $this->response->withStatus(201);
		}
		else {
			$this->response = $this->response->withStatus(500);
			$status = 'error';
		}

		// Set the response data
		$this->set([
			'status' => $status,
		]);
	}
}
