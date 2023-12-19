<?php
/**
 * @var \Cake\View\View $this
 * @var \Awyiss\Model\Entity\Language $language
 */

?>
<div class="row">
	<div class="column-responsive column-80">
		<div class="languages form content">
			<?=$this->Form->create($language)?>
			<fieldset>
				<legend><?=__('Edit Language')?></legend>
				<?php
				echo $this->Form->control('shortcode');
				echo $this->Form->control('title');
				echo $this->Form->control('timezone');
				echo $this->Form->control('locale');
				echo $this->Form->control('type');
				echo $this->Form->control('active');
				echo $this->Form->control('system_order');
				?>
			</fieldset>
			<?=$this->Form->button(__('Submit'))?>
			<?=$this->Form->end()?>
		</div>
	</div>
</div>
