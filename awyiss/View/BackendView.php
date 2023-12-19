<?php

declare(strict_types=1);


namespace Awyiss\View;


use Cake\View\View;


/**
 * Application View
 *
 * @property \Awyiss\View\Helper\FlashHelper $Flash
 * @property \Awyiss\View\Helper\PaginatorHelper $Paginator
 */
class BackendView extends View {
	/**
	 * Constant for view file type 'element'
	 *
	 * @var string
	 */
	public const TYPE_ELEMENT = 'Element';
	/**
	 * Constant for view file type 'layout'
	 *
	 * @var string
	 */
	public const TYPE_LAYOUT = 'Layout';


	public function initialize (): void {
		$this->loadHelper('Authentication.Identity');

		$this->loadHelper('Paginator', ['templates' => 'paginator-templates']);

		/*
		 * TODO: change this to use the language saved in the session (saved after login)
		 * but only for the backend.
		 * The frontend should always output the time in the language-specific timezone
		 */
		$lo_language = \Awyiss\Controller\AppController::getUrlLanguage();
		$this->loadHelper('Time', ['outputTimezone' => $lo_language->timezone]);

		$this->loadHelper('Form', [
			'autoSetCustomValidity' => FALSE,
			//'templates' => ,
			'type' => 'file',
		]);
	}
}
