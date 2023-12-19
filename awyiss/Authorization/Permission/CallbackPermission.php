<?php

declare(strict_types=1);


namespace Awyiss\Authorization\Permission;


use RuntimeException;


class CallbackPermission extends SimplePermission {
	private mixed $lx_callback;


	public function __construct (array $aa_config, PermissionCollection $ao_permissionCollection) {
		parent::__construct($aa_config, $ao_permissionCollection);

		if (isset($aa_config['callback'])) {
			$this->setCallback($aa_config['callback']);
		}
	}


	public function setCallback (mixed $ax_callback): self {
		if (!is_callable($ax_callback)) {
			throw new RuntimeException('Config `callback` must be callable');
		}

		$this->lx_callback = $ax_callback;

		return $this;
	}


	public function getCallback (): mixed {
		return $this->lx_callback;
	}
}