<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior\Translate;


use ArrayObject;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior\Translate\EavStrategy as BaseEavStrategy;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Hash;


/**
 * @inheritDoc
 */
class EavStrategy extends BaseEavStrategy {
	/**
	 * @inheritDoc
	 */
	protected function setupAssociations(): void {
		parent::setupAssociations();

		$ls_targetAlias = $this->translationTable->getAlias();
		$lo_association = $this->table->getAssociation($ls_targetAlias);

		// Remove the "not empty" condition for the content field since null fields are allowed
		$la_conditions = $lo_association->getConditions();
		unset($la_conditions['I18n.content !=']);
		$lo_association->setConditions($la_conditions);
	}

	/**
	 * @inheritDoc
	 * @param \Cake\Event\EventInterface $event
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param \ArrayObject $options
	 * @return void
	 */
	public function beforeFind(EventInterface $event, SelectQuery $query, ArrayObject $options): void {
		$la_options = Hash::get($options, 'translate', []);

		if (($la_options['skip'] ?? false) === true) {
			return;
		}

		parent::beforeFind($event, $query, $options);
	}


	/**
	 * {@inheritDoc}
	 *
	 * Implemented here nearly 1:1 without removing the dirty flag on translatable fields
	 *
	 * @param EventInterface $event The beforeSave event that was fired
	 * @param EntityInterface $entity The entity that is going to be saved
	 * @param ArrayObject $options the options passed to the save method
	 */
	public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		$ls_locale = $entity->has('_locale') ? $entity->get('_locale') : $this->getLocale();
		/** @noinspection PhpVariableNamingConventionInspection */
		$options['associated'] = [$this->translationTable->getAlias() => ['validate' => false]] + $options['associated'];

		// Check early if empty translations are present in the entity.
		// If this is the case, unset them to prevent persistence.
		// This only applies if $this->_config['allowEmptyTranslations'] is false
		if ($this->_config['allowEmptyTranslations'] === false) {
			$this->unsetEmptyFields($entity);
		}

		/**
		 * If the entity is a copy, we need to mark all translations as new
		 * to ensure they are saved as well.
		 *
		 * @var array<string, \Cake\Datasource\EntityInterface> $la_translations
		 */
		$la_translations = (array)$entity->get('_translations');
		if ($la_translations && ($options['isCopy'] ?? false) === true) {
			foreach ($la_translations as $lo_translation) {
				$lo_translation->setNew(true);
			}
		}

		$this->bundleTranslatedFields($entity);
		$la_bundled = $entity->has('_i18n') ? $entity->get('_i18n') : [];
		$lb_noBundled = count($la_bundled) === 0;

		// No additional translation records need to be saved,
		// as the entity is in the default locale.
		if ($lb_noBundled) {
			return;
		}

		$la_values = $entity->extract($this->_config['fields'], true);
		$la_fields = array_keys($la_values);
		$lb_noFields = $la_fields === [];

		// If there are no fields and no bundled translations, or both fields
		// in the default locale and bundled translations we can
		// skip the remaining logic as it's not necessary.
		if ($lb_noFields || ($la_fields && $la_bundled)) {
			return;
		}

