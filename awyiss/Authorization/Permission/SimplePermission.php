<?php

declare(strict_types=1);


namespace Awyiss\Authorization\Permission;


use Cake\Utility\Hash;
use RuntimeException;


class SimplePermission implements PermissionInterface {
	private PermissionCollection $lo_permissionCollection;
	private string $ls_scope;
	private string $ls_identifier;


	public function __construct (array $aa_config, PermissionCollection $ao_permissionCollection) {
		$this->lo_permissionCollection = $ao_permissionCollection;

		if (isset($aa_config['scope'])) {
			$this->setScope($aa_config['scope']);
		}
		if (isset($aa_config['identifier'])) {
			$this->setIdentifier($aa_config['identifier']);
		}
	}


	public function setScope (string $as_scope): PermissionInterface {
		$this->ls_scope = substr($as_scope, strrpos($as_scope, '\\') + 1);
		$this->ls_scope = \Cake\Utility\Inflector::underscore($this->ls_scope);

		return $this;
	}


	public function getScope (): string {
		return $this->ls_scope;
	}


	public function setIdentifier (string $as_identifier): PermissionInterface {
		$this->_checkDuplicateIdentifier($as_identifier);

		$this->ls_identifier = $as_identifier;

		return $this;
	}


	public function getIdentifier (): string {
		return $this->ls_identifier;
	}


	public function getAccessFromData (array $aa_data): int|string {
		return (int)Hash::get($aa_data, 'permission.' . $this->ls_scope . '.' . $this->ls_identifier, 0);
	}


	public function getSettingsFromData (array $aa_data): ?array {
		return NULL;
	}


	public function getFormElements (\Cake\View\View $ao_view, array $aa_currentData = []): string {
		$la_permissionData = array_merge([
			'access' => 0,
			'settings' => NULL,
		], $aa_currentData[ $this->ls_scope ][ $this->ls_identifier ] ?? []);

		$la_options = [
			['value' => -1, 'text' => ''],
			['value' => 0, 'text' => ''],
			['value' => 1, 'text' => ''],
		];

		return $ao_view->Form->radio($ls_fieldName = 'permission[' . $this->ls_scope . '][' . $this->ls_identifier . ']', $la_options, ['label' => FALSE, 'hiddenField' => FALSE, 'value' => $la_permissionData['access']]) .
			$ao_view->Form->label(($ls_label = mb_strtolower(\Cake\Utility\Text::slug($ls_fieldName))), __('::permission_option_deny'), ['for' => $ls_label . '--1']) .
			$ao_view->Form->label($ls_label . '-0', __('::permission_option_inherit')) .
			$ao_view->Form->label($ls_label . '-1', __('::permission_option_allow'));
	}


	/**
	 * @param string $as_identifier
	 *
	 * @throws \RuntimeException
	 */
	protected function _checkDuplicateIdentifier (string $as_identifier): void {
		if (in_array($as_identifier, $this->lo_permissionCollection->loaded()) && $this->lo_permissionCollection->get($as_identifier) !== $this) {
			$ls_msg = sprintf('The identifier "%s" is already in use.', $as_identifier);
			throw new RuntimeException($ls_msg);
		}
	}
}