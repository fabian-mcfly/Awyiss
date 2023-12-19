<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use ArrayObject;
use Awyiss\Middleware\LocaleMiddleware;
use Cake\Event\EventInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\Utility\Inflector;


/**
 * @inheritDoc
 */
class TranslateBehavior extends \Cake\ORM\Behavior\TranslateBehavior {
	/**
	 * @var array
	 */
	protected array $languages = [];


	/**
	 * Initialize hook
	 *
	 * @param array<string, mixed> $aa_config The config for this behavior.
	 *
	 * @return void
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function initialize (array $aa_config): void {
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
	 * Gets the Model callbacks this behavior is interested in.
	 *
	 * @return array<string, mixed>
	 */
	public function implementedEvents (): array {
		return [
			'Model.beforeMarshal' => 'beforeMarshal',
			'Model.beforeFind' => 'beforeFind',
			'Model.beforeSave' => 'beforeSave',
			'Model.afterSave' => 'afterSave',
		];
	}


	/**
	 * @param EventInterface $ao_event
	 * @param SelectQuery $ao_query
	 * @param ArrayObject $ao_options
	 * @param bool $ab_primary
	 *
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeFind (EventInterface $ao_event, SelectQuery $ao_query, ArrayObject $ao_options, bool $ab_primary): void {
		$ao_query->find('translations');

		$this->strategy->beforeFind($ao_event, $ao_query, $ao_options);
	}


	/**
	 * @param EventInterface $ao_event
	 * @param ArrayObject $ao_data
	 * @param ArrayObject $ao_options
	 *
	 * @return void
	 *
	 * @noinspection PhpUnusedParameterInspection
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function beforeMarshal (EventInterface $ao_event, ArrayObject $ao_data, ArrayObject $ao_options): void {
		$ls_firstLanguageShortcode = array_key_first($this->languages);

		if (!isset($ao_data['_translations'])) {
			return;
		}

		foreach ($this->getConfig('fields') AS $ls_field) {
			$ls_defaultTranslation = $ao_data['_translations'][ $ls_firstLanguageShortcode ][ $ls_field ] ?? NULL;
			$ao_data[ $ls_field ] = $ls_defaultTranslation;
		}
	}


	/**
	 * @inheritDoc
	 */
	protected function referenceName (Table $ao_table): string {
		$ls_name = namespaceSplit($ao_table::class);
		$ls_name = substr((string) end($ls_name), 0, -5);
		if (empty($ls_name)) {
			$ls_name = $ao_table->getTable() ?: $ao_table->getAlias();
		}

		return Inflector::underscore($ls_name);
	}
}
