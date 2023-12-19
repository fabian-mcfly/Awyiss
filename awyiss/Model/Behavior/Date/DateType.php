<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior\Date;


enum DateType: string {
	case EVENT_START = 'event_start';
	case EVENT_END = 'event_end';
	case PUBLICATION_START = 'publication_start';
	case PUBLICATION_END = 'publication_end';
}
