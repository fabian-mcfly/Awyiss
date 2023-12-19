<?php declare(strict_types=1);


namespace Awyiss\Controller;


use Cake\Controller\Controller;
use Cake\Database\Expression\QueryExpression;
use Cake\ORM\Query;


/**
 * @property \Awyiss\Model\Table\LanguagesTable $Languages
 * @property \Awyiss\Model\Table\ConfigurationTable $Configuration
 */
abstract class AppController extends Controller {
	protected static array $Configuration = [];


	public function initialize (): void {
		parent::initialize();

		$this->loadConfiguration();
	}


	protected function loadConfiguration (): void {
		$lo_ConfigurationTable = $this->fetchTable('Configuration');

		$lo_query = $lo_ConfigurationTable->find()->enableHydration(FALSE);

		if (IS_BACKEND) {
			/** @var \Awyiss\Middleware\LocaleMiddleware $lo_locale */
			$lo_locale = $this->request->getAttribute('locale');

			$lo_query->where(function (QueryExpression $exp, Query $query) use ($lo_locale) {
				$lo_scopeNegated = $query->newExpr()->and(['name NOT LIKE' => 'frontend.%'])
					->add(['name NOT LIKE' => 'backend.%']);

				return $exp->or([
					['languages_shortcode IS' => NULL],
					$exp->and([['name LIKE' => 'backend.%'], ['languages_shortcode' => $lo_locale->getLanguageFromSession()->shortcode]]),
					$exp->and([['name LIKE' => 'frontend.%'], ['languages_shortcode' => $lo_locale->getLanguageFromUrl()->shortcode]]),
					$exp->and([$lo_scopeNegated, ['languages_shortcode IS NOT' => NULL]]),
				]);
			});

			$lo_query->order([
				'scope' => 'ASC',
				'name' => 'ASC',
				'languages_shortcode IS NULL' => 'ASC',
				'languages_shortcode' => 'ASC',
			]);
		}

		$la_config = [];
		foreach ($lo_query->all() AS $la_item) {
			/*$la_scopeParts = explode('.', $la_item['scope']);
			$la_scopeParts = array_map([\Cake\Utility\Inflector::class, 'camelize'], $la_scopeParts);
			$ls_scope = implode('.', $la_scopeParts);
			$ls_path = 'Awyiss.' . $ls_scope . '.' . $la_item['name'];*/
			$ls_path = 'Awyiss.' . $la_item['scope'] . '.' . $la_item['name'];

			if (!isset($la_config[ $ls_path ])) {
				$la_config[ $ls_path ] = $la_item['value'];
			}
		}

		\Cake\Core\Configure::write($la_config);
	}
}
