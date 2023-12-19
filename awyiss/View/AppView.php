<?php declare(strict_types=1);


namespace Awyiss\View;


use Awyiss\Twig\Extension\AwyissExtension;
use Awyiss\Twig\FileLoader;
use Awyiss\View\Helper\AuthorizationHelper;
use Awyiss\View\Helper\CategoriesHelper;
use Awyiss\View\Helper\FlashHelper;
use Awyiss\View\Helper\FormHelper;
use Awyiss\View\Helper\LocaleHelper;
use Awyiss\View\Helper\PaginatorHelper;
use Awyiss\View\Helper\SystemOrderHelper;
use Awyiss\View\Helper\UrlHelper;
use Cake\Core\Configure;
use Cake\TwigView\View\TwigView;
use Twig\Error\LoaderError;
use Twig\Loader\LoaderInterface;


/**
 * Application View
 *
 * @property AuthorizationHelper $Authorization
 * @property CategoriesHelper $Categories
 * @property FlashHelper $Flash
 * @property FormHelper $Form
 * @property LocaleHelper $Locale
 * @property PaginatorHelper $Paginator
 * @property SystemOrderHelper $SystemOrder
 * @property UrlHelper $Url
 */
class AppView extends TwigView {
	protected static bool $initialized = FALSE;

	/**
	 * @inheritDoc
	 *
	 * @return void
	 *
	 * @throws LoaderError
	 */
	public function initialize (): void {
		$this->setConfig('environment', [
			'auto_reload' => TRUE,
			//'cache' => FALSE,
			//'debug' => Configure::read('debug'),
			'strict_variables' => FALSE,
		]);

		parent::initialize();

		$lo_twig = $this->getTwig();

		if (!static::$initialized) {
			/** @var FileLoader $lo_loader */
			$lo_loader = $lo_twig->getLoader();

			$lo_loader->addPath(ROOT . DS . CUSTOM_DIR . DS . 'templates' . DS, CUSTOM_NAMESPACE);
			$lo_loader->addPath(ROOT . DS . APP_DIR . DS . 'templates' . DS, Configure::read('App.namespace'));

			$lo_loader->setPaths([
				ROOT . DS . CUSTOM_DIR . DS . 'templates' . DS . 'Backend' . DS,
				ROOT . DS . APP_DIR . DS . 'templates' . DS . 'Backend' . DS,
			], 'Backend');

			$lo_twig->addExtension(new AwyissExtension());

			//This looks for a custom Twig Extension class in \<custom namespace>\Twig\Extension\<CustomNamespace>Extension.php and adds it
			$ls_customExtensionClass = '\\' . CUSTOM_NAMESPACE . '\Twig\Extension\\' . CUSTOM_NAMESPACE . 'Extension';
			if (class_exists($ls_customExtensionClass)) {
				$lo_twig->addExtension(new $ls_customExtensionClass());
			}

			static::$initialized = TRUE;
		}
	}


	/**
	 * @inheritDoc
	 *
	 * @return LoaderInterface
	 */
	protected function createLoader (): LoaderInterface {
		return new FileLoader($this->extensions);
	}
}
