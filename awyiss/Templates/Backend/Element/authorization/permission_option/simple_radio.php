<?php declare(strict_types=1);
/**
 * @var SimplePermissionOption $ao_permission
 * @var Entity|null $ao_entity
 * @var string $as_identifier
 * @var string $as_scope
 * @var \Awyiss\View\AppView $this
 */


use Awyiss\Authorization\PermissionOption\SimplePermissionOption;
use Awyiss\Model\Entity;

$lx_value = $ao_entity->permissions[ $as_identifier ]?->access ?? null;

if (!empty($as_scope)) {

	$lx_value = $ao_entity->permissions[ $as_scope ][ $as_identifier ]?->access ?? null;
}

echo $this->Form->radio('permissions[' . $as_scope . '][' . $as_identifier . ']', $ao_permission->getOptions(), ['value' => $lx_value ?? '']);
