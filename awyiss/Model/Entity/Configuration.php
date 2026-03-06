<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Awyiss;
use Awyiss\Configuration\ConfigOptionsProvider;
use Awyiss\Configuration\ConfigOptionType;
use Awyiss\Model\Entity;
use Awyiss\Utility\Inflector;


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
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [ // phpcs:ignore
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
		'scope' => 'System',
	];
	/**
	 * @inheritDoc
	 */
	protected array $_virtual = [ // phpcs:ignore
		'printableValue',
	];


	/**
	 * Make sure the identifier is always variableCase
	 *
	 * @param string|null $identifier
	 * @return string|null
	 * @see \Awyiss\Model\Entity\Configuration::$identifier
	 */
	protected function _setIdentifier(?string $identifier): ?string {
		if ($identifier === null) {
			return null;
		}


		return Inflector::variable($identifier);
	}


	/**
	 * @return mixed
	 */
	protected function _getPrintableValue(): mixed {
		if (!$this->scope || !$this->realm || !$this->identifier) {
			return null;
		}

		$configOptions = ConfigOptionsProvider::loadConfigOptions($this->scope);
		$configOption = $configOptions?->getConfigOption($this->realm, $this->identifier);

		$value = $this->value;

		if ($configOption) {
			$value = $configOption->typecastConfigValue($this->value, $this->languageShortcode);

			if ($configOption->getType() === ConfigOptionType::ListKey) {
				return $configOption->getValues(true, $this->languageShortcode)[ $value ] ?? $value;
			}

			if ($configOption->getType() === ConfigOptionType::ValueCollection) {
				$values = $configOption->getValues(true, $this->languageShortcode);

				if (!is_array($value)) {
					$value = $value ? [$value] : [];
				}

				$values = array_intersect_key($values, array_flip($value));

				return implode(', ', $values);
			}
		}


		return match (gettype($value)) {
			'NULL' => null,
			'boolean' => $value ? 'true' : 'false',
			'array' => array_is_list($value) ? implode(', ', $value) : print_r($value, true),
			'object' => print_r($value, true),
			default => $value,
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

		$scope = Inflector::underscore($scope);
		$scope = Inflector::singularize($scope);
		$scope = Inflector::pluralize($scope);


		return Inflector::camelize($scope);
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
