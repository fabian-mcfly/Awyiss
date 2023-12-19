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

			$lo_twig->addFunction(new \Twig\TwigFunction('staticCall', function($as_class, $as_method, $aa_args = []) {
				if (class_exists($as_class) && method_exists($as_class, $as_method)) {
					return call_user_func_array([$as_class, $as_method], $aa_args);
				}

				return NULL;
			}));

			$lo_twig->addFunction(new \Twig\TwigFunction('combine', function($aa_keys, $aa_values) {
				return array_combine($aa_keys, $aa_values);
			}));

			$lo_twig->addFunction(new \Twig\TwigFunction('naturalSort', function(array $aa_data, int|string $as_key = NULL) {
				uasort($aa_data, function($a, $b) use ($as_key) {
					if (!empty($as_key)) {
						return strnatcmp($a[ $as_key ], $b[ $as_key ]);
					}

					return strnatcmp($a, $b);
				});

				return $aa_data;
			}));

			$lo_twig->addTest(new \Twig\TwigTest('array', function ($ax_value) {
				return is_array($ax_value);
			}));

			$lo_twig->initialized = TRUE;
		}
	}


	protected function createLoader(): \Twig\Loader\LoaderInterface {
		return new \Awyiss\Twig\FileLoader($this->extensions);
	}
}
