<?php declare(strict_types=1);


namespace Awyiss\Model;


use Cake\Utility\Inflector;


/**
 * Page Entity
 *
 * @property string $label
 */
class Entity extends \Cake\ORM\Entity {
	/**
	 * @var array Default values for the entity
	 */
	protected array $defaults = [];


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function __construct (array $aa_properties = [], array $aa_options = []) {
		parent::__construct($aa_properties, $aa_options);

		if (($lo_attributes = ($this->_fields['attributes'] ?? NULL)) && is_a($lo_attributes, Entity::class)) {
			$ls_foreignKey = Inflector::singularize(Inflector::underscore($this->getSource())) . '_id';

			/** @var Entity $lo_attributes */
			foreach ($lo_attributes->_fields as $ls_key => $lx_value) {
				if (in_array($ls_key, ['id', $ls_foreignKey])) {
					continue;
				}
				$this->setVirtual([$ls_key], TRUE);
			}
			$this->setHidden(['attributes'], TRUE);
		}
	}


	/**
	 * Creates and returns a specific text, used for list items and so on
	 * It uses the first of following db colums identifier, filename, title if present and
	 * prepends a translatable text in case the entity is inactive (active = 0)
	 *
	 * The label can be translated as well
	 *
	 * @noinspection PhpUnused
	 */
	protected function _getLabel (): string {
		$ls_scope = Inflector::variable($this->getSource());

		$ls_identifier = $this->identifier ?? $this->filename ?? $this->title ?? ($this->getSource() . $this->id);

		$ls_translationKey = $ls_scope . '::title' . Inflector::camelize($ls_identifier);
		if ($ls_translationKey == ($ls_title = __($ls_translationKey))) {
			$ls_title = $ls_identifier;
		}

		$ls_inactive = '';
		if (key_exists('active', $this->_fields) && empty($this->active)) {
			$ls_translationKey = $ls_scope . '::inactive';
			if ($ls_translationKey == ($ls_inactive = __($ls_translationKey))) {
				$ls_inactive = __('system::inactive');
			}
			$ls_inactive .= ' ';
		}

		return $ls_inactive . $ls_title;
	}


	/**
	 * @todo reconsider if this is a good idea.
	 * Not being able to set or modify virtual avoids confusion when values aren't persisted after saving an entity
	 * BUT it removes the ability to modify values "on the fly" that never need to make it to the database
	 *
	public function set($as_field, $ax_value = NULL, array $aa_options = []): static {
		if ($as_field === 'jason_test') {
			if (in_array($as_field, $this->getVirtual())) {
				throw new \RuntimeException('Cannot modify virtual elements');
			}
		}

		parent::set($as_field, $ax_value, $aa_options);

		return $this;
	}*/


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function &get (string $as_field) {
		/*$ls_field = \Cake\Utility\Inflector::underscore($as_field);
		if (!array_key_exists($ls_field, $this->_fields)) {
			$ls_field = $as_field;
		}*/
		$ls_field = $as_field;
		$lx_value = parent::get($ls_field);
		$ls_method = static::_accessor($ls_field, 'get');

		//\Cake\Log\Log::write('error', $as_field . ' -> ' . $ls_field);

		//Awyiss!
		if ($this->_fields) {
			if ( ! $lx_value && ! array_key_exists($ls_field, $this->_fields) && ! $ls_method && in_array($ls_field, $this->getVirtual())) {
				$lx_value = $this->_fields['attributes']->get($ls_field);
			}
		}

		return $lx_value;
	}


	/**
	 * Return the default values set for this entity
	 *
	 * @return array
	 */
	public function defaultValues (): array {
		return $this->defaults;
	}
}