<?php
/**
 * @var \Awyiss\View\BackendView $this
 * @var \Awyiss\Model\Entity\SystemConfiguration $systemConfiguration
 */

?>
<div class="row">
	<aside class="column">
		<div class="side-nav">
			<h4 class="heading"><?=__('Actions')?></h4>
			<?=$this->Html->link(__('List System Configuration'), ['action' => 'overview'], ['class' => 'side-nav-item'])?>
		</div>
	</aside>
	<div class="column-responsive column-80">
		<div class="systemConfiguration form content">
			<?=$this->Form->create($systemConfiguration)?>
			<fieldset>
				<legend><?=__('Add System Configuration')?></legend>
				<?php
				echo $this->Form->control('key');
				echo $this->Form->control('value');
				echo $this->Form->control('languages_shortcode');
				?>
			</fieldset>
			<?=$this->Form->button(__('Submit'))?>
			<?=$this->Form->end()?>
		</div>
	</div>
</div>
