<?php declare(strict_types=1);


namespace Awyiss\Authorization\PermissionOption;


use Cake\Core\InstanceConfigTrait;
use RuntimeException;


/**
 * Abstract Permission class that offers some required methods, as defined in `PermissionInterface`
 */
abstract class AbstractPermissionOption implements PermissionOptionInterface {
	use InstanceConfigTrait;


	/**
	 * @var array
	 */
	protected array $options = [];
	/**
	 * @var PermissionOptionCollection
	 */
	protected PermissionOptionCollection $permissionOptionCollection;
	/**
	 * @var string
	 */
	protected string $type;
	/**
	 * Default config for this object.
	 * - `preferredInput` The preferred form input to display when showing the permission-selection
	 * - `identifier` The identifier used by this permission
	 *
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [ // phpcs:ignore
		'preferredInput' => PermissionOptionType::Radio,
		'identifier' => null,
	];


	/**
	 * @inheritDoc
	 */
	public function __construct(array $config, PermissionOptionCollection $permissionOptionCollection) {
		$this->permissionOptionCollection = $permissionOptionCollection;

		$this->setConfig($config);
	}


	/**
	 * @inheritDoc
	 */
	public function getPermissionOptionCollection(): PermissionOptionCollection {
		return $this->permissionOptionCollection;
	}


	/**
	 * @inheritDoc
	 */
	public function getType(): string {
		return $this->type;
	}


	/**
	 * @inheritDoc
	 */
	public function getOptions(): array {
		return $this->options;
	}


	/**
	 * @inheritDoc
	 * @throws RuntimeException
	 */
	public function setOptions(array $options): static {
		throw new RuntimeException(sprintf('`%s` does not allow setting options. Use `%s` instead.', static::class, CallbackPermissionOption::class));
	}


	/**
	 * @inheritDoc
	 */
	public function hasSettings(): bool {
		return false;
	}
}
