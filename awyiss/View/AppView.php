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


	/**
	 * @throws \Twig\Error\LoaderError
	 */
	public function initialize (): void {
		$this->setConfig('environment', [
			'auto_reload' => TRUE,
			'cache' => FALSE,
			'debug' => \Cake\Core\Configure::read('debug'),
			'strict_variables' => FALSE,
		]);

		parent::initialize();

		$lo_twig = $this->getTwig();

		if (empty($lo_twig->initialized)) {
			/** @var \Awyiss\Twig\FileLoader $lo_loader */
			$lo_loader = $lo_twig->getLoader();

			$lo_loader->addPath(ROOT . DS . CUSTOM_DIR . DS . 'templates' . DS, CUSTOM_NAMESPACE);
			$lo_loader->addPath(ROOT . DS . APP_DIR . DS . 'templates' . DS, \Cake\Core\Configure::read('App.namespace'));

			$lo_loader->setPaths([
				ROOT . DS . CUSTOM_DIR . DS . 'templates' . DS . 'Backend' . DS,
				ROOT . DS . APP_DIR . DS . 'templates' . DS . 'Backend' . DS
			], 'Backend');

			$lo_twig->addExtension(new \Awyiss\Twig\Extension\AwyissExtension());

			//This looks for a custom Twig Extension class in \<custom namespace>\Twig\Extension\<CustomNamespace>Extension.php and adds it
			$ls_customExtensionClass = '\\' . CUSTOM_NAMESPACE . '\Twig\Extension\\' . CUSTOM_NAMESPACE . 'Extension';
			if (class_exists($ls_customExtensionClass)) {
				$lo_twig->addExtension(new $ls_customExtensionClass());
			}

			$lo_twig->initialized = TRUE;
		}
	}


	protected function createLoader(): \Twig\Loader\LoaderInterface {
		return new \Awyiss\Twig\FileLoader($this->extensions);
	}
}
