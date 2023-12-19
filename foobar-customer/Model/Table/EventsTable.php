<?php declare(strict_types=1);


namespace FoobarCustomer\Model\Table;


use Awyiss\Model\Entity\Page;
use Awyiss\Model\Table\ContentsTable;
use Awyiss\Model\Table\LanguagesTable;
use Awyiss\Model\Table\PageRolesTable;
use Awyiss\Model\Table\PagesTable;
use Awyiss\Model\Table\PageTemplatesTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\HasMany;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\EntityInterface;
use Cake\Utility\Inflector;


/**
* Events Model
*
* @property EventsTable&BelongsTo $ParentEvents
* @property EventsTable&HasMany $ChildEvents
* @property EventsTable&HasMany $DuplicateEvents
* @property EventsTable&BelongsTo $DuplicateOfEvents
* @property ContentsTable&HasMany $Contents
* @property LanguagesTable&BelongsTo $Languages
* @property PageRolesTable&BelongsTo $PageRoles
* @property PageTemplatesTable&BelongsTo $PageTemplates
*
* @method Page newDefaultEntity(array $aa_additionalData = [])
* @method CollectionInterface|NULL getNestedChildren(EntityInterface $ao_entity, array $aa_options = [], int $ai_currentLevel = 0)
* @method CollectionInterface|NULL getChildren(EntityInterface $ao_entity)
* @method Page getParent(EntityInterface $ao_entity)
* @method CollectionInterface|NULL getParents(EntityInterface $ao_entity, array $aa_options = [], int $ai_currentLevel = 0)
*/
class EventsTable extends PagesTable {
	/**
	* @inheritDoc
	*/
	protected string $pageRole = 'event';

}
