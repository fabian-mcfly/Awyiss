<?php

/**
 * @var \Awyiss\View\BackendView $this
 */

?>
<div class="paginator">
	<ul class="pagination">
		<?=$this->Paginator->first('<< ' . __('first'))?>
		<?=$this->Paginator->prev('< ' . __('previous'))?>
		<?=$this->Paginator->numbers()?>
		<?=$this->Paginator->next(__('next') . ' >')?>
		<?=$this->Paginator->last(__('last') . ' >>')?>
	</ul>
</div>