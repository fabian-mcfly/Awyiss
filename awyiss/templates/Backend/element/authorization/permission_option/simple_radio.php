<?php declare(strict_types=1);
/**
 * @var SimplePermissionOption $permission
 * @var Entity|null $entity
 * @var string $identifier
 * @var string $scope
 * @var \Awyiss\View\AppView $this
 */


use Awyiss\Authorization\PermissionOption\SimplePermissionOption;
use Awyiss\Model\Entity;

$value = $entity->permissions[ $identifier ]?->access ?? null;

if (!empty($scope)) {

	$value = $entity->permissions[ $scope ][ $identifier ]?->access ?? null;
}

echo $this->Form->radio('permissions[' . $scope . '][' . $identifier . ']', $permission->getOptions(), ['value' => $value ?? '']);
