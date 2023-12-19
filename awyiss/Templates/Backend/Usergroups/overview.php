<?php
/**
 * @var \Awyiss\View\BackendView $this
 * @var \Awyiss\Model\Entity\Usergroup[]|\Cake\Collection\CollectionInterface $usergroups
 */
?>
<div class="usergroups overview content">
	<?=$this->Html->link(__('New Usergroup'), ['action' => 'add'], ['class' => 'button float-right'])?>
	<h3><?=__('Usergroups')?></h3>
	<div class="table-responsive">
		<table>
			<thead>
				<tr>
					<th><?=$this->Paginator->sort('id')?></th>
					<th><?=$this->Paginator->sort('title')?></th>
					<th><?=$this->Paginator->sort('active')?></th>
					<th class="actions"><?=__('Actions')?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($usergroups as $usergroup):?>
				<tr>
					<td><?=$this->Number->format($usergroup->id)?></td>
					<td><?=h($usergroup->title)?></td>
					<td><?=h($usergroup->active)?></td>
					<td class="actions">
						<?=$this->Html->link(__('Edit'), ['action' => 'edit', 'id' => $usergroup->id])?>
						<?=$this->Form->postLink(__('Delete'), ['action' => 'delete', $usergroup->id], ['confirm' => __('Are you sure you want to delete # {0}?', $usergroup->id)])?>
					</td>
				</tr>
				<?php endforeach;?>
			</tbody>
		</table>
	</div>
	<?=$this->Paginator?>
</div>
