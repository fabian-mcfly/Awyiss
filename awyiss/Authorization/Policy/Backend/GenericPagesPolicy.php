<?php declare(strict_types=1);


namespace Awyiss\Authorization\Policy\Backend;


use Awyiss\Authorization\Policy\AbstractGenericPolicy;


/**
 * Instances of this class are used for pages/page roles that have no own policy class.
 *
 * It provides four `SimplePermission` for the identifiers 'read', 'create', 'update' and 'delete' (CRUD).
 *
 * It needs to provide non-static methods so itcan be used for multiple pages/page roles at the same time.
 *
 * @see \Awyiss\Authorization\PermissionOption\SimplePermissionOption
 */
class GenericPagesPolicy extends AbstractGenericPolicy {
}
