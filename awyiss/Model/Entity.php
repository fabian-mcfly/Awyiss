<?php declare(strict_types=1);


namespace Awyiss\Model;


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

		if (($lo_attributes = ($this->_fields['attributes'] ?? NULL)) && is_a($lo_attributes, \Cake\ORM\Entity::class)) {
			/** @var \Cake\ORM\Entity $lo_attributes */
			foreach ($lo_attributes->_fields as $ls_key => $lx_value) {
				if ($ls_key == 'parent_id') {
					continue;
				}
				$this->setVirtual([$ls_key], TRUE);
			}
			$this->setHidden(['attributes']);
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
		$ls_scope = \Cake\Utility\Inflector::variable($this->getSource());

		$ls_identifier = $this->identifier ?? $this->filename ?? $this->title ?? ($this->getSource() . $this->id);

		$ls_translationKey = $ls_scope . '::title' . \Cake\Utility\Inflector::camelize($ls_identifier);
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