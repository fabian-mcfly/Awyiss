<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use ArrayObject;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Utility\Inflector;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior\TranslateBehavior as BaseTranslateBehavior;
use Cake\ORM\Table;


/**
 * @inheritDoc
 */
class TranslateBehavior extends BaseTranslateBehavior {
	/**
	 * @var array
	 */
	protected array $languages = [];


	/**
	 * Initialize hook
	 *
	 * @param array<string, mixed> $config The config for this behavior.
	 * @return void
	 */
	public function initialize(array $config): void {
		parent::initialize($config);

		if (isset($config['locale'])) {
			$this->getStrategy()->setLocale($config['locale']);
		}

		foreach (LocaleMiddleware::getLanguages() as $la_languages) {
			foreach ($la_languages as $lo_language) {
				//If a language already exist, and it's active, do not use another one with the same shortcode.
				if (isset($this->languages[ $lo_language->shortcode ]) && $this->languages[ $lo_language->shortcode ]->active) {
					continue;
				}

				$this->languages[ $lo_language->shortcode ] = $lo_language;
			}
		}

		if (!$this->getConfig('realm')) {
			$this->setConfig('realm', LocaleMiddleware::getRealm());
		}
	}


	/**
	 * @param EventInterface $event
	 * @param ArrayObject $data
	 * @param ArrayObject $options
	 * @return void
	 */
	public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void {
		$ls_firstLanguageShortcode = array_key_first($this->languages);
		$ls_currentLanguageShortcode = LocaleMiddleware::getLanguage($this->getConfig('realm'))->shortcode;

		if (empty($data['_translations'])) {
			return;
		}

		// Alle Energie auf die Deflektorschilde
		$la_forcedFields = $this->getConfig('forcedFields', ['title']);

		foreach ($this->getConfig('fields') as $ls_field) {
			$ls_defaultTranslation = $data['_translations'][ $ls_firstLanguageShortcode ][ $ls_field ] ?? null;

			if (
				!$ls_defaultTranslation &&
				$ls_firstLanguageShortcode !== $ls_currentLanguageShortcode &&
				in_array($ls_field, $la_forcedFields)
			) {
				$ls_defaultTranslation = $data['_translations'][ $ls_currentLanguageShortcode ][ $ls_field ] ?? null;
			}

			/** @noinspection PhpVariableNamingConventionInspection */
			$data[ $ls_field ] = $ls_defaultTranslation;

			if ($data[ $ls_field ] === '') {
				/** @noinspection PhpVariableNamingConventionInspection */
				$data[ $ls_field ] = null;
				/** @noinspection PhpVariableNamingConventionInspection */
				$data['_translations'][ $ls_firstLanguageShortcode ][ $ls_field ] = null;
			}
		}
	}


	/**
	 * @inheritDoc
	 */
	protected function referenceName(Table $table): string {
		$ls_name = namespaceSplit($table::class);
		$ls_name = substr((string)end($ls_name), 0, -5);
		if (empty($ls_name)) {
			$ls_name = $table->getTable() ?: $table->getAlias();
		}


		return Inflector::underscore($ls_name);
	}
}
