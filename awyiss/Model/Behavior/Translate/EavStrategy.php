<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior\Translate;


use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\Utility\Inflector;


/**
 * @inheritDoc
 */
class EavStrategy extends \Cake\ORM\Behavior\Translate\EavStrategy {
	/**
	 * {@inheritDoc}
	 *
	 * Implemented here nearly 1:1 without removing the dirty flag on translatable fields
	 *
	 * @param EventInterface $ao_event The beforeSave event that was fired
	 * @param EntityInterface $ao_entity The entity that is going to be saved
	 * @param ArrayObject $ao_options the options passed to the save method
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function beforeSave (EventInterface $ao_event, EntityInterface $ao_entity, ArrayObject $ao_options): void {
		$ls_locale = $ao_entity->get('_locale') ?: $this->getLocale();
		$ao_options['associated'] = [$this->translationTable->getAlias() => ['validate' => FALSE]] + $ao_options['associated'];

		// Check early if empty translations are present in the entity.
		// If this is the case, unset them to prevent persistence.
		// This only applies if $this->_config['allowEmptyTranslations'] is false
		if ($this->_config['allowEmptyTranslations'] === FALSE) {
			$this->unsetEmptyFields($ao_entity);
		}

		$this->bundleTranslatedFields($ao_entity);
		$la_bundled = $ao_entity->get('_i18n') ?: [];
		$lb_noBundled = count($la_bundled) === 0;

		// No additional translation records need to be saved,
		// as the entity is in the default locale.
		if ($lb_noBundled && $ls_locale === $this->getConfig('defaultLocale')) {
			return;
		}

		$la_values = $ao_entity->extract($this->_config['fields'], TRUE);
		$la_fields = array_keys($la_values);
		$lb_noFields = empty($la_fields);

		// If there are no fields and no bundled translations, or both fields
		// in the default locale and bundled translations we can
		// skip the remaining logic as it's not necessary.
		if ($lb_noFields && $lb_noBundled || ($la_fields && $la_bundled)) {
			return;
		}

		$ls_primaryKey = (array) $this->table->getPrimaryKey();
		$li_key = $ao_entity->get(current($ls_primaryKey));
		// When we have no key and bundled translations, we
		// need to mark the entity dirty so the root
		// entity persists.
		if ($lb_noFields && $la_bundled && ! $li_key) {
			foreach ($this->_config['fields'] as $ls_field) {
				$ao_entity->setDirty($ls_field);
			}

			return;
		}

		if ($lb_noFields) {
			return;
		}

		$ls_modelName = $this->_config['referenceName'];

		$la_preexistentValues = [];
		if ($li_key) {
			$la_preexistentValues = $this->translationTable->find()->select(['id', 'field'])->where([
					'field IN' => $la_fields,
					'locale' => $ls_locale,
					'foreign_key' => $li_key,
					'model' => $ls_modelName,
				])->all()->indexBy('field');
		}


		$la_modifiedValues = [];
		foreach ($la_preexistentValues as $ls_field => $lo_translation) {
			//$lo_translation->set('content', $la_values[ $ls_field ]);
			$la_modifiedValues[ $ls_field ] = $lo_translation;
		}
		$la_newValues = array_diff_key($la_values, $la_modifiedValues);
		foreach ($la_newValues as $ls_field => $ls_content) {
			$la_newValues[ $ls_field ] = new Entity([
				'locale' => $ls_locale,
				'field' => $ls_field,
				'content' => $ls_content,
				'model' => $ls_modelName,
			], [
				'useSetters' => FALSE,
				'markNew' => TRUE,
			]);
		}

		$ao_entity->set('_i18n', array_merge($la_bundled, array_values($la_modifiedValues + $la_newValues)));
		$ao_entity->set('_locale', $ls_locale, ['setter' => FALSE]);
		$ao_entity->setDirty('_locale', FALSE);

		/* With those lines, the main language would not find its way in the db
		foreach ($la_fields as $ls_field) {
			$ao_entity->setDirty($ls_field, FALSE);
		}
		*/
	}


	/**
	 * Deletes translations not being present in the entity`s `_translation`-property but in its original state
	 * unsets the temporary `_i18n` property after the entity has been saved
	 *
	 * @param EventInterface $ao_event The beforeSave event that was fired
	 * @param EntityInterface $ao_entity The entity that is going to be saved
	 *
	 * @return void
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function afterSave (EventInterface $ao_event, EntityInterface $ao_entity): void {
		$la_original = $ao_entity->hasOriginal('_translations') ? $ao_entity->getOriginal('_translations') : [];
		$la_translationsDiff = array_diff_key($la_original, $ao_entity->get('_translations') ?? []);

		if (!empty($la_translationsDiff)) {
			$la_primaryKey = (array)$this->table->getPrimaryKey();
			$this->translationTable->deleteQuery()->delete()->where([
				'locale IN' => array_keys($la_translationsDiff),
				'foreign_key' => $ao_entity->get(current($la_primaryKey)),
				'scope' => $this->_config['referenceName'],
			])->execute();
		}

		//Check if there are keys in the translation entities that aren't set in the config
		$la_unusedKeys = [];
		foreach ($ao_entity->get('_translations') ?? [] AS $lo_translation) {
			$la_keys = array_diff(array_keys($lo_translation->extract()), $this->getConfig('fields'), ['locale']);
			if ($la_keys) {
				$lo_translation->unset($la_keys);
			}

			$la_unusedKeys = array_merge($la_unusedKeys, $la_keys);
		}

		//Delete unused entries for fields that aren't set in the config
		if ($la_unusedKeys) {
			$la_primaryKey = (array) $this->table->getPrimaryKey();
			$this->translationTable->deleteQuery()->delete()->where([
				'locale IN' => array_keys($ao_entity->get('_translations')),
				'foreign_key' => $ao_entity->get(current($la_primaryKey)),
				'field IN' => $la_unusedKeys,
				'scope' => $this->_config['referenceName'],
			])->execute();
		}

		$ao_entity->unset('_i18n');
	}


	/*
	 * Modifies the results from a table find in order to merge the translated fields
	 * into each entity for a given locale.
	 *
	 * @param ResultSetInterface $ao_results Results to map.
	 * @param string $as_locale Locale string
	 *
	 * @return CollectionInterface
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	/*protected function rowMapper (CollectionInterface $ao_results, string $as_locale): CollectionInterface {
		return $ao_results->map(function($ao_row) use ($as_locale) {
			@var EntityInterface|array|null $ao_row
			if ($ao_row === NULL) {
				return $ao_row;
			}
			$lb_hydrated = ! is_array($ao_row);

			foreach ($this->_config['fields'] as $ls_field) {
				$ls_name = $ls_field . '_translation';
				$ls_translation = $ao_row[ $ls_name ] ?? NULL;

				if ($ls_translation === NULL || $ls_translation === FALSE) {
					unset($ao_row[ $ls_name ]);
					continue;
				}

				$ls_content = $ls_translation['content'] ?? NULL;
				if ($ls_content !== NULL) {
					$ao_row[ $ls_field ] = $ls_content;
				}

				unset($ao_row[ $ls_name ]);
			}

			$ao_row['_locale'] = $as_locale;
			if ($lb_hydrated) {
				$ao_row->clean();
			}

			return $ao_row;
		});
	}*/


	/**
	 * Unset empty translations to avoid persistence.
	 *
	 * Should only be called if $this->_config['allowEmptyTranslations'] is false.
	 *
	 * Re-implemented to not use `unset($entity->get('_translations')[$locale]);`, which is... wrong
	 * It also resets the `_translation`-property
	 *
	 * @param EntityInterface $ao_entity The entity to check for empty translations fields inside.
	 *
	 * @return void
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection*/
	protected function unsetEmptyFields(EntityInterface $ao_entity): void {
		/** @var array<Entity> $la_translations */
		$la_translations = (array)$ao_entity->get('_translations');
		foreach ($la_translations as $ls_locale => $lo_translation) {
			$la_fields = $lo_translation->extract($this->getConfig('fields'));
			foreach ($la_fields as $ls_field => $lx_value) {
				if ($lx_value === NULL || $lx_value === '') {
					$lo_translation->unset($ls_field);
				}
			}

			$lo_translation = $lo_translation->extract($this->getConfig('fields'));

			// If now, the current ls_locale property is empty,
			// unset it completely.
			if (empty(array_filter($lo_translation))) {
				unset($la_translations[ $ls_locale ]);
			}
		}

		//Set the _translations property to the cleared array of translations
		$ao_entity->set('_translations', $la_translations, ['guard' => FALSE]);

		// If now, the whole _translations property is empty,
		// unset it completely and return
		if (empty($ao_entity->get('_translations'))) {
			$ao_entity->unset('_translations');
		}
	}
}
