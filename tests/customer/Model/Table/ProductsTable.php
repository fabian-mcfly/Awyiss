<?php declare(strict_types=1);


namespace Customer\Model\Table;


use Awyiss\Model\Table\PagesTable;


/**
 * Products Model
 *
 * @property \Awyiss\Model\Table\LanguagesTable&\Awyiss\ORM\Association\BelongsTo $Languages
 * @property \Awyiss\Model\Table\PageRolesTable&\Awyiss\ORM\Association\BelongsTo $PageRoles
 * @property \Awyiss\Model\Table\PageTemplatesTable&\Awyiss\ORM\Association\BelongsTo $PageTemplates
 * @property \FoobarCustomer\Model\Table\ProductsTable&\Awyiss\ORM\Association\HasMany $DuplicatingProducts
 * @property \FoobarCustomer\Model\Table\ProductsTable&\Awyiss\ORM\Association\BelongsTo $DuplicateOfProducts
 * @property \FoobarCustomer\Model\Table\ProductsTable&\Awyiss\ORM\Association\BelongsTo $ParentProducts
 * @property \FoobarCustomer\Model\Table\ProductsTable&\Awyiss\ORM\Association\HasMany $ChildProducts
 * @property \Awyiss\Model\Table\ContentsTable&\Awyiss\ORM\Association\HasMany $Contents
 * @method \FoobarCustomer\Model\Entity\Product newDefaultEntity(array $additionalData = [], array $options = [])
 * @method \Cake\Collection\CollectionInterface|null getNestedChildren(\Cake\Datasource\EntityInterface $entity, array $options = [], int $currentLevel = 0)
 * @method \Cake\Collection\CollectionInterface|null getChildren(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \FoobarCustomer\Model\Entity\Product getParent(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Cake\Collection\CollectionInterface|null getParents(\Cake\Datasource\EntityInterface $entity, array $options = [], int $currentLevel = 0)
 */
class ProductsTable extends PagesTable {
}
