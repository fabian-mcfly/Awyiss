<?php declare(strict_types=1);


namespace Awyiss\View;


use Awyiss\Twig\Extension\AwyissExtension;
use Awyiss\Twig\FileLoader;
use Cake\Core\Configure;
use Cake\TwigView\View\TwigView;
use Twig\Loader\LoaderInterface;


/**
 * Application View
 *
 * @property \Awyiss\View\Helper\AuthorizationHelper $Authorization
 * @property \Awyiss\View\Helper\CategoriesHelper $Categories
 * @property \Awyiss\View\Helper\FlashHelper $Flash
 * @property \Awyiss\View\Helper\FormHelper $Form
 * @property \Awyiss\View\Helper\LocaleHelper $Locale
 * @property \Awyiss\View\Helper\PaginatorHelper $Paginator
 * @property \Awyiss\View\Helper\SystemOrderHelper $SystemOrder
 * @property \Awyiss\View\Helper\UrlHelper $Url
 * @property \Awyiss\View\Helper\AttributesHelper $Attributes
 */
class AppView extends TwigView {
	protected static bool $initialized = false;


	/**
	 * @inheritDoc
	 * @return void
	 * @throws \Twig\Error\LoaderError
	 */
	public function initialize(): void {
		$this->setConfig('environment', [
			'auto_reload' => true,
			//'cache' => false,
			//'debug' => Configure::read('debug'),
			'strict_variables' => false,
		]);

		parent::initialize();

		$lo_twig = $this->getTwig();

		if (!static::$initialized) {
			/** @var FileLoader $lo_loader */
			$lo_loader = $lo_twig->getLoader();

			if (defined('CUSTOM_DIR')) {
				$lo_loader->addPath(ROOT . DS . CUSTOM_DIR . DS . 'templates' . DS, CUSTOM_NAMESPACE);
			}

			$lo_loader->addPath(ROOT . DS . APP_DIR . DS . 'templates' . DS, Configure::read('App.namespace'));

			if (defined('CUSTOM_DIR')) {
				$lo_loader->setPaths([
					ROOT . DS . CUSTOM_DIR . DS . 'templates' . DS . 'Backend' . DS,
					ROOT . DS . APP_DIR . DS . 'templates' . DS . 'Backend' . DS,
				], 'Backend');
			}
			else {
				$lo_loader->addPath(ROOT . DS . APP_DIR . DS . 'templates' . DS . 'Backend' . DS, 'Backend');
			}

			$lo_twig->addExtension(new AwyissExtension());

			if (defined('CUSTOM_NAMESPACE')) {
				//This looks for a custom Twig Extension class in \<custom namespace>\Twig\Extension\<CustomNamespace>Extension.php and adds it
				$ls_customExtensionClass = '\\' . CUSTOM_NAMESPACE . '\Twig\Extension\\' . CUSTOM_NAMESPACE . 'Extension';
				if (class_exists($ls_customExtensionClass)) {
					$lo_twig->addExtension(new $ls_customExtensionClass());
				}
			}

			static::$initialized = true;
		}
	}


	/**
	 * @inheritDoc
	 * @return LoaderInterface
	 */
	protected function createLoader(): LoaderInterface {
		return new FileLoader($this->extensions);
	}
}
