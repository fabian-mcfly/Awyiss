<?php declare(strict_types=1);


namespace Awyiss\Authorization;


class AccessCollection {
	protected array $access = [];


	public function add (string $as_scope, string $as_identifier, mixed $ax_access, mixed $ax_settings = NULL) {
		if (!isset($this->access[ $as_scope ])) {
			$this->access[ $as_scope ] = [];
		}

		if (!isset($this->access[ $as_scope ][ $as_identifier ])) {
			$this->access[ $as_scope ][ $as_identifier ] = [];
		}

		$this->access[ $as_scope ][ $as_identifier ][] = [
			'access' => $ax_access,
			'settings' => $ax_settings,
		];
	}


	public function get (): array {
		return $this->access;
	}


	/**
	 * @noinspection PhpUnused
	 */
	public function hasScope (string $as_scope): bool {
		return isset($this->access[ $as_scope ]);
	}


	public function getScope (string $as_scope): ?array {
		return $this->access[ $as_scope ] ?? NULL;
	}


	/**
	 * @noinspection PhpUnused
	 */
	public function hasIdentifier (string $as_scope, string $as_identifier): bool {
		return isset($this->access[ $as_scope ][ $as_identifier ]);
	}


	public function getIdentifier (string $as_scope, ?string $as_identifier): ?array {
		return $this->access[ $as_scope ][ $as_identifier ] ?? NULL;
	}
}