<?php declare(strict_types=1);


namespace Customer\Model\Table;


use Awyiss\Model\Table\PagesTable;


/**
 * News Model
 *
 * @property \Awyiss\Model\Table\LanguagesTable&\Awyiss\ORM\Association\BelongsTo $Languages
 * @property \Awyiss\Model\Table\PageRolesTable&\Awyiss\ORM\Association\BelongsTo $PageRoles
 * @property \Awyiss\Model\Table\PageTemplatesTable&\Awyiss\ORM\Association\BelongsTo $PageTemplates
 * @property \FoobarCustomer\Model\Table\NewsTable&\Awyiss\ORM\Association\HasMany $DuplicatingNews
 * @property \FoobarCustomer\Model\Table\NewsTable&\Awyiss\ORM\Association\BelongsTo $DuplicateOfNews
 * @property \FoobarCustomer\Model\Table\NewsTable&\Awyiss\ORM\Association\BelongsTo $ParentNews
 * @property \FoobarCustomer\Model\Table\NewsTable&\Awyiss\ORM\Association\HasMany $ChildNews
 * @property \Awyiss\Model\Table\ContentsTable&\Awyiss\ORM\Association\HasMany $Contents
 * @method \FoobarCustomer\Model\Entity\News newDefaultEntity(array $additionalData = [], array $options = [])
 * @method \Cake\Collection\CollectionInterface|null getNestedChildren(\Cake\Datasource\EntityInterface $entity, array $options = [], int $currentLevel = 0)
 * @method \Cake\Collection\CollectionInterface|null getChildren(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \FoobarCustomer\Model\Entity\News getParent(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Cake\Collection\CollectionInterface|null getParents(\Cake\Datasource\EntityInterface $entity, array $options = [], int $currentLevel = 0)
 */
class NewsTable extends PagesTable {
}
