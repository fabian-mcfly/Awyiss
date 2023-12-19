<?php declare(strict_types=1);


namespace Awyiss\Controller;


//use Cake\Core\Configure;
//use Cake\Routing\Router;


/**
 * Frontend Controller that handles all page requests
 */
class FrontendController extends AppController {
	/**
	 * @throws \Exception
	 */
	public function initialize (): void {
		defined('IS_BACKEND') || define('IS_BACKEND', FALSE);

		parent::initialize();

		$this->viewBuilder()->setClassName('Frontend');
	}


	/**
	 * @return void
	 */
	public function index (): void {
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
		//TODO: Multi-domain: get and set default language of current domain

		//Get the first language and redirect
		if ( ! $la_languages = $this->getLanguages('frontend')) {
			throw new \Exception('No frontend language found.');
		}

		$lo_firstLanguage = reset($la_languages);

		$la_requestParams = Router::getRequest()->getAttribute('params');

		$li_redirectStatus = Configure::read('debug') ? 307 : 308;

		return $this->redirect(['lang' => $lo_firstLanguage->shortcode, '_name' => 'frontend'] + $la_requestParams, $li_redirectStatus);
	}*/
}
