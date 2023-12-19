<?php declare(strict_types=1);


namespace Awyiss\ORM\Rule;


use Cake\Datasource\EntityInterface;
use Cake\ORM\Association;
use Cake\ORM\Table;
use RuntimeException;


/**
 * Checks that the value provided in a field exists as the primary key of another
 * table.
 */
class _IsUnique extends \Cake\ORM\Rule\IsUnique {

}