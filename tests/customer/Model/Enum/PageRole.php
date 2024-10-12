<?php declare(strict_types=1);


namespace Customer\Model\Enum;


use Awyiss\Model\Enum\PageRoleEnumInterface;
use Awyiss\Model\Trait\PageRoleEnumTrait;
use Cake\Database\Type\EnumLabelInterface;


/**
 * PageRole Enum
 */
enum PageRole: int implements EnumLabelInterface, PageRoleEnumInterface {
	use PageRoleEnumTrait;


	case Page = 1;
	case Newscategory = 2;
	case News = 3;
	case Product = 4;
}
