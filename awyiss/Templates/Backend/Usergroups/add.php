<?php
/**
 * @var \Cake\View\View $this
 * @var \Awyiss\Model\Entity\Usergroup $usergroup
 * @var \Awyiss\Model\Entity\User[] $users
 * @var array $authorizationPolicies
 */

?>
<div class="row">
	<div class="column-responsive column-80">
		<div class="usergroups form content">
			<?=$this->Form->create($usergroup)?>
			<fieldset>
				<legend><?=__('Add Usergroup')?></legend>
				<?php
				echo $this->Form->control('title');
				echo $this->Form->control('active');
				?>
			</fieldset>

			<fieldset>
				<legend><?=__('::users')?></legend>
				<ul class="NoList">
					<?php foreach ($users as $lo_user) : ?>
						<li>
							<?=$this->Form->control('users._ids.' . $lo_user->id, [
								'hiddenField' => FALSE,
								'type' => 'checkbox',
								'value' => $lo_user->id,
								'label' => $lo_user->username,
								'checked' => in_array($lo_user->id, array_column($usergroup->users ?? [], 'id')),
							])?>
						</li>
					<?php endforeach ?>
				</ul>
			</fieldset>

			<fieldset>
				<legend><?=__('::permissions')?></legend>

				<?php foreach ($authorizationPolicies as $ls_policyClass) : ?>
					<fieldset>
						<legend><?=$ls_policyClass::getScope()?></legend>
						<ul class="NoList Permissions">
							<?php /** @var \Awyiss\Authorization\Permission\PermissionInterface $lo_permissions */
							foreach ($ls_policyClass::getPermissions() as $ls_identifier => $lo_permission) : ?>
								<li class="Permission">
									<?=__('::' . $ls_identifier)?>
									<?=$this->Permission->options($lo_permission, $usergroup, NULL, 'usergroups')?>
									<? //=$lo_permissions->getSettings()->render($this, 'usergroups')?>
								</li>
							<?php endforeach ?>
						</ul>
					</fieldset>
				<?php endforeach ?>
			</fieldset>

			<?=$this->Form->button(__('Submit'))?>
			<?=$this->Form->end()?>
		</div>
	</div>
</div>
