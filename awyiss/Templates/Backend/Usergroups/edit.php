<?php
/**
 * @var \Awyiss\View\BackendView $this
 * @var \Awyiss\Model\Entity\Usergroup $usergroup
 * @var array $currentPermissions
 * @var array $controllerPermissions
 */

?>
<div class="row">
	<div class="column-responsive column-80">
		<div class="usergroups form content">
			<?=$this->Form->create($usergroup)?>
			<fieldset>
				<legend><?=__('Edit Usergroup')?></legend>
				<?php
				echo $this->Form->control('active');
				echo $this->Form->control('title');
				?>
			</fieldset>

			<fieldset>
				<legend><?=__('::permissions')?></legend>

				<?php foreach ($controllerPermissions as $la_controllerData) : ?>
					<fieldset>
						<legend><?=$la_controllerData['title']?></legend>
						<ul class="Permissions">
							<?php /** @var \Awyiss\Authorization\Permission\PermissionInterface $lo_permissions */
							foreach ($la_controllerData['permissions'] as $lo_permissions) : ?>
								<li class="Permission">
									<?=__('::' . $lo_permissions->getIdentifier())?>
									<?=$lo_permissions->getFormElements($this, $currentPermissions)?>
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
