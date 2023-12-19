<?php declare(strict_types=1);


namespace Awyiss\Controller;


use Awyiss\Awyiss;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Table\ConfigurationTable;
use Awyiss\Model\Table\LanguagesTable;
use Cake\Controller\Controller;


/**
 * @property LanguagesTable $Languages
 * @property ConfigurationTable $Configuration
 */
abstract class AppController extends Controller {
	/**
	 * @throws \Exception
	 */
	public function initialize (): void {
		Awyiss::loadConfiguration(
			LocaleMiddleware::getLanguage()->shortcode,
			LocaleMiddleware::getLanguage(Awyiss::REALM_BACKEND)->shortcode,
		);
	}
}
