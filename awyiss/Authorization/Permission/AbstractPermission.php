<?php /** @noinspection PhpUnused */

declare(strict_types=1);


namespace Awyiss\Authorization\Permission;


use Cake\Core\InstanceConfigTrait;


abstract class AbstractPermission implements PermissionInterface {
	use InstanceConfigTrait;


	public const TYPE_CHECKBOX = 'checkbox';
	public const TYPE_RADIO = 'radio';
	public const TYPE_SELECT = 'select';
	public const TYPE_MULTISELECT = 'select_multi';


	protected array $options = [];
	protected PermissionCollection $permissionCollection;
	protected string $type;
	/**
	 * Default config for this object.
	 * - `fields` The fields to use to identify a user by.
	 *
	 * @var array
	 */
	protected array $_defaultConfig = [
		'preferredInput' => self::TYPE_RADIO,
		'identifier' => NULL,
	];



	public function __construct (array $aa_config, PermissionCollection $ao_permissionCollection) {
		$this->permissionCollection = $ao_permissionCollection;

		$this->setConfig($aa_config);
	}


	public function getPermissionCollection (): PermissionCollection {
		return $this->permissionCollection;
	}


	/**
	 * @return string
	 */
	public function getType (): string {
		if (!isset($this->type)) {
			$la_parts = explode('\\', static::class);
			$this->type = array_pop($la_parts) ?? '';
			$this->type = substr($this->type, 0, -10);
			$this->type = \Cake\Utility\Inflector::underscore($this->type);
		}

		return $this->type;
	}


	/**
	 * @return array
	 */
	public function getOptions (): array {
		return $this->options;
	}


	/**
	 * @param array $aa_options
	 *
	 * @return \Awyiss\Authorization\Permission\PermissionInterface
	 */
	public function setOptions (array $aa_options): PermissionInterface {
		throw new \RuntimeException(sprintf('`%s` does not allow setting options. Use `%s` instead.', static::class, CallbackPermission::class));
	}


	/**
	 * @return bool
	 */
	public function hasSettings (): bool {
		return FALSE;
	}
}