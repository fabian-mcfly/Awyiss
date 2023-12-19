<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior\Translate;


use ArrayObject;
use Awyiss\Model\Entity;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;


/**
 * @inheritDoc
 */
class EavStrategy extends \Cake\ORM\Behavior\Translate\EavStrategy {
	/**
	 * {@inheritDoc}
	 *
	 * Implemented here nearly 1:1 without removing the dirty flag on translatable fields
	 *
	 * @param \Cake\Event\EventInterface $ao_event The beforeSave event that was fired
	 * @param \Cake\Datasource\EntityInterface $ao_entity The entity that is going to be saved
	 * @param \ArrayObject $ao_options the options passed to the save method
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function beforeSave (EventInterface $ao_event, EntityInterface $ao_entity, ArrayObject $ao_options) {
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
			$la_preexistentValues = $this->translationTable->find()->where([
					'field IN' => $la_fields,
					'locale' => $ls_locale,
					'foreign_key' => $li_key,
					'model' => $ls_modelName,
				])->disableBufferedResults()->all()->indexBy('field');
		}

		$la_modifiedValues = [];
		foreach ($la_preexistentValues as $ls_field => $lo_translation) {
			$lo_translation->set('content', $la_values[ $ls_field ]);
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

		if ($this->getConfig('defaultLocale') !== '') {
			foreach ($la_fields as $ls_field) {
				$ao_entity->setDirty($ls_field, FALSE);
			}
		}
	}
}