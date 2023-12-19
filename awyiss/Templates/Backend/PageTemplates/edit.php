<?php
/**
 * @var \Awyiss\View\BackendView $this
 * @var \Awyiss\Model\Entity\PageTemplate $pageTemplate
 */
?>
<div class="row">
	<div class="column-responsive column-80">
		<div class="pageTemplates form content">
			<?=$this->Form->create($pageTemplate)?>
			<fieldset>
				<legend><?=__('Edit Page Template')?></legend>
				<?php
						echo $this->Form->control('title');
						echo $this->Form->control('filename');
						echo $this->Form->control('contentareas');
					echo $this->Form->control('page_roles_id', ['options' => $pageRoles]);
						echo $this->Form->control('active');
						echo $this->Form->control('deleted');
						echo $this->Form->control('system_order');
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
