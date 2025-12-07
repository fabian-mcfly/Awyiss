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

		$targetAlias = $this->translationTable->getAlias();
		$association = $this->table->getAssociation($targetAlias);

		// Remove the "not empty" condition for the content field since null fields are allowed
		$conditions = $association->getConditions();
		unset($conditions['I18n.content !=']);
		$association->setConditions($conditions);
	}

	/**
	 * @inheritDoc
	 * @param \Cake\Event\EventInterface $event
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param \ArrayObject $options
	 * @return void
	 */
	public function beforeFind(EventInterface $event, SelectQuery $query, ArrayObject $options): void {
		$queryOptions = Hash::get($options, 'translate', []);

		if (($queryOptions['skip'] ?? false) === true || !$this->getConfig('fields')) {
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
		$locale = $entity->has('_locale') ? $entity->get('_locale') : $this->getLocale();
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
		 * @var array<string, \Cake\Datasource\EntityInterface> $translations
		 */
		$translations = (array)$entity->get('_translations');
		if ($translations && ($options['isCopy'] ?? false) === true) {
			foreach ($translations as $translation) {
				$translation->setNew(true);
			}
		}

		$this->bundleTranslatedFields($entity);
		$bundled = $entity->has('_i18n') ? $entity->get('_i18n') : [];
		$noBundled = count($bundled) === 0;

		// No additional translation records need to be saved,
		// as the entity is in the default locale.
		if ($noBundled) {
			return;
		}

		$values = $entity->extract($this->_config['fields'], true);
		$fields = array_keys($values);
		$noFields = $fields === [];

		// If there are no fields and no bundled translations, or both fields
		// in the default locale and bundled translations we can
		// skip the remaining logic as it is not necessary.
		if ($noFields || ($fields && $bundled)) {
			return;
		}

		/** @var string $primaryKey */
		$primaryKey = current((array)$this->table->getPrimaryKey());
		$key = $entity->has($primaryKey) ? $entity->get($primaryKey) : null;

		// When we have no key and bundled translations, we
		// need to mark the entity dirty so the root
		// entity persists.
		if ($bundled && !$key) {
			foreach ($this->_config['fields'] as $field) {
				$entity->setDirty($field);
			}

			return;
		}

		$modelName = $this->_config['referenceName'];

		$preexistentValues = [];
		if ($key) {
			$preexistentValues = $this->translationTable->find()->select(['id', 'field'])->where([
				'field IN' => $fields,
				'locale' => $locale,
				'foreign_key' => $key,
				'model' => $modelName,
			])->all()->indexBy('field')->toArray();
		}


		$newValues = $this->prepareTranslationEntities($preexistentValues, $values, $locale, $modelName);

		$entity->set('_i18n', array_merge($bundled, $newValues));
		$entity->set('_locale', $locale, ['setter' => false]);
		$entity->setDirty('_locale', false);
		/* With those lines, the main language would not find its way in the db
		foreach ($fields as $field) {
			$entity->setDirty($field, false);
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
		$original = $entity->hasOriginal('_translations') ? $entity->getOriginal('_translations') : [];
		$translationsDiff = array_diff_key($original, $entity->get('_translations') ?? []);

		if (!empty($translationsDiff)) {
			$primaryKey = (array)$this->table->getPrimaryKey();
			$this->translationTable->deleteQuery()->delete()->where([
				'locale IN' => array_keys($translationsDiff),
				'foreign_key' => $entity->get(current($primaryKey)),
				'model' => $this->_config['referenceName'],
			])->execute();
		}

		//Check if there are keys in the translation entities that aren't set in the config
		$unusedKeys = [];
		foreach ($entity->get('_translations') ?? [] as $translation) {
			$keys = array_diff(array_keys($translation->extract()), $this->getConfig('fields'), ['locale']);
			if ($keys) {
				$translation->unset($keys);
			}

			$unusedKeys = array_merge($unusedKeys, $keys);
		}

		//Delete unused entries for fields that aren't set in the config
		if ($unusedKeys) {
			$primaryKey = (array)$this->table->getPrimaryKey();
			$this->translationTable->deleteQuery()->delete()->where([
				'locale IN' => array_keys($entity->get('_translations')),
				'foreign_key' => $entity->get(current($primaryKey)),
				'field IN' => $unusedKeys,
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

			$translations = $row->has('_i18n') ? $row->get('_i18n') : [];
			if ($translations === []) {
				if ($row->has('_translations')) {
					return $row;
				}

				$row->set('_translations', [])->setDirty('_translations', false);
				unset($row['_i18n']);

				return $row;
			}

			$grouped = new Collection($translations);

			$result = [];
			$entityClass = $this->table->getEntityClass();

			foreach ($grouped->combine('field', 'content', 'locale') as $locale => $keys) {
				$translation = new $entityClass($keys + ['locale' => $locale], [
					'markNew' => false,
					'useSetters' => false,
					'markClean' => true,
					'source' => $row->getSource(),
				]);
				$result[ $locale ] = $translation;
			}

			$row->set('_translations', $result, ['setter' => false, 'guard' => false])->setDirty('_translations', false);
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
		if (!$entity->has('_translations')) {
			//$entity->unset('_i18n');

			return;
		}

		/** @var array<\Awyiss\Model\Entity> $translations */
		$translations = $entity->get('_translations');

		foreach ($translations as $locale => $translation) {
			$fields = $translation->extract($this->getConfig('fields'));

			foreach ($fields as $field => $value) {
				if ($value === null || $value === '') {
					$translation->unset($field);

					if ($translation->hasOriginal($field)) {
						$translation->setDirty($field);
					}
				}
			}

			$translatedFields = $translation->extract($this->getConfig('fields'));

			// If now, the current locale property is empty,
			// unset it completely.
			if (array_filter($translatedFields) === []) {
				unset($translations[ $locale ]);
			}
		}

		//Set the _translations property to the cleared array of translations
		$entity->set('_translations', $translations, ['guard' => false]);

		// If now, the whole _translations property is empty,
		// unset it completely and return
		if (empty($translations)) {
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
		$modifiedValues = [];
		/** @noinspection PhpLoopCanBeConvertedToArrayMapInspection */
		foreach ($preExistentValues as $field => $translation) {
			//$translation->set('content', $values[ $field ]);
			$modifiedValues[ $field ] = $translation;
		}

		$newValues = array_diff_key($values, $modifiedValues);
		$entityClass = $this->table->getEntityClass();
		foreach ($newValues as $field => $content) {
			$newValues[ $field ] = new $entityClass([
				'locale' => $locale,
				'field' => $field,
				'content' => $content,
				'model' => $modelName,
			], [
				'useSetters' => false,
				'markNew' => true,
			]);
		}

		return array_values($modifiedValues + $newValues);
	}
}
