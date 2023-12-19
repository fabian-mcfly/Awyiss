<?php
/**
 * @var \Awyiss\View\BackendView $this
 * @var \Awyiss\Model\Entity\User $user
 * @var \Awyiss\Model\Entity\Usergroup[] $usergroups
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?=__('Actions')?></h4>
                        <?=$this->Html->link(__('List Users'), ['action' => 'overview'], ['class' => 'side-nav-item'])?>
        </div>
    </aside>
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
				<ul>
					<?php foreach ($usergroups AS $lo_usergroup) : ?>
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
