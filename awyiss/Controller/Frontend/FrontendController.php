<?php declare(strict_types=1);


namespace Awyiss\Controller\Frontend;


use Awyiss\Controller\AppController;


/**
 * Frontend Controller that handles all page requests
 */
class FrontendController extends AppController {
	/**
	 * @throws \Exception
	 */
	public function initialize(): void {
		AppController::initialize();

		$this->viewBuilder()->setClassName('Frontend');
	}


	/**
	 * @return void
	 */
	public function index(): void {
	}


	/*
	 * No language (first two-letter part of the url) found
	 *
	 * @return \Cake\Http\Response
	 * @throws \Exception
	 * @noinspection PhpUnused
	 * @noinspection PhpFullyQualifiedNameUsageInspection
	 */
	/*public function noLanguageFound (): \Cake\Http\Response {
		to do: Multi-domain: get and set default language of current domain

		//Get the first language and redirect
		if ( ! $la_languages = $this->getLanguages(Awyiss::DOMAIN_FRONTEND)) {
			throw new \Exception('No frontend language found.');
		}

		$lo_firstLanguage = reset($la_languages);

		$la_requestParams = Router::getRequest()->getAttribute('params');

		$li_redirectStatus = Configure::read('debug') ? 307 : 308;

		return $this->redirect(['lang' => $lo_firstLanguage->shortcode, '_name' => Awyiss::DOMAIN_FRONTEND] + $la_requestParams, $li_redirectStatus);
	}*/
}
