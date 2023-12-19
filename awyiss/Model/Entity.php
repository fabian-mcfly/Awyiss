<?php declare(strict_types=1);


namespace Awyiss\Model;


abstract class Entity extends \Cake\ORM\Entity {
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


	public function &get (string $as_field) {
		$lx_value = parent::get($as_field);
		$ls_method = static::_accessor($as_field, 'get');

		//Awyiss!
		if ($this->_fields) {
			if ( ! $lx_value && ! array_key_exists($as_field, $this->_fields) && ! $ls_method && in_array($as_field, $this->getVirtual())) {
				$lx_value = $this->_fields['attributes']->get($as_field);
			}
		}

		return $lx_value;
	}
}