<?php declare(strict_types=1);


namespace Awyiss\Annotation;


use Attribute;


/**
 * Class NoDirectAccess
 * This annotation should be used to mark methods that should not be called directly.
 */
#[Attribute(Attribute::TARGET_METHOD)]
class NoDirectAccess {
}
