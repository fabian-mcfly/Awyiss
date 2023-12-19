<?php
/**
 * @var \Awyiss\View\BackendView $this
 * @var \Awyiss\Model\Entity\User[]|\Cake\Collection\CollectionInterface $users
 */

$lo_hasher = new \Authentication\PasswordHasher\DefaultPasswordHasher();

?>
<div class="users overview content">
	<?=$this->Html->link(__('New User'), ['action' => 'add'], ['class' => 'button float-right'])?>
	<h3><?=__('Users')?></h3>
	<div class="table-responsive">
		<table>
			<thead>
				<tr>
					<th><?=$this->Paginator->sort('id')?></th>
					<th><?=$this->Paginator->sort('username')?></th>
					<th><?=$this->Paginator->sort('last_login')?></th>
					<th><?=$this->Paginator->sort('firstname')?></th>
					<th><?=$this->Paginator->sort('lastname')?></th>
					<th><?=$this->Paginator->sort('email')?></th>
					<th class="actions"><?=__('Actions')?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($users as $user):?>
					<tr>
						<td><?=$this->Number->format($user->id)?></td>
						<td><?=h($user->username)?></td>
						<td><?=h($this->Time->format($user->last_login))?></td>
						<td><?=h($user->firstname)?></td>
						<td><?=h($user->lastname)?></td>
						<td><?=h($user->email)?></td>
						<td class="actions">
							<?=$this->Html->link(__('edit'), ['action' => 'edit', 'id' => $user->id])?>
							<?php /*if (!$this->Identity->is($user->id)) :*/ ?>
							<?=$this->Html->link(__('delete'), ['action' => 'delete', 'id' => $user->id])?>
							<?php /*endif*/ ?>
						</td>
					</tr>
				<?php endforeach;?>
			</tbody>
		</table>
	</div>
	<?=$this->Paginator?>
</div>
