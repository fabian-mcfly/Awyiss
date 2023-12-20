<?php declare(strict_types=1);

/** @noinspection HtmlUnknownTarget */
return [
	'nextActive' => '<li class="Sort-Next"><a rel="next" href="{{url}}">{{text}}</a></li>',
	'nextDisabled' => '<li class="Sort-Next Disabled"><span>{{text}}</span></li>',
	'prevActive' => '<li class="Sort-Prev"><a rel="prev" href="{{url}}">{{text}}</a></li>',
	'prevDisabled' => '<li class="Sort-Prev Disabled"><span>{{text}}</span></li>',
	'counterRange' => '{{start}} - {{end}} of {{count}}',
	'counterPages' => '{{page}} of {{pages}}',
	'first' => '<li class="Sort-First"><a href="{{url}}">{{text}}</a></li>',
	'last' => '<li class="Sort-Last"><a href="{{url}}">{{text}}</a></li>',
	'number' => '<li class="Sort-Number"><a href="{{url}}">{{text}}</a></li>',
	'current' => '<li class="Sort-Number Sort-Current"><a href="">{{text}}</a></li>',
	'ellipsis' => '<li class="Sort-Number Sort-Ellipsis">&hellip;</li>',
	'sort' => '<a class="Sort Sort-Title" href="{{url}}">{{text}}</a>',
	'sortAsc' => '<a class="Sort Sort-Direction Sort-Asc" href="{{url}}">{{text}}</a>',
	'sortDesc' => '<a class="Sort Sort-Direction Sort-Desc" href="{{url}}">{{text}}</a>',
	'sortAscLocked' => '<a class="Sort Sort-Direction Sort-Asc Sort-Locked" href="{{url}}">{{text}}</a>',
	'sortDescLocked' => '<a class="Sort Sort-Direction Sort-Desc Sort-Locked" href="{{url}}">{{text}}</a>',
];
