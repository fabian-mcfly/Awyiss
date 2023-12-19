<?php declare(strict_types=1);


namespace Awyiss\Authorization\Permission;


use Awyiss\Authorization\AccessCollection;
//use RuntimeException;


/**
 * A permission class that uses a defined callback to define the accessibility
 */
class CallbackPermission extends SimplePermission {
	/**
	 * @var array
	 */
	protected array $callbacks = [
		'general' => NULL,
		'Entity.create' => NULL,
		'Entity.update' => NULL,
		'Model.beforeFind' => NULL,
		'Model.beforeSoftDelete' => NULL,
		'Model.beforeDelete' => NULL,
	];


	/**
	 * @param array $aa_config
	 * @param \Awyiss\Authorization\Permission\PermissionCollection $ao_permissionCollection
	 */
	public function __construct (array $aa_config, PermissionCollection $ao_permissionCollection) {
		parent::__construct($aa_config, $ao_permissionCollection);

		if (isset($aa_config['callbacks'])) {
			$this->setCallbacks($aa_config['callbacks']);
		}
	}


	/**
	 * @param string $as_event
	 *
	 * @return NULL|callable
	 *
	 * @noinspection PhpUnused
	 */
	public function getCallback (string $as_event): ?callable {
		return $this->callbacks[ $as_event ] ?? NULL;
	}


	/**
	 * Sets the callback to be used by the permission
	 *
	 * @param mixed $ax_callback
	 *
	 * @return $this
	 */
	public function setCallback (string $as_event, callable $ax_callback): static {
		$this->callbacks[ $as_event ] = $ax_callback;

		return $this;
	}


	/**
	 * @return mixed
	 *
	 * @noinspection PhpUnused
	 */
	public function getCallbacks (): array {
		return $this->callbacks;
	}


	/**
	 * Sets the callback to be used by the permission
	 *
	 * @param mixed $aa_callbacks
	 *
	 * @return $this
	 */
	public function setCallbacks (array $aa_callbacks): static {
		foreach ($aa_callbacks AS $ls_event => $lc_callback) {
			$this->setCallback($ls_event, $lc_callback);
		}

		return $this;
	}


	/**
	 * {@inheritDoc]
	 *
	 * Additionally, get a callable from the configuration and call it.
	 * This allows the callback to define additional logic for the accessibility of the permission
	 */
	public function isAccessible (array $aa_access, array $aa_additionalData, AccessCollection $ao_accessCollection): ?bool {
		$lb_accessible = parent::isAccessible($aa_access, $aa_additionalData, $ao_accessCollection);

		$lc_callback = NULL;
		if ( ! empty($aa_additionalData['event'])) {
			$lc_callback = $this->getCallback($aa_additionalData['event']);
		}

		//If the callback for the given event is not set, fall back to the general one. To disable one event completely, its callback needs to be FALSE
		if ($lc_callback === NULL) {
			$lc_callback = $this->getCallback('general');
		}

		if ($lc_callback) {
			$lb_accessible = call_user_func($lc_callback, $lb_accessible, $aa_access, $aa_additionalData, $ao_accessCollection);
		}

		return $lb_accessible;

		//throw new RuntimeException(sprintf('`%s` is not implemented in `%s` yet.', __FUNCTION__, static::class));
	}
}