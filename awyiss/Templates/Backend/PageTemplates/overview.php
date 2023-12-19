<?php
/**
 * @var \Awyiss\View\BackendView $this
 * @var \Awyiss\Model\Entity\PageTemplate[]|\Cake\Collection\CollectionInterface $pageTemplates
 */
?>
<div class="pageTemplates overview content">
	<?=$this->Html->link(__('New Page Template'), ['action' => 'add'], ['class' => 'button float-right'])?>
	<h3><?=__('Page Templates')?></h3>
	<div class="table-responsive">
		<table>
			<thead>
				<tr>
					<th><?=$this->Paginator->sort('id')?></th>
					<th><?=$this->Paginator->sort('title')?></th>
					<th><?=$this->Paginator->sort('filename')?></th>
					<th><?=$this->Paginator->sort('contentareas')?></th>
					<th><?=$this->Paginator->sort('page_roles_id')?></th>
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
				<?php foreach ($pageTemplates as $pageTemplate):?>
				<tr>
					<td><?=$this->Number->format($pageTemplate->id)?></td>
					<td><?=h($pageTemplate->title)?></td>
					<td><?=h($pageTemplate->filename)?></td>
					<td><?=h($pageTemplate->contentareas)?></td>
					<td><?=$pageTemplate->has('page_role') ? $this->Html->link($pageTemplate->page_role->title, ['controller' => 'PageRoles', 'action' => 'view', $pageTemplate->page_role->id]) : ''?></td>
					<td><?=h($pageTemplate->active)?></td>
					<td><?=h($pageTemplate->deleted)?></td>
					<td><?=$this->Number->format($pageTemplate->system_order)?></td>
					<td><?=$this->Number->format($pageTemplate->created_by)?></td>
					<td><?=h($pageTemplate->created_on)?></td>
					<td><?=$this->Number->format($pageTemplate->changed_by)?></td>
					<td><?=h($pageTemplate->changed_on)?></td>
					<td><?=$this->Number->format($pageTemplate->deleted_by)?></td>
					<td><?=h($pageTemplate->deleted_on)?></td>
					<td class="actions">
						<?=$this->Html->link(__('Edit'), ['action' => 'edit', 'id' => $pageTemplate->id])?>
						<?=$this->Form->postLink(__('Delete'), ['action' => 'delete', $pageTemplate->id], ['confirm' => __('Are you sure you want to delete # {0}?', $pageTemplate->id)])?>
					</td>
				</tr>
				<?php endforeach;?>
			</tbody>
		</table>
	</div>
	<?=$this->Paginator?>
</div>
