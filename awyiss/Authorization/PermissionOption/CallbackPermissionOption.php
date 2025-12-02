<?php declare(strict_types=1);


namespace Awyiss\Authorization\PermissionOption;


use Awyiss\Authorization\Permission\PermissionCollection;


//use RuntimeException;

/**
 * A permission class that uses a defined callback to define the accessibility
 */
class CallbackPermissionOption extends SimplePermissionOption {
	/**
	 * @var array
	 */
	protected array $callbacks = [
		'general' => null,
		'Entity.create' => null,
		'Entity.update' => null,
		'Model.beforeFind' => null,
		'Model.beforeSoftDelete' => null,
		'Model.beforeDelete' => null,
	];
	/**
	 * @var string
	 */
	protected string $type = 'callback';


	/**
	 * @param array $config
	 * @param PermissionOptionCollection $permissionOptionCollection
	 */
	public function __construct(array $config, PermissionOptionCollection $permissionOptionCollection) {
		parent::__construct($config, $permissionOptionCollection);

		if (isset($config['callbacks'])) {
			$this->setCallbacks($config['callbacks']);
		}
	}


	/**
	 * @param string $event
	 * @return callable|null
	 * @noinspection PhpUnused
	 */
	public function getCallback(string $event): ?callable {
		return $this->callbacks[ $event ] ?? null;
	}


	/**
	 * Sets the callback to be used by the permission
	 *
	 * @param mixed $callback
	 * @return $this
	 */
	public function setCallback(string $event, callable $callback): static {
		$this->callbacks[ $event ] = $callback;


		return $this;
	}


	/**
	 * @return mixed
	 * @noinspection PhpUnused
	 */
	public function getCallbacks(): array {
		return $this->callbacks;
	}


	/**
	 * Sets the callback to be used by the permission
	 *
	 * @param mixed $callbacks
	 * @return $this
	 */
	public function setCallbacks(array $callbacks): static {
		foreach ($callbacks as $event => $callback) {
			$this->setCallback($event, $callback);
		}


		return $this;
	}


	/**
	 * {@inheritDoc]
	 *
	 * Additionally, get a callable from the configuration and call it.
	 * This allows the callback to define additional logic for the accessibility of the permission
	 */
	public function isAccessible(mixed $access, mixed $settings, array $additionalData, PermissionCollection $permissionCollection): ?bool {
		$isAccessible = parent::isAccessible($access, $settings, $additionalData, $permissionCollection);

		$callback = null;
		if (!empty($additionalData['event'])) {
			$callback = $this->getCallback($additionalData['event']);
		}

		// If the callback for the given event is not set, fall back to the general one. To disable one event completely, its callback needs to be false
		if ($callback === null) {
			$callback = $this->getCallback('general');
		}

		if ($callback) {
			$isAccessible = call_user_func($callback, $isAccessible, $access, $settings, $additionalData, $permissionCollection);
		}

		return $isAccessible;
	}
}
