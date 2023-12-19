<?php declare(strict_types=1);


namespace Awyiss\View;


use Awyiss\Middleware\LocaleMiddleware;
use Twig\Error\LoaderError;


/**
 * Backend View
 */
class BackendView extends AppView {
	/**
	 * @inheritDoc
	 *
	 * @return void
	 *
	 * @throws LoaderError
	 */
	public function initialize (): void {
		parent::initialize();

		$this->addHelper('Attributes');
		$this->addHelper('Authentication.Identity');
		$this->addHelper('Authorization');
		$this->addHelper('Locale');
		$this->addHelper('Paginator', ['templates' => 'paginator_templates']);
		$this->addHelper('SystemOrder', [
			'templates' => [
				'titleOption' => function(mixed $ax_option): string {
					return __('system_order_after') . ' ' . $ax_option->label;
				},
				'titleOptionCurrent' => function(mixed $ax_option): string {
					return $ax_option->label;
				},
				'titleOptionSelected' => function(mixed $ax_option): string {
					return '-> ' . __('system_order_after') . ' ' . $ax_option->label;
				},
			],
		]);

		/** @noinspection PhpUnhandledExceptionInspection */
		if ($lo_language = LocaleMiddleware::getLanguage(NULL)) {
			$this->addHelper('Time', ['outputTimezone' => $lo_language->timezone]);
		}

		$this->addHelper('Form', [
			'autoSetCustomValidity' => FALSE,
			'errorClass' => 'Error',
			'templates' => 'form_templates_backend',
		]);
	}
}
