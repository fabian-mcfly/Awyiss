<?php declare(strict_types=1);


namespace Awyiss\View;


use Cake\TwigView\View\TwigView;


/**
 * Application View
 *
 * @property \Awyiss\View\Helper\PermissionHelper $Authorization
 * @property \Awyiss\View\Helper\FlashHelper $Flash
 * @property \Awyiss\View\Helper\PaginatorHelper $Paginator
 */
class BackendView extends AppView {
	public function initialize (): void {
		parent::initialize();

		$this->loadHelper('Access');
		$this->loadHelper('Authentication.Identity');
		$this->loadHelper('Paginator', ['templates' => 'paginator_templates']);

		/*
		 * TODO: change this to use the language saved in the session (saved after login)
		 * but only for the backend.
		 * The frontend should always output the time in the language-specific timezone
		 */
		$lo_language = \Awyiss\Controller\AppController::getUrlLanguage();
		$this->loadHelper('Time', ['outputTimezone' => $lo_language->timezone]);

		$this->loadHelper('Form', [
			'autoSetCustomValidity' => FALSE,
			'errorClass' => 'Error',
			'templates' => 'form_templates_backend',
		]);
	}
}
