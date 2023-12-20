<?php declare(strict_types=1);


namespace Awyiss\Authorization\PermissionOption;


use Cake\Core\InstanceConfigTrait;
use Cake\Utility\Inflector;
use Cake\Utility\Text;
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
	protected array $_defaultConfig = [
		'preferredInput' => PermissionOptionType::TYPE_RADIO,
		'identifier' => NULL,
	];


	/**
	 * @inheritDoc
	 */
	public function __construct(array $aa_config, PermissionOptionCollection $ao_permissionOptionCollection) {
		$ls_type = static::getType();
		$ls_testType = strtolower(Text::slug($ls_type, '_'));

		if ($ls_testType !== $ls_type) {
			throw new RuntimeException(
				sprintf(
					'The provided type should be written underscored (`%s`). `%s` given.',
					$ls_testType,
					$ls_type
				)
			);
		}

		$this->permissionOptionCollection = $ao_permissionOptionCollection;

		$this->setConfig($aa_config);
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
	public function getOptions(): array {
		return $this->options;
	}


	/**
	 * @inheritDoc
	 *
	 * @throws RuntimeException
	 */
	public function setOptions(array $aa_options): static {
		throw new RuntimeException(sprintf('`%s` does not allow setting options. Use `%s` instead.', static::class, CallbackPermissionOption::class));
	}


	/**
	 * @inheritDoc
	 */
	public function hasSettings(): bool {
		return FALSE;
	}
}
