<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use ArrayObject;
use Awyiss\Middleware\LocaleMiddleware;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior\TranslateBehavior as BaseTranslateBehavior;
use Cake\ORM\Table;
use Cake\Utility\Inflector;


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
	 * @param array<string, mixed> $aa_config The config for this behavior.
	 * @return void
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function initialize(array $aa_config): void {
		parent::initialize($aa_config);

		if (isset($aa_config['locale'])) {
			$this->getStrategy()->setLocale($aa_config['locale']);
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
	}


	/**
	 * @param EventInterface $ao_event
	 * @param ArrayObject $ao_data
	 * @param ArrayObject $ao_options
	 * @return void
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function beforeMarshal(EventInterface $ao_event, ArrayObject $ao_data, ArrayObject $ao_options): void {
		$ls_firstLanguageShortcode = array_key_first($this->languages);

		if (empty($ao_data['_translations'])) {
			return;
		}

		foreach ($this->getConfig('fields') as $ls_field) {
			$ls_defaultTranslation = $ao_data['_translations'][ $ls_firstLanguageShortcode ][ $ls_field ] ?? null;
			$ao_data[ $ls_field ] = $ls_defaultTranslation;
		}
	}


	/**
	 * @inheritDoc
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function referenceName(Table $ao_table): string {
		$ls_name = namespaceSplit($ao_table::class);
		$ls_name = substr((string)end($ls_name), 0, -5);
		if (empty($ls_name)) {
			$ls_name = $ao_table->getTable() ?: $ao_table->getAlias();
		}


		return Inflector::underscore($ls_name);
	}
}
