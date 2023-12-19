<?php
/**
 * @var \Awyiss\View\BackendView $this
 * @var \Awyiss\Model\Entity\Usergroup $usergroup
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
			<?=$this->Form->button(__('Submit'))?>
			<?=$this->Form->end()?>
		</div>
	</div>
</div>
