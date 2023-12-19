<?php
/**
 * @var \Awyiss\View\BackendView $this
 * @var \Awyiss\Model\Entity\ContentTemplate $contentTemplate
 */

?>
<div class="row">
	<aside class="column">
		<div class="side-nav">
			<h4 class="heading"><?=__('Actions')?></h4>
			<?=$this->Form->postLink(__('Delete'), [
				'action' => 'delete',
				$contentTemplate->id,
			], ['confirm' => __('Are you sure you want to delete # {0}?', $contentTemplate->id), 'class' => 'side-nav-item'])?>
			<?=$this->Html->link(__('List Content Templates'), ['action' => 'overview'], ['class' => 'side-nav-item'])?>
		</div>
	</aside>
	<div class="column-responsive column-80">
		<div class="contentTemplates form content">
			<?=$this->Form->create($contentTemplate)?>
			<fieldset>
				<legend><?=__('Edit Content Template')?></legend>
				<?php
				echo $this->Form->control('title');
				echo $this->Form->control('filename');
				echo $this->Form->control('visible_elements');
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
