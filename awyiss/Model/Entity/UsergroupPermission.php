<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Authorization\Permission\PermissionAccess;
use Awyiss\Authorization\Permission\PermissionInterface;
use Awyiss\Model\Entity;
use Awyiss\Utility\Inflector;
use Cake\Datasource\FactoryLocator;


/**
 * UsergroupPermission Entity
 *
 * @property int $id
 * @property int|null $usergroupId
 * @property string|null $scope
 * @property string|null $identifier
 * @property \Awyiss\Authorization\Permission\PermissionAccess|null $access
 * @property array|null $settings
 * @property \Awyiss\Model\Entity\Usergroup $usergroup
 */
class UsergroupPermission extends Entity implements PermissionInterface {
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'usergroup_id' => 'usergroupId',
	];
	/**
	 * @var array<string, \Awyiss\Model\Entity\Datatable>
	 */
	protected static array $datatables;
	/**
	 * @var array<string, \Awyiss\Model\Entity\PageRole>
	 */
	protected static array $pageRoles;


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [ // phpcs:ignore
		'usergroupId' => true,
		'scope' => true,
		'identifier' => true,
		'access' => true,
		'settings' => true,
	];


	/**
	 * In the database, the scope exists as an underscored string
	 *
	 * @param string|null $scope
	 * @return string|null
	 * @see \Awyiss\Model\Entity\UsergroupPermission::$scope
	 */
	protected function _setScope(?string $scope): ?string {
		if ($scope === null) {
			return null;
		}

		return Inflector::underscore($scope);
	}


	/**
	 * In the database, the identifier exists as an underscored string
	 *
	 * @param string|null $identifier
	 * @return string|null
	 * @see \Awyiss\Model\Entity\UsergroupPermission::$identifier
	 */
	protected function _setIdentifier(?string $identifier): ?string {
		if ($identifier === null) {
			return null;
		}

		return Inflector::underscore($identifier);
	}


	/**
	 * @inheritDoc
	 *
	 * Required by PermissionInterface
	 */
	public function getScope(): string {
		return $this->scope;
	}


	/**
	 * @inheritDoc
	 *
	 * Required by PermissionInterface
	 */
	public function getIdentifier(): string {
		return $this->identifier;
	}


	/**
	 * @inheritDoc
	 *
	 * Required by PermissionInterface
	 */
	public function getAccess(): PermissionAccess {
		return $this->access;
	}


	/**
	 * @inheritDoc
	 *
	 * Required by PermissionInterface
	 */
	public function getSettings(): ?array {
		return $this->settings;
	}


	/**
	 * @inheritDoc
	 */
	protected function _getLabel(): string {
		['scope' => $scopeTitle, 'identifier' => $identifierTitle, 'access' => $accessTitle] = $this->_getLabelData();

		return sprintf(
			'%s - %s (%s)',
			$scopeTitle,
			$identifierTitle,
			$accessTitle
		);
	}


	/**
	 * Get the data required for the label
	 *
	 * @return array{scope: string, identifier: string, access: string}
	 */
	protected function _getLabelData(): array {
		$tableLocator = FactoryLocator::get('Table');

		if (!isset(static::$datatables)) {
			static::$datatables = $tableLocator->get('Datatables')->findAllAndCache()->indexBy('identifier')->toArray();
		}

		if (!isset(static::$pageRoles)) {
			static::$pageRoles = $tableLocator->get('PageRoles')->findAllAndCache()->indexBy(function (PageRole $pageRole) {
				return Inflector::pluralize($pageRole->identifier);
			})->toArray();
		}

		$scopeTitle = $this->scope ? __d($this->scope, 'headline_overview') : null;
		if ($this->scope && str_contains($scopeTitle, '::headline_overview')) {
			if (isset(static::$pageRoles[ $this->scope ])) {
				$scopeTitle = static::$pageRoles[ $this->scope ]->label;
			}
			elseif (isset(static::$datatables[ $this->scope ])) {
				$scopeTitle = static::$datatables[ $this->scope ]->label;
			}
		}

		$identifierTitle = $this->scope ? __df($this->scope, 'usergroups', 'permission_' . Inflector::underscore($this->identifier)) : null;

		$accessTitle = $this->access ? __d('authorization', 'simple_permission_option_' . Inflector::underscore($this->access->name)) : null;

		return [
			'scope' => $scopeTitle,
			'identifier' => $identifierTitle,
			'access' => $accessTitle,
		];
	}
}
