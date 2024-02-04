<?php declare(strict_types=1);


namespace Awyiss\Model\Enum;


use Awyiss\Database\Type\PageRoleEnumInterface;
use Awyiss\Model\Trait\PageRoleEnumTrait;
use Cake\Database\Type\EnumLabelInterface;


/**
 * PageRole Enum
 */
enum PageRole: int implements EnumLabelInterface, PageRoleEnumInterface {
	use PageRoleEnumTrait;


	case Page = 1;
}
