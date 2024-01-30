<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior\Translate;


use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior\Translate\EavStrategy as BaseEavStrategy;
use Cake\ORM\Entity;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Hash;


/**
 * @inheritDoc
 */
class EavStrategy extends BaseEavStrategy {
	/**
	 * @inheritDoc
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param \Cake\ORM\Query\SelectQuery $ao_query
	 * @param \ArrayObject $ao_options
	 * @return void
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function beforeFind(EventInterface $ao_event, SelectQuery $ao_query, ArrayObject $ao_options): void {
		$la_options = Hash::get($ao_options, 'translate', []);

		if (($la_options['skip'] ?? false) === true) {
			return;
		}

		parent::beforeFind($ao_event, $ao_query, $ao_options);
	}


	/**
	 * {@inheritDoc}
	 *
	 * Implemented here nearly 1:1 without removing the dirty flag on translatable fields
	 *
	 * @param EventInterface $ao_event The beforeSave event that was fired
	 * @param EntityInterface $ao_entity The entity that is going to be saved
	 * @param ArrayObject $ao_options the options passed to the save method
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function beforeSave(EventInterface $ao_event, EntityInterface $ao_entity, ArrayObject $ao_options): void {
		$ls_locale = $ao_entity->get('_locale') ?: $this->getLocale();
		$ao_options['associated'] = [$this->translationTable->getAlias() => ['validate' => false]] + $ao_options['associated'];

		// Check early if empty translations are present in the entity.
		// If this is the case, unset them to prevent persistence.
		// This only applies if $this->_config['allowEmptyTranslations'] is false
		if ($this->_config['allowEmptyTranslations'] === false) {
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

		$la_values = $ao_entity->extract($this->_config['fields'], true);
		$la_fields = array_keys($la_values);
		$lb_noFields = empty($la_fields);

		// If there are no fields and no bundled translations, or both fields
		// in the default locale and bundled translations we can
		// skip the remaining logic as it's not necessary.
		if ($lb_noFields && $lb_noBundled || ($la_fields && $la_bundled)) {
			return;
		}

		$ls_primaryKey = (array)$this->table->getPrimaryKey();
		$li_key = $ao_entity->get(current($ls_primaryKey));
		// When we have no key and bundled translations, we
		// need to mark the entity dirty so the root
		// entity persists.
		if ($lb_noFields && $la_bundled && !$li_key) {
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
				'useSetters' => false,
				'markNew' => true,
			]);
		}

		$ao_entity->set('_i18n', array_merge($la_bundled, array_values($la_modifiedValues + $la_newValues)));
		$ao_entity->set('_locale', $ls_locale, ['setter' => false]);
		$ao_entity->setDirty('_locale', false);
		/* With those lines, the main language would not find its way in the db
		foreach ($la_fields as $ls_field) {
			$ao_entity->setDirty($ls_field, false);
		}
		*/
	}


	/**
	 * Deletes translations not being present in the entity`s `_translation`-property but in its original state
	 * unsets the temporary `_i18n` property after the entity has been saved
	 *
	 * @param EventInterface $ao_event The beforeSave event that was fired
	 * @param EntityInterface $ao_entity The entity that is going to be saved
	 * @return void
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function afterSave(EventInterface $ao_event, EntityInterface $ao_entity): void {
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
		foreach ($ao_entity->get('_translations') ?? [] as $lo_translation) {
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
				'locale IN' => array_keys($ao_entity->get('_translations')),
				'foreign_key' => $ao_entity->get(current($la_primaryKey)),
				'field IN' => $la_unusedKeys,
				'scope' => $this->_config['referenceName'],
			])->execute();
		}

		$ao_entity->unset('_i18n');
	}


	/**
	 * Unset empty translations to avoid persistence.
	 *
	 * Should only be called if $this->_config['allowEmptyTranslations'] is false.
	 *
	 * Re-implemented to not use `unset($entity->get('_translations')[$locale]);`, which is... wrong
	 * It also resets the `_translation`-property
	 *
	 * @param EntityInterface $ao_entity The entity to check for empty translations fields inside.
	 * @return void
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function unsetEmptyFields(EntityInterface $ao_entity): void {
		/** @var array<Entity> $la_translations */
		$la_translations = (array)$ao_entity->get('_translations');
		foreach ($la_translations as $ls_locale => $lo_translation) {
			$la_fields = $lo_translation->extract($this->getConfig('fields'));
			foreach ($la_fields as $ls_field => $lx_value) {
				if ($lx_value === null || $lx_value === '') {
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
		$ao_entity->set('_translations', $la_translations, ['guard' => false]);

		// If now, the whole _translations property is empty,
		// unset it completely and return
		if (empty($ao_entity->get('_translations'))) {
			$ao_entity->unset('_translations');
		}
	}
}
