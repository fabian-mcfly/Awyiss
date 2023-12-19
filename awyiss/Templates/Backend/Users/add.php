<?php
/**
 * @var \Cake\View\View $this
 * @var \Awyiss\Model\Entity\User $user
 * @var \Awyiss\Model\Entity\Usergroup[] $usergroups
 */

?>
<div class="row">
	<div class="column-responsive column-80">
		<div class="users form content">
			<?=$this->Form->create($user)?>
			<fieldset>
				<legend><?=__('Add User')?></legend>
				<?php
				echo $this->Form->control('username');
				echo $this->Form->control('password', ['value' => '']);
				echo $this->Form->control('firstname');
				echo $this->Form->control('lastname');
				echo $this->Form->control('email');
				echo $this->Form->control('active');
				?>
			</fieldset>

			<fieldset>
				<legend><?=__('::fieldset_usergroups')?></legend>
				<ul class="NoList">
					<?php foreach ($usergroups as $lo_usergroup) : ?>
						<li>
							<?=$this->Form->control('usergroups._ids.' . $lo_usergroup->id, [
								'hiddenField' => FALSE,
								'type' => 'checkbox',
								'value' => $lo_usergroup->id,
								'label' => $lo_usergroup->title,
								'checked' => in_array($lo_usergroup->id, array_column($user->usergroups ?? [], 'id')),
							])?>
						</li>
					<?php endforeach ?>
				</ul>
			</fieldset>

			<?=$this->Form->button(__('Submit'))?>
			<?=$this->Form->end()?>
		</div>
	</div>
</div>
