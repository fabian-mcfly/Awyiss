<?php declare(strict_types=1);


namespace Awyiss\Authorization;


class AccessCollection {
	private $la_access = [];


	public function add ($as_scope, $as_identifier, $ax_access, $ax_settings = NULL) {
		if (!isset($this->la_access[ $as_scope ])) {
			$this->la_access[ $as_scope ] = [];
		}

		if (!isset($this->la_access[ $as_scope ][ $as_identifier ])) {
			$this->la_access[ $as_scope ][ $as_identifier ] = [];
		}

		$this->la_access[ $as_scope ][ $as_identifier ][] = [
			'access' => $ax_access,
			'settings' => $ax_settings,
		];
	}


	public function get (): array {
		return $this->la_access;
	}


	public function hasScope (string $as_scope): bool {
		return isset($this->la_access[ $as_scope ]);
	}


	public function getScope (string $as_scope): ?array {
		return $this->la_access[ $as_scope ] ?? NULL;
	}


	public function hasIdentifier (string $as_scope, string $as_identifier): bool {
		return isset($this->la_access[ $as_scope ][ $as_identifier ]);
	}


	public function getIdentifier (string $as_scope, ?string $as_identifier): ?array {
		return $this->la_access[ $as_scope ][ $as_identifier ] ?? NULL;
	}
}