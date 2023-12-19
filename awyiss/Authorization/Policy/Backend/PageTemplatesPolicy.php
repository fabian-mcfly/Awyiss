<?php declare(strict_types=1);


namespace Awyiss\Authorization\Policy\Backend;


use Awyiss\Authorization\Policy\PolicyInterface;


class PageTemplatesPolicy implements PolicyInterface {
	use \Awyiss\Authorization\Policy\Trait\BasicCrudPermissionsTrait;
}