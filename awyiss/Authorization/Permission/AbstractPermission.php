<?php declare(strict_types=1);


namespace Awyiss\Authorization\Permission;


use Cake\Core\InstanceConfigTrait;
use Cake\Utility\Inflector;
use RuntimeException;


/**
 * Abstract Permission class that offers some required methods, as defined in `PermissionInterface`
 */
abstract class AbstractPermission implements PermissionInterface {
	use InstanceConfigTrait;


	protected array $options = [];
	protected PermissionCollection $permissionCollection;
	protected string $type;
	/**
	 * Default config for this object.
	 * - `preferredInput` The preferred form input to display when showing the permission-selection
	 * - `identifier` The identifier used by this permission
	 *
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'preferredInput' => PermissionTypes::TYPE_RADIO,
		'identifier' => NULL,
	];


	/**
	 * @inheritDoc
	 */
	public function __construct (array $aa_config, PermissionCollection $ao_permissionCollection) {
		$this->permissionCollection = $ao_permissionCollection;

		$this->setConfig($aa_config);
	}


	/**
	 * @inheritDoc
	 */
	public function getPermissionCollection (): PermissionCollection {
		return $this->permissionCollection;
	}


	/**
	 * @inheritDoc
	 */
	public function getType (): string {
		if (!isset($this->type)) {
			$la_parts = explode('\\', static::class);
			$this->type = array_pop($la_parts) ?? '';
			$this->type = substr($this->type, 0, -10);
			$this->type = Inflector::underscore($this->type);
		}

		return $this->type;
	}


	/**
	 * @inheritDoc
	 */
	public function getOptions (): array {
		return $this->options;
	}


	/**
	 * @inheritDoc
	 *
	 * @throws \RuntimeException
	 */
	public function setOptions (array $aa_options): static {
		throw new RuntimeException(sprintf('`%s` does not allow setting options. Use `%s` instead.', static::class, CallbackPermission::class));
	}


	/**
	 * @inheritDoc
	 */
	public function hasSettings (): bool {
		return FALSE;
	}
}