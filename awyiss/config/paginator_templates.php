<?php declare(strict_types=1);

/** @noinspection HtmlUnknownTarget */
return [
	'nextActive' => '<li class="Sort-Next"><a rel="next" href="{{url}}" class="Arrow Arrow-Next">{{text}}</a></li>',
	'nextDisabled' => '<li class="Sort-Next Disabled"><span class="Arrow Arrow-Next">{{text}}</span></li>',
	'prevActive' => '<li class="Sort-Prev"><a rel="prev" href="{{url}}" class="Arrow Arrow-Prev">{{text}}</a></li>',
	'prevDisabled' => '<li class="Sort-Prev Disabled"><span class="Arrow Arrow-Prev">{{text}}</span></li>',
	'counterRange' => '{{start}} - {{end}} of {{count}}',
	'counterPages' => '{{page}} of {{pages}}',
	'first' => '<li class="Sort-First"><a href="{{url}}" class="Arrow Arrow-First">{{text}}</a></li>',
	'last' => '<li class="Sort-Last"><a href="{{url}}" class="Arrow Arrow-Last">{{text}}</a></li>',
	'number' => '<li class="Sort-Number"><a href="{{url}}" class="Number" title="{{page}} {{text}}">{{text}}</a></li>',
	'current' => '<li class="Sort-Number Sort-Current"><span class="Number">{{text}}</span></li>',
	'ellipsis' => '<li class="Sort-Number Sort-Ellipsis">&hellip;</li>',
	'sort' => '<a class="Sort Sort-{{identifier}}" href="{{url}}">{{text}}</a>',
	'sortAsc' => '<a class="Sort Sort-Direction Sort-Asc" href="{{url}}">{{text}}</a>',
	'sortDesc' => '<a class="Sort Sort-Direction Sort-Desc" href="{{url}}">{{text}}</a>',
	'sortAscLocked' => '<a class="Sort Sort-Direction Sort-Asc Sort-Locked" href="{{url}}">{{text}}</a>',
	'sortDescLocked' => '<a class="Sort Sort-Direction Sort-Desc Sort-Locked" href="{{url}}">{{text}}</a>',
];
