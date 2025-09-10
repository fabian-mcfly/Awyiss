<?php declare(strict_types=1);


namespace Customer\Model\Table;


use Awyiss\Model\Table\PagesTable;


/**
 * Newscategories Model
 *
 * @property \Awyiss\Model\Table\LanguagesTable&\Awyiss\ORM\Association\BelongsTo $Languages
 * @property \Awyiss\Model\Table\PageRolesTable&\Awyiss\ORM\Association\BelongsTo $PageRoles
 * @property \Awyiss\Model\Table\PageTemplatesTable&\Awyiss\ORM\Association\BelongsTo $PageTemplates
 * @property \Customer\Model\Table\NewscategoriesTable&\Awyiss\ORM\Association\HasMany $DuplicatingNewscategories
 * @property \Customer\Model\Table\NewscategoriesTable&\Awyiss\ORM\Association\BelongsTo $DuplicateOfNewscategories
 * @property \Customer\Model\Table\NewscategoriesTable&\Awyiss\ORM\Association\BelongsTo $ParentNewscategories
 * @property \Customer\Model\Table\NewscategoriesTable&\Awyiss\ORM\Association\HasMany $ChildNewscategories
 * @property \Awyiss\Model\Table\ContentsTable&\Awyiss\ORM\Association\HasMany $Contents
 * @method \Customer\Model\Entity\Newscategory newDefaultEntity(array $additionalData = [], array $options = [])
 * @method \Cake\Collection\CollectionInterface|null getNestedChildren(\Cake\Datasource\EntityInterface $entity, array $options = [], int $currentLevel = 0)
 * @method \Cake\Collection\CollectionInterface|null getChildren(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Customer\Model\Entity\Newscategory getParent(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Cake\Collection\CollectionInterface|null getParents(\Cake\Datasource\EntityInterface $entity, array $options = [], int $currentLevel = 0)
 * @method \Cake\Collection\CollectionInterface getPossibleParents(\Awyiss\Model\Entity $entity, \Cake\Collection\CollectionInterface $threadedEntities)
 */
class NewscategoriesTable extends PagesTable {
}
