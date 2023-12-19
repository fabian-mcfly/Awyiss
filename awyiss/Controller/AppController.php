<?php declare(strict_types=1);


namespace Awyiss\Controller;


use Awyiss\Configuration\ConfigOptionsProvider;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Database\Expression\QueryExpression;
use Cake\ORM\Query;
use Cake\Utility\Inflector;


/**
 * @property \Awyiss\Model\Table\LanguagesTable $Languages
 * @property \Awyiss\Model\Table\ConfigurationTable $Configuration
 */
abstract class AppController extends Controller {
	/**
	 * @throws \Exception
	 */
	public function initialize (): void {
		parent::initialize();

		$this->loadConfiguration();
	}


	/**
	 * Loads the Awyiss configuration from either the database or a config file inside the custom namespace.
	 * When loaded from the database, the configuration is dumped into a php config file.
	 *
	 * The filename is the underscored name of the custom namespace,
	 * followed by the frontend language and the backend language, both in square brackets.
	 *
	 * For example:
	 * `example_customer[de][en].php`
	 *
	 * @throws \Exception
	 */
	protected function loadConfiguration (): void {
		$lo_configurationTable = $this->fetchTable('Configuration');

		/** @var \Awyiss\Middleware\LocaleMiddleware $lo_locale */
		$lo_locale = $this->request->getAttribute('locale');

		$ls_fileName = Inflector::underscore(CUSTOM_NAMESPACE);
		if (IS_BACKEND && $lo_locale) {
			$ls_fileName .= '[' . $lo_locale->getLanguageFromUrl()->shortcode . '][' . ($lo_locale->getLanguageFromSession() ?? $lo_locale->getLanguageFromUrl())->shortcode . ']';
		}

		/*
		 * If the config path `Awyiss` is not empty, we do have a config file
		 * Therefore loading the database config is skipped
		 */
		Configure::load($ls_fileName, 'default', FALSE);
		if (Configure::read('Awyiss')) {
			return;
		}

		if (IS_BACKEND) {
			$lo_query = $lo_configurationTable->find()->applyOptions(['authorization' => ['skip' => TRUE]])->enableHydration(FALSE);

			if (!$lo_locale) {
				$lo_query->where(['language_shortcode IS' => NULL]);
			}
			else {
				$lo_query->where(function(QueryExpression $ao_exp, Query $lo_query) use ($lo_locale) {
					$lo_scopeNegated = $lo_query->newExpr()->and(['name NOT LIKE' => 'frontend.%'])->add(['name NOT LIKE' => 'backend.%']);

					return $ao_exp->or([
						['language_shortcode IS' => NULL],
						$ao_exp->and([['name LIKE' => 'backend.%'], ['language_shortcode' => $lo_locale->getLanguageFromSession()->shortcode]]),
						$ao_exp->and([['name LIKE' => 'frontend.%'], ['language_shortcode' => $lo_locale->getLanguageFromUrl()->shortcode]]),
						$ao_exp->and([$lo_scopeNegated, ['language_shortcode IS NOT' => NULL]]),
					]);
				});
			}

			$lo_query->order([
				'scope' => 'ASC',
				'name' => 'ASC',
				'language_shortcode IS NULL' => 'ASC',
				'language_shortcode' => 'ASC',
			]);

		}
		else {
			dd('foobar');
		}

		$la_config = [];
		foreach ($lo_query->all() AS $la_item) {
			$ls_path = 'Awyiss.' . Inflector::camelize($la_item['scope']) . '.' . $la_item['name'];

			$la_item['value'] = ConfigOptionsProvider::typecastConfigValue($la_item['scope'], $la_item['name'], $la_item['value']);

			if (!isset($la_config[ $ls_path ])) {
				$la_config[ $ls_path ] = $la_item['value'];
			}
		}

		Configure::write($la_config);

		//TODO: check if we want to have this inside a queue task, so it can be run with www-user privileges
		Configure::dump($ls_fileName, 'default', ['Awyiss']);
	}
}
