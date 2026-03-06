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

		foreach (LocaleMiddleware::getLanguages() as $languages) {
			foreach ($languages as $language) {
				//If a language already exist, and it's active, do not use another one with the same shortcode.
				if (isset($this->languages[ $language->shortcode ]) && $this->languages[ $language->shortcode ]->active) {
					continue;
				}

				$this->languages[ $language->shortcode ] = $language;
			}
		}
	}


	/**
	 * Gets the Model callbacks this behavior is interested in.
	 *
	 * @return array<string, mixed>
	 */
	public function implementedEvents(): array {
		return [
			'Model.beforeFind' => 'beforeFind',
			'Model.beforeMarshal' => 'beforeMarshal',
			'Model.beforeSave' => [
				'callable' => 'beforeSave',
				'priority' => 100,
			],
			'Model.afterSave' => 'afterSave',
		];
	}


	/**
	 * @param \Cake\Event\EventInterface $event
	 * @param \ArrayObject $data
	 * @param \ArrayObject $options
	 * @return void
	 * @throws \Exception
	 */
	public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void {
		$firstLanguageShortcode = array_key_first($this->languages);
		$currentLanguageShortcode = LocaleMiddleware::getLanguage($this->getConfig('realm') ?? LocaleMiddleware::getRealm())->shortcode;

		if (empty($data['_translations'])) {
			return;
		}

		// Alle Energie auf die Deflektorschilde
		$forcedFields = $this->getConfig('forcedFields', ['title']);

		foreach ($this->getConfig('fields') as $field) {
			// Set the main entity's field to the first language's translation
			$data[ $field ] = $data['_translations'][ $firstLanguageShortcode ][ $field ] ?? null;

			/**
			 * If the field is empty, the current language is not the first one,
			 * and the field is in the forced fields list,
			 * then set the field to the current language's translation.
			 *
			 * This ensures that the main entity's field is always set, even if the main
			 * language's field is empty, but allows later changes with the main language.
			 *
			 * Workflow:
			 * Create a record in the second language, e.g. Spanish.
			 * The main entity's field will be set to the Spanish translation.
			 * Now modify the record in the main language, e.g. German.
			 * The main entity's field will be set to the German translation.
			 * If the main language's field remains empty, it will be set to the Spanish translation again.
			 */
			if (
				!$data[ $field ] &&
				$firstLanguageShortcode !== $currentLanguageShortcode &&
				in_array($field, $forcedFields)
			) {
				$data[ $field ] = $data['_translations'][ $currentLanguageShortcode ][ $field ] ?? null;
			}

			if ($data[ $field ] === '') {
				$data[ $field ] = null;
				$data['_translations'][ $firstLanguageShortcode ][ $field ] = null;
			}
		}
	}


	/**
	 * @inheritDoc
	 */
	protected function referenceName(Table $table): string {
		$name = namespaceSplit($table::class);
		$name = substr((string)end($name), 0, -5);
		if (empty($name)) {
			$name = $table->getTable() ?: $table->getAlias();
		}


		return Inflector::underscore($name);
	}
}
