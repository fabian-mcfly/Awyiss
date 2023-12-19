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
class AppView extends TwigView {
	/**
	 * Constant for view file type 'element'
	 *
	 * @var string
	 */
	//public const TYPE_ELEMENT = 'Element';
	/**
	 * Constant for view file type 'layout'
	 *
	 * @var string
	 */
	//public const TYPE_LAYOUT = 'Layout';


	public function initialize (): void {
		$this->setConfig('environment', [
			'auto_reload' => TRUE,
			'cache' => FALSE,
			'debug' => \Cake\Core\Configure::read('debug'),
			'strict_variables' => FALSE,
		]);

		parent::initialize();
	}


	protected function createLoader(): \Twig\Loader\LoaderInterface {
		return new \Awyiss\Twig\FileLoader($this->extensions);
	}
}
