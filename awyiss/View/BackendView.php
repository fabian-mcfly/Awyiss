<?php declare(strict_types=1);


namespace Awyiss\View;


use Awyiss\Middleware\LocaleMiddleware;


/**
 * Backend View
 */
class BackendView extends AppView {
	/**
	 * @inheritDoc
	 * @return void
	 * @throws \Twig\Error\LoaderError
	 */
	public function initialize(): void {
		parent::initialize();

		$this->addHelper('Asset');
		$this->addHelper('Attributes');
		$this->addHelper('Authentication.Identity');
		$this->addHelper('Authorization');
		$this->addHelper('Categories');
		$this->addHelper('Flash');
		$this->addHelper('Form', [
			'autoSetCustomValidity' => false,
			'errorClass' => 'Error',
			'templates' => 'form_templates_backend',
		]);
		$this->addHelper('Html');
		$this->addHelper('Locale');
		$this->addHelper('Paginator', ['templates' => 'paginator_templates']);
		$this->addHelper('SystemOrder', [
			'templates' => [
				'titleOption' => function (mixed $ax_option): string {
					return __('system_order_after') . ' ' . $ax_option->label;
				},
				'titleOptionCurrent' => function (mixed $ax_option): string {
					return $ax_option->label;
				},
				'titleOptionSelected' => function (mixed $ax_option): string {
					return '-> ' . __('system_order_after') . ' ' . $ax_option->label;
				},
			],
		]);

		/**
		 * @var \Awyiss\Model\Entity\Language|null $lo_language
		 * @noinspection PhpUnhandledExceptionInspection
		 */

		$lo_language = LocaleMiddleware::getLanguage(null);
		if ($lo_language) {
			$this->addHelper('Time', ['outputTimezone' => $lo_language->timezone]);
		}
	}
}
