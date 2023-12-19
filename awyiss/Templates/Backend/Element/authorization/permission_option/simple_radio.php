<?php declare(strict_types=1);
/**
 * @var SimplePermissionOption $ao_permission
 * @var Entity|NULL $ao_entity
 * @var string $as_identifier
 * @var string $as_scope
 * @var View $this
 */


use Awyiss\Authorization\PermissionOption\SimplePermissionOption;
use Awyiss\Model\Entity;
use Cake\View\View;

$lx_value = $ao_entity->permissions[ $as_identifier ]?->access ?? NULL;

if (!empty($as_scope)) {

	$lx_value = $ao_entity->permissions[ $as_scope ][ $as_identifier ]?->access ?? NULL;
}

echo $this->Form->radio('permissions[' . $as_scope . '][' . $as_identifier . ']', $ao_permission->getOptions(), ['value' => $lx_value ?? '']);
