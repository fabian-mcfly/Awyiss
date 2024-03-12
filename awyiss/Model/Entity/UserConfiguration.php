<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Awyiss;
use Awyiss\Configuration\ConfigOptionsProvider;
use Awyiss\Configuration\ConfigOptionType;
use Awyiss\Model\Entity;
use Cake\Utility\Inflector;


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
	 * @param string|null $as_identifier
	 * @return string|null
	 * @see \Awyiss\Model\Entity\UserConfiguration::$identifier
	 */
	protected function _setIdentifier(?string $as_identifier): ?string {
		if ($as_identifier === null) {
			return null;
		}


		return Inflector::underscore($as_identifier);
	}


	/**
	 * @return mixed
	 * @throws \ReflectionException
	 */
	public function _getPrintableValue(): mixed {
		if (!$this->scope || !$this->identifier) {
			return null;
		}

		$lo_configuration = ConfigOptionsProvider::loadConfigOptions($this->scope);
		$lo_configOption = $lo_configuration?->getConfigOption(Awyiss::REALM_BACKEND, $this->identifier);

		$lx_value = $this->value;

		if ($lo_configOption) {
			$lx_value = $lo_configOption->typecastConfigValue($this->value);

			if ($lo_configOption->getType() === ConfigOptionType::ListKey) {
				return $lo_configOption->getValues(true)[ $lx_value ] ?? $lx_value;
			}
		}


		return match (gettype($lx_value)) {
			'NULL' => null,
			'boolean' => $lx_value ? 'true' : 'false',
			'array', 'object' => print_r($lx_value, true),
			default => $lx_value,
		};
	}


	/**
	 * Make sure the scope is always lowercase, underscored and free of special characters
	 *
	 * @param string|null $as_scope
	 * @return string|null
	 * @see \Awyiss\Model\Entity\UserConfiguration::$scope
	 */
	protected function _setScope(?string $as_scope): ?string {
		if ($as_scope === null) {
			return null;
		}

		$ls_scope = Inflector::underscore($as_scope);
		$ls_scope = Inflector::singularize($ls_scope);
		$ls_scope = Inflector::pluralize($ls_scope);


		return Inflector::underscore($ls_scope);
	}


	/**
	 * If the provided value is false, set the value to 0
	 *
	 * @param mixed $ax_value
	 * @return mixed
	 * @see \Awyiss\Model\Entity\UserConfiguration::$value
	 */
	protected function _setValue(mixed $ax_value): mixed {
		return $ax_value === false ? 0 : $ax_value;
	}
}
