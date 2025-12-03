<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Awyiss;
use Awyiss\Configuration\ConfigOptionsProvider;
use Awyiss\Configuration\ConfigOptionType;
use Awyiss\Model\Entity;
use Awyiss\Utility\Inflector;


/**
 * UserConfiguration Entity
 *
 * @property int $id
 * @property string|null $scope
 * @property string|null $identifier
 * @property string|null $value
 * @property int|null $userId
 * @property \Awyiss\Model\Entity\User $user
 * @property mixed $printableValue
 */
class UserConfiguration extends Entity {
	protected static array $fieldMap = [
		'user_id' => 'userId',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'scope' => true,
		'identifier' => true,
		'value' => true,
		'userId' => true,
	];
	/**
	 * @inheritDoc
	 */
	protected array $defaultValues = [
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
	 * @see \Awyiss\Model\Entity\UserConfiguration::$identifier
	 */
	protected function _setIdentifier(?string $identifier): ?string {
		if ($identifier === null) {
			return null;
		}


		return Inflector::underscore($identifier);
	}


	/**
	 * @return mixed
	 * @noinspection DuplicatedCode
	 */
	protected function _getPrintableValue(): mixed {
		if (!$this->scope || !$this->identifier) {
			return null;
		}

		$configOptions = ConfigOptionsProvider::loadConfigOptions($this->scope);
		$configOption = $configOptions?->getConfigOption(Awyiss::REALM_BACKEND, $this->identifier);

		$value = $this->value;

		if ($configOption) {
			$value = $configOption->typecastConfigValue($this->value);

			if ($configOption->getType() === ConfigOptionType::ListKey) {
				return $configOption->getValues(true)[ $value ] ?? $value;
			}

			if ($configOption->getType() === ConfigOptionType::ValueCollection) {
				$values = $configOption->getValues(true);

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
			'array', 'object' => print_r($value, true),
			default => $value,
		};
	}


	/**
	 * Make sure the scope is always lowercase, underscored and free of special characters
	 *
	 * @param string|null $scope
	 * @return string|null
	 */
	protected function _setScope(?string $scope): ?string {
		if ($scope === null) {
			return null;
		}

		$scope = Inflector::underscore($scope);
		$scope = Inflector::singularize($scope);
		$scope = Inflector::pluralize($scope);


		return Inflector::underscore($scope);
	}


	/**
	 * If the provided value is false, set the value to 0
	 *
	 * @param mixed $value
	 * @return mixed
	 * @see \Awyiss\Model\Entity\UserConfiguration::$value
	 */
	protected function _setValue(mixed $value): mixed {
		return $value === false ? 0 : $value;
	}
}
