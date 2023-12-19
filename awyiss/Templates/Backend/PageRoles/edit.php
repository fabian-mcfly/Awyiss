<?php
/**
 * @var \Awyiss\View\BackendView $this
 * @var \Awyiss\Model\Entity\PageRole $pageRole
 */
?>
<div class="row">
	<div class="column-responsive column-80">
		<div class="pageRoles form content">
			<?=$this->Form->create($pageRole)?>
			<fieldset>
				<legend><?=__('Edit Page Role')?></legend>
				<?php
						echo $this->Form->control('title');
						echo $this->Form->control('identifier');
						echo $this->Form->control('include_in_linklist');
						echo $this->Form->control('system_order');
						echo $this->Form->control('active');
						echo $this->Form->control('deleted');
						echo $this->Form->control('created_by');
						echo $this->Form->control('created_on');
						echo $this->Form->control('changed_by');
						echo $this->Form->control('changed_on');
						echo $this->Form->control('deleted_by');
						echo $this->Form->control('deleted_on');
				?>
			</fieldset>
			<?=$this->Form->button(__('Submit'))?>
			<?=$this->Form->end()?>
		</div>
	</div>
</div>
