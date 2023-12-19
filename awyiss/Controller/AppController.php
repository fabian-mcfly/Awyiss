<?php declare(strict_types=1);


namespace Awyiss\Controller;


use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Database\Expression\QueryExpression;
use Cake\ORM\Query;


/**
 * @property \Awyiss\Model\Table\LanguagesTable $Languages
 * @property \Awyiss\Model\Table\ConfigurationTable $Configuration
 */
abstract class AppController extends Controller {
	//protected static array $Configuration = [];


	/**
	 * @throws \Exception
	 */
	public function initialize (): void {
		parent::initialize();

		$this->loadConfiguration();
	}


	/**
	 * @throws \Exception
	 */
	protected function loadConfiguration (): void {
		$lo_configurationTable = $this->fetchTable('Configuration');

		/** @var \Awyiss\Middleware\LocaleMiddleware $lo_locale */
		$lo_locale = $this->request->getAttribute('locale');

		$ls_fileNameSuffix = NULL;
		if (IS_BACKEND && $lo_locale) {
			$ls_fileNameSuffix = '[' . $lo_locale->getLanguageFromUrl()->shortcode . '][' . ($lo_locale->getLanguageFromSession() ?? $lo_locale->getLanguageFromUrl())->shortcode . ']';
		}

		$ls_fileName = \Cake\Utility\Inflector::underscore(CUSTOM_NAMESPACE);
		$ls_fileName .= $ls_fileNameSuffix;

		/*
		 * If the config path `Awyiss` is not empty, we do have a config file
		 * Therefore loading the database config is skipped
		 */
		Configure::load($ls_fileName, 'default', FALSE);
		if (Configure::read('Awyiss')) {
			return;
		}

		if (IS_BACKEND) {
			$lo_query = $lo_configurationTable->find()->applyOptions(['access' => ['skip' => TRUE]])->enableHydration(FALSE);

			if (!$lo_locale) {
				$lo_query->where(['languages_shortcode IS' => NULL]);
			}
			else {
				$lo_query->where(function(QueryExpression $ao_exp, Query $lo_query) use ($lo_locale) {
					$lo_scopeNegated = $lo_query->newExpr()->and(['name NOT LIKE' => 'frontend.%'])->add(['name NOT LIKE' => 'backend.%']);

					return $ao_exp->or([
						['languages_shortcode IS' => NULL],
						$ao_exp->and([['name LIKE' => 'backend.%'], ['languages_shortcode' => $lo_locale->getLanguageFromSession()->shortcode]]),
						$ao_exp->and([['name LIKE' => 'frontend.%'], ['languages_shortcode' => $lo_locale->getLanguageFromUrl()->shortcode]]),
						$ao_exp->and([$lo_scopeNegated, ['languages_shortcode IS NOT' => NULL]]),
					]);
				});
			}

			$lo_query->order([
				'scope' => 'ASC',
				'name' => 'ASC',
				'languages_shortcode IS NULL' => 'ASC',
				'languages_shortcode' => 'ASC',
			]);

		}
		else {
			dd('foobar');
		}

		$la_config = [];
		foreach ($lo_query->all() AS $la_item) {
			$ls_path = 'Awyiss.' . \Cake\Utility\Inflector::camelize($la_item['scope']) . '.' . $la_item['name'];

			$la_item['value'] = \Awyiss\Configuration\ConfigOptionsProvider::typecastConfigValue($la_item['scope'], $la_item['name'], $la_item['value']);

			if (!isset($la_config[ $ls_path ])) {
				$la_config[ $ls_path ] = $la_item['value'];
			}
		}

		Configure::write($la_config);
		Configure::dump($ls_fileName, 'default', ['Awyiss']);
	}
}
