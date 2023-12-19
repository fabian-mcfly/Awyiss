<?php declare(strict_types=1);
/**
 * @var \Awyiss\Authorization\Permission\SimplePermission $ao_permission
 * @var \Awyiss\Model\Entity\Usergroup|NULL $ao_entity
 * @var string $as_identifier
 * @var string $as_scope
 * @var \Cake\View\View $this
 */


$ls_scope = '[' . $as_scope . ']';
$ls_fieldName = 'permissions' . $ls_scope . '[' . $as_identifier . ']';
$ls_label = strtolower(\Cake\Utility\Text::slug($ls_fieldName));

$lx_value = $ao_entity->usergroup_permissions[ $as_scope ][ $as_identifier ]?->access ?? NULL;

$la_options = [
	$ao_permission::OPTION_GRANTED => NULL,
	$ao_permission::OPTION_INDIFFERENT => NULL,
	$ao_permission::OPTION_DENIED => NULL,
];


echo $this->Form->radio($ls_fieldName, $la_options, ['label' => FALSE, 'hiddenField' => FALSE, 'value' => $lx_value ?? '']);
echo $this->Form->label($ls_label . ' ' . $ao_permission::OPTION_GRANTED, __('::simple_permission_option_granted'));
echo $this->Form->label($ls_label . ' ' . $ao_permission::OPTION_INDIFFERENT, __('::simple_permission_option_indifferent'));
echo $this->Form->label($ls_label . ' ' . $ao_permission::OPTION_DENIED, __('::simple_permission_option_denied'));
