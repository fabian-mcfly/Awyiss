<?php

declare(strict_types=1);


namespace Awyiss\Authorization\Permission;


interface PermissionInterface {
	public function __construct (array $aa_config, PermissionCollection $ao_permissionCollection);


	public function setScope (string $as_scope): self;


	public function getScope (): string;


	public function setIdentifier (string $as_identifier): self;


	public function getIdentifier (): string;


	public function getAccessFromData (array $aa_data): int|string;


	public function getSettingsFromData (array $aa_data): ?array;


	public function getFormElements (\Cake\View\View $ao_view, array $aa_currentData = []): string;
}