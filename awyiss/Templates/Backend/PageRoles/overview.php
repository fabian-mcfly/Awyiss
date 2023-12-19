<?php
/**
 * @var \Awyiss\View\BackendView $this
 * @var \Awyiss\Model\Entity\PageRole[]|\Cake\Collection\CollectionInterface $pageRoles
 */
?>
<div class="pageRoles overview content">
	<?=$this->Html->link(__('New Page Role'), ['action' => 'add'], ['class' => 'button float-right'])?>
	<h3><?=__('Page Roles')?></h3>
	<div class="table-responsive">
		<table>
			<thead>
				<tr>
					<th><?=$this->Paginator->sort('id')?></th>
					<th><?=$this->Paginator->sort('title')?></th>
					<th><?=$this->Paginator->sort('identifier')?></th>
					<th><?=$this->Paginator->sort('include_in_linklist')?></th>
					<th><?=$this->Paginator->sort('system_order')?></th>
					<th><?=$this->Paginator->sort('active')?></th>
					<th><?=$this->Paginator->sort('deleted')?></th>
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
				<?php foreach ($pageRoles as $pageRole):?>
				<tr>
					<td><?=$this->Number->format($pageRole->id)?></td>
					<td><?=h($pageRole->title)?></td>
					<td><?=h($pageRole->identifier)?></td>
					<td><?=h($pageRole->include_in_linklist)?></td>
					<td><?=$this->Number->format($pageRole->system_order)?></td>
					<td><?=h($pageRole->active)?></td>
					<td><?=h($pageRole->deleted)?></td>
					<td><?=$this->Number->format($pageRole->created_by)?></td>
					<td><?=h($pageRole->created_on)?></td>
					<td><?=$this->Number->format($pageRole->changed_by)?></td>
					<td><?=h($pageRole->changed_on)?></td>
					<td><?=$this->Number->format($pageRole->deleted_by)?></td>
					<td><?=h($pageRole->deleted_on)?></td>
					<td class="actions">
						<?=$this->Html->link(__('Edit'), ['action' => 'edit', 'id' => $pageRole->id])?>
						<?=$this->Form->postLink(__('Delete'), ['action' => 'delete', $pageRole->id], ['confirm' => __('Are you sure you want to delete # {0}?', $pageRole->id)])?>
					</td>
				</tr>
				<?php endforeach;?>
			</tbody>
		</table>
	</div>
	<?=$this->Paginator?>
</div>
