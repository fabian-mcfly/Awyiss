<?php
/**
 * @var \Cake\View\View $this
 * @var \Awyiss\Model\Entity\Language[]|\Cake\Collection\CollectionInterface $languages
 */

?>
<div class="languages overview content">
	<?=$this->Html->link(__('New Language'), ['action' => 'add'], ['class' => 'button float-right'])?>
	<h3><?=__('::headline')?></h3>

	<div class="table-responsive">
		<table>
			<thead>
				<tr>
					<th><?=$this->Paginator->sort('shortcode')?></th>
					<th><?=$this->Paginator->sort('title')?></th>
					<th><?=$this->Paginator->sort('timezone')?></th>
					<th><?=$this->Paginator->sort('locale')?></th>
					<th><?=$this->Paginator->sort('type')?></th>
					<th><?=$this->Paginator->sort('active')?></th>
					<th><?=$this->Paginator->sort('deleted')?></th>
					<th><?=$this->Paginator->sort('system_order')?></th>
					<th class="actions"><?=__('Actions')?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($languages as $language):?>
					<tr>
						<td><?=h($language->shortcode)?></td>
						<td><?=h($language->title)?></td>
						<td><?=h($language->timezone)?></td>
						<td><?=h($language->locale)?></td>
						<td><?=h($language->type)?></td>
						<td><?=h($language->active)?></td>
						<td><?=h($language->deleted)?></td>
						<td><?=$this->Number->format($language->system_order)?></td>
						<td class="actions">
							<?=$this->Html->link(__('Edit'), ['action' => 'edit', 'id' => $language->id])?>
							<?=$this->Form->postLink(__('Delete'), [
								'action' => 'delete',
								$language->id,
							], ['confirm' => __('Are you sure you want to delete # {0}?', $language->id)])?>
						</td>
					</tr>
				<?php endforeach;?>
			</tbody>
		</table>
	</div>
	<?=$this->Paginator?>
</div>
