<?php
/**
 * @var \Awyiss\View\BackendView $this
 * @var \Awyiss\Model\Entity\SystemConfiguration[]|\Cake\Collection\CollectionInterface $systemConfiguration
 */

?>
<div class="systemConfiguration overview content">
	<?=$this->Html->link(__('New System Configuration'), ['action' => 'add'], ['class' => 'button float-right'])?>
	<h3><?=__('System Configuration')?></h3>
	<div class="table-responsive">
		<table>
			<thead>
				<tr>
					<th><?=$this->Paginator->sort('id')?></th>
					<th><?=$this->Paginator->sort('key')?></th>
					<th><?=$this->Paginator->sort('languages_shortcode')?></th>
					<th class="actions"><?=__('Actions')?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($systemConfiguration as $systemConfiguration):?>
					<tr>
						<td><?=$this->Number->format($systemConfiguration->id)?></td>
						<td><?=h($systemConfiguration->key)?></td>
						<td><?=h($systemConfiguration->languages_shortcode)?></td>
						<td class="actions">
							<?=$this->Html->link(__('Edit'), ['action' => 'edit', $systemConfiguration->id])?>
							<?=$this->Form->postLink(__('Delete'), [
								'action' => 'delete',
								$systemConfiguration->id,
							], ['confirm' => __('Are you sure you want to delete # {0}?', $systemConfiguration->id)])?>
						</td>
					</tr>
				<?php endforeach;?>
			</tbody>
		</table>
	</div>
	<?=$this->Paginator?>
</div>
