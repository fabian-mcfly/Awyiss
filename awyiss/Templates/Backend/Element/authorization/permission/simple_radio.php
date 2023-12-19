<?php
/**
 * @var \Awyiss\Authorization\Permission\SimplePermission $ao_permission
 * @var \Awyiss\Model\Entity|NULL $ao_entity
 * @var string $as_identifier
 * @var string $as_scope
 * @var \Cake\View\View $this
 */

$ls_scope = NULL;
$lx_value = $ao_entity->permissions[ $as_identifier ]?->access ?? NULL;

if (!empty($as_scope)) {
	$ls_scope = '[' . $as_scope . ']';
	$lx_value = $ao_entity->permissions[ $as_scope ][ $as_identifier ]?->access ?? NULL;
}

echo $this->Form->radio('permissions' . $ls_scope . '[' . $as_identifier . ']', $ao_permission->getOptions(), ['value' => $lx_value ?? '']);