		$ls_primaryKey = (array)$this->table->getPrimaryKey();
		$li_key = $entity->get(current($ls_primaryKey));
		// When we have no key and bundled translations, we
		// need to mark the entity dirty so the root
		// entity persists.
		if ($la_bundled && !$li_key) {
			foreach ($this->_config['fields'] as $ls_field) {
				$entity->setDirty($ls_field);
			}

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
			])->all()->indexBy('field')->toArray();
		}


		$la_newValues = $this->prepareTranslationEntities($la_preexistentValues, $la_values, $ls_locale, $ls_modelName);

		$entity->set('_i18n', array_merge($la_bundled, $la_newValues));
		$entity->set('_locale', $ls_locale, ['setter' => false]);
		$entity->setDirty('_locale', false);
		/* With those lines, the main language would not find its way in the db
		foreach ($la_fields as $ls_field) {
			$entity->setDirty($ls_field, false);
		}*/
	}


	/**
	 * Deletes translations not being present in the entity`s `_translation`-property but in its original state
	 * unsets the temporary `_i18n` property after the entity has been saved
	 *
	 * @param EventInterface $event The afterSave event that was fired
	 * @param EntityInterface $entity The entity that is going to be saved
	 * @return void
	 */
	public function afterSave(EventInterface $event, EntityInterface $entity): void {
		$la_original = $entity->hasOriginal('_translations') ? $entity->getOriginal('_translations') : [];
		$la_translationsDiff = array_diff_key($la_original, $entity->get('_translations') ?? []);

		if (!empty($la_translationsDiff)) {
			$la_primaryKey = (array)$this->table->getPrimaryKey();
			$this->translationTable->deleteQuery()->delete()->where([
				'locale IN' => array_keys($la_translationsDiff),
				'foreign_key' => $entity->get(current($la_primaryKey)),
				'model' => $this->_config['referenceName'],
			])->execute();
		}

		//Check if there are keys in the translation entities that aren't set in the config
		$la_unusedKeys = [];
		foreach ($entity->get('_translations') ?? [] as $lo_translation) {
			$la_keys = array_diff(array_keys($lo_translation->extract()), $this->getConfig('fields'), ['locale']);
			if ($la_keys) {
				$lo_translation->unset($la_keys);
			}

			$la_unusedKeys = array_merge($la_unusedKeys, $la_keys);
		}

		//Delete unused entries for fields that aren't set in the config
		if ($la_unusedKeys) {
			$la_primaryKey = (array)$this->table->getPrimaryKey();
			$this->translationTable->deleteQuery()->delete()->where([
				'locale IN' => array_keys($entity->get('_translations')),
				'foreign_key' => $entity->get(current($la_primaryKey)),
				'field IN' => $la_unusedKeys,
				'model' => $this->_config['referenceName'],
			])->execute();
		}

		$entity->unset('_i18n');
	}


	/**
	 * 1:1 reimplementation of the original method, but adds the `source` field to the translation entity
	 *
	 * @inheritDoc
	 * @param \Cake\Collection\CollectionInterface $results Results to modify.
	 * @return \Cake\Collection\CollectionInterface
	 */
	public function groupTranslations(CollectionInterface $results): CollectionInterface {
		return $results->map(function ($row) {
			if (!$row instanceof EntityInterface) {
				return $row;
			}

			$la_translations = $row->has('_i18n') ? $row->get('_i18n') : null;
			if (!$la_translations && $row->get('_translations')) {
				return $row;
			}

			$lo_grouped = new Collection($la_translations);

			$la_result = [];
			$ls_entityClass = $this->table->getEntityClass();
			foreach ($lo_grouped->combine('field', 'content', 'locale') as $ls_locale => $la_keys) {
				$lo_translation = new $ls_entityClass($la_keys + ['locale' => $ls_locale], [
					'markNew' => false,
					'useSetters' => false,
					'markClean' => true,
					'source' => $row->getSource(),
				]);
				$la_result[ $ls_locale ] = $lo_translation;
			}

			$la_options = ['setter' => false, 'guard' => false];
			$row->set('_translations', $la_result, $la_options);
			$row->setDirty('_translations', false);
			/** @noinspection PhpVariableNamingConventionInspection */
			unset($row['_i18n']);

			return $row;
		});
	}


	/**
	 * Unset empty translations to avoid persistence.
	 *
	 * Should only be called if $this->_config['allowEmptyTranslations'] is false.
	 *
	 * Re-implemented to not use `unset($entity->get('_translations')[$locale]);`, which is... wrong
	 * It also resets the `_translation`-property
	 *
	 * @param EntityInterface $entity The entity to check for empty translations fields inside.
	 * @return void
	 */
	protected function unsetEmptyFields(EntityInterface $entity): void {
		/** @var array<\Awyiss\Model\Entity> $la_translations */
		$la_translations = (array)$entity->get('_translations');

		if (!$entity->has('_translations')) {
			$entity->unset('_i18n');

			return;
		}

		foreach ($la_translations as $ls_locale => $lo_translation) {
			$la_fields = $lo_translation->extract($this->getConfig('fields'));

			foreach ($la_fields as $ls_field => $lx_value) {
				if ($lx_value === null || $lx_value === '') {
					$lo_translation->unset($ls_field);

					if ($lo_translation->hasOriginal($ls_field)) {
						$lo_translation->setDirty($ls_field);
					}
				}
			}

			$la_translatedFields = $lo_translation->extract($this->getConfig('fields'));

			// If now, the current locale property is empty,
			// unset it completely.
			if (empty(array_filter($la_translatedFields))) {
				unset($la_translations[ $ls_locale ]);
			}
		}

		//Set the _translations property to the cleared array of translations
		$entity->set('_translations', $la_translations, ['guard' => false]);

		// If now, the whole _translations property is empty,
		// unset it completely and return
		if (empty($la_translations)) {
			$entity->unset('_translations');
		}
	}


	/**
	 * Extracts and prepares translation values for an entity.
	 * This method processes pre-existing translation values and new values,
	 * creating or updating translation entities as necessary.
	 *
	 * @param array $preExistentValues An array of pre-existing translation entities, indexed by field name.
	 * @param array $values An array of new translation values, indexed by field name.
	 * @param string $locale The locale for the translations.
	 * @param string $modelName The name of the model to which the translations belong.
	 * @return array An array of translation entities, ready to be saved.
	 */
	protected function prepareTranslationEntities(array $preExistentValues, array $values, string $locale, string $modelName): array {
		$la_modifiedValues = [];
		foreach ($preExistentValues as $ls_field => $lo_translation) {
			//$lo_translation->set('content', $values[ $ls_field ]);
			$la_modifiedValues[ $ls_field ] = $lo_translation;
		}

		$la_newValues = array_diff_key($values, $la_modifiedValues);
		$ls_entityClass = $this->table->getEntityClass();
		foreach ($la_newValues as $ls_field => $ls_content) {
			$la_newValues[ $ls_field ] = new $ls_entityClass([
				'locale' => $locale,
				'field' => $ls_field,
				'content' => $ls_content,
				'model' => $modelName,
			], [
				'useSetters' => false,
				'markNew' => true,
			]);
		}

		return array_values($la_modifiedValues + $la_newValues);
	}
}
