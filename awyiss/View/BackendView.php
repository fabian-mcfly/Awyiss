<?php declare(strict_types=1);


namespace Awyiss\View;


/**
 * Application View
 *
 * @property \Awyiss\View\Helper\PermissionHelper $Permission
 * @property \Awyiss\View\Helper\FlashHelper $Flash
 * @property \Awyiss\View\Helper\PaginatorHelper $Paginator
 */
class BackendView extends AppView {
	/**
	 * @inheritDoc
	 *
	 * @return void
	 *
	 * @throws \Twig\Error\LoaderError
	 */
	public function initialize (): void {
		parent::initialize();

		$this->loadHelper('Access');
		$this->loadHelper('Authentication.Identity');
		$this->loadHelper('Paginator', ['templates' => 'paginator_templates']);
		$this->loadHelper('SystemOrder', [
			'templates' => [
				'titleOption' => function(mixed $ax_option): string {
					$ls_inactive = '';
					if (empty($ax_option->active ?? TRUE)) {
						$ls_inactive = '(' . __('::system_order_inactive') . ') ';
					}

					return __('::system_order_after') . ' ' . $ls_inactive . $ax_option->title;
				},
				'titleOptionCurrent' => function(mixed $ax_option): string {
					$ls_inactive = '';
					if (empty($ax_option->active ?? TRUE)) {
						$ls_inactive = '(' . __('::system_order_inactive') . ') ';
					}

					return $ls_inactive . $ax_option->title;
				},
				'titleOptionSelected' => function(mixed $ax_option): string {
					$ls_inactive = '';
					if (empty($ax_option->active ?? TRUE)) {
						$ls_inactive = '(' . __('::system_order_inactive') . ') ';
					}

					return '-> ' . __('::system_order_after') . ' ' . $ls_inactive . $ax_option->title;
				},
			],
		]);

		/** @var \Awyiss\Middleware\LocaleMiddleware $lo_locale */
		$lo_locale = $this->request->getAttribute('locale');
		if ($lo_language = $lo_locale->getLanguageFromSession()) {
			$this->loadHelper('Time', ['outputTimezone' => $lo_language->timezone]);
		}

		$this->loadHelper('Form', [
			'autoSetCustomValidity' => FALSE,
			'errorClass' => 'Error',
			'templates' => 'form_templates_backend',
		]);
	}
}
