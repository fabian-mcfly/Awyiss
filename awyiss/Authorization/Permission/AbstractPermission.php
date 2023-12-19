<?php declare(strict_types=1);


namespace Awyiss\Authorization\Permission;


use Cake\Core\InstanceConfigTrait;


abstract class AbstractPermission implements PermissionInterface {
	use InstanceConfigTrait;


	public const TYPE_CHECKBOX = 'checkbox';
	public const TYPE_RADIO = 'radio';
	public const TYPE_SELECT = 'select';
	public const TYPE_MULTISELECT = 'select_multi';


	protected ?array $la_options = NULL;
	protected ?PermissionCollection $lo_permissionCollection;
	protected ?string $ls_type = NULL;
	/**
	 * Default config for this object.
	 * - `fields` The fields to use to identify a user by.
	 *
	 * @var array
	 */
	protected $_defaultConfig = [
		'preferredInput' => self::TYPE_RADIO,
		'identifier' => NULL,
	];



	public function __construct (array $aa_config, ?PermissionCollection $ao_permissionCollection = NULL) {
		$this->lo_permissionCollection = $ao_permissionCollection;

		$this->setConfig($aa_config);
	}


	public function getPermissionCollection (): ?PermissionCollection {
		return $this->lo_permissionCollection;
	}


	/**
	 * @return string
	 */
	public function getType (): string {
		if ($this->ls_type === NULL) {
			$la_parts = explode('\\', static::class);
			$this->ls_type = array_pop($la_parts) ?? '';
			$this->ls_type = substr($this->ls_type, 0, -10);
			$this->ls_type = \Cake\Utility\Inflector::underscore($this->ls_type);
		}

		return $this->ls_type;
	}


	/**
	 * @return string
	 */
	public function getOptions (): array {
		return $this->la_options;
	}


	/**
	 * @param array $aa_options
	 *
	 * @return \Awyiss\Authorization\Permission\PermissionInterface
	 */
	public function setOptions (array $aa_options): PermissionInterface {
		throw new \RuntimeException(sprintf('`%s` does not allow setting options. Use `%s` instead.', self::class, CallbackPermission::class));
	}


	/**
	 * @return bool
	 */
	public function hasSettings (): bool {
		return FALSE;
	}
}