<?php
/**
 * @var \Cake\View\View $this
 * @var \Awyiss\Model\Entity\ContentTemplate[]|\Cake\Collection\CollectionInterface $contentTemplates
 */

?>
<div class="contentTemplates overview content">
	<?=$this->Html->link(__('New Content Template'), ['action' => 'add'], ['class' => 'button float-right'])?>
	<h3><?=__('Content Templates')?></h3>
	<div class="table-responsive">
		<table>
			<thead>
				<tr>
					<th><?=$this->Paginator->sort('id')?></th>
					<th><?=$this->Paginator->sort('title')?></th>
					<th><?=$this->Paginator->sort('filename')?></th>
					<th><?=$this->Paginator->sort('visible_elements')?></th>
					<th><?=$this->Paginator->sort('active')?></th>
					<th><?=$this->Paginator->sort('deleted')?></th>
					<th><?=$this->Paginator->sort('system_order')?></th>
					<th><?=$this->Paginator->sort('created_by')?></th>
					<th><?=$this->Paginator->sort('created_on')?></th>
					<th><?=$this->Paginator->sort('changed_by')?></th>
					<th><?=$this->Paginator->sort('changed_on')?></th>
					<th><?=$this->Paginator->sort('deleted_by')?></th>
					<th><?=$this->Paginator->sort('deleted_on')?></th>
					<th class="actions"><?=__('Actions')?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($contentTemplates as $contentTemplate):?>
					<tr>
						<td><?=$this->Number->format($contentTemplate->id)?></td>
						<td><?=h($contentTemplate->title)?></td>
						<td><?=h($contentTemplate->filename)?></td>
						<td><?=h($contentTemplate->visible_elements)?></td>
						<td><?=h($contentTemplate->active)?></td>
						<td><?=h($contentTemplate->deleted)?></td>
						<td><?=$this->Number->format($contentTemplate->system_order)?></td>
						<td><?=$this->Number->format($contentTemplate->created_by)?></td>
						<td><?=h($contentTemplate->created_on)?></td>
						<td><?=$this->Number->format($contentTemplate->changed_by)?></td>
						<td><?=h($contentTemplate->changed_on)?></td>
						<td><?=$this->Number->format($contentTemplate->deleted_by)?></td>
						<td><?=h($contentTemplate->deleted_on)?></td>
						<td class="actions">
							<?=$this->Html->link(__('Edit'), ['action' => 'edit', $contentTemplate->id])?>
							<?=$this->Form->postLink(__('Delete'), [
								'action' => 'delete',
								$contentTemplate->id,
							], ['confirm' => __('Are you sure you want to delete # {0}?', $contentTemplate->id)])?>
						</td>
					</tr>
				<?php endforeach;?>
			</tbody>
		</table>
	</div>
	<?=$this->Paginator?>
</div>
