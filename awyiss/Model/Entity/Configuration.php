<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Awyiss;
use Awyiss\Configuration\ConfigOptionsProvider;
use Awyiss\Configuration\ConfigOptionType;
use Awyiss\Model\Entity;
use Cake\Utility\Inflector;


/**
 * Configuration Entity
 *
 * @property int $id
 * @property string|null $realm
 * @property string|null $scope
 * @property string|null $identifier
 * @property string|null $value
 * @property string|null $languageShortcode
 * @property string|null $description
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 * @property \Awyiss\Model\Entity\Language $language
 * @property mixed $printableValue
 */
class Configuration extends Entity {
	protected static array $fieldMap = [
		'language_shortcode' => 'languageShortcode',
		'created_by' => 'createdBy',
		'created_on' => 'createdOn',
		'changed_by' => 'changedBy',
		'changed_on' => 'changedOn',
		'deleted_by' => 'deletedBy',
		'deleted_on' => 'deletedOn',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'realm' => true,
		'scope' => true,
		'identifier' => true,
		'value' => true,
		'languageShortcode' => true,
		'description' => true,
	];
	/**
	 * @inheritDoc
	 */
	protected array $defaultValues = [
		'realm' => Awyiss::REALM_FRONTEND,
		'scope' => 'system',
	];
	/**
	 * @inheritDoc
	 */
	protected array $_virtual = [
		'printableValue',
	];


	/**
	 * Make sure the identifier is always underscored
	 *
	 * @param string|null $identifier
	 * @return string|null
	 * @see \Awyiss\Model\Entity\Configuration::$identifier
	 */
	protected function _setIdentifier(?string $identifier): ?string {
		if ($identifier === null) {
			return null;
		}


		return Inflector::underscore($identifier);
	}


	/**
	 * @return mixed
	 * @throws \ReflectionException
	 */
	public function _getPrintableValue(): mixed {
		if (!$this->scope || !$this->realm || !$this->identifier) {
			return null;
		}

		$lo_configuration = ConfigOptionsProvider::loadConfigOptions($this->scope);
		$lo_configOption = $lo_configuration?->getConfigOption($this->realm, $this->identifier);

		$lx_value = $this->value;

		if ($lo_configOption) {
			$lx_value = $lo_configOption->typecastConfigValue($this->value, $this->languageShortcode);

			if ($lo_configOption->getType() === ConfigOptionType::ListKey) {
				return $lo_configOption->getValues(true, $this->languageShortcode)[ $lx_value ] ?? $lx_value;
			}

			if ($lo_configOption->getType() === ConfigOptionType::ValueCollection) {
				$la_values = $lo_configOption->getValues(true, $this->languageShortcode);
				$la_values = array_intersect_key($la_values, array_flip($lx_value ?? []));

				return implode(', ', $la_values);
			}
		}


		return match (gettype($lx_value)) {
			'NULL' => null,
			'boolean' => $lx_value ? 'true' : 'false',
			'array' => array_is_list($lx_value) ? implode(', ', $lx_value) : print_r($lx_value, true),
			'object' => print_r($lx_value, true),
			default => $lx_value,
		};
	}


	/**
	 * Make sure the scope is always lowercase, underscored and free of special characters
	 *
	 * @param string|null $scope
	 * @return string|null
	 * @see \Awyiss\Model\Entity\Configuration::$scope
	 */
	protected function _setScope(?string $scope): ?string {
		if ($scope === null) {
			return null;
		}

		$ls_scope = Inflector::underscore($scope);
		$ls_scope = Inflector::singularize($ls_scope);
		$ls_scope = Inflector::pluralize($ls_scope);


		return Inflector::underscore($ls_scope);
	}


	/**
	 * If the provided value is false, set the value to 0
	 *
	 * @param mixed $value
	 * @return mixed
	 * @see \Awyiss\Model\Entity\Configuration::$value
	 */
	protected function _setValue(mixed $value): mixed {
		return $value === false ? 0 : $value;
	}
}
