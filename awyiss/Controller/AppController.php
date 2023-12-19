<?php declare(strict_types=1);


namespace Awyiss\Controller;


use Cake\Controller\Controller;


/**
 * @property \Awyiss\Model\Table\LanguagesTable $Languages
 * @property \Awyiss\Model\Table\SystemConfigurationTable $SystemConfiguration
 */
abstract class AppController extends Controller {
	private static array $la_languages = ['frontend' => [], 'backend' => []];
	private static array $la_systemConfiguration = [];


	public static function getUrlLanguage (): object {
		$ls_lang_shortcode = \Cake\Routing\Router::getRequest()->getParam('lang');
		$lo_language = static::getLanguages('frontend')[ $ls_lang_shortcode ] ?? NULL;
		if ( ! $lo_language) {
			throw new \Exception(__('::language_shortcode_not_found'));
		}

		return $lo_language;
	}


	public static function getLanguages ($as_type): array {
		return static::$la_languages[ $as_type ] ?? [];
	}


	public function initialize (): void {
		parent::initialize();

		$this->loadLanguages();
		$this->loadSystemConfiguration();
	}


	private function loadLanguages (): void {
		$this->loadModel('Languages');

		$lo_result = $this->Languages->find()->order(['system_order' => 'ASC']);


		foreach ($lo_result->all() as $lo_language) {
			static::$la_languages[ $lo_language['type'] ][ $lo_language['shortcode'] ] = $lo_language;
		}
	}


	private function loadSystemConfiguration (): void {
		$this->loadModel('SystemConfiguration');

		$lo_result = $this->SystemConfiguration->find()->where(['languages_shortcode IS' => NULL])->enableHydration(FALSE);

		foreach ($lo_result->toList() as $la_item) {
			$ls_language = $la_item['languages_shortcode'] ?: 'global';

			if (!isset($la_systemConfiguration[ $ls_language ])) {
				$la_systemConfiguration[ $ls_language ] = [];
			}
			if (!isset($la_systemConfiguration[ $ls_language ][ $la_item['scope'] ])) {
				$la_systemConfiguration[ $ls_language ][ $la_item['scope'] ] = [];
			}

			$la_systemConfiguration[ $ls_language ][ $la_item['scope'] ][ $la_item['key'] ] = $la_item['value'];

			$ls_key = strtoupper($la_item['scope'] . '_' . $la_item['key']);
			if ( ! defined($ls_key)) {
				define($ls_key, $la_item['value']);
			}
		}

		$this->la_systemConfiguration = $la_systemConfiguration;
	}
}
