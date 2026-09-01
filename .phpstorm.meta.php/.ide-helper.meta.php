<?php
// @link https://confluence.jetbrains.com/display/PhpStorm/PhpStorm+Advanced+Metadata
namespace PHPSTORM_META {

	expectedArguments(
		\Cake\Cache\Cache::add(),
		2,
		argumentsSet('cacheEngines'),
	);

	expectedArguments(
		\Cake\Cache\Cache::clear(),
		0,
		argumentsSet('cacheEngines'),
	);

	expectedArguments(
		\Cake\Cache\Cache::clearGroup(),
		1,
		argumentsSet('cacheEngines'),
	);

	expectedArguments(
		\Cake\Cache\Cache::decrement(),
		2,
		argumentsSet('cacheEngines'),
	);

	expectedArguments(
		\Cake\Cache\Cache::delete(),
		1,
		argumentsSet('cacheEngines'),
	);

	expectedArguments(
		\Cake\Cache\Cache::deleteMany(),
		1,
		argumentsSet('cacheEngines'),
	);

	expectedArguments(
		\Cake\Cache\Cache::increment(),
		2,
		argumentsSet('cacheEngines'),
	);

	expectedArguments(
		\Cake\Cache\Cache::read(),
		1,
		argumentsSet('cacheEngines'),
	);

	expectedArguments(
		\Cake\Cache\Cache::readMany(),
		1,
		argumentsSet('cacheEngines'),
	);

	expectedArguments(
		\Cake\Cache\Cache::remember(),
		2,
		argumentsSet('cacheEngines'),
	);

	expectedArguments(
		\Cake\Cache\Cache::write(),
		2,
		argumentsSet('cacheEngines'),
	);

	exitPoint(\Cake\Console\ConsoleIo::abort());

	override(
		\Cake\Console\ConsoleIo::helper(0),
		map([
			'Banner' => \Cake\Command\Helper\BannerHelper::class,
			'Progress' => \Cake\Command\Helper\ProgressHelper::class,
			'Table' => \Cake\Command\Helper\TableHelper::class,
			'Tree' => \Cake\Command\Helper\TreeHelper::class,
		]),
	);

	expectedArguments(
		\Cake\Controller\ComponentRegistry::unload(),
		0,
		'Authentication',
		'Authorization',
		'Categories',
		'CheckHttpCache',
		'EventTrigger',
		'Flash',
		'FormProtection',
		'Lock',
		'Paginate',
		'Search',
		'SystemOrder',
	);

	override(
		\Cake\Controller\Controller::loadComponent(0),
		map([
			'Authentication.Authentication' => \Authentication\Controller\Component\AuthenticationComponent::class,
			'Authorization' => \Awyiss\Controller\Component\AuthorizationComponent::class,
			'Categories' => \Awyiss\Controller\Component\CategoriesComponent::class,
			'CheckHttpCache' => \Cake\Controller\Component\CheckHttpCacheComponent::class,
			'EventTrigger' => \Awyiss\Controller\Component\EventTriggerComponent::class,
			'Flash' => \Cake\Controller\Component\FlashComponent::class,
			'FormProtection' => \Cake\Controller\Component\FormProtectionComponent::class,
			'Lock' => \Awyiss\Controller\Component\LockComponent::class,
			'Paginate' => \Awyiss\Controller\Component\PaginateComponent::class,
			'Search' => \Awyiss\Controller\Component\SearchComponent::class,
			'SystemOrder' => \Awyiss\Controller\Component\SystemOrderComponent::class,
		]),
	);

	expectedArguments(
		\Cake\Core\Configure::check(),
		0,
		argumentsSet('configureKeys'),
	);

	expectedArguments(
		\Cake\Core\Configure::consume(),
		0,
		argumentsSet('configureKeys'),
	);

	expectedArguments(
		\Cake\Core\Configure::consumeOrFail(),
		0,
		argumentsSet('configureKeys'),
	);

	expectedArguments(
		\Cake\Core\Configure::delete(),
		0,
		argumentsSet('configureKeys'),
	);

	expectedArguments(
		\Cake\Core\Configure::read(),
		0,
		argumentsSet('configureKeys'),
	);

	expectedArguments(
		\Cake\Core\Configure::readOrFail(),
		0,
		argumentsSet('configureKeys'),
	);

	expectedArguments(
		\Cake\Core\Configure::write(),
		0,
		argumentsSet('configureKeys'),
	);

	override(
		\Cake\Core\PluginApplicationInterface::addPlugin(0),
		map([
			'Authentication' => \Cake\Http\BaseApplication::class,
			'Bake' => \Cake\Http\BaseApplication::class,
			'Cake/TwigView' => \Cake\Http\BaseApplication::class,
			'CsvView' => \Cake\Http\BaseApplication::class,
			'DebugKit' => \Cake\Http\BaseApplication::class,
			'IdeHelper' => \Cake\Http\BaseApplication::class,
			'Migrations' => \Cake\Http\BaseApplication::class,
			'Queue' => \Cake\Http\BaseApplication::class,
		]),
	);

	override(
		\Cake\Database\TypeFactory::build(0),
		map([
			'biginteger' => \Awyiss\Database\Type\IntegerType::class,
			'binary' => \Cake\Database\Type\BinaryType::class,
			'binaryuuid' => \Cake\Database\Type\BinaryUuidType::class,
			'boolean' => \Cake\Database\Type\BoolType::class,
			'char' => \Awyiss\Database\Type\StringType::class,
			'cidr' => \Cake\Database\Type\StringType::class,
			'citext' => \Cake\Database\Type\StringType::class,
			'date' => \Cake\Database\Type\DateType::class,
			'datetime' => \Cake\Database\Type\DateTimeType::class,
			'datetimefractional' => \Cake\Database\Type\DateTimeFractionalType::class,
			'decimal' => \Cake\Database\Type\DecimalType::class,
			'float' => \Cake\Database\Type\FloatType::class,
			'geometry' => \Cake\Database\Type\StringType::class,
			'inet' => \Cake\Database\Type\StringType::class,
			'integer' => \Awyiss\Database\Type\IntegerType::class,
			'json' => \Cake\Database\Type\JsonType::class,
			'linestring' => \Cake\Database\Type\StringType::class,
			'macaddr' => \Cake\Database\Type\StringType::class,
			'nativeuuid' => \Cake\Database\Type\UuidType::class,
			'point' => \Cake\Database\Type\StringType::class,
			'polygon' => \Cake\Database\Type\StringType::class,
			'smallinteger' => \Awyiss\Database\Type\IntegerType::class,
			'string' => \Awyiss\Database\Type\StringType::class,
			'text' => \Awyiss\Database\Type\StringType::class,
			'time' => \Cake\Database\Type\TimeType::class,
			'timestamp' => \Cake\Database\Type\DateTimeType::class,
			'timestampfractional' => \Cake\Database\Type\DateTimeFractionalType::class,
			'timestamptimezone' => \Cake\Database\Type\DateTimeTimezoneType::class,
			'tinyinteger' => \Awyiss\Database\Type\IntegerType::class,
			'uuid' => \Cake\Database\Type\UuidType::class,
			'year' => \Cake\Database\Type\IntegerType::class,
		]),
	);

	expectedArguments(
		\Cake\Database\TypeFactory::map(),
		0,
		'biginteger',
		'binary',
		'binaryuuid',
		'boolean',
		'char',
		'cidr',
		'citext',
		'date',
		'datetime',
		'datetimefractional',
		'decimal',
		'float',
		'geometry',
		'inet',
		'integer',
		'json',
		'linestring',
		'macaddr',
		'nativeuuid',
		'point',
		'polygon',
		'smallinteger',
		'string',
		'text',
		'time',
		'timestamp',
		'timestampfractional',
		'timestamptimezone',
		'tinyinteger',
		'uuid',
		'year',
	);

	expectedArguments(
		\Cake\Datasource\ConnectionManager::get(),
		0,
		'default',
		'test',
	);

	override(
		\Cake\Datasource\ModelAwareTrait::fetchModel(0),
		map([
			'Attributes' => \Awyiss\Model\Table\AttributesTable::class,
			'Audit' => \Awyiss\Model\Table\AuditTable::class,
			'BackendMenuEntries' => \Awyiss\Model\Table\BackendMenuEntriesTable::class,
			'Configuration' => \Awyiss\Model\Table\ConfigurationTable::class,
			'ContentAreas' => \Awyiss\Model\Table\ContentAreasTable::class,
			'ContentTemplateContentAreas' => \Awyiss\Model\Table\ContentTemplateContentAreasTable::class,
			'ContentTemplateElements' => \Awyiss\Model\Table\ContentTemplateElementsTable::class,
			'ContentTemplates' => \Awyiss\Model\Table\ContentTemplatesTable::class,
			'Contents' => \Awyiss\Model\Table\ContentsTable::class,
			'CustomerGroupAccessSettings' => \Awyiss\Model\Table\CustomerGroupAccessSettingsTable::class,
			'CustomerGroupAssignments' => \Awyiss\Model\Table\CustomerGroupAssignmentsTable::class,
			'CustomerGroups' => \Awyiss\Model\Table\CustomerGroupsTable::class,
			'CustomerGroupsCustomers' => \Awyiss\Model\Table\CustomerGroupsCustomersTable::class,
			'Customers' => \Awyiss\Model\Table\CustomersTable::class,
			'DashboardElements' => \Awyiss\Model\Table\DashboardElementsTable::class,
			'Datatables' => \Awyiss\Model\Table\DatatablesTable::class,
			'Designs' => \Awyiss\Model\Table\DesignsTable::class,
			'EmailTemplates' => \Awyiss\Model\Table\EmailTemplatesTable::class,
			'FormConditionalRecipients' => \Awyiss\Model\Table\FormConditionalRecipientsTable::class,
			'FormElements' => \Awyiss\Model\Table\FormElementsTable::class,
			'FormEntries' => \Awyiss\Model\Table\FormEntriesTable::class,
			'Forms' => \Awyiss\Model\Table\FormsTable::class,
			'GlobalContentTemplateElements' => \Awyiss\Model\Table\GlobalContentTemplateElementsTable::class,
			'GlobalContentTemplates' => \Awyiss\Model\Table\GlobalContentTemplatesTable::class,
			'GlobalContents' => \Awyiss\Model\Table\GlobalContentsTable::class,
			'I18n' => \Awyiss\Model\Table\I18nTable::class,
			'Languages' => \Awyiss\Model\Table\LanguagesTable::class,
			'Locks' => \Awyiss\Model\Table\LocksTable::class,
			'Media' => \Awyiss\Model\Table\MediaTable::class,
			'MediaAssignments' => \Awyiss\Model\Table\MediaAssignmentsTable::class,
			'MediaElementAssignments' => \Awyiss\Model\Table\MediaElementAssignmentsTable::class,
			'MediaElementSelectors' => \Awyiss\Model\Table\MediaElementSelectorsTable::class,
			'MediaElements' => \Awyiss\Model\Table\MediaElementsTable::class,
			'MediaFolders' => \Awyiss\Model\Table\MediaFoldersTable::class,
			'MediaResizedImages' => \Awyiss\Model\Table\MediaResizedImagesTable::class,
			'MediaSelectors' => \Awyiss\Model\Table\MediaSelectorsTable::class,
			'MenuEntries' => \Awyiss\Model\Table\MenuEntriesTable::class,
			'Menus' => \Awyiss\Model\Table\MenusTable::class,
			'PageRoles' => \Awyiss\Model\Table\PageRolesTable::class,
			'PageTemplateContentAreas' => \Awyiss\Model\Table\PageTemplateContentAreasTable::class,
			'PageTemplates' => \Awyiss\Model\Table\PageTemplatesTable::class,
			'Pages' => \Awyiss\Model\Table\PagesTable::class,
			'PublicationData' => \Awyiss\Model\Table\PublicationDataTable::class,
			'Queue.QueueProcesses' => \Queue\Model\Table\QueueProcessesTable::class,
			'Queue.QueuedJobs' => \Queue\Model\Table\QueuedJobsTable::class,
			'SurveyAnswers' => \Awyiss\Model\Table\SurveyAnswersTable::class,
			'SurveyEntries' => \Awyiss\Model\Table\SurveyEntriesTable::class,
			'SurveyQuestions' => \Awyiss\Model\Table\SurveyQuestionsTable::class,
			'SurveySurveyAnswers' => \Awyiss\Model\Table\SurveySurveyAnswersTable::class,
			'SurveySurveyQuestions' => \Awyiss\Model\Table\SurveySurveyQuestionsTable::class,
			'Surveys' => \Awyiss\Model\Table\SurveysTable::class,
			'ThirdPartyConsents' => \Awyiss\Model\Table\ThirdPartyConsentsTable::class,
			'UrlHistory' => \Awyiss\Model\Table\UrlHistoryTable::class,
			'UrlsNotFound' => \Awyiss\Model\Table\UrlsNotFoundTable::class,
			'UserConfiguration' => \Awyiss\Model\Table\UserConfigurationTable::class,
			'UsergroupPermissions' => \Awyiss\Model\Table\UsergroupPermissionsTable::class,
			'Usergroups' => \Awyiss\Model\Table\UsergroupsTable::class,
			'UsergroupsUsers' => \Awyiss\Model\Table\UsergroupsUsersTable::class,
			'Users' => \Awyiss\Model\Table\UsersTable::class,
		]),
	);

	override(
		\Cake\Datasource\QueryInterface::find(0),
		map([
			'accessible' => \Cake\ORM\Query\SelectQuery::class,
			'active' => \Cake\ORM\Query\SelectQuery::class,
			'all' => \Cake\ORM\Query\SelectQuery::class,
			'deleted' => \Cake\ORM\Query\SelectQuery::class,
			'forCurrentLanguage' => \Cake\ORM\Query\SelectQuery::class,
			'latestForPages' => \Cake\ORM\Query\SelectQuery::class,
			'list' => \Cake\ORM\Query\SelectQuery::class,
			'mediaAssignments' => \Cake\ORM\Query\SelectQuery::class,
			'mediaElementAssignments' => \Cake\ORM\Query\SelectQuery::class,
			'published' => \Cake\ORM\Query\SelectQuery::class,
			'publishedEndingAfter' => \Cake\ORM\Query\SelectQuery::class,
			'publishedEndingBefore' => \Cake\ORM\Query\SelectQuery::class,
			'publishedStartingAfter' => \Cake\ORM\Query\SelectQuery::class,
			'publishedStartingBefore' => \Cake\ORM\Query\SelectQuery::class,
			'queued' => \Cake\ORM\Query\SelectQuery::class,
			'recent' => \Cake\ORM\Query\SelectQuery::class,
			'threaded' => \Cake\ORM\Query\SelectQuery::class,
			'translations' => \Cake\ORM\Query\SelectQuery::class,
			'withAuditUsers' => \Cake\ORM\Query\SelectQuery::class,
			'withDeleted' => \Cake\ORM\Query\SelectQuery::class,
			'withMatchingAttributes' => \Cake\ORM\Query\SelectQuery::class,
			'withUsages' => \Cake\ORM\Query\SelectQuery::class,
		]),
	);

	override(
		\Cake\Http\ServerRequest::getAttribute(0),
		map([
			'authentication' => \Authentication\AuthenticationService::class,
			'authenticationResult' => \Authentication\Authenticator\Result::class,
			'base' => 'string',
			'cspScriptNonce' => 'string',
			'cspStyleNonce' => 'string',
			'csrfToken' => 'string',
			'formTokenData' => 'array',
			'here' => 'string',
			'identity' => \Authentication\IdentityInterface::class,
			'paging' => 'array',
			'params' => 'array',
			'route' => \Cake\Routing\Route\Route::class,
			'session' => \Cake\Http\Session::class,
			'webroot' => 'string',
		]),
	);

	expectedArguments(
		\Cake\Http\ServerRequest::getParam(),
		0,
		'_ext',
		'_matchedRoute',
		'action',
		'controller',
		'pass',
		'plugin',
		'prefix',
	);

	override(
		\Cake\ORM\Association::find(0),
		map([
			'accessible' => \Cake\ORM\Query\SelectQuery::class,
			'active' => \Cake\ORM\Query\SelectQuery::class,
			'all' => \Cake\ORM\Query\SelectQuery::class,
			'deleted' => \Cake\ORM\Query\SelectQuery::class,
			'forCurrentLanguage' => \Cake\ORM\Query\SelectQuery::class,
			'latestForPages' => \Cake\ORM\Query\SelectQuery::class,
			'list' => \Cake\ORM\Query\SelectQuery::class,
			'mediaAssignments' => \Cake\ORM\Query\SelectQuery::class,
			'mediaElementAssignments' => \Cake\ORM\Query\SelectQuery::class,
			'published' => \Cake\ORM\Query\SelectQuery::class,
			'publishedEndingAfter' => \Cake\ORM\Query\SelectQuery::class,
			'publishedEndingBefore' => \Cake\ORM\Query\SelectQuery::class,
			'publishedStartingAfter' => \Cake\ORM\Query\SelectQuery::class,
			'publishedStartingBefore' => \Cake\ORM\Query\SelectQuery::class,
			'queued' => \Cake\ORM\Query\SelectQuery::class,
			'recent' => \Cake\ORM\Query\SelectQuery::class,
			'threaded' => \Cake\ORM\Query\SelectQuery::class,
			'translations' => \Cake\ORM\Query\SelectQuery::class,
			'withAuditUsers' => \Cake\ORM\Query\SelectQuery::class,
			'withDeleted' => \Cake\ORM\Query\SelectQuery::class,
			'withMatchingAttributes' => \Cake\ORM\Query\SelectQuery::class,
			'withUsages' => \Cake\ORM\Query\SelectQuery::class,
		]),
	);

	override(
		\Cake\ORM\Locator\LocatorAwareTrait::fetchTable(0),
		map([
			'Attributes' => \Awyiss\Model\Table\AttributesTable::class,
			'Audit' => \Awyiss\Model\Table\AuditTable::class,
			'BackendMenuEntries' => \Awyiss\Model\Table\BackendMenuEntriesTable::class,
			'Configuration' => \Awyiss\Model\Table\ConfigurationTable::class,
			'ContentAreas' => \Awyiss\Model\Table\ContentAreasTable::class,
			'ContentTemplateContentAreas' => \Awyiss\Model\Table\ContentTemplateContentAreasTable::class,
			'ContentTemplateElements' => \Awyiss\Model\Table\ContentTemplateElementsTable::class,
			'ContentTemplates' => \Awyiss\Model\Table\ContentTemplatesTable::class,
			'Contents' => \Awyiss\Model\Table\ContentsTable::class,
			'CustomerGroupAccessSettings' => \Awyiss\Model\Table\CustomerGroupAccessSettingsTable::class,
			'CustomerGroupAssignments' => \Awyiss\Model\Table\CustomerGroupAssignmentsTable::class,
			'CustomerGroups' => \Awyiss\Model\Table\CustomerGroupsTable::class,
			'CustomerGroupsCustomers' => \Awyiss\Model\Table\CustomerGroupsCustomersTable::class,
			'Customers' => \Awyiss\Model\Table\CustomersTable::class,
			'DashboardElements' => \Awyiss\Model\Table\DashboardElementsTable::class,
			'Datatables' => \Awyiss\Model\Table\DatatablesTable::class,
			'Designs' => \Awyiss\Model\Table\DesignsTable::class,
			'EmailTemplates' => \Awyiss\Model\Table\EmailTemplatesTable::class,
			'FormConditionalRecipients' => \Awyiss\Model\Table\FormConditionalRecipientsTable::class,
			'FormElements' => \Awyiss\Model\Table\FormElementsTable::class,
			'FormEntries' => \Awyiss\Model\Table\FormEntriesTable::class,
			'Forms' => \Awyiss\Model\Table\FormsTable::class,
			'GlobalContentTemplateElements' => \Awyiss\Model\Table\GlobalContentTemplateElementsTable::class,
			'GlobalContentTemplates' => \Awyiss\Model\Table\GlobalContentTemplatesTable::class,
			'GlobalContents' => \Awyiss\Model\Table\GlobalContentsTable::class,
			'I18n' => \Awyiss\Model\Table\I18nTable::class,
			'Languages' => \Awyiss\Model\Table\LanguagesTable::class,
			'Locks' => \Awyiss\Model\Table\LocksTable::class,
			'Media' => \Awyiss\Model\Table\MediaTable::class,
			'MediaAssignments' => \Awyiss\Model\Table\MediaAssignmentsTable::class,
			'MediaElementAssignments' => \Awyiss\Model\Table\MediaElementAssignmentsTable::class,
			'MediaElementSelectors' => \Awyiss\Model\Table\MediaElementSelectorsTable::class,
			'MediaElements' => \Awyiss\Model\Table\MediaElementsTable::class,
			'MediaFolders' => \Awyiss\Model\Table\MediaFoldersTable::class,
			'MediaResizedImages' => \Awyiss\Model\Table\MediaResizedImagesTable::class,
			'MediaSelectors' => \Awyiss\Model\Table\MediaSelectorsTable::class,
			'MenuEntries' => \Awyiss\Model\Table\MenuEntriesTable::class,
			'Menus' => \Awyiss\Model\Table\MenusTable::class,
			'PageRoles' => \Awyiss\Model\Table\PageRolesTable::class,
			'PageTemplateContentAreas' => \Awyiss\Model\Table\PageTemplateContentAreasTable::class,
			'PageTemplates' => \Awyiss\Model\Table\PageTemplatesTable::class,
			'Pages' => \Awyiss\Model\Table\PagesTable::class,
			'PublicationData' => \Awyiss\Model\Table\PublicationDataTable::class,
			'Queue.QueueProcesses' => \Queue\Model\Table\QueueProcessesTable::class,
			'Queue.QueuedJobs' => \Queue\Model\Table\QueuedJobsTable::class,
			'SurveyAnswers' => \Awyiss\Model\Table\SurveyAnswersTable::class,
			'SurveyEntries' => \Awyiss\Model\Table\SurveyEntriesTable::class,
			'SurveyQuestions' => \Awyiss\Model\Table\SurveyQuestionsTable::class,
			'SurveySurveyAnswers' => \Awyiss\Model\Table\SurveySurveyAnswersTable::class,
			'SurveySurveyQuestions' => \Awyiss\Model\Table\SurveySurveyQuestionsTable::class,
			'Surveys' => \Awyiss\Model\Table\SurveysTable::class,
			'ThirdPartyConsents' => \Awyiss\Model\Table\ThirdPartyConsentsTable::class,
			'UrlHistory' => \Awyiss\Model\Table\UrlHistoryTable::class,
			'UrlsNotFound' => \Awyiss\Model\Table\UrlsNotFoundTable::class,
			'UserConfiguration' => \Awyiss\Model\Table\UserConfigurationTable::class,
			'UsergroupPermissions' => \Awyiss\Model\Table\UsergroupPermissionsTable::class,
			'Usergroups' => \Awyiss\Model\Table\UsergroupsTable::class,
			'UsergroupsUsers' => \Awyiss\Model\Table\UsergroupsUsersTable::class,
			'Users' => \Awyiss\Model\Table\UsersTable::class,
		]),
	);

	override(
		\Cake\ORM\Locator\LocatorInterface::get(0),
		map([
			'Attributes' => \Awyiss\Model\Table\AttributesTable::class,
			'Audit' => \Awyiss\Model\Table\AuditTable::class,
			'BackendMenuEntries' => \Awyiss\Model\Table\BackendMenuEntriesTable::class,
			'Configuration' => \Awyiss\Model\Table\ConfigurationTable::class,
			'ContentAreas' => \Awyiss\Model\Table\ContentAreasTable::class,
			'ContentTemplateContentAreas' => \Awyiss\Model\Table\ContentTemplateContentAreasTable::class,
			'ContentTemplateElements' => \Awyiss\Model\Table\ContentTemplateElementsTable::class,
			'ContentTemplates' => \Awyiss\Model\Table\ContentTemplatesTable::class,
			'Contents' => \Awyiss\Model\Table\ContentsTable::class,
			'CustomerGroupAccessSettings' => \Awyiss\Model\Table\CustomerGroupAccessSettingsTable::class,
			'CustomerGroupAssignments' => \Awyiss\Model\Table\CustomerGroupAssignmentsTable::class,
			'CustomerGroups' => \Awyiss\Model\Table\CustomerGroupsTable::class,
			'CustomerGroupsCustomers' => \Awyiss\Model\Table\CustomerGroupsCustomersTable::class,
			'Customers' => \Awyiss\Model\Table\CustomersTable::class,
			'DashboardElements' => \Awyiss\Model\Table\DashboardElementsTable::class,
			'Datatables' => \Awyiss\Model\Table\DatatablesTable::class,
			'Designs' => \Awyiss\Model\Table\DesignsTable::class,
			'EmailTemplates' => \Awyiss\Model\Table\EmailTemplatesTable::class,
			'FormConditionalRecipients' => \Awyiss\Model\Table\FormConditionalRecipientsTable::class,
			'FormElements' => \Awyiss\Model\Table\FormElementsTable::class,
			'FormEntries' => \Awyiss\Model\Table\FormEntriesTable::class,
			'Forms' => \Awyiss\Model\Table\FormsTable::class,
			'GlobalContentTemplateElements' => \Awyiss\Model\Table\GlobalContentTemplateElementsTable::class,
			'GlobalContentTemplates' => \Awyiss\Model\Table\GlobalContentTemplatesTable::class,
			'GlobalContents' => \Awyiss\Model\Table\GlobalContentsTable::class,
			'I18n' => \Awyiss\Model\Table\I18nTable::class,
			'Languages' => \Awyiss\Model\Table\LanguagesTable::class,
			'Locks' => \Awyiss\Model\Table\LocksTable::class,
			'Media' => \Awyiss\Model\Table\MediaTable::class,
			'MediaAssignments' => \Awyiss\Model\Table\MediaAssignmentsTable::class,
			'MediaElementAssignments' => \Awyiss\Model\Table\MediaElementAssignmentsTable::class,
			'MediaElementSelectors' => \Awyiss\Model\Table\MediaElementSelectorsTable::class,
			'MediaElements' => \Awyiss\Model\Table\MediaElementsTable::class,
			'MediaFolders' => \Awyiss\Model\Table\MediaFoldersTable::class,
			'MediaResizedImages' => \Awyiss\Model\Table\MediaResizedImagesTable::class,
			'MediaSelectors' => \Awyiss\Model\Table\MediaSelectorsTable::class,
			'MenuEntries' => \Awyiss\Model\Table\MenuEntriesTable::class,
			'Menus' => \Awyiss\Model\Table\MenusTable::class,
			'PageRoles' => \Awyiss\Model\Table\PageRolesTable::class,
			'PageTemplateContentAreas' => \Awyiss\Model\Table\PageTemplateContentAreasTable::class,
			'PageTemplates' => \Awyiss\Model\Table\PageTemplatesTable::class,
			'Pages' => \Awyiss\Model\Table\PagesTable::class,
			'PublicationData' => \Awyiss\Model\Table\PublicationDataTable::class,
			'Queue.QueueProcesses' => \Queue\Model\Table\QueueProcessesTable::class,
			'Queue.QueuedJobs' => \Queue\Model\Table\QueuedJobsTable::class,
			'SurveyAnswers' => \Awyiss\Model\Table\SurveyAnswersTable::class,
			'SurveyEntries' => \Awyiss\Model\Table\SurveyEntriesTable::class,
			'SurveyQuestions' => \Awyiss\Model\Table\SurveyQuestionsTable::class,
			'SurveySurveyAnswers' => \Awyiss\Model\Table\SurveySurveyAnswersTable::class,
			'SurveySurveyQuestions' => \Awyiss\Model\Table\SurveySurveyQuestionsTable::class,
			'Surveys' => \Awyiss\Model\Table\SurveysTable::class,
			'ThirdPartyConsents' => \Awyiss\Model\Table\ThirdPartyConsentsTable::class,
			'UrlHistory' => \Awyiss\Model\Table\UrlHistoryTable::class,
			'UrlsNotFound' => \Awyiss\Model\Table\UrlsNotFoundTable::class,
			'UserConfiguration' => \Awyiss\Model\Table\UserConfigurationTable::class,
			'UsergroupPermissions' => \Awyiss\Model\Table\UsergroupPermissionsTable::class,
			'Usergroups' => \Awyiss\Model\Table\UsergroupsTable::class,
			'UsergroupsUsers' => \Awyiss\Model\Table\UsergroupsUsersTable::class,
			'Users' => \Awyiss\Model\Table\UsersTable::class,
		]),
	);

	expectedArguments(
		\Cake\ORM\Table::addBehavior(),
		0,
		'Attributes',
		'Audit',
		'AutoPrefix',
		'Categories',
		'CounterCache',
		'CustomerGroupAccessSetting',
		'DefaultValues',
		'EventTrigger',
		'MediaAssignment',
		'MediaElementAssignment',
		'Nest',
		'PublicationData',
		'Queue.Jsonable',
		'Search',
		'SoftDelete',
		'SystemOrder',
		'Timestamp',
		'Translate',
		'Tree',
	);

	override(
		\Cake\ORM\Table::belongToMany(0),
		map([
			'Attributes' => \Cake\ORM\Association\BelongsToMany::class,
			'Audit' => \Cake\ORM\Association\BelongsToMany::class,
			'BackendMenuEntries' => \Cake\ORM\Association\BelongsToMany::class,
			'Configuration' => \Cake\ORM\Association\BelongsToMany::class,
			'ContentAreas' => \Cake\ORM\Association\BelongsToMany::class,
			'ContentTemplateContentAreas' => \Cake\ORM\Association\BelongsToMany::class,
			'ContentTemplateElements' => \Cake\ORM\Association\BelongsToMany::class,
			'ContentTemplates' => \Cake\ORM\Association\BelongsToMany::class,
			'Contents' => \Cake\ORM\Association\BelongsToMany::class,
			'CustomerGroupAccessSettings' => \Cake\ORM\Association\BelongsToMany::class,
			'CustomerGroupAssignments' => \Cake\ORM\Association\BelongsToMany::class,
			'CustomerGroups' => \Cake\ORM\Association\BelongsToMany::class,
			'CustomerGroupsCustomers' => \Cake\ORM\Association\BelongsToMany::class,
			'Customers' => \Cake\ORM\Association\BelongsToMany::class,
			'DashboardElements' => \Cake\ORM\Association\BelongsToMany::class,
			'Datatables' => \Cake\ORM\Association\BelongsToMany::class,
			'Designs' => \Cake\ORM\Association\BelongsToMany::class,
			'EmailTemplates' => \Cake\ORM\Association\BelongsToMany::class,
			'FormConditionalRecipients' => \Cake\ORM\Association\BelongsToMany::class,
			'FormElements' => \Cake\ORM\Association\BelongsToMany::class,
			'FormEntries' => \Cake\ORM\Association\BelongsToMany::class,
			'Forms' => \Cake\ORM\Association\BelongsToMany::class,
			'GlobalContentTemplateElements' => \Cake\ORM\Association\BelongsToMany::class,
			'GlobalContentTemplates' => \Cake\ORM\Association\BelongsToMany::class,
			'GlobalContents' => \Cake\ORM\Association\BelongsToMany::class,
			'I18n' => \Cake\ORM\Association\BelongsToMany::class,
			'Languages' => \Cake\ORM\Association\BelongsToMany::class,
			'Locks' => \Cake\ORM\Association\BelongsToMany::class,
			'Media' => \Cake\ORM\Association\BelongsToMany::class,
			'MediaAssignments' => \Cake\ORM\Association\BelongsToMany::class,
			'MediaElementAssignments' => \Cake\ORM\Association\BelongsToMany::class,
			'MediaElementSelectors' => \Cake\ORM\Association\BelongsToMany::class,
			'MediaElements' => \Cake\ORM\Association\BelongsToMany::class,
			'MediaFolders' => \Cake\ORM\Association\BelongsToMany::class,
			'MediaResizedImages' => \Cake\ORM\Association\BelongsToMany::class,
			'MediaSelectors' => \Cake\ORM\Association\BelongsToMany::class,
			'MenuEntries' => \Cake\ORM\Association\BelongsToMany::class,
			'Menus' => \Cake\ORM\Association\BelongsToMany::class,
			'PageRoles' => \Cake\ORM\Association\BelongsToMany::class,
			'PageTemplateContentAreas' => \Cake\ORM\Association\BelongsToMany::class,
			'PageTemplates' => \Cake\ORM\Association\BelongsToMany::class,
			'Pages' => \Cake\ORM\Association\BelongsToMany::class,
			'PublicationData' => \Cake\ORM\Association\BelongsToMany::class,
			'Queue.QueueProcesses' => \Cake\ORM\Association\BelongsToMany::class,
			'Queue.QueuedJobs' => \Cake\ORM\Association\BelongsToMany::class,
			'SurveyAnswers' => \Cake\ORM\Association\BelongsToMany::class,
			'SurveyEntries' => \Cake\ORM\Association\BelongsToMany::class,
			'SurveyQuestions' => \Cake\ORM\Association\BelongsToMany::class,
			'SurveySurveyAnswers' => \Cake\ORM\Association\BelongsToMany::class,
			'SurveySurveyQuestions' => \Cake\ORM\Association\BelongsToMany::class,
			'Surveys' => \Cake\ORM\Association\BelongsToMany::class,
			'ThirdPartyConsents' => \Cake\ORM\Association\BelongsToMany::class,
			'UrlHistory' => \Cake\ORM\Association\BelongsToMany::class,
			'UrlsNotFound' => \Cake\ORM\Association\BelongsToMany::class,
			'UserConfiguration' => \Cake\ORM\Association\BelongsToMany::class,
			'UsergroupPermissions' => \Cake\ORM\Association\BelongsToMany::class,
			'Usergroups' => \Cake\ORM\Association\BelongsToMany::class,
			'UsergroupsUsers' => \Cake\ORM\Association\BelongsToMany::class,
			'Users' => \Cake\ORM\Association\BelongsToMany::class,
		]),
	);

	override(
		\Cake\ORM\Table::belongsTo(0),
		map([
			'Attributes' => \Cake\ORM\Association\BelongsTo::class,
			'Audit' => \Cake\ORM\Association\BelongsTo::class,
			'BackendMenuEntries' => \Cake\ORM\Association\BelongsTo::class,
			'Configuration' => \Cake\ORM\Association\BelongsTo::class,
			'ContentAreas' => \Cake\ORM\Association\BelongsTo::class,
			'ContentTemplateContentAreas' => \Cake\ORM\Association\BelongsTo::class,
			'ContentTemplateElements' => \Cake\ORM\Association\BelongsTo::class,
			'ContentTemplates' => \Cake\ORM\Association\BelongsTo::class,
			'Contents' => \Cake\ORM\Association\BelongsTo::class,
			'CustomerGroupAccessSettings' => \Cake\ORM\Association\BelongsTo::class,
			'CustomerGroupAssignments' => \Cake\ORM\Association\BelongsTo::class,
			'CustomerGroups' => \Cake\ORM\Association\BelongsTo::class,
			'CustomerGroupsCustomers' => \Cake\ORM\Association\BelongsTo::class,
			'Customers' => \Cake\ORM\Association\BelongsTo::class,
			'DashboardElements' => \Cake\ORM\Association\BelongsTo::class,
			'Datatables' => \Cake\ORM\Association\BelongsTo::class,
			'Designs' => \Cake\ORM\Association\BelongsTo::class,
			'EmailTemplates' => \Cake\ORM\Association\BelongsTo::class,
			'FormConditionalRecipients' => \Cake\ORM\Association\BelongsTo::class,
			'FormElements' => \Cake\ORM\Association\BelongsTo::class,
			'FormEntries' => \Cake\ORM\Association\BelongsTo::class,
			'Forms' => \Cake\ORM\Association\BelongsTo::class,
			'GlobalContentTemplateElements' => \Cake\ORM\Association\BelongsTo::class,
			'GlobalContentTemplates' => \Cake\ORM\Association\BelongsTo::class,
			'GlobalContents' => \Cake\ORM\Association\BelongsTo::class,
			'I18n' => \Cake\ORM\Association\BelongsTo::class,
			'Languages' => \Cake\ORM\Association\BelongsTo::class,
			'Locks' => \Cake\ORM\Association\BelongsTo::class,
			'Media' => \Cake\ORM\Association\BelongsTo::class,
			'MediaAssignments' => \Cake\ORM\Association\BelongsTo::class,
			'MediaElementAssignments' => \Cake\ORM\Association\BelongsTo::class,
			'MediaElementSelectors' => \Cake\ORM\Association\BelongsTo::class,
			'MediaElements' => \Cake\ORM\Association\BelongsTo::class,
			'MediaFolders' => \Cake\ORM\Association\BelongsTo::class,
			'MediaResizedImages' => \Cake\ORM\Association\BelongsTo::class,
			'MediaSelectors' => \Cake\ORM\Association\BelongsTo::class,
			'MenuEntries' => \Cake\ORM\Association\BelongsTo::class,
			'Menus' => \Cake\ORM\Association\BelongsTo::class,
			'PageRoles' => \Cake\ORM\Association\BelongsTo::class,
			'PageTemplateContentAreas' => \Cake\ORM\Association\BelongsTo::class,
			'PageTemplates' => \Cake\ORM\Association\BelongsTo::class,
			'Pages' => \Cake\ORM\Association\BelongsTo::class,
			'PublicationData' => \Cake\ORM\Association\BelongsTo::class,
			'Queue.QueueProcesses' => \Cake\ORM\Association\BelongsTo::class,
			'Queue.QueuedJobs' => \Cake\ORM\Association\BelongsTo::class,
			'SurveyAnswers' => \Cake\ORM\Association\BelongsTo::class,
			'SurveyEntries' => \Cake\ORM\Association\BelongsTo::class,
			'SurveyQuestions' => \Cake\ORM\Association\BelongsTo::class,
			'SurveySurveyAnswers' => \Cake\ORM\Association\BelongsTo::class,
			'SurveySurveyQuestions' => \Cake\ORM\Association\BelongsTo::class,
			'Surveys' => \Cake\ORM\Association\BelongsTo::class,
			'ThirdPartyConsents' => \Cake\ORM\Association\BelongsTo::class,
			'UrlHistory' => \Cake\ORM\Association\BelongsTo::class,
			'UrlsNotFound' => \Cake\ORM\Association\BelongsTo::class,
			'UserConfiguration' => \Cake\ORM\Association\BelongsTo::class,
			'UsergroupPermissions' => \Cake\ORM\Association\BelongsTo::class,
			'Usergroups' => \Cake\ORM\Association\BelongsTo::class,
			'UsergroupsUsers' => \Cake\ORM\Association\BelongsTo::class,
			'Users' => \Cake\ORM\Association\BelongsTo::class,
		]),
	);

	override(
		\Cake\ORM\Table::find(0),
		map([
			'accessible' => \Cake\ORM\Query\SelectQuery::class,
			'active' => \Cake\ORM\Query\SelectQuery::class,
			'all' => \Cake\ORM\Query\SelectQuery::class,
			'deleted' => \Cake\ORM\Query\SelectQuery::class,
			'forCurrentLanguage' => \Cake\ORM\Query\SelectQuery::class,
			'latestForPages' => \Cake\ORM\Query\SelectQuery::class,
			'list' => \Cake\ORM\Query\SelectQuery::class,
			'mediaAssignments' => \Cake\ORM\Query\SelectQuery::class,
			'mediaElementAssignments' => \Cake\ORM\Query\SelectQuery::class,
			'published' => \Cake\ORM\Query\SelectQuery::class,
			'publishedEndingAfter' => \Cake\ORM\Query\SelectQuery::class,
			'publishedEndingBefore' => \Cake\ORM\Query\SelectQuery::class,
			'publishedStartingAfter' => \Cake\ORM\Query\SelectQuery::class,
			'publishedStartingBefore' => \Cake\ORM\Query\SelectQuery::class,
			'queued' => \Cake\ORM\Query\SelectQuery::class,
			'recent' => \Cake\ORM\Query\SelectQuery::class,
			'threaded' => \Cake\ORM\Query\SelectQuery::class,
			'translations' => \Cake\ORM\Query\SelectQuery::class,
			'withAuditUsers' => \Cake\ORM\Query\SelectQuery::class,
			'withDeleted' => \Cake\ORM\Query\SelectQuery::class,
			'withMatchingAttributes' => \Cake\ORM\Query\SelectQuery::class,
			'withUsages' => \Cake\ORM\Query\SelectQuery::class,
		]),
	);

	override(
		\Cake\ORM\Table::getBehavior(),
		map([
			'Attributes' => \Awyiss\Model\Behavior\AttributesBehavior::class,
			'Audit' => \Awyiss\Model\Behavior\AuditBehavior::class,
			'AutoPrefix' => \Awyiss\Model\Behavior\AutoPrefixBehavior::class,
			'Categories' => \Awyiss\Model\Behavior\CategoriesBehavior::class,
			'CounterCache' => \Cake\ORM\Behavior\CounterCacheBehavior::class,
			'CustomerGroupAccessSetting' => \Awyiss\Model\Behavior\CustomerGroupAccessSettingBehavior::class,
			'DefaultValues' => \Awyiss\Model\Behavior\DefaultValuesBehavior::class,
			'EventTrigger' => \Awyiss\Model\Behavior\EventTriggerBehavior::class,
			'Jsonable' => \Queue\Model\Behavior\JsonableBehavior::class,
			'MediaAssignment' => \Awyiss\Model\Behavior\MediaAssignmentBehavior::class,
			'MediaElementAssignment' => \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::class,
			'Nest' => \Awyiss\Model\Behavior\NestBehavior::class,
			'PublicationData' => \Awyiss\Model\Behavior\PublicationDataBehavior::class,
			'Search' => \Awyiss\Model\Behavior\SearchBehavior::class,
			'SoftDelete' => \Awyiss\Model\Behavior\SoftDeleteBehavior::class,
			'SystemOrder' => \Awyiss\Model\Behavior\SystemOrderBehavior::class,
			'Timestamp' => \Cake\ORM\Behavior\TimestampBehavior::class,
			'Translate' => \Awyiss\Model\Behavior\TranslateBehavior::class,
			'Tree' => \Cake\ORM\Behavior\TreeBehavior::class,
		]),
	);

	expectedArguments(
		\Cake\ORM\Table::hasBehavior(),
		0,
		'Attributes',
		'Audit',
		'AutoPrefix',
		'Categories',
		'CounterCache',
		'CustomerGroupAccessSetting',
		'DefaultValues',
		'EventTrigger',
		'Jsonable',
		'MediaAssignment',
		'MediaElementAssignment',
		'Nest',
		'PublicationData',
		'Search',
		'SoftDelete',
		'SystemOrder',
		'Timestamp',
		'Translate',
		'Tree',
	);

	override(
		\Cake\ORM\Table::hasMany(0),
		map([
			'Attributes' => \Cake\ORM\Association\HasMany::class,
			'Audit' => \Cake\ORM\Association\HasMany::class,
			'BackendMenuEntries' => \Cake\ORM\Association\HasMany::class,
			'Configuration' => \Cake\ORM\Association\HasMany::class,
			'ContentAreas' => \Cake\ORM\Association\HasMany::class,
			'ContentTemplateContentAreas' => \Cake\ORM\Association\HasMany::class,
			'ContentTemplateElements' => \Cake\ORM\Association\HasMany::class,
			'ContentTemplates' => \Cake\ORM\Association\HasMany::class,
			'Contents' => \Cake\ORM\Association\HasMany::class,
			'CustomerGroupAccessSettings' => \Cake\ORM\Association\HasMany::class,
			'CustomerGroupAssignments' => \Cake\ORM\Association\HasMany::class,
			'CustomerGroups' => \Cake\ORM\Association\HasMany::class,
			'CustomerGroupsCustomers' => \Cake\ORM\Association\HasMany::class,
			'Customers' => \Cake\ORM\Association\HasMany::class,
			'DashboardElements' => \Cake\ORM\Association\HasMany::class,
			'Datatables' => \Cake\ORM\Association\HasMany::class,
			'Designs' => \Cake\ORM\Association\HasMany::class,
			'EmailTemplates' => \Cake\ORM\Association\HasMany::class,
			'FormConditionalRecipients' => \Cake\ORM\Association\HasMany::class,
			'FormElements' => \Cake\ORM\Association\HasMany::class,
			'FormEntries' => \Cake\ORM\Association\HasMany::class,
			'Forms' => \Cake\ORM\Association\HasMany::class,
			'GlobalContentTemplateElements' => \Cake\ORM\Association\HasMany::class,
			'GlobalContentTemplates' => \Cake\ORM\Association\HasMany::class,
			'GlobalContents' => \Cake\ORM\Association\HasMany::class,
			'I18n' => \Cake\ORM\Association\HasMany::class,
			'Languages' => \Cake\ORM\Association\HasMany::class,
			'Locks' => \Cake\ORM\Association\HasMany::class,
			'Media' => \Cake\ORM\Association\HasMany::class,
			'MediaAssignments' => \Cake\ORM\Association\HasMany::class,
			'MediaElementAssignments' => \Cake\ORM\Association\HasMany::class,
			'MediaElementSelectors' => \Cake\ORM\Association\HasMany::class,
			'MediaElements' => \Cake\ORM\Association\HasMany::class,
			'MediaFolders' => \Cake\ORM\Association\HasMany::class,
			'MediaResizedImages' => \Cake\ORM\Association\HasMany::class,
			'MediaSelectors' => \Cake\ORM\Association\HasMany::class,
			'MenuEntries' => \Cake\ORM\Association\HasMany::class,
			'Menus' => \Cake\ORM\Association\HasMany::class,
			'PageRoles' => \Cake\ORM\Association\HasMany::class,
			'PageTemplateContentAreas' => \Cake\ORM\Association\HasMany::class,
			'PageTemplates' => \Cake\ORM\Association\HasMany::class,
			'Pages' => \Cake\ORM\Association\HasMany::class,
			'PublicationData' => \Cake\ORM\Association\HasMany::class,
			'Queue.QueueProcesses' => \Cake\ORM\Association\HasMany::class,
			'Queue.QueuedJobs' => \Cake\ORM\Association\HasMany::class,
			'SurveyAnswers' => \Cake\ORM\Association\HasMany::class,
			'SurveyEntries' => \Cake\ORM\Association\HasMany::class,
			'SurveyQuestions' => \Cake\ORM\Association\HasMany::class,
			'SurveySurveyAnswers' => \Cake\ORM\Association\HasMany::class,
			'SurveySurveyQuestions' => \Cake\ORM\Association\HasMany::class,
			'Surveys' => \Cake\ORM\Association\HasMany::class,
			'ThirdPartyConsents' => \Cake\ORM\Association\HasMany::class,
			'UrlHistory' => \Cake\ORM\Association\HasMany::class,
			'UrlsNotFound' => \Cake\ORM\Association\HasMany::class,
			'UserConfiguration' => \Cake\ORM\Association\HasMany::class,
			'UsergroupPermissions' => \Cake\ORM\Association\HasMany::class,
			'Usergroups' => \Cake\ORM\Association\HasMany::class,
			'UsergroupsUsers' => \Cake\ORM\Association\HasMany::class,
			'Users' => \Cake\ORM\Association\HasMany::class,
		]),
	);

	override(
		\Cake\ORM\Table::hasOne(0),
		map([
			'Attributes' => \Cake\ORM\Association\HasOne::class,
			'Audit' => \Cake\ORM\Association\HasOne::class,
			'BackendMenuEntries' => \Cake\ORM\Association\HasOne::class,
			'Configuration' => \Cake\ORM\Association\HasOne::class,
			'ContentAreas' => \Cake\ORM\Association\HasOne::class,
			'ContentTemplateContentAreas' => \Cake\ORM\Association\HasOne::class,
			'ContentTemplateElements' => \Cake\ORM\Association\HasOne::class,
			'ContentTemplates' => \Cake\ORM\Association\HasOne::class,
			'Contents' => \Cake\ORM\Association\HasOne::class,
			'CustomerGroupAccessSettings' => \Cake\ORM\Association\HasOne::class,
			'CustomerGroupAssignments' => \Cake\ORM\Association\HasOne::class,
			'CustomerGroups' => \Cake\ORM\Association\HasOne::class,
			'CustomerGroupsCustomers' => \Cake\ORM\Association\HasOne::class,
			'Customers' => \Cake\ORM\Association\HasOne::class,
			'DashboardElements' => \Cake\ORM\Association\HasOne::class,
			'Datatables' => \Cake\ORM\Association\HasOne::class,
			'Designs' => \Cake\ORM\Association\HasOne::class,
			'EmailTemplates' => \Cake\ORM\Association\HasOne::class,
			'FormConditionalRecipients' => \Cake\ORM\Association\HasOne::class,
			'FormElements' => \Cake\ORM\Association\HasOne::class,
			'FormEntries' => \Cake\ORM\Association\HasOne::class,
			'Forms' => \Cake\ORM\Association\HasOne::class,
			'GlobalContentTemplateElements' => \Cake\ORM\Association\HasOne::class,
			'GlobalContentTemplates' => \Cake\ORM\Association\HasOne::class,
			'GlobalContents' => \Cake\ORM\Association\HasOne::class,
			'I18n' => \Cake\ORM\Association\HasOne::class,
			'Languages' => \Cake\ORM\Association\HasOne::class,
			'Locks' => \Cake\ORM\Association\HasOne::class,
			'Media' => \Cake\ORM\Association\HasOne::class,
			'MediaAssignments' => \Cake\ORM\Association\HasOne::class,
			'MediaElementAssignments' => \Cake\ORM\Association\HasOne::class,
			'MediaElementSelectors' => \Cake\ORM\Association\HasOne::class,
			'MediaElements' => \Cake\ORM\Association\HasOne::class,
			'MediaFolders' => \Cake\ORM\Association\HasOne::class,
			'MediaResizedImages' => \Cake\ORM\Association\HasOne::class,
			'MediaSelectors' => \Cake\ORM\Association\HasOne::class,
			'MenuEntries' => \Cake\ORM\Association\HasOne::class,
			'Menus' => \Cake\ORM\Association\HasOne::class,
			'PageRoles' => \Cake\ORM\Association\HasOne::class,
			'PageTemplateContentAreas' => \Cake\ORM\Association\HasOne::class,
			'PageTemplates' => \Cake\ORM\Association\HasOne::class,
			'Pages' => \Cake\ORM\Association\HasOne::class,
			'PublicationData' => \Cake\ORM\Association\HasOne::class,
			'Queue.QueueProcesses' => \Cake\ORM\Association\HasOne::class,
			'Queue.QueuedJobs' => \Cake\ORM\Association\HasOne::class,
			'SurveyAnswers' => \Cake\ORM\Association\HasOne::class,
			'SurveyEntries' => \Cake\ORM\Association\HasOne::class,
			'SurveyQuestions' => \Cake\ORM\Association\HasOne::class,
			'SurveySurveyAnswers' => \Cake\ORM\Association\HasOne::class,
			'SurveySurveyQuestions' => \Cake\ORM\Association\HasOne::class,
			'Surveys' => \Cake\ORM\Association\HasOne::class,
			'ThirdPartyConsents' => \Cake\ORM\Association\HasOne::class,
			'UrlHistory' => \Cake\ORM\Association\HasOne::class,
			'UrlsNotFound' => \Cake\ORM\Association\HasOne::class,
			'UserConfiguration' => \Cake\ORM\Association\HasOne::class,
			'UsergroupPermissions' => \Cake\ORM\Association\HasOne::class,
			'Usergroups' => \Cake\ORM\Association\HasOne::class,
			'UsergroupsUsers' => \Cake\ORM\Association\HasOne::class,
			'Users' => \Cake\ORM\Association\HasOne::class,
		]),
	);

	expectedArguments(
		\Cake\ORM\Table::removeBehavior(),
		0,
		'Attributes',
		'Audit',
		'AutoPrefix',
		'Categories',
		'CounterCache',
		'CustomerGroupAccessSetting',
		'DefaultValues',
		'EventTrigger',
		'Jsonable',
		'MediaAssignment',
		'MediaElementAssignment',
		'Nest',
		'PublicationData',
		'Search',
		'SoftDelete',
		'SystemOrder',
		'Timestamp',
		'Translate',
		'Tree',
	);

	expectedArguments(
		\Cake\Routing\Router::pathUrl(),
		0,
		argumentsSet('routePaths'),
	);

	expectedArguments(
		\Cake\TestSuite\TestCase::addFixture(),
		0,
		'core.AliasedArticles',
		'core.Articles',
		'core.ArticlesMoreTranslations',
		'core.ArticlesTags',
		'core.ArticlesTagsBindingKeys',
		'core.ArticlesTranslations',
		'core.Attachments',
		'core.AuthUsers',
		'core.Authors',
		'core.AuthorsTags',
		'core.AuthorsTranslations',
		'core.BinaryUuidItems',
		'core.BinaryUuidItemsBinaryUuidTags',
		'core.BinaryUuidTags',
		'core.CakeSessions',
		'core.Categories',
		'core.ColumnSchemaAwareTypeValues',
		'core.Comments',
		'core.CommentsTranslations',
		'core.CompositeIncrements',
		'core.CompositeKeyArticles',
		'core.CompositeKeyArticlesTags',
		'core.CounterCacheCategories',
		'core.CounterCacheComments',
		'core.CounterCachePosts',
		'core.CounterCacheUserCategoryPosts',
		'core.CounterCacheUsers',
		'core.Datatypes',
		'core.DateKeys',
		'core.Equipment',
		'core.FeaturedTags',
		'core.Members',
		'core.MenuLinkTrees',
		'core.NullableAuthors',
		'core.NumberTrees',
		'core.NumberTreesArticles',
		'core.OrderedUuidItems',
		'core.Orders',
		'core.OtherArticles',
		'core.PolymorphicTagged',
		'core.Posts',
		'core.Products',
		'core.Profiles',
		'core.Sections',
		'core.SectionsMembers',
		'core.SectionsTranslations',
		'core.Sessions',
		'core.SiteArticles',
		'core.SiteArticlesTags',
		'core.SiteAuthors',
		'core.SiteTags',
		'core.SpecialPk',
		'core.SpecialTags',
		'core.SpecialTagsTranslations',
		'core.Tags',
		'core.TagsShadowTranslations',
		'core.TagsTranslations',
		'core.TestPluginComments',
		'core.Things',
		'core.Translates',
		'core.UniqueAuthors',
		'core.Users',
		'core.UuidItems',
		'plugin.Bake.Articles',
		'plugin.Bake.ArticlesTags',
		'plugin.Bake.Authors',
		'plugin.Bake.BakeArticles',
		'plugin.Bake.BakeArticlesBakeTags',
		'plugin.Bake.BakeCar',
		'plugin.Bake.BakeComments',
		'plugin.Bake.BakeTags',
		'plugin.Bake.BakeTemplateAuthors',
		'plugin.Bake.BakeTemplateProfiles',
		'plugin.Bake.BakeTemplateRoles',
		'plugin.Bake.BinaryTests',
		'plugin.Bake.Categories',
		'plugin.Bake.CategoriesProducts',
		'plugin.Bake.CategoryThreads',
		'plugin.Bake.Comments',
		'plugin.Bake.Datatypes',
		'plugin.Bake.HiddenFields',
		'plugin.Bake.Invitations',
		'plugin.Bake.News',
		'plugin.Bake.NumberTrees',
		'plugin.Bake.OldProducts',
		'plugin.Bake.Posts',
		'plugin.Bake.ProductVersions',
		'plugin.Bake.Products',
		'plugin.Bake.Relations',
		'plugin.Bake.Tags',
		'plugin.Bake.TodoItems',
		'plugin.Bake.TodoItemsTodoLabels',
		'plugin.Bake.TodoLabels',
		'plugin.Bake.TodoTasks',
		'plugin.Bake.UniqueFields',
		'plugin.Bake.Users',
		'plugin.IdeHelper.BarBars',
		'plugin.IdeHelper.Cars',
		'plugin.IdeHelper.Foos',
		'plugin.IdeHelper.Houses',
		'plugin.IdeHelper.Wheels',
		'plugin.IdeHelper.Windows',
		'plugin.Queue.QueueProcesses',
		'plugin.Queue.QueuedJobs',
	);

	expectedArguments(
		\Cake\Validation\Validator::allowEmptyArray(),
		2,
		argumentsSet('validationWhen'),
	);

	expectedArguments(
		\Cake\Validation\Validator::allowEmptyDate(),
		2,
		argumentsSet('validationWhen'),
	);

	expectedArguments(
		\Cake\Validation\Validator::allowEmptyDateTime(),
		2,
		argumentsSet('validationWhen'),
	);

	expectedArguments(
		\Cake\Validation\Validator::allowEmptyFile(),
		2,
		argumentsSet('validationWhen'),
	);

	expectedArguments(
		\Cake\Validation\Validator::allowEmptyFor(),
		2,
		argumentsSet('validationWhen'),
	);

	expectedArguments(
		\Cake\Validation\Validator::allowEmptyString(),
		2,
		argumentsSet('validationWhen'),
	);

	expectedArguments(
		\Cake\Validation\Validator::allowEmptyTime(),
		2,
		argumentsSet('validationWhen'),
	);

	expectedArguments(
		\Cake\Validation\Validator::notEmptyArray(),
		2,
		argumentsSet('validationWhen'),
	);

	expectedArguments(
		\Cake\Validation\Validator::notEmptyDate(),
		2,
		argumentsSet('validationWhen'),
	);

	expectedArguments(
		\Cake\Validation\Validator::notEmptyDateTime(),
		2,
		argumentsSet('validationWhen'),
	);

	expectedArguments(
		\Cake\Validation\Validator::notEmptyFile(),
		2,
		argumentsSet('validationWhen'),
	);

	expectedArguments(
		\Cake\Validation\Validator::notEmptyString(),
		2,
		argumentsSet('validationWhen'),
	);

	expectedArguments(
		\Cake\Validation\Validator::notEmptyTime(),
		2,
		argumentsSet('validationWhen'),
	);

	expectedArguments(
		\Cake\Validation\Validator::requirePresence(),
		1,
		argumentsSet('validationWhen'),
	);

	expectedArguments(
		\Cake\View\Helper\FormHelper::control(),
		0,
		'acceptType',
		'acceptedCategories',
		'access',
		'accessType',
		'active',
		'allowCustomAnswer',
		'alt',
		'attempts',
		'averageColor',
		'avif',
		'bcc',
		'body',
		'bodyConfirmation',
		'cc',
		'changedBy',
		'changedOn',
		'columnIndent',
		'columnLast',
		'columnRtl',
		'columnSpan',
		'columnWidth',
		'completed',
		'conditionalRecipientsStrategy',
		'confirmationEmailTemplateId',
		'consentId',
		'content',
		'contentAreaId',
		'contentTemplateId',
		'content_type',
		'created',
		'createdBy',
		'createdOn',
		'crop',
		'css',
		'cssClass',
		'customAnswerTitle',
		'customerGroupId',
		'customerId',
		'data',
		'dataNew',
		'dataOld',
		'dateFormat',
		'dateTime',
		'defaultValue',
		'deleted',
		'deletedBy',
		'deletedOn',
		'description',
		'diff',
		'duplicateOf',
		'element',
		'email',
		'emailTemplateId',
		'external',
		'failedAttempts',
		'failureMessage',
		'failure_message',
		'fetched',
		'field',
		'fieldset',
		'fileName',
		'finalAction',
		'firstname',
		'focusPoint',
		'foreignKey',
		'formId',
		'globalContentTemplateId',
		'hasIndex',
		'height',
		'hidden',
		'id',
		'identifier',
		'inContentRow',
		'inUse',
		'includeInLinklist',
		'inputType',
		'insertAfterId',
		'internal',
		'ipHash',
		'isPreview',
		'isRobot',
		'job_group',
		'job_task',
		'languageShortcode',
		'lastLogin',
		'lastname',
		'layout',
		'link',
		'locale',
		'mediaElementId',
		'mediaElementSelectorIdentifier',
		'mediaFolderId',
		'mediaId',
		'mediaSelectorId',
		'memory',
		'menuId',
		'metaData',
		'metaDescription',
		'metaTitle',
		'method',
		'mimeType',
		'model',
		'modified',
		'multistep',
		'name',
		'nextAction',
		'nextActionTarget',
		'notbefore',
		'operator',
		'options',
		'output',
		'ownerEmail',
		'ownerName',
		'pageId',
		'pageRoleId',
		'pageTemplateId',
		'panel',
		'parentId',
		'parentsActive',
		'password',
		'passwordResetCode',
		'passwordResetOn',
		'path',
		'pid',
		'placeholder',
		'postHash',
		'preview',
		'priority',
		'progress',
		'realHeight',
		'realWidth',
		'realm',
		'recipient',
		'redirectLink',
		'reference',
		'referrer',
		'rejectedCategories',
		'request_id',
		'requested_at',
		'required',
		'robotsFollow',
		'robotsIndex',
		'salutation',
		'salutationConfirmation',
		'scope',
		'sendConfirmationEmail',
		'sendEmail',
		'server',
		'settings',
		'shortcode',
		'slug',
		'status',
		'status_code',
		'strategy',
		'subject',
		'subjectConfirmation',
		'subjectLeftForeignKey',
		'subjectLeftTable',
		'subjectRightForeignKey',
		'subjectRightTable',
		'subtitle',
		'subtitleTag',
		'successMessage',
		'summarizeErrors',
		'summary',
		'surveyAnswerId',
		'surveyId',
		'surveyQuestionId',
		'surveySurveyQuestionId',
		'systemOrder',
		'target',
		'terminate',
		'text',
		'textHtml',
		'textPlain',
		'timeFormat',
		'timezone',
		'title',
		'titleEmail',
		'titleTag',
		'transactionId',
		'translatable',
		'transportProfile',
		'type',
		'uniqueId',
		'url',
		'userEmail',
		'userId',
		'userName',
		'usergroupId',
		'username',
		'value',
		'verificationCode',
		'verified',
		'verifiedOn',
		'webp',
		'width',
		'workerkey',
	);

	expectedArguments(
		\Cake\View\Helper\HtmlHelper::linkFromPath(),
		1,
		argumentsSet('routePaths'),
	);

	expectedArguments(
		\Cake\View\Helper\UrlHelper::buildFromPath(),
		0,
		argumentsSet('routePaths'),
	);

	override(
		\Cake\View\View::addHelper(0),
		map([
			'Asset' => \Awyiss\View\Helper\AssetHelper::class,
			'Attributes' => \Awyiss\View\Helper\AttributesHelper::class,
			'Audit' => \Awyiss\View\Helper\AuditHelper::class,
			'Authentication.Identity' => \Authentication\View\Helper\IdentityHelper::class,
			'Authorization' => \Awyiss\View\Helper\AuthorizationHelper::class,
			'Bake.Bake' => \Bake\View\Helper\BakeHelper::class,
			'Bake.DocBlock' => \Bake\View\Helper\DocBlockHelper::class,
			'Breadcrumbs' => \Cake\View\Helper\BreadcrumbsHelper::class,
			'Categories' => \Awyiss\View\Helper\CategoriesHelper::class,
			'Flash' => \Awyiss\View\Helper\FlashHelper::class,
			'Form' => \Awyiss\View\Helper\FormHelper::class,
			'Html' => \Awyiss\View\Helper\HtmlHelper::class,
			'IdeHelper.DocBlock' => \IdeHelper\View\Helper\DocBlockHelper::class,
			'Locale' => \Awyiss\View\Helper\LocaleHelper::class,
			'Media' => \Awyiss\View\Helper\MediaHelper::class,
			'Migrations.Migration' => \Migrations\View\Helper\MigrationHelper::class,
			'Number' => \Cake\View\Helper\NumberHelper::class,
			'Paginator' => \Awyiss\View\Helper\PaginatorHelper::class,
			'Queue.Configure' => \Queue\View\Helper\ConfigureHelper::class,
			'Queue.Queue' => \Queue\View\Helper\QueueHelper::class,
			'Queue.QueueProgress' => \Queue\View\Helper\QueueProgressHelper::class,
			'Survey' => \Awyiss\View\Helper\SurveyHelper::class,
			'SystemOrder' => \Awyiss\View\Helper\SystemOrderHelper::class,
			'Text' => \Cake\View\Helper\TextHelper::class,
			'Time' => \Cake\View\Helper\TimeHelper::class,
			'Url' => \Awyiss\View\Helper\UrlHelper::class,
		]),
	);

	expectedArguments(
		\Cake\View\View::element(),
		0,
		'Cake/TwigView.twig_panel',
		'Queue.Queue/connection_switcher',
		'Queue.Queue/mobile_nav',
		'Queue.Queue/sidebar',
		'Queue.Queue/stats_card',
		'Queue.Queue/status_badge',
		'Queue.flash/error',
		'Queue.flash/flash',
		'Queue.flash/info',
		'Queue.flash/success',
		'Queue.flash/warning',
		'Queue.icon',
		'Queue.ok',
		'Queue.pagination',
		'Queue.search',
		'Queue.yes_no',
	);

	override(
		\Cake\View\View::loadHelper(0),
		map([
			'Asset' => \Awyiss\View\Helper\AssetHelper::class,
			'Attributes' => \Awyiss\View\Helper\AttributesHelper::class,
			'Audit' => \Awyiss\View\Helper\AuditHelper::class,
			'Authentication.Identity' => \Authentication\View\Helper\IdentityHelper::class,
			'Authorization' => \Awyiss\View\Helper\AuthorizationHelper::class,
			'Bake.Bake' => \Bake\View\Helper\BakeHelper::class,
			'Bake.DocBlock' => \Bake\View\Helper\DocBlockHelper::class,
			'Breadcrumbs' => \Cake\View\Helper\BreadcrumbsHelper::class,
			'Categories' => \Awyiss\View\Helper\CategoriesHelper::class,
			'Flash' => \Awyiss\View\Helper\FlashHelper::class,
			'Form' => \Awyiss\View\Helper\FormHelper::class,
			'Html' => \Awyiss\View\Helper\HtmlHelper::class,
			'IdeHelper.DocBlock' => \IdeHelper\View\Helper\DocBlockHelper::class,
			'Locale' => \Awyiss\View\Helper\LocaleHelper::class,
			'Media' => \Awyiss\View\Helper\MediaHelper::class,
			'Migrations.Migration' => \Migrations\View\Helper\MigrationHelper::class,
			'Number' => \Cake\View\Helper\NumberHelper::class,
			'Paginator' => \Awyiss\View\Helper\PaginatorHelper::class,
			'Queue.Configure' => \Queue\View\Helper\ConfigureHelper::class,
			'Queue.Queue' => \Queue\View\Helper\QueueHelper::class,
			'Queue.QueueProgress' => \Queue\View\Helper\QueueProgressHelper::class,
			'Survey' => \Awyiss\View\Helper\SurveyHelper::class,
			'SystemOrder' => \Awyiss\View\Helper\SystemOrderHelper::class,
			'Text' => \Cake\View\Helper\TextHelper::class,
			'Time' => \Cake\View\Helper\TimeHelper::class,
			'Url' => \Awyiss\View\Helper\UrlHelper::class,
		]),
	);

	expectedArguments(
		\Cake\View\ViewBuilder::addHelper(),
		0,
		'Asset',
		'Attributes',
		'Audit',
		'Authentication.Identity',
		'Authorization',
		'Bake.Bake',
		'Bake.DocBlock',
		'Breadcrumbs',
		'Categories',
		'Flash',
		'Form',
		'Html',
		'IdeHelper.DocBlock',
		'Locale',
		'Media',
		'Migrations.Migration',
		'Number',
		'Paginator',
		'Queue.Configure',
		'Queue.Queue',
		'Queue.QueueProgress',
		'Survey',
		'SystemOrder',
		'Text',
		'Time',
		'Url',
	);

	expectedArguments(
		\Cake\View\ViewBuilder::setLayout(),
		0,
		'Queue.queue',
		'dev_error',
	);

	expectedArguments(
		\Migrations\BaseMigration::hasTable(),
		0,
		argumentsSet('tableNames'),
	);

	expectedArguments(
		\Migrations\BaseMigration::table(),
		0,
		argumentsSet('tableNames'),
	);

	expectedArguments(
		\Migrations\BaseSeed::hasTable(),
		0,
		argumentsSet('tableNames'),
	);

	expectedArguments(
		\Migrations\BaseSeed::table(),
		0,
		argumentsSet('tableNames'),
	);

	expectedArguments(
		\Migrations\Db\Table::addColumn(),
		0,
		argumentsSet('columnNames'),
	);

	expectedArguments(
		\Migrations\Db\Table::addColumn(),
		1,
		argumentsSet('columnTypes'),
	);

	expectedArguments(
		\Migrations\Db\Table::changeColumn(),
		0,
		argumentsSet('columnNames'),
	);

	expectedArguments(
		\Migrations\Db\Table::changeColumn(),
		1,
		argumentsSet('columnTypes'),
	);

	expectedArguments(
		\Migrations\Db\Table::hasColumn(),
		0,
		argumentsSet('columnNames'),
	);

	expectedArguments(
		\Migrations\Db\Table::removeColumn(),
		0,
		argumentsSet('columnNames'),
	);

	expectedArguments(
		\Migrations\Db\Table::renameColumn(),
		0,
		argumentsSet('columnNames'),
	);

	expectedArguments(
		\Migrations\Db\Table::renameColumn(),
		1,
		argumentsSet('columnNames'),
	);

	expectedArguments(
		\Queue\Model\Table\QueuedJobsTable::createJob(),
		0,
		'Attributes/Delete',
		'Attributes/Upsert',
		'AutoTranslate',
		'Design/WebfontDownload',
		'Queue.CostsExample',
		'Queue.Email',
		'Queue.Example',
		'Queue.ExceptionExample',
		'Queue.Execute',
		'Queue.Mailer',
		'Queue.MonitorExample',
		'Queue.ProgressExample',
		'Queue.RetryExample',
		'Queue.SuperExample',
		'Queue.UniqueExample',
	);

	expectedArguments(
		\Queue\Model\Table\QueuedJobsTable::isQueued(),
		1,
		'Attributes/Delete',
		'Attributes/Upsert',
		'AutoTranslate',
		'Design/WebfontDownload',
		'Queue.CostsExample',
		'Queue.Email',
		'Queue.Example',
		'Queue.ExceptionExample',
		'Queue.Execute',
		'Queue.Mailer',
		'Queue.MonitorExample',
		'Queue.ProgressExample',
		'Queue.RetryExample',
		'Queue.SuperExample',
		'Queue.UniqueExample',
	);

	expectedArguments(
		\Awyiss\Model\Entity\Attribute::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Attribute'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Attribute::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Attribute'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Attribute::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Attribute'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Attribute::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Attribute'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Attribute::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Attribute'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Attribute::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Attribute'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Attribute::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Attribute'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Attribute::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Attribute'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Attribute::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Attribute'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Attribute::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Attribute'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Audit::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Audit'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Audit::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Audit'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Audit::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Audit'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Audit::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Audit'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Audit::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Audit'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Audit::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Audit'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Audit::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Audit'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Audit::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Audit'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Audit::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Audit'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Audit::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Audit'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\BackendMenuEntry::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\BackendMenuEntry'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\BackendMenuEntry::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\BackendMenuEntry'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\BackendMenuEntry::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\BackendMenuEntry'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\BackendMenuEntry::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\BackendMenuEntry'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\BackendMenuEntry::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\BackendMenuEntry'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\BackendMenuEntry::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\BackendMenuEntry'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\BackendMenuEntry::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\BackendMenuEntry'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\BackendMenuEntry::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\BackendMenuEntry'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\BackendMenuEntry::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\BackendMenuEntry'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\BackendMenuEntry::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\BackendMenuEntry'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Configuration::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Configuration'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Configuration::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Configuration'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Configuration::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Configuration'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Configuration::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Configuration'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Configuration::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Configuration'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Configuration::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Configuration'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Configuration::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Configuration'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Configuration::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Configuration'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Configuration::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Configuration'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Configuration::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Configuration'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Content::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Content'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Content::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Content'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Content::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Content'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Content::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Content'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Content::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Content'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Content::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Content'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Content::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Content'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Content::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Content'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Content::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Content'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Content::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Content'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ContentArea::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ContentArea'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ContentArea::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ContentArea'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ContentArea::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ContentArea'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ContentArea::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ContentArea'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ContentArea::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ContentArea'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ContentArea::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ContentArea'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ContentArea::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ContentArea'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ContentArea::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ContentArea'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ContentArea::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ContentArea'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ContentArea::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ContentArea'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ContentTemplate::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ContentTemplate'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ContentTemplate::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ContentTemplate'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ContentTemplate::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ContentTemplate'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ContentTemplate::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ContentTemplate'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ContentTemplate::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ContentTemplate'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ContentTemplate::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ContentTemplate'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ContentTemplate::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ContentTemplate'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ContentTemplate::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ContentTemplate'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ContentTemplate::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ContentTemplate'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ContentTemplate::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ContentTemplate'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ContentTemplateContentArea::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ContentTemplateContentArea'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ContentTemplateContentArea::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ContentTemplateContentArea'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ContentTemplateContentArea::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ContentTemplateContentArea'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ContentTemplateContentArea::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ContentTemplateContentArea'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ContentTemplateContentArea::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ContentTemplateContentArea'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ContentTemplateContentArea::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ContentTemplateContentArea'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ContentTemplateContentArea::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ContentTemplateContentArea'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ContentTemplateContentArea::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ContentTemplateContentArea'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ContentTemplateContentArea::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ContentTemplateContentArea'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ContentTemplateContentArea::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ContentTemplateContentArea'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ContentTemplateElement::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ContentTemplateElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ContentTemplateElement::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ContentTemplateElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ContentTemplateElement::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ContentTemplateElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ContentTemplateElement::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ContentTemplateElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ContentTemplateElement::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ContentTemplateElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ContentTemplateElement::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ContentTemplateElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ContentTemplateElement::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ContentTemplateElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ContentTemplateElement::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ContentTemplateElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ContentTemplateElement::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ContentTemplateElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ContentTemplateElement::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ContentTemplateElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Customer::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Customer'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Customer::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Customer'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Customer::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Customer'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Customer::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Customer'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Customer::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Customer'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Customer::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Customer'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Customer::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Customer'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Customer::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Customer'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Customer::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Customer'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Customer::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Customer'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\CustomerGroup::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\CustomerGroup'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\CustomerGroup::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\CustomerGroup'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\CustomerGroup::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\CustomerGroup'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\CustomerGroup::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\CustomerGroup'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\CustomerGroup::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\CustomerGroup'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\CustomerGroup::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\CustomerGroup'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\CustomerGroup::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\CustomerGroup'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\CustomerGroup::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\CustomerGroup'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\CustomerGroup::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\CustomerGroup'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\CustomerGroup::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\CustomerGroup'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\CustomerGroupAccessSetting::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\CustomerGroupAccessSetting'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\CustomerGroupAccessSetting::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\CustomerGroupAccessSetting'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\CustomerGroupAccessSetting::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\CustomerGroupAccessSetting'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\CustomerGroupAccessSetting::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\CustomerGroupAccessSetting'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\CustomerGroupAccessSetting::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\CustomerGroupAccessSetting'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\CustomerGroupAccessSetting::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\CustomerGroupAccessSetting'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\CustomerGroupAccessSetting::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\CustomerGroupAccessSetting'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\CustomerGroupAccessSetting::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\CustomerGroupAccessSetting'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\CustomerGroupAccessSetting::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\CustomerGroupAccessSetting'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\CustomerGroupAccessSetting::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\CustomerGroupAccessSetting'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\CustomerGroupAssignment::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\CustomerGroupAssignment'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\CustomerGroupAssignment::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\CustomerGroupAssignment'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\CustomerGroupAssignment::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\CustomerGroupAssignment'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\CustomerGroupAssignment::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\CustomerGroupAssignment'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\CustomerGroupAssignment::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\CustomerGroupAssignment'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\CustomerGroupAssignment::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\CustomerGroupAssignment'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\CustomerGroupAssignment::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\CustomerGroupAssignment'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\CustomerGroupAssignment::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\CustomerGroupAssignment'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\CustomerGroupAssignment::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\CustomerGroupAssignment'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\CustomerGroupAssignment::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\CustomerGroupAssignment'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\CustomerGroupsCustomer::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\CustomerGroupsCustomer'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\CustomerGroupsCustomer::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\CustomerGroupsCustomer'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\CustomerGroupsCustomer::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\CustomerGroupsCustomer'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\CustomerGroupsCustomer::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\CustomerGroupsCustomer'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\CustomerGroupsCustomer::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\CustomerGroupsCustomer'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\CustomerGroupsCustomer::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\CustomerGroupsCustomer'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\CustomerGroupsCustomer::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\CustomerGroupsCustomer'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\CustomerGroupsCustomer::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\CustomerGroupsCustomer'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\CustomerGroupsCustomer::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\CustomerGroupsCustomer'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\CustomerGroupsCustomer::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\CustomerGroupsCustomer'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\DashboardElement::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\DashboardElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\DashboardElement::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\DashboardElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\DashboardElement::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\DashboardElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\DashboardElement::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\DashboardElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\DashboardElement::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\DashboardElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\DashboardElement::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\DashboardElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\DashboardElement::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\DashboardElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\DashboardElement::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\DashboardElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\DashboardElement::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\DashboardElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\DashboardElement::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\DashboardElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Datatable::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Datatable'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Datatable::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Datatable'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Datatable::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Datatable'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Datatable::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Datatable'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Datatable::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Datatable'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Datatable::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Datatable'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Datatable::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Datatable'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Datatable::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Datatable'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Datatable::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Datatable'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Datatable::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Datatable'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Design::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Design'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Design::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Design'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Design::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Design'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Design::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Design'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Design::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Design'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Design::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Design'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Design::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Design'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Design::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Design'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Design::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Design'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Design::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Design'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\EmailTemplate::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\EmailTemplate'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\EmailTemplate::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\EmailTemplate'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\EmailTemplate::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\EmailTemplate'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\EmailTemplate::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\EmailTemplate'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\EmailTemplate::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\EmailTemplate'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\EmailTemplate::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\EmailTemplate'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\EmailTemplate::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\EmailTemplate'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\EmailTemplate::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\EmailTemplate'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\EmailTemplate::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\EmailTemplate'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\EmailTemplate::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\EmailTemplate'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Form::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Form'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Form::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Form'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Form::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Form'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Form::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Form'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Form::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Form'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Form::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Form'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Form::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Form'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Form::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Form'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Form::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Form'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Form::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Form'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\FormConditionalRecipient::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\FormConditionalRecipient'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\FormConditionalRecipient::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\FormConditionalRecipient'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\FormConditionalRecipient::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\FormConditionalRecipient'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\FormConditionalRecipient::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\FormConditionalRecipient'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\FormConditionalRecipient::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\FormConditionalRecipient'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\FormConditionalRecipient::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\FormConditionalRecipient'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\FormConditionalRecipient::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\FormConditionalRecipient'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\FormConditionalRecipient::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\FormConditionalRecipient'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\FormConditionalRecipient::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\FormConditionalRecipient'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\FormConditionalRecipient::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\FormConditionalRecipient'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\FormElement::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\FormElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\FormElement::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\FormElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\FormElement::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\FormElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\FormElement::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\FormElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\FormElement::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\FormElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\FormElement::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\FormElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\FormElement::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\FormElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\FormElement::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\FormElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\FormElement::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\FormElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\FormElement::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\FormElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\FormEntry::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\FormEntry'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\FormEntry::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\FormEntry'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\FormEntry::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\FormEntry'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\FormEntry::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\FormEntry'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\FormEntry::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\FormEntry'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\FormEntry::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\FormEntry'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\FormEntry::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\FormEntry'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\FormEntry::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\FormEntry'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\FormEntry::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\FormEntry'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\FormEntry::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\FormEntry'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\GlobalContent::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\GlobalContent'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\GlobalContent::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\GlobalContent'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\GlobalContent::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\GlobalContent'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\GlobalContent::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\GlobalContent'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\GlobalContent::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\GlobalContent'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\GlobalContent::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\GlobalContent'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\GlobalContent::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\GlobalContent'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\GlobalContent::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\GlobalContent'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\GlobalContent::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\GlobalContent'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\GlobalContent::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\GlobalContent'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\GlobalContentTemplate::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\GlobalContentTemplate'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\GlobalContentTemplate::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\GlobalContentTemplate'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\GlobalContentTemplate::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\GlobalContentTemplate'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\GlobalContentTemplate::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\GlobalContentTemplate'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\GlobalContentTemplate::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\GlobalContentTemplate'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\GlobalContentTemplate::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\GlobalContentTemplate'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\GlobalContentTemplate::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\GlobalContentTemplate'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\GlobalContentTemplate::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\GlobalContentTemplate'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\GlobalContentTemplate::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\GlobalContentTemplate'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\GlobalContentTemplate::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\GlobalContentTemplate'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\GlobalContentTemplateElement::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\GlobalContentTemplateElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\GlobalContentTemplateElement::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\GlobalContentTemplateElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\GlobalContentTemplateElement::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\GlobalContentTemplateElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\GlobalContentTemplateElement::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\GlobalContentTemplateElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\GlobalContentTemplateElement::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\GlobalContentTemplateElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\GlobalContentTemplateElement::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\GlobalContentTemplateElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\GlobalContentTemplateElement::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\GlobalContentTemplateElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\GlobalContentTemplateElement::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\GlobalContentTemplateElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\GlobalContentTemplateElement::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\GlobalContentTemplateElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\GlobalContentTemplateElement::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\GlobalContentTemplateElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\I18n::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\I18n'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\I18n::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\I18n'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\I18n::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\I18n'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\I18n::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\I18n'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\I18n::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\I18n'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\I18n::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\I18n'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\I18n::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\I18n'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\I18n::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\I18n'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\I18n::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\I18n'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\I18n::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\I18n'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Language::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Language'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Language::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Language'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Language::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Language'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Language::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Language'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Language::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Language'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Language::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Language'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Language::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Language'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Language::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Language'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Language::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Language'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Language::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Language'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Lock::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Lock'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Lock::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Lock'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Lock::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Lock'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Lock::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Lock'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Lock::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Lock'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Lock::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Lock'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Lock::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Lock'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Lock::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Lock'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Lock::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Lock'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Lock::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Lock'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Media::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Media'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Media::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Media'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Media::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Media'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Media::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Media'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Media::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Media'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Media::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Media'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Media::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Media'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Media::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Media'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Media::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Media'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Media::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Media'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaAssignment::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaAssignment'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaAssignment::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaAssignment'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaAssignment::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaAssignment'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaAssignment::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaAssignment'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaAssignment::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaAssignment'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaAssignment::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaAssignment'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaAssignment::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaAssignment'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaAssignment::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaAssignment'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaAssignment::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaAssignment'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaAssignment::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaAssignment'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaElement::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaElement::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaElement::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaElement::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaElement::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaElement::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaElement::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaElement::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaElement::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaElement::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaElement'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaElementAssignment::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaElementAssignment'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaElementAssignment::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaElementAssignment'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaElementAssignment::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaElementAssignment'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaElementAssignment::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaElementAssignment'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaElementAssignment::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaElementAssignment'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaElementAssignment::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaElementAssignment'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaElementAssignment::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaElementAssignment'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaElementAssignment::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaElementAssignment'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaElementAssignment::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaElementAssignment'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaElementAssignment::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaElementAssignment'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaElementSelector::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaElementSelector'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaElementSelector::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaElementSelector'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaElementSelector::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaElementSelector'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaElementSelector::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaElementSelector'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaElementSelector::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaElementSelector'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaElementSelector::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaElementSelector'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaElementSelector::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaElementSelector'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaElementSelector::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaElementSelector'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaElementSelector::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaElementSelector'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaElementSelector::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaElementSelector'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaFolder::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaFolder'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaFolder::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaFolder'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaFolder::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaFolder'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaFolder::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaFolder'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaFolder::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaFolder'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaFolder::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaFolder'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaFolder::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaFolder'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaFolder::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaFolder'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaFolder::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaFolder'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaFolder::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaFolder'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaResizedImage::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaResizedImage'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaResizedImage::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaResizedImage'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaResizedImage::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaResizedImage'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaResizedImage::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaResizedImage'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaResizedImage::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaResizedImage'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaResizedImage::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaResizedImage'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaResizedImage::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaResizedImage'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaResizedImage::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaResizedImage'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaResizedImage::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaResizedImage'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaResizedImage::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaResizedImage'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaSelector::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaSelector'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaSelector::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaSelector'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaSelector::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaSelector'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaSelector::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaSelector'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaSelector::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaSelector'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaSelector::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaSelector'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaSelector::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaSelector'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaSelector::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaSelector'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaSelector::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaSelector'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MediaSelector::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MediaSelector'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Menu::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Menu'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Menu::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Menu'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Menu::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Menu'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Menu::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Menu'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Menu::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Menu'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Menu::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Menu'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Menu::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Menu'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Menu::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Menu'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Menu::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Menu'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Menu::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Menu'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MenuEntry::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MenuEntry'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MenuEntry::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MenuEntry'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MenuEntry::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MenuEntry'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MenuEntry::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MenuEntry'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MenuEntry::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MenuEntry'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MenuEntry::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MenuEntry'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MenuEntry::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MenuEntry'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MenuEntry::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MenuEntry'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MenuEntry::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MenuEntry'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\MenuEntry::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\MenuEntry'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Page::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Page'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Page::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Page'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Page::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Page'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Page::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Page'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Page::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Page'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Page::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Page'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Page::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Page'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Page::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Page'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Page::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Page'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Page::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Page'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\PageRole::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\PageRole'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\PageRole::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\PageRole'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\PageRole::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\PageRole'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\PageRole::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\PageRole'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\PageRole::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\PageRole'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\PageRole::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\PageRole'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\PageRole::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\PageRole'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\PageRole::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\PageRole'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\PageRole::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\PageRole'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\PageRole::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\PageRole'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\PageTemplate::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\PageTemplate'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\PageTemplate::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\PageTemplate'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\PageTemplate::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\PageTemplate'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\PageTemplate::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\PageTemplate'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\PageTemplate::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\PageTemplate'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\PageTemplate::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\PageTemplate'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\PageTemplate::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\PageTemplate'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\PageTemplate::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\PageTemplate'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\PageTemplate::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\PageTemplate'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\PageTemplate::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\PageTemplate'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\PageTemplateContentArea::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\PageTemplateContentArea'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\PageTemplateContentArea::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\PageTemplateContentArea'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\PageTemplateContentArea::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\PageTemplateContentArea'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\PageTemplateContentArea::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\PageTemplateContentArea'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\PageTemplateContentArea::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\PageTemplateContentArea'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\PageTemplateContentArea::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\PageTemplateContentArea'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\PageTemplateContentArea::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\PageTemplateContentArea'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\PageTemplateContentArea::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\PageTemplateContentArea'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\PageTemplateContentArea::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\PageTemplateContentArea'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\PageTemplateContentArea::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\PageTemplateContentArea'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\PublicationData::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\PublicationData'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\PublicationData::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\PublicationData'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\PublicationData::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\PublicationData'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\PublicationData::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\PublicationData'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\PublicationData::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\PublicationData'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\PublicationData::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\PublicationData'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\PublicationData::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\PublicationData'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\PublicationData::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\PublicationData'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\PublicationData::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\PublicationData'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\PublicationData::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\PublicationData'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Survey::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Survey'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Survey::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Survey'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Survey::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Survey'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Survey::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Survey'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Survey::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Survey'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Survey::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Survey'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Survey::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Survey'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Survey::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Survey'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Survey::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Survey'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Survey::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Survey'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveyAnswer::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveyAnswer'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveyAnswer::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveyAnswer'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveyAnswer::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveyAnswer'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveyAnswer::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveyAnswer'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveyAnswer::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveyAnswer'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveyAnswer::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveyAnswer'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveyAnswer::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveyAnswer'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveyAnswer::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveyAnswer'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveyAnswer::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveyAnswer'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveyAnswer::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveyAnswer'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveyEntry::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveyEntry'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveyEntry::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveyEntry'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveyEntry::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveyEntry'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveyEntry::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveyEntry'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveyEntry::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveyEntry'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveyEntry::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveyEntry'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveyEntry::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveyEntry'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveyEntry::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveyEntry'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveyEntry::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveyEntry'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveyEntry::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveyEntry'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveyQuestion::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveyQuestion'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveyQuestion::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveyQuestion'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveyQuestion::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveyQuestion'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveyQuestion::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveyQuestion'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveyQuestion::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveyQuestion'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveyQuestion::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveyQuestion'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveyQuestion::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveyQuestion'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveyQuestion::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveyQuestion'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveyQuestion::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveyQuestion'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveyQuestion::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveyQuestion'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveySurveyAnswer::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveySurveyAnswer'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveySurveyAnswer::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveySurveyAnswer'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveySurveyAnswer::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveySurveyAnswer'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveySurveyAnswer::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveySurveyAnswer'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveySurveyAnswer::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveySurveyAnswer'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveySurveyAnswer::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveySurveyAnswer'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveySurveyAnswer::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveySurveyAnswer'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveySurveyAnswer::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveySurveyAnswer'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveySurveyAnswer::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveySurveyAnswer'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveySurveyAnswer::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveySurveyAnswer'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveySurveyQuestion::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveySurveyQuestion'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveySurveyQuestion::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveySurveyQuestion'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveySurveyQuestion::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveySurveyQuestion'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveySurveyQuestion::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveySurveyQuestion'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveySurveyQuestion::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveySurveyQuestion'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveySurveyQuestion::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveySurveyQuestion'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveySurveyQuestion::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveySurveyQuestion'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveySurveyQuestion::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveySurveyQuestion'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveySurveyQuestion::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveySurveyQuestion'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\SurveySurveyQuestion::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\SurveySurveyQuestion'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ThirdPartyConsent::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ThirdPartyConsent'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ThirdPartyConsent::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ThirdPartyConsent'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ThirdPartyConsent::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ThirdPartyConsent'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ThirdPartyConsent::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ThirdPartyConsent'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ThirdPartyConsent::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ThirdPartyConsent'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ThirdPartyConsent::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ThirdPartyConsent'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ThirdPartyConsent::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ThirdPartyConsent'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ThirdPartyConsent::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ThirdPartyConsent'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ThirdPartyConsent::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ThirdPartyConsent'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\ThirdPartyConsent::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\ThirdPartyConsent'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UrlHistory::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UrlHistory'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UrlHistory::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UrlHistory'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UrlHistory::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UrlHistory'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UrlHistory::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UrlHistory'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UrlHistory::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UrlHistory'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UrlHistory::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UrlHistory'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UrlHistory::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UrlHistory'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UrlHistory::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UrlHistory'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UrlHistory::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UrlHistory'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UrlHistory::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UrlHistory'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UrlsNotFound::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UrlsNotFound'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UrlsNotFound::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UrlsNotFound'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UrlsNotFound::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UrlsNotFound'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UrlsNotFound::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UrlsNotFound'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UrlsNotFound::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UrlsNotFound'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UrlsNotFound::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UrlsNotFound'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UrlsNotFound::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UrlsNotFound'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UrlsNotFound::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UrlsNotFound'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UrlsNotFound::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UrlsNotFound'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UrlsNotFound::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UrlsNotFound'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\User::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\User'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\User::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\User'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\User::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\User'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\User::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\User'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\User::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\User'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\User::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\User'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\User::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\User'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\User::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\User'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\User::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\User'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\User::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\User'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UserConfiguration::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UserConfiguration'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UserConfiguration::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UserConfiguration'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UserConfiguration::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UserConfiguration'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UserConfiguration::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UserConfiguration'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UserConfiguration::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UserConfiguration'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UserConfiguration::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UserConfiguration'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UserConfiguration::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UserConfiguration'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UserConfiguration::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UserConfiguration'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UserConfiguration::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UserConfiguration'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UserConfiguration::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UserConfiguration'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Usergroup::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Usergroup'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Usergroup::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Usergroup'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Usergroup::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Usergroup'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Usergroup::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Usergroup'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Usergroup::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Usergroup'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Usergroup::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Usergroup'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Usergroup::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Usergroup'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Usergroup::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Usergroup'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Usergroup::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Usergroup'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\Usergroup::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\Usergroup'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UsergroupPermission::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UsergroupPermission'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UsergroupPermission::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UsergroupPermission'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UsergroupPermission::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UsergroupPermission'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UsergroupPermission::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UsergroupPermission'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UsergroupPermission::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UsergroupPermission'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UsergroupPermission::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UsergroupPermission'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UsergroupPermission::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UsergroupPermission'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UsergroupPermission::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UsergroupPermission'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UsergroupPermission::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UsergroupPermission'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UsergroupPermission::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UsergroupPermission'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UsergroupsUser::get(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UsergroupsUser'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UsergroupsUser::getError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UsergroupsUser'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UsergroupsUser::getInvalidField(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UsergroupsUser'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UsergroupsUser::getOriginal(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UsergroupsUser'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UsergroupsUser::has(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UsergroupsUser'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UsergroupsUser::hasValue(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UsergroupsUser'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UsergroupsUser::isDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UsergroupsUser'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UsergroupsUser::isEmpty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UsergroupsUser'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UsergroupsUser::setDirty(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UsergroupsUser'),
	);

	expectedArguments(
		\Awyiss\Model\Entity\UsergroupsUser::setError(),
		0,
		argumentsSet('entityFields:\Awyiss\Model\Entity\UsergroupsUser'),
	);

	expectedArguments(
		\__d(),
		0,
		'attributes',
		'audit',
		'authentication',
		'authorization',
		'backend_menu_entries',
		'bake',
		'cake',
		'cake/twig_view',
		'column_system',
		'configuration',
		'content_areas',
		'content_templates',
		'contents',
		'csv_view',
		'customer_groups',
		'customers',
		'dashboard',
		'dashboard_elements',
		'datatables',
		'designs',
		'email_templates',
		'form',
		'form_conditional_recipients',
		'form_elements',
		'form_entries',
		'forms',
		'frontend',
		'generic_datatables',
		'generic_pages',
		'global_content_templates',
		'global_contents',
		'ide_helper',
		'languages',
		'locks',
		'media',
		'media_assignments',
		'media_element_assignments',
		'media_element_selectors',
		'media_elements',
		'media_folders',
		'media_resized_images',
		'media_selectors',
		'menu_entries',
		'menus',
		'migrations',
		'page_roles',
		'page_templates',
		'pages',
		'pagination',
		'publication_data',
		'queue',
		'queued_jobs',
		'route',
		'search',
		'seo',
		'survey_answers',
		'survey_entries',
		'survey_questions',
		'surveys',
		'system',
		'third_party_consents',
		'url_history',
		'urls_not_found',
		'user_configuration',
		'usergroups',
		'users',
		'validation',
		'widgets',
	);

	expectedArguments(
		\__d(),
		1,
		'accept_type',
		'accepted_categories',
		'access',
		'access_helper_button_text',
		'access_helper_dialog_confirm_apply',
		'access_helper_dialog_confirm_cancel',
		'access_helper_dialog_label_permission',
		'access_helper_dialog_label_scope',
		'access_helper_dialog_title',
		'access_type',
		'access_type_all_groups',
		'access_type_available_for_all',
		'access_type_hide_on_login',
		'access_type_specific_groups',
		'account_verification_email_greeting_generic',
		'account_verification_email_greeting_name',
		'account_verification_email_link_text',
		'account_verification_email_text',
		'active',
		'add',
		'add_batch_failed',
		'add_batch_succeeded',
		'add_failed',
		'add_files',
		'add_succeeded',
		'additional_email_settings',
		'address',
		'all',
		'all_answers',
		'allow_custom_answer',
		'alt',
		'altcha_error',
		'analysis_answer',
		'analysis_count',
		'analysis_custom_answers',
		'analysis_percentage',
		'analysis_question_analysis',
		'analysis_total_answers',
		'analysis_total_entries',
		'analysis_total_questions',
		'analysis_visualization',
		'analyze',
		'analyze_assets_css_writable',
		'analyze_assets_css_writable_error_message',
		'analyze_assets_font_writable',
		'analyze_assets_font_writable_error_message',
		'analyze_assets_js_writable',
		'analyze_assets_js_writable_error_message',
		'analyze_awyiss_css_writable',
		'analyze_awyiss_css_writable_error_message',
		'analyze_awyiss_header',
		'analyze_awyiss_js_writable',
		'analyze_awyiss_js_writable_error_message',
		'analyze_cronjob_running',
		'analyze_cronjob_running_error_message',
		'analyze_fix',
		'analyze_frontend_header',
		'analyze_log_writable',
		'analyze_log_writable_error_message',
		'analyze_media_writable',
		'analyze_media_writable_error_message',
		'analyze_tmp_writable',
		'analyze_tmp_writable_error_message',
		'analyze_webroot_not_in_url',
		'analyze_webroot_not_in_url_error_message',
		'answers',
		'apply',
		'as_copy',
		'assigned_page_templates',
		'auto_translate_automatic',
		'auto_translate_disabled',
		'auto_translate_manual',
		'auto_translate_system',
		'auto_translation',
		'average_color',
		'avif',
		'back',
		'back_to_overview',
		'bake_page_roles_seed',
		'bake_seed',
		'batch_edit_failed',
		'batch_edit_succeeded',
		'bcc',
		'bcc_email',
		'bcc_name',
		'body',
		'body_confirmation',
		'button_area_toggle',
		'button_change_password',
		'button_forgot_password',
		'button_login',
		'button_register',
		'button_request_extension',
		'button_request_lock',
		'button_request_renewal',
		'button_reset_password',
		'button_save_changes',
		'button_verify_account',
		'button_verify_reset_code',
		'cancel',
		'cancel_preview',
		'cancel_upload',
		'captcha_info',
		'captcha_label',
		'captcha_legend',
		'captcha_word_number',
		'categories',
		'category_filter_all',
		'category_filter_label',
		'category_filter_unassigned',
		'cc',
		'cc_email',
		'cc_name',
		'changed_by',
		'changed_info_label',
		'changed_on',
		'characters',
		'clear_awyiss_cache',
		'clear_cache',
		'clear_cache_completed',
		'clear_cache_running',
		'clear_full_cache',
		'clear_media_cache',
		'clear_twig_cache',
		'close',
		'color',
		'column_indent',
		'column_last',
		'column_margin',
		'column_rtl',
		'column_span',
		'column_width',
		'column_width_full',
		'completed',
		'components',
		'conditional_recipients_strategy',
		'conditional_recipients_strategy_match_all',
		'conditional_recipients_strategy_match_first',
		'conditional_recipients_strategy_match_last',
		'configuration_category_backend_auto_translate',
		'configuration_category_backend_categories',
		'configuration_category_backend_column_system',
		'configuration_category_backend_contents',
		'configuration_category_backend_forms',
		'configuration_category_backend_interface',
		'configuration_category_backend_lock',
		'configuration_category_backend_media_folders',
		'configuration_category_backend_meta',
		'configuration_category_backend_nest',
		'configuration_category_backend_overview',
		'configuration_category_backend_overview_column_view',
		'configuration_category_backend_paginate',
		'configuration_category_backend_publication_data',
		'configuration_category_backend_system_order',
		'configuration_category_backend_upload',
		'configuration_category_frontend_categories',
		'configuration_category_frontend_emails',
		'configuration_category_frontend_login',
		'configuration_category_frontend_media_folders',
		'configuration_category_frontend_meta',
		'configuration_category_frontend_navigation',
		'configuration_category_frontend_password_reset',
		'configuration_category_frontend_profile',
		'configuration_category_frontend_protection',
		'configuration_category_frontend_publication_data',
		'configuration_category_frontend_registration',
		'configuration_category_frontend_resizing',
		'configuration_category_frontend_route',
		'configuration_identifier_backend_auto_create_menu_entries',
		'configuration_identifier_backend_auto_translate_deepl_api_key',
		'configuration_identifier_backend_auto_translate_google_api_key',
		'configuration_identifier_backend_auto_translate_mode',
		'configuration_identifier_backend_auto_translate_open_ai_api_key',
		'configuration_identifier_backend_auto_translate_open_ai_model',
		'configuration_identifier_backend_auto_translate_translation_service',
		'configuration_identifier_backend_categories_allow_aggregation',
		'configuration_identifier_backend_categories_allow_unassigned',
		'configuration_identifier_backend_categories_association_name',
		'configuration_identifier_backend_categories_categories',
		'configuration_identifier_backend_categories_enabled',
		'configuration_identifier_backend_categories_identifier',
		'configuration_identifier_backend_categories_include_parent_categories',
		'configuration_identifier_backend_categories_threaded',
		'configuration_identifier_backend_categories_use_datasource',
		'configuration_identifier_backend_column_system_class_name',
		'configuration_identifier_backend_column_system_max_columns',
		'configuration_identifier_backend_contents_enabled',
		'configuration_identifier_backend_create_historical_paths',
		'configuration_identifier_backend_forms_enabled',
		'configuration_identifier_backend_handle_images_in_html',
		'configuration_identifier_backend_html_cleaning',
		'configuration_identifier_backend_interface_dark_mode',
		'configuration_identifier_backend_interface_disctraction_free_mode',
		'configuration_identifier_backend_interface_editor',
		'configuration_identifier_backend_interface_highlight_color',
		'configuration_identifier_backend_interface_scale',
		'configuration_identifier_backend_interface_sidebar_mode',
		'configuration_identifier_backend_known_identifiers',
		'configuration_identifier_backend_lock_enabled',
		'configuration_identifier_backend_lock_session_based',
		'configuration_identifier_backend_lock_timeout',
		'configuration_identifier_backend_media_folders_auto_create',
		'configuration_identifier_backend_meta_title_appendix',
		'configuration_identifier_backend_meta_title_separator',
		'configuration_identifier_backend_nest_enabled',
		'configuration_identifier_backend_overview_column_view_enabled',
		'configuration_identifier_backend_overview_displayed_fields',
		'configuration_identifier_backend_paginate_enabled',
		'configuration_identifier_backend_paginate_limit',
		'configuration_identifier_backend_publication_data_enabled',
		'configuration_identifier_backend_split_into_languages',
		'configuration_identifier_backend_system_order_direction',
		'configuration_identifier_backend_system_order_field',
		'configuration_identifier_backend_timezone',
		'configuration_identifier_backend_translatable',
		'configuration_identifier_backend_typography_fixing',
		'configuration_identifier_backend_upload_auto_overwrite',
		'configuration_identifier_frontend_categories_forced_root_page_id',
		'configuration_identifier_frontend_default_breakpoints',
		'configuration_identifier_frontend_editor',
		'configuration_identifier_frontend_emails_sender_email',
		'configuration_identifier_frontend_emails_sender_name',
		'configuration_identifier_frontend_emails_transport_profile',
		'configuration_identifier_frontend_login_enabled',
		'configuration_identifier_frontend_media_folders_parent_folder_id',
		'configuration_identifier_frontend_meta_title_appendix',
		'configuration_identifier_frontend_meta_title_separator',
		'configuration_identifier_frontend_navigation_menu_identifier',
		'configuration_identifier_frontend_password_reset_code_validity',
		'configuration_identifier_frontend_password_reset_enabled',
		'configuration_identifier_frontend_profile_email_change_allowed',
		'configuration_identifier_frontend_protection_methods',
		'configuration_identifier_frontend_publication_data_check_ancestor_pages_publication_status',
		'configuration_identifier_frontend_registration_active_on_registration',
		'configuration_identifier_frontend_registration_default_groups',
		'configuration_identifier_frontend_registration_delete_unverified_accounts',
		'configuration_identifier_frontend_registration_enabled',
		'configuration_identifier_frontend_registration_requires_verification',
		'configuration_identifier_frontend_registration_verification_code_validity',
		'configuration_identifier_frontend_resizing_driver',
		'configuration_identifier_frontend_resizing_file_type',
		'configuration_identifier_frontend_resizing_quality',
		'configuration_identifier_frontend_route_ors_api_key',
		'configuration_identifier_frontend_route_routing_service',
		'configuration_identifier_frontend_timezone',
		'confirm_apply',
		'confirm_apply_no',
		'confirm_apply_title',
		'confirm_apply_yes',
		'confirm_delete',
		'confirm_delete_no',
		'confirm_delete_selected',
		'confirm_delete_selected_title',
		'confirm_delete_title',
		'confirm_delete_yes',
		'confirm_in_use',
		'confirm_multiple_in_use',
		'confirmation_email_template_id',
		'consent_id',
		'content_area_id',
		'content_areas[new][identifier][0]',
		'content_areas[new][title][0]',
		'content_areas_assigned',
		'content_areas_available',
		'content_elements_assigned',
		'content_elements_available',
		'content_from_external_provider',
		'content_template_id',
		'content_templates',
		'copy_descendants_with_different_page_role',
		'copy_descendants_with_different_page_role_no',
		'copy_descendants_with_different_page_role_yes',
		'counter',
		'create',
		'create_enum',
		'create_historical_paths_always',
		'create_historical_paths_disabled',
		'create_historical_paths_file_name_change',
		'create_historical_paths_folder_name_change',
		'create_page_role_model',
		'create_redirect',
		'created',
		'created_by',
		'created_info_label',
		'created_on',
		'credentials_missing',
		'crop',
		'crop_height',
		'crop_width',
		'cropping_in_progress',
		'css',
		'css_class',
		'css_dialog_close',
		'css_dialog_info',
		'css_dialog_title',
		'current_password',
		'current_settings',
		'current_value',
		'custom_answer',
		'custom_answer_label',
		'custom_font_name',
		'customer_group_filter_all',
		'customer_group_filter_label',
		'customer_group_filter_unassigned',
		'customer_groups',
		'customer_groups_assigned',
		'customer_groups_available',
		'customers',
		'dark_mode_switch_off',
		'dark_mode_switch_on',
		'data',
		'date_format',
		'date_operator_last24_hours',
		'date_operator_last_month',
		'date_operator_last_week',
		'date_operator_last_year',
		'date_operator_since_last_login',
		'date_operator_this_month',
		'date_operator_this_week',
		'date_operator_this_year',
		'date_operator_today',
		'date_operator_yesterday',
		'date_time_end',
		'date_time_format_help_text',
		'date_time_start',
		'default',
		'default_value',
		'delete',
		'delete_failed',
		'delete_failed_in_progress',
		'delete_old_failed',
		'delete_old_succeeded',
		'delete_older_all',
		'delete_older_than',
		'delete_older_than_one_month',
		'delete_older_than_one_week',
		'delete_older_than_one_year',
		'delete_succeeded',
		'deleted_by',
		'deleted_on',
		'description',
		'description_empty',
		'design_preview_mode_cancel',
		'design_preview_mode_label',
		'detect_available_commands',
		'driver_gd',
		'driver_imagick',
		'drop_datatables_table',
		'drop_table',
		'dropzone_drop_to_upload',
		'duplicate_of',
		'duplicate_of_page_id',
		'edit',
		'edit_css',
		'edit_failed',
		'edit_succeeded',
		'email',
		'email_subject_password_reset',
		'email_subject_verify_account',
		'email_template_id',
		'embed_id',
		'error_account_not_verified',
		'error_account_verification_failed',
		'error_ascii',
		'error_assignment_allows_entity_level',
		'error_assignment_allows_model_level',
		'error_assignment_invalid_entity',
		'error_children_not_duplicating_contents_on_same_page',
		'error_circular_duplicating',
		'error_compare_fields',
		'error_compare_with',
		'error_config_option_is_personalizable',
		'error_config_owned_by_user',
		'error_confirmation_email_template_exists',
		'error_content_area_exists',
		'error_content_template_exists',
		'error_customer_exists',
		'error_customer_group_exists',
		'error_date',
		'error_datetime',
		'error_deleted_not_modified',
		'error_duplicate_not_on_same_page',
		'error_email',
		'error_email_required',
		'error_email_send',
		'error_email_template_exists',
		'error_email_unique',
		'error_email_verification_failed',
		'error_enum',
		'error_equal_to',
		'error_exact_length',
		'error_exists_in',
		'error_file_name_unique',
		'error_files_in_use',
		'error_form_id_set_when_required',
		'error_identifier_allowed',
		'error_identifier_unchanged',
		'error_identifier_unique',
		'error_identifier_unique_for_media_element',
		'error_identifier_unique_for_scope',
		'error_in_list',
		'error_in_use',
		'error_invalid_login',
		'error_invalid_reset_code',
		'error_invalid_verification_code',
		'error_is_integer',
		'error_language_exists',
		'error_link_constraint_rule',
		'error_linked_contents',
		'error_linked_form_confirmation_emails',
		'error_linked_form_emails',
		'error_linked_global_contents',
		'error_linked_media_assignments',
		'error_linked_media_element_selectors',
		'error_linked_pages',
		'error_linked_surveys',
		'error_max_file_size',
		'error_max_length',
		'error_media_element_unique_for_scope',
		'error_media_exists',
		'error_media_has_file_extension',
		'error_media_mime_type_matches_extension',
		'error_mimetype_not_modified',
		'error_min_length',
		'error_no_circular_references',
		'error_no_duplicated_children',
		'error_no_duplicated_contents',
		'error_no_duplicating_contents',
		'error_no_duplicating_pages',
		'error_no_invalid_next_actions',
		'error_no_linked_page_templates',
		'error_no_linked_pages',
		'error_no_nested_children_with_different_page_role',
		'error_no_repeated_questions_in_linear_survey',
		'error_not_authenticated',
		'error_not_blank',
		'error_not_boolean',
		'error_not_default_element_deletion',
		'error_not_default_selector_deletion',
		'error_not_duplicating_duplicated',
		'error_not_duplicating_duplicating',
		'error_not_empty',
		'error_not_in_use',
		'error_not_last_active_language_in_realm_on_deactivate',
		'error_not_last_active_language_in_realm_on_realm_change',
		'error_not_last_language_in_realm',
		'error_not_nested_under_root',
		'error_not_page_role_page_deletion',
		'error_not_root_deletion',
		'error_not_same_as',
		'error_not_self_duplicating',
		'error_option_not_localizable',
		'error_option_not_nullable',
		'error_option_not_translatable_when_split_into_languages',
		'error_option_when_split_into_languages_when_translatable',
		'error_page_template_content_area_exists',
		'error_page_template_exists',
		'error_password_change_failed',
		'error_password_incorrect',
		'error_password_mismatch',
		'error_password_reset_disabled',
		'error_password_reset_failed',
		'error_password_too_short',
		'error_profile_update_failed',
		'error_record_not_found',
		'error_registration_disabled',
		'error_registration_failed',
		'error_required',
		'error_required_multicheckbox',
		'error_reserved_identifier',
		'error_root_active_unchanged',
		'error_root_hidden_unchanged',
		'error_root_language_shortcode_unchanged',
		'error_root_parent_id_unchanged',
		'error_root_path_unchanged',
		'error_root_title_unchanged',
		'error_same_as',
		'error_sending_password_reset_email',
		'error_sending_verification_email',
		'error_shortcode_unique_for_realm',
		'error_subfolders_hidden',
		'error_time',
		'error_title_unique',
		'error_transport_profile_exists',
		'error_unique',
		'error_url',
		'error_user_exists',
		'error_user_id_unchanged',
		'error_usergroup_exists',
		'error_username_unique',
		'error_valid_captcha',
		'error_valid_content_area_id',
		'error_valid_content_area_id_for_children',
		'error_valid_content_areas',
		'error_valid_content_elements',
		'error_valid_content_template_id',
		'error_valid_count',
		'error_valid_duplicate_of',
		'error_valid_fieldset',
		'error_valid_final_action',
		'error_valid_foreign_key',
		'error_valid_form_id',
		'error_valid_input_type',
		'error_valid_layout',
		'error_valid_locale',
		'error_valid_media_folder_id',
		'error_valid_page_id',
		'error_valid_page_role',
		'error_valid_page_template',
		'error_valid_parent_id',
		'error_valid_realm',
		'error_valid_status',
		'error_valid_survey_answer_id',
		'error_valid_survey_id',
		'error_valid_survey_question_id',
		'error_valid_survey_survey_question_id',
		'error_valid_timezone',
		'error_valid_value',
		'error_valid_width_indent_combination',
		'export',
		'export_entries',
		'export_format',
		'export_languages',
		'external',
		'failed_attempts',
		'failure_message',
		'false',
		'field',
		'fieldset',
		'fieldset_access_type',
		'fieldset_answers',
		'fieldset_assignable_models',
		'fieldset_attributes',
		'fieldset_backend',
		'fieldset_colors',
		'fieldset_conditions',
		'fieldset_confirmation_email',
		'fieldset_content',
		'fieldset_crop',
		'fieldset_customer_groups',
		'fieldset_customer_groups_access_type',
		'fieldset_customers',
		'fieldset_data',
		'fieldset_details',
		'fieldset_email',
		'fieldset_filter_settings',
		'fieldset_fonts',
		'fieldset_frontend',
		'fieldset_general',
		'fieldset_list_fields',
		'fieldset_list_sort',
		'fieldset_media',
		'fieldset_media_selectors',
		'fieldset_menus',
		'fieldset_options',
		'fieldset_permissions',
		'fieldset_presentation',
		'fieldset_publication',
		'fieldset_questions_assigned',
		'fieldset_questions_available',
		'fieldset_quick_actions',
		'fieldset_seo',
		'fieldset_summary',
		'fieldset_usergroups',
		'fieldset_users',
		'fieldset_variables',
		'file',
		'file_changes',
		'file_changes_in_progress',
		'file_name',
		'filter_active',
		'filter_add_column',
		'filter_close',
		'filter_headline',
		'filter_operator',
		'filter_remove_column',
		'filter_reset',
		'filter_settings',
		'filter_show',
		'filter_submit',
		'final_action',
		'first',
		'first_occurrence',
		'firstname',
		'focus_point',
		'font_name_alternative',
		'font_name_main',
		'font_size_alternative',
		'font_size_clamp_main',
		'font_size_main',
		'font_stack_fallback_alternative',
		'font_stack_fallback_main',
		'font_style_alternative',
		'font_style_main',
		'font_weight_alternative',
		'font_weight_main',
		'foreign_key',
		'form_conditional_recipients',
		'form_filter_all',
		'form_filter_label',
		'form_has_errors',
		'form_id',
		'form_leave_confirmation_dialog_confirm_leave',
		'form_leave_confirmation_dialog_confirm_stay',
		'form_leave_confirmation_dialog_message',
		'form_leave_confirmation_dialog_title',
		'form_template',
		'form_template_appointment_form',
		'form_template_callback_form',
		'form_template_contact_form',
		'form_template_datetime',
		'form_template_email',
		'form_template_firstname',
		'form_template_job_application_file',
		'form_template_job_application_files_text',
		'form_template_job_application_form',
		'form_template_lastname',
		'form_template_message',
		'form_template_name',
		'form_template_optional_placeholder',
		'form_template_phone',
		'form_template_privacy_accepted',
		'form_template_privacy_accepted_text',
		'form_template_privacy_accepted_yes',
		'form_template_submit',
		'form_template_title',
		'form_template_title_diverse',
		'form_template_title_mr',
		'form_template_title_ms',
		'forms_overview',
		'free_answer_label',
		'frontend_preview',
		'geocode_address',
		'geocode_address_found',
		'geocode_error_address',
		'geocode_error_api_key_missing',
		'geocode_multiple_results_found',
		'geocode_multiple_results_found_title',
		'global',
		'global_content_elements_assigned',
		'global_content_elements_available',
		'global_content_template_id',
		'global_language',
		'global_value',
		'group_entries',
		'has_index',
		'headline_add',
		'headline_add_batch',
		'headline_analyze',
		'headline_batch_edit',
		'headline_clear_cache',
		'headline_diagram',
		'headline_duplicate_configuration',
		'headline_edit',
		'headline_error_400',
		'headline_error_500',
		'headline_history',
		'headline_media_folder_select',
		'headline_overview',
		'headline_preview',
		'headline_saved_designs',
		'headline_status_cell',
		'headline_structure',
		'headline_structure_error_h1_missing',
		'headline_structure_error_h1_not_first',
		'headline_structure_status_error',
		'headline_structure_status_ok',
		'headline_structure_status_warning',
		'headline_structure_summary_error',
		'headline_structure_summary_warning',
		'headline_structure_warning_h2_missing',
		'headline_structure_warning_multiple_h1',
		'headline_structure_warning_too_many_h2',
		'headline_structure_warning_wrong_headline_order',
		'headline_usages',
		'headline_widget_configuration',
		'height',
		'hidden',
		'history_entry_info',
		'homepage_id',
		'html_cleaning_moderate',
		'html_cleaning_none',
		'html_cleaning_strict',
		'id',
		'identifier',
		'identifier_interface_dark_mode',
		'in_content_row',
		'in_use',
		'inactive',
		'include_current_page',
		'include_homepage',
		'include_in_linklist',
		'include_inaccessible_pages',
		'input_type',
		'input_type_bool_label',
		'input_type_checkbox',
		'input_type_color',
		'input_type_date',
		'input_type_datetime',
		'input_type_hidden',
		'input_type_input_key_value_list',
		'input_type_input_list',
		'input_type_multicheckbox',
		'input_type_password',
		'input_type_select',
		'input_type_select_multiple',
		'input_type_text',
		'input_type_textarea',
		'input_type_texteditor',
		'input_type_time',
		'insert_after_id',
		'interface_editor_jodit',
		'interface_editor_plain',
		'interface_editor_tinymce',
		'interface_scale_medium',
		'interface_scale_regular',
		'interface_scale_small',
		'internal',
		'ip_hash',
		'is_preview',
		'is_robot',
		'italic',
		'items_per_page',
		'javascript_is_required_to_display_this_content',
		'jump_to_content',
		'label_boolean_all',
		'label_boolean_false',
		'label_boolean_true',
		'language_filter_label',
		'language_shortcode',
		'last',
		'last_login',
		'last_occurrence',
		'lastname',
		'lat',
		'layout',
		'legend_login',
		'length',
		'limit_per_page',
		'line_height_alternative',
		'line_height_main',
		'link',
		'link_change_password',
		'link_customer_center',
		'link_dashboard',
		'link_edit_profile',
		'link_forgot_password',
		'link_helper_button_text',
		'link_helper_dialog_confirm_apply',
		'link_helper_dialog_confirm_cancel',
		'link_helper_dialog_label_controller',
		'link_helper_dialog_label_method',
		'link_helper_dialog_label_url_param_add',
		'link_helper_dialog_label_url_param_remove',
		'link_helper_dialog_label_url_params',
		'link_helper_dialog_title',
		'link_login',
		'link_logout',
		'link_register',
		'link_reset_password',
		'link_save_changes',
		'list_fields',
		'list_item_add',
		'list_item_remove',
		'list_nested',
		'list_paginated',
		'list_sort',
		'lng',
		'load_content',
		'load_settings',
		'locale',
		'lock_warning',
		'lock_warning_minutes_plural',
		'lock_warning_minutes_singular',
		'lock_warning_timed_out',
		'locked_message',
		'logged_in_as',
		'login',
		'login_failed',
		'login_logo',
		'logout',
		'media_element',
		'media_element_id',
		'media_element_inline_img_tag',
		'media_element_selector_identifier',
		'media_elements_assigned',
		'media_elements_available',
		'media_folder_filter_label',
		'media_folder_global',
		'media_folder_hidden',
		'media_folder_id',
		'media_folders_toggle',
		'media_id',
		'media_selector_id',
		'media_usages_inaccessible',
		'menu_add',
		'menu_add_batch',
		'menu_analyze',
		'menu_batch_edit',
		'menu_breakpoint',
		'menu_configure',
		'menu_filter_label',
		'menu_id',
		'menu_overview',
		'menu_title',
		'menu_title_templates',
		'menus_overview',
		'message_account_verified',
		'message_logout_success',
		'message_password_changed',
		'message_password_reset_sent',
		'message_password_reset_success',
		'message_profile_updated',
		'message_registration_success',
		'message_registration_success_verification_required',
		'meta_data',
		'meta_description',
		'meta_description_length_empty',
		'meta_description_length_error',
		'meta_description_length_ok',
		'meta_description_length_warning',
		'meta_description_summary_error',
		'meta_description_summary_warning',
		'meta_title',
		'meta_title_add',
		'meta_title_add_batch',
		'meta_title_analyze',
		'meta_title_auto',
		'meta_title_batch_edit',
		'meta_title_clear_cache',
		'meta_title_diagram',
		'meta_title_duplicate_configuration',
		'meta_title_edit',
		'meta_title_history',
		'meta_title_length_empty',
		'meta_title_length_error',
		'meta_title_length_ok',
		'meta_title_length_warning',
		'meta_title_login',
		'meta_title_overview',
		'meta_title_preview',
		'meta_title_summary_error',
		'meta_title_summary_warning',
		'meta_title_unique_warning',
		'meta_title_usages',
		'meta_title_view',
		'meta_title_view_confirmation',
		'meta_title_widget_configuration',
		'mime_type',
		'mostly_used_words',
		'multistep',
		'name',
		'new_password',
		'new_value',
		'next',
		'next_action',
		'next_action_abort',
		'next_action_form',
		'next_action_inherit_form',
		'next_action_next_question',
		'next_action_question',
		'next_action_save_and_end',
		'next_action_save_and_show_form',
		'next_action_show_form',
		'next_action_show_form_and_save',
		'next_action_specific_question',
		'no_audit_entries',
		'no_records',
		'normal',
		'number_of_items',
		'occurrences',
		'offset',
		'old_value',
		'operator',
		'operator_between',
		'operator_contains',
		'operator_ends_with',
		'operator_equal',
		'operator_greater_than',
		'operator_greater_than_or_equal',
		'operator_in',
		'operator_length_equal',
		'operator_length_not_equal',
		'operator_less_than',
		'operator_less_than_or_equal',
		'operator_longer_than',
		'operator_longer_than_or_equal',
		'operator_not_between',
		'operator_not_contains',
		'operator_not_ends_with',
		'operator_not_equal',
		'operator_not_in',
		'operator_not_starts_with',
		'operator_regexp',
		'operator_shorter_than',
		'operator_shorter_than_or_equal',
		'operator_starts_with',
		'option_key',
		'option_value',
		'options',
		'overview',
		'overview_list_linear',
		'overview_list_with_columns',
		'owner_email',
		'owner_name',
		'page',
		'page_filter_label',
		'page_id',
		'page_padding',
		'page_role_filter_all',
		'page_role_filter_label',
		'page_role_id',
		'page_role_title',
		'page_template_id',
		'page_title_change_password',
		'page_title_dashboard',
		'page_title_edit_profile',
		'page_title_forgot_password',
		'page_title_login',
		'page_title_register',
		'page_title_reset_password',
		'page_title_verify_account',
		'page_width',
		'pages',
		'paginate',
		'parent_id',
		'parents_active',
		'password',
		'password_confirm',
		'password_reset_code',
		'password_reset_email_greeting_generic',
		'password_reset_email_greeting_name',
		'password_reset_email_link_text',
		'password_reset_email_text',
		'password_reset_on',
		'path',
		'permission_analyze',
		'permission_configure',
		'permission_contents',
		'permission_create',
		'permission_delete',
		'permission_load',
		'permission_read',
		'permission_restart_failed',
		'permission_save',
		'permission_update',
		'permission_use',
		'php_array',
		'pick_question',
		'placeholder',
		'placeholder_data',
		'placeholder_help_text',
		'post_hash',
		'preview',
		'preview_mode',
		'preview_mode_disable',
		'preview_mode_label',
		'preview_mode_mark_inactive_elements',
		'previous',
		'profile_name',
		'protection',
		'protection_method_altcha',
		'protection_method_duplicate_check',
		'protection_method_duplicate_check_error_duplicate_found',
		'protection_method_hidden_input',
		'protection_method_hidden_input_error_field_empty',
		'protection_method_ip_check',
		'protection_method_ip_check_error_duplicate_found',
		'protection_method_none',
		'question_filter_label',
		'question_type_free_text',
		'question_type_info_text',
		'question_type_multiple_choice',
		'question_type_single_choice',
		'questions_diagram',
		'quick_action_add_page',
		'quick_action_user_configuration',
		'read_more',
		'real_height',
		'real_width',
		'realm',
		'realm_backend',
		'realm_frontend',
		'recipient',
		'record_not_found',
		'redirect_link',
		'reference',
		'reference_column',
		'referrer',
		'rejected_categories',
		'remember_consent',
		'remove_answer',
		'remove_file',
		'remove_question',
		'required',
		'reset',
		'reset_code',
		'resize_height',
		'resize_media_file_type_avif',
		'resize_media_file_type_match_source',
		'resize_media_file_type_webp',
		'resize_width',
		'restart_failed',
		'restart_failed_not_failed',
		'restart_failed_task',
		'restart_succeeded',
		'revert_value',
		'robots_follow',
		'robots_index',
		'robots_no_follow',
		'robots_no_index',
		'route_planner_calculate_route',
		'route_planner_directions_found',
		'route_planner_end',
		'route_planner_error_access',
		'route_planner_error_api_key_missing',
		'route_planner_error_end_coordinates',
		'route_planner_error_start_coordinates',
		'route_planner_multiple_results_found',
		'route_planner_no_directions_found',
		'route_planner_reset',
		'route_planner_route_summary_distance',
		'route_planner_route_summary_duration',
		'route_planner_route_summary_end',
		'route_planner_route_summary_start',
		'route_planner_start',
		'route_planner_start_choices_close',
		'route_planner_start_placeholder',
		'salutation',
		'salutation_confirmation',
		'salutation_confirmation_placeholder',
		'save',
		'save_close',
		'save_entry_failed',
		'save_failed',
		'save_settings',
		'save_settings_no',
		'save_settings_title',
		'save_settings_yes',
		'save_succeeded',
		'save_system_order',
		'scope',
		'scope_filter_label',
		'scope_media',
		'scope_pages',
		'section_account_info',
		'section_account_options',
		'section_welcome',
		'select_empty_label',
		'select_file',
		'select_filter_placeholder',
		'send_confirmation_email',
		'send_email',
		'seo_analyze',
		'service',
		'service_instagram',
		'service_vimeo',
		'service_youtube',
		'settings',
		'shortcode',
		'show_columns',
		'show_diagram',
		'show_history',
		'show_on_homepage',
		'show_password',
		'show_transportation_modes',
		'sidebar_toggle',
		'simple_permission_option_denied',
		'simple_permission_option_granted',
		'simple_permission_option_indifferent',
		'single_column_breakpoint',
		'slug',
		'sort_asc',
		'sort_desc',
		'status',
		'status_301',
		'status_302',
		'status_307',
		'status_308',
		'strategy',
		'subject',
		'subject_confirmation',
		'subject_confirmation_placeholder',
		'subtitle',
		'subtitle_error_400',
		'subtitle_error_500',
		'subtitle_tag',
		'success_message',
		'summarize_errors',
		'survey_id',
		'survey_question_id',
		'survey_questions_overview',
		'survey_type_configurator',
		'survey_type_linear',
		'surveys_overview',
		'system_order',
		'system_order_after',
		'system_order_first',
		'system_order_manual_order_is_disabled',
		'system_order_not_enabled',
		'system_order_not_saved',
		'system_order_saved',
		'system_overview_category_analyze',
		'system_overview_category_configuration',
		'system_overview_category_extend',
		'system_overview_category_templates',
		'system_overview_category_users_usergroups',
		'table_changes',
		'table_changes_in_progress',
		'target',
		'task_failed',
		'text',
		'text_already_registered',
		'text_enter_new_password',
		'text_html',
		'text_length',
		'text_length_error_too_few_words',
		'text_length_error_too_many_words',
		'text_length_status_error',
		'text_length_status_ok',
		'text_length_status_warning',
		'text_length_summary_error',
		'text_length_summary_warning',
		'text_length_warning_too_few_words',
		'text_length_warning_too_many_words',
		'text_not_registered_yet',
		'text_plain',
		'text_verify_account_confirmation',
		'time_format',
		'timezone',
		'timezone_automatic',
		'title',
		'title_email',
		'title_empty',
		'title_tag',
		'toggle_configuration',
		'translatable',
		'translations_button_title',
		'translations_dialog_apply',
		'translations_dialog_cancel',
		'translations_dialog_title',
		'transport_profile',
		'transport_profile_debug',
		'transport_profile_default',
		'transport_profile_smtp',
		'transportation_mode',
		'transportation_mode_bike',
		'transportation_mode_car',
		'transportation_mode_foot',
		'true',
		'type',
		'type_checkbox',
		'type_current_page',
		'type_date',
		'type_datetime',
		'type_element_identifier',
		'type_email',
		'type_fieldset',
		'type_file',
		'type_free_text',
		'type_hidden',
		'type_number',
		'type_radio',
		'type_range',
		'type_select',
		'type_select_multiple',
		'type_submit',
		'type_tel',
		'type_text',
		'type_textarea',
		'type_time',
		'type_url',
		'unassigned',
		'unique_respondents',
		'unknown_content_area',
		'unknown_entity',
		'unknown_file',
		'unused_questions',
		'update_preview',
		'upload_overwrite_existing_files',
		'upload_overwrite_existing_files_active',
		'upload_overwrite_existing_files_inactive',
		'upload_queue_actions',
		'upload_queue_name',
		'upload_queue_status',
		'url',
		'usage_count',
		'use_files',
		'used_for_confirmation_emails',
		'used_for_contents',
		'used_for_emails',
		'used_for_global_contents',
		'used_for_pages',
		'user_email',
		'user_email_placeholder',
		'user_name',
		'user_name_placeholder',
		'user_system',
		'user_unknown',
		'usergroup_filter_all',
		'usergroup_filter_label',
		'usergroup_filter_unassigned',
		'usergroup_permissions',
		'usergroups',
		'username',
		'users',
		'value',
		'verification_code',
		'verified',
		'verified_on',
		'view_answers',
		'view_confirmation_email',
		'view_contents',
		'view_data',
		'view_elements',
		'view_email',
		'view_entries',
		'view_files',
		'view_gallery',
		'view_on_instagram',
		'view_on_vimeo',
		'view_on_youtube',
		'webfont_download',
		'webp',
		'widget_identifier',
		'width',
		'zoom_in',
		'zoom_out',
		'{0,number,#,###.##} GB',
		'{0,number,#,###.##} KB',
		'{0,number,#,###.##} MB',
		'{0,number,#,###.##} TB',
		'{0,number,integer} Byte',
		'{0,number,integer} Bytes',
	);

	expectedArguments(
		\env(),
		0,
		'CGI_MODE',
		'CONFIG_ENV',
		'CONTENT_LENGTH',
		'CONTENT_TYPE',
		'CUSTOM_DIR',
		'DOCUMENT_ROOT',
		'DOCUMENT_URI',
		'GATEWAY_INTERFACE',
		'HOME',
		'HTTPS',
		'HTTP_ACCEPT',
		'HTTP_ACCEPT_ENCODING',
		'HTTP_ACCEPT_LANGUAGE',
		'HTTP_CONNECTION',
		'HTTP_COOKIE',
		'HTTP_HOST',
		'HTTP_USER_AGENT',
		'LANG',
		'LOGNAME',
		'LS_COLORS',
		'MAIL',
		'PATH',
		'PATH_TRANSLATED',
		'PHP_SELF',
		'PWD',
		'QUERY_STRING',
		'REDIRECT_STATUS',
		'REMOTE_ADDR',
		'REMOTE_PORT',
		'REQUEST_METHOD',
		'REQUEST_SCHEME',
		'REQUEST_TIME',
		'REQUEST_TIME_FLOAT',
		'REQUEST_URI',
		'SCRIPT_FILENAME',
		'SCRIPT_NAME',
		'SECURITY_SALT',
		'SERVER_NAME',
		'SERVER_PORT',
		'SERVER_PROTOCOL',
		'SESSION_COOKIE_NAME',
		'SHELL',
		'SUDO_COMMAND',
		'SUDO_GID',
		'SUDO_HOME',
		'SUDO_UID',
		'SUDO_USER',
		'TERM',
		'USER',
		'argc',
		'argv',
	);

	expectedArguments(
		\urlArray(),
		0,
		argumentsSet('routePaths'),
	);

	registerArgumentsSet(
		'cacheEngines',
		'_cake_model_',
		'_cake_translations_',
		'default',
		'instagram',
		'persistent',
	);

	registerArgumentsSet(
		'columnNames',
		'acceptType',
		'acceptedCategories',
		'access',
		'accessType',
		'active',
		'allowCustomAnswer',
		'alt',
		'attempts',
		'averageColor',
		'avif',
		'bcc',
		'body',
		'bodyConfirmation',
		'cc',
		'changedBy',
		'changedOn',
		'columnIndent',
		'columnLast',
		'columnRtl',
		'columnSpan',
		'columnWidth',
		'completed',
		'conditionalRecipientsStrategy',
		'confirmationEmailTemplateId',
		'consentId',
		'contentAreaId',
		'contentId',
		'contentTemplateId',
		'created',
		'createdBy',
		'createdOn',
		'crop',
		'css',
		'cssClass',
		'customAnswerTitle',
		'customerGroupId',
		'customerId',
		'data',
		'dataNew',
		'dataOld',
		'date',
		'dateFormat',
		'dateTime',
		'datetime',
		'days',
		'defaultValue',
		'deleted',
		'deletedBy',
		'deletedOn',
		'description',
		'diff',
		'duplicateOf',
		'email',
		'emailTemplateId',
		'external',
		'failedAttempts',
		'failureMessage',
		'failure_message',
		'fetched',
		'field',
		'fieldset',
		'fileName',
		'finalAction',
		'firstname',
		'focusPoint',
		'foreignKey',
		'formId',
		'globalContentId',
		'globalContentTemplateId',
		'hasIndex',
		'height',
		'hidden',
		'id',
		'identifier',
		'inContentRow',
		'inUse',
		'includeInLinklist',
		'inputType',
		'insertAfterId',
		'internal',
		'ipHash',
		'isPreview',
		'isRobot',
		'job_group',
		'job_task',
		'languageShortcode',
		'lastLogin',
		'lastname',
		'layout',
		'link',
		'locale',
		'mediaElementId',
		'mediaElementSelectorIdentifier',
		'mediaFolderId',
		'mediaId',
		'mediaSelectorId',
		'memory',
		'menuId',
		'metaData',
		'metaDescription',
		'metaTitle',
		'mimeType',
		'modified',
		'multistep',
		'name',
		'nextAction',
		'nextActionTarget',
		'notbefore',
		'operator',
		'options',
		'output',
		'ownerEmail',
		'ownerName',
		'pageId',
		'pageRoleId',
		'pageTemplateId',
		'parentId',
		'parentsActive',
		'password',
		'passwordResetCode',
		'passwordResetOn',
		'path',
		'pid',
		'placeholder',
		'postHash',
		'preview',
		'priority',
		'progress',
		'realHeight',
		'realWidth',
		'realm',
		'recipient',
		'redirectLink',
		'reference',
		'referrer',
		'rejectedCategories',
		'required',
		'robotsFollow',
		'robotsIndex',
		'salutation',
		'salutationConfirmation',
		'scope',
		'sendConfirmationEmail',
		'sendEmail',
		'server',
		'settings',
		'shortcode',
		'slug',
		'status',
		'strategy',
		'subject',
		'subjectConfirmation',
		'subjectLeftForeignKey',
		'subjectLeftTable',
		'subjectRightForeignKey',
		'subjectRightTable',
		'subtitle',
		'subtitleTag',
		'successMessage',
		'summarizeErrors',
		'surveyAnswerId',
		'surveyId',
		'surveyQuestionId',
		'surveySurveyQuestionId',
		'systemOrder',
		'target',
		'terminate',
		'text',
		'textHtml',
		'textPlain',
		'timeFormat',
		'timezone',
		'title',
		'titleEmail',
		'titleTag',
		'transactionId',
		'translatable',
		'transportProfile',
		'type',
		'uniqueId',
		'url',
		'userEmail',
		'userId',
		'userName',
		'usergroupId',
		'username',
		'value',
		'verificationCode',
		'verified',
		'verifiedOn',
		'webp',
		'width',
		'workerkey',
	);

	registerArgumentsSet(
		'columnTypes',
		'biginteger',
		'binary',
		'binaryuuid',
		'bit',
		'blob',
		'boolean',
		'char',
		'date',
		'datetime',
		'datetimefractional',
		'decimal',
		'double',
		'enum',
		'float',
		'geometry',
		'integer',
		'json',
		'linestring',
		'longblob',
		'mediumblob',
		'nativeuuid',
		'point',
		'polygon',
		'set',
		'smallinteger',
		'string',
		'text',
		'time',
		'timestamp',
		'timestampfractional',
		'timestamptimezone',
		'tinyblob',
		'tinyinteger',
		'uuid',
		'varbinary',
		'year',
	);

	registerArgumentsSet(
		'configureKeys',
		'App',
		'App.base',
		'App.defaultLocale',
		'App.defaultTimezone',
		'App.dir',
		'App.encoding',
		'App.fullBaseUrl',
		'App.namespace',
		'App.paths',
		'App.paths.assets',
		'App.paths.assets.Backend',
		'App.paths.assets.Backend.awyiss',
		'App.paths.assets.Backend.customer',
		'App.paths.assets.Frontend',
		'App.paths.assets.Frontend.customer',
		'App.paths.locales',
		'App.paths.locales.awyiss',
		'App.paths.locales.customer',
		'App.paths.plugins',
		'App.paths.plugins.awyiss',
		'App.paths.plugins.customer',
		'App.paths.templates',
		'App.paths.templates.awyiss',
		'App.paths.templates.customer',
		'App.webroot',
		'App.wwwRoot',
		'AvailableCommands',
		'Datasources',
		'Datasources.default',
		'Datasources.default.cacheMetadata',
		'Datasources.default.className',
		'Datasources.default.database',
		'Datasources.default.driver',
		'Datasources.default.flags',
		'Datasources.default.host',
		'Datasources.default.log',
		'Datasources.default.password',
		'Datasources.default.persistent',
		'Datasources.default.port',
		'Datasources.default.quoteIdentifiers',
		'Datasources.default.timezone',
		'Datasources.default.username',
		'Debugger',
		'Debugger.editor',
		'Design',
		'Design.allowCompile',
		'Design.allowGoogleFonts',
		'Design.autoCompile',
		'Design.blocklistedVariables',
		'Design.fontStacks',
		'Design.fontStacks.display',
		'Design.fontStacks.handwriting',
		'Design.fontStacks.monospace',
		'Design.fontStacks.sans-serif',
		'Design.fontStacks.serif',
		'Design.previewScssFiles',
		'Design.scssFiles',
		'Design.units',
		'Design.units.%',
		'Design.units.%.range',
		'Design.units.%.range.max',
		'Design.units.%.range.min',
		'Design.units.%.step',
		'Design.units.px',
		'Design.units.px.range',
		'Design.units.px.range.max',
		'Design.units.px.range.min',
		'Design.units.px.step',
		'Design.units.rem',
		'Design.units.rem.range',
		'Design.units.rem.range.max',
		'Design.units.rem.range.min',
		'Design.units.rem.step',
		'Design.units.vw',
		'Design.units.vw.range',
		'Design.units.vw.range.max',
		'Design.units.vw.range.min',
		'Design.units.vw.step',
		'Design.variableMapping',
		'Design.variableMapping.([a-z]\w+)Breakpoint',
		'Design.variableMapping.([a-z]\w+)Breakpoint.forcedUnit',
		'Design.variableMapping.([a-z]\w+)Breakpoint.inputType',
		'Design.variableMapping.([a-z]\w+)Breakpoint.stripUnit',
		'Design.variableMapping.([a-z]\w+)Breakpoint.type',
		'Design.variableMapping.([a-z]\w+)Breakpoint.units',
		'Design.variableMapping.([a-z]\w+)Breakpoint.units.px',
		'Design.variableMapping.([a-z]\w+)Breakpoint.units.px.range',
		'Design.variableMapping.([a-z]\w+)Breakpoint.units.px.range.max',
		'Design.variableMapping.([a-z]\w+)Breakpoint.units.px.range.min',
		'Design.variableMapping.([a-z]\w+)Margin',
		'Design.variableMapping.([a-z]\w+)Margin.inputType',
		'Design.variableMapping.([a-z]\w+)Margin.type',
		'Design.variableMapping.([a-z]\w+)Padding',
		'Design.variableMapping.([a-z]\w+)Padding.inputType',
		'Design.variableMapping.([a-z]\w+)Padding.type',
		'Design.variableMapping.([a-z]\w+)Padding.units',
		'Design.variableMapping.([a-z]\w+)Padding.units.%',
		'Design.variableMapping.([a-z]\w+)Width',
		'Design.variableMapping.([a-z]\w+)Width.forcedUnit',
		'Design.variableMapping.([a-z]\w+)Width.inputType',
		'Design.variableMapping.([a-z]\w+)Width.stripUnit',
		'Design.variableMapping.([a-z]\w+)Width.type',
		'Design.variableMapping.([a-z]\w+)Width.units',
		'Design.variableMapping.([a-z]\w+)Width.units.px',
		'Design.variableMapping.([a-z]\w+)Width.units.px.range',
		'Design.variableMapping.([a-z]\w+)Width.units.px.range.max',
		'Design.variableMapping.([a-z]\w+)Width.units.px.range.min',
		'Design.variableMapping.color([A-Z]\w+)',
		'Design.variableMapping.color([A-Z]\w+).category',
		'Design.variableMapping.color([A-Z]\w+).type',
		'Design.variableMapping.fontName([A-Z]\w+)',
		'Design.variableMapping.fontName([A-Z]\w+).associatedVariables',
		'Design.variableMapping.fontName([A-Z]\w+).category',
		'Design.variableMapping.fontName([A-Z]\w+).group',
		'Design.variableMapping.fontName([A-Z]\w+).type',
		'Design.variableMapping.fontSize(?!Clamp)([A-Z]\w+)',
		'Design.variableMapping.fontSize(?!Clamp)([A-Z]\w+).category',
		'Design.variableMapping.fontSize(?!Clamp)([A-Z]\w+).group',
		'Design.variableMapping.fontSize(?!Clamp)([A-Z]\w+).inputType',
		'Design.variableMapping.fontSize(?!Clamp)([A-Z]\w+).type',
		'Design.variableMapping.fontSize(?!Clamp)([A-Z]\w+).units',
		'Design.variableMapping.fontSize(?!Clamp)([A-Z]\w+).units.em',
		'Design.variableMapping.fontSize(?!Clamp)([A-Z]\w+).units.em.range',
		'Design.variableMapping.fontSize(?!Clamp)([A-Z]\w+).units.em.range.max',
		'Design.variableMapping.fontSize(?!Clamp)([A-Z]\w+).units.em.range.min',
		'Design.variableMapping.fontSize(?!Clamp)([A-Z]\w+).units.em.step',
		'Design.variableMapping.fontSize(?!Clamp)([A-Z]\w+).units.rem',
		'Design.variableMapping.fontSize(?!Clamp)([A-Z]\w+).units.rem.range',
		'Design.variableMapping.fontSize(?!Clamp)([A-Z]\w+).units.rem.range.max',
		'Design.variableMapping.fontStackFallback([A-Z]\w+)',
		'Design.variableMapping.fontStackFallback([A-Z]\w+).category',
		'Design.variableMapping.fontStackFallback([A-Z]\w+).group',
		'Design.variableMapping.fontStackFallback([A-Z]\w+).type',
		'Design.variableMapping.fontStyle([A-Z]\w+)',
		'Design.variableMapping.fontStyle([A-Z]\w+).category',
		'Design.variableMapping.fontStyle([A-Z]\w+).group',
		'Design.variableMapping.fontStyle([A-Z]\w+).options',
		'Design.variableMapping.fontStyle([A-Z]\w+).type',
		'Design.variableMapping.fontWeight([A-Z]\w+)',
		'Design.variableMapping.fontWeight([A-Z]\w+).category',
		'Design.variableMapping.fontWeight([A-Z]\w+).group',
		'Design.variableMapping.fontWeight([A-Z]\w+).type',
		'Design.variableMapping.lineHeight([A-Z]\w+)',
		'Design.variableMapping.lineHeight([A-Z]\w+).category',
		'Design.variableMapping.lineHeight([A-Z]\w+).group',
		'Design.variableMapping.lineHeight([A-Z]\w+).inputType',
		'Design.variableMapping.lineHeight([A-Z]\w+).type',
		'Design.variableMapping.lineHeight([A-Z]\w+).units',
		'Design.variableMapping.lineHeight([A-Z]\w+).units.',
		'Design.variableMapping.lineHeight([A-Z]\w+).units.%',
		'Design.variableMapping.lineHeight([A-Z]\w+).units.%.range',
		'Design.variableMapping.lineHeight([A-Z]\w+).units.%.range.max',
		'Design.variableMapping.lineHeight([A-Z]\w+).units.%.step',
		'Design.variableMapping.lineHeight([A-Z]\w+).units..range',
		'Design.variableMapping.lineHeight([A-Z]\w+).units..range.max',
		'Design.variableMapping.lineHeight([A-Z]\w+).units..range.min',
		'Design.variableMapping.lineHeight([A-Z]\w+).units..step',
		'Error',
		'Error.errorLevel',
		'Error.exceptionRenderer',
		'Error.ignoredDeprecationPaths',
		'Error.log',
		'Error.skipLog',
		'Error.trace',
		'IdeHelper',
		'IdeHelper.generatorTasks',
		'MimeTypes',
		'MimeTypes.application/csv',
		'MimeTypes.application/download',
		'MimeTypes.application/excel',
		'MimeTypes.application/force-download',
		'MimeTypes.application/json',
		'MimeTypes.application/mac-binary',
		'MimeTypes.application/mac-binhex',
		'MimeTypes.application/mac-binhex40',
		'MimeTypes.application/mac-compactpro',
		'MimeTypes.application/macbinary',
		'MimeTypes.application/msexcel',
		'MimeTypes.application/msword',
		'MimeTypes.application/octet-stream',
		'MimeTypes.application/oda',
		'MimeTypes.application/pdf',
		'MimeTypes.application/postscript',
		'MimeTypes.application/powerpoint',
		'MimeTypes.application/smil',
		'MimeTypes.application/vnd-mif',
		'MimeTypes.application/vnd-ms-excel',
		'MimeTypes.application/vnd-ms-office',
		'MimeTypes.application/vnd-ms-powerpoint',
		'MimeTypes.application/vnd-msexcel',
		'MimeTypes.application/vnd-openxmlformats-officedocument-presentationml-presentation',
		'MimeTypes.application/vnd-openxmlformats-officedocument-spreadsheetml-sheet',
		'MimeTypes.application/vnd-openxmlformats-officedocument-wordprocessingml-document',
		'MimeTypes.application/x-binary',
		'MimeTypes.application/x-binhex40',
		'MimeTypes.application/x-csv',
		'MimeTypes.application/x-dos_ms_excel',
		'MimeTypes.application/x-download',
		'MimeTypes.application/x-excel',
		'MimeTypes.application/x-mac-binhex40',
		'MimeTypes.application/x-macbinary',
		'MimeTypes.application/x-ms-excel',
		'MimeTypes.application/x-msdownload',
		'MimeTypes.application/x-msexcel',
		'MimeTypes.application/x-photoshop',
		'MimeTypes.application/x-rar-compressed',
		'MimeTypes.application/x-xls',
		'MimeTypes.application/xls',
		'MimeTypes.application/xml',
		'MimeTypes.application/zip',
		'MimeTypes.audio/mpeg',
		'MimeTypes.audio/ogg',
		'MimeTypes.binary/octet-stream',
		'MimeTypes.image/avif',
		'MimeTypes.image/gif',
		'MimeTypes.image/jpeg',
		'MimeTypes.image/png',
		'MimeTypes.image/svg+xml',
		'MimeTypes.image/vnd-adobe-photoshop',
		'MimeTypes.image/webp',
		'MimeTypes.text/comma-separated-values',
		'MimeTypes.text/css',
		'MimeTypes.text/csv',
		'MimeTypes.text/html',
		'MimeTypes.text/javascript',
		'MimeTypes.text/plain',
		'MimeTypes.text/vtt',
		'MimeTypes.text/x-comma-separated-values',
		'MimeTypes.text/x-csv',
		'MimeTypes.video/mp4',
		'MimeTypes.video/webm',
		'MimeTypes.video/x-msvideo',
		'PeriodicEvents',
		'PeriodicEvents.daily',
		'PeriodicEvents.hourly',
		'Queue',
		'Queue.defaultRequeueTimeout',
		'Queue.maxworkers',
		'Queue.workerLifetime',
		'Route',
		'Route.CustomerCenter',
		'Route.CustomerCenter.actions',
		'Route.CustomerCenter.actions.changePassword',
		'Route.CustomerCenter.actions.dashboard',
		'Route.CustomerCenter.actions.editProfile',
		'Route.CustomerCenter.actions.forgotPassword',
		'Route.CustomerCenter.actions.login',
		'Route.CustomerCenter.actions.logout',
		'Route.CustomerCenter.actions.register',
		'Route.CustomerCenter.actions.resetPassword',
		'Route.CustomerCenter.actions.verifyAccount',
		'Route.CustomerCenter.languages',
		'Route.CustomerCenter.languages.de',
		'Route.CustomerCenter.languages.de.actions',
		'Route.CustomerCenter.languages.de.actions.changePassword',
		'Route.CustomerCenter.languages.de.actions.dashboard',
		'Route.CustomerCenter.languages.de.actions.editProfile',
		'Route.CustomerCenter.languages.de.actions.forgotPassword',
		'Route.CustomerCenter.languages.de.actions.login',
		'Route.CustomerCenter.languages.de.actions.logout',
		'Route.CustomerCenter.languages.de.actions.register',
		'Route.CustomerCenter.languages.de.actions.resetPassword',
		'Route.CustomerCenter.languages.de.actions.verifyAccount',
		'Route.CustomerCenter.languages.de.path',
		'Route.CustomerCenter.path',
		'Route.includeLanguageShortcode',
		'Security',
		'Security.prehashPassword',
		'Seo',
		'Seo.stopWords',
		'Seo.stopWords.de',
		'Seo.stopWords.en',
		'Seo.stopWords.es',
		'Seo.stopWords.fr',
		'Seo.stopWords.it',
		'Session',
		'Session.cookie',
		'Session.defaults',
		'Session.ini',
		'Session.ini.session.cookie_lifetime',
		'Session.ini.session.gc_divisor',
		'Session.ini.session.gc_maxlifetime',
		'Session.ini.session.gc_probability',
		'Session.timeout',
		'debug',
		'plugins',
		'plugins.Authentication',
		'plugins.Bake',
		'plugins.Cake/TwigView',
		'plugins.CsvView',
		'plugins.DebugKit',
		'plugins.IdeHelper',
		'plugins.Migrations',
		'plugins.Queue',
	);

	registerArgumentsSet(
		'entityFields:Queue\Model\Entity\QueueProcess',
		'active_job',
		'created',
		'id',
		'modified',
		'pid',
		'server',
		'terminate',
		'workerkey',
	);

	registerArgumentsSet(
		'entityFields:Queue\Model\Entity\QueuedJob',
		'attempts',
		'completed',
		'created',
		'data',
		'failure_message',
		'fetched',
		'id',
		'job_group',
		'job_task',
		'memory',
		'notbefore',
		'output',
		'priority',
		'progress',
		'reference',
		'status',
		'worker_process',
		'workerkey',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\Attribute',
		'active',
		'changedBy',
		'changedByUser',
		'changedOn',
		'column',
		'columnSpan',
		'createdBy',
		'createdByUser',
		'createdOn',
		'defaultValue',
		'deleted',
		'deletedBy',
		'deletedByUser',
		'deletedOn',
		'fieldset',
		'hasIndex',
		'id',
		'identifier',
		'inputType',
		'label',
		'required',
		'scope',
		'systemOrder',
		'title',
		'translatable',
		'type',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\Audit',
		'createdBy',
		'createdByUser',
		'createdOn',
		'dataNew',
		'dataOld',
		'diff',
		'foreignKey',
		'id',
		'label',
		'scope',
		'subjectLeftForeignKey',
		'subjectLeftTable',
		'subjectRightForeignKey',
		'subjectRightTable',
		'transactionId',
		'type',
		'user',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\BackendMenuEntry',
		'access',
		'active',
		'changedBy',
		'changedByUser',
		'changedOn',
		'childBackendMenuEntries',
		'createdBy',
		'createdByUser',
		'createdOn',
		'deleted',
		'deletedBy',
		'deletedByUser',
		'deletedOn',
		'external',
		'id',
		'insertAfterId',
		'label',
		'link',
		'mediaAssignments',
		'parentBackendMenuEntry',
		'parentId',
		'systemOrder',
		'title',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\Configuration',
		'changedBy',
		'changedByUser',
		'changedOn',
		'createdBy',
		'createdByUser',
		'createdOn',
		'deletedBy',
		'deletedByUser',
		'deletedOn',
		'description',
		'id',
		'identifier',
		'language',
		'languageShortcode',
		'mediaAssignments',
		'printableValue',
		'realm',
		'scope',
		'value',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\Content',
		'active',
		'attributes',
		'changedBy',
		'changedByUser',
		'changedOn',
		'childContents',
		'column',
		'columnIndent',
		'columnLast',
		'columnRtl',
		'columnWidth',
		'contentArea',
		'contentAreaId',
		'contentTemplate',
		'contentTemplateId',
		'createdBy',
		'createdByUser',
		'createdOn',
		'css',
		'cssClass',
		'customerGroupAccessSettings',
		'customerGroupAssignments',
		'data',
		'deleted',
		'deletedBy',
		'deletedByUser',
		'deletedOn',
		'duplicateOf',
		'duplicateOfContent',
		'duplicatingContents',
		'form',
		'formId',
		'id',
		'label',
		'link',
		'mediaAssignments',
		'pageId',
		'parentContent',
		'parentId',
		'subtitle',
		'subtitleTag',
		'survey',
		'surveyId',
		'systemOrder',
		'text',
		'title',
		'titleTag',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\ContentArea',
		'active',
		'changedBy',
		'changedByUser',
		'changedOn',
		'contentTemplates',
		'createdBy',
		'createdByUser',
		'createdOn',
		'deleted',
		'deletedBy',
		'deletedByUser',
		'deletedOn',
		'id',
		'identifier',
		'label',
		'mediaAssignments',
		'pageTemplates',
		'title',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\ContentTemplate',
		'active',
		'changedBy',
		'changedByUser',
		'changedOn',
		'contentAreas',
		'contentTemplateElements',
		'contents',
		'createdBy',
		'createdByUser',
		'createdOn',
		'deleted',
		'deletedBy',
		'deletedByUser',
		'deletedOn',
		'fileName',
		'id',
		'inContentRow',
		'label',
		'mediaAssignments',
		'pageTemplates',
		'systemOrder',
		'title',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\ContentTemplateContentArea',
		'contentArea',
		'contentAreaId',
		'contentTemplate',
		'contentTemplateId',
		'id',
		'mediaAssignments',
		'pageTemplate',
		'pageTemplateId',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\ContentTemplateElement',
		'column',
		'columnSpan',
		'contentTemplate',
		'contentTemplateId',
		'fieldset',
		'id',
		'identifier',
		'label',
		'mediaAssignments',
		'required',
		'systemOrder',
		'title',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\Customer',
		'active',
		'changedBy',
		'changedByUser',
		'changedOn',
		'createdBy',
		'createdByUser',
		'createdOn',
		'customerGroups',
		'deleted',
		'deletedBy',
		'deletedByUser',
		'deletedOn',
		'email',
		'failedAttempts',
		'firstname',
		'id',
		'label',
		'lastLogin',
		'lastname',
		'mediaAssignments',
		'password',
		'passwordResetCode',
		'passwordResetOn',
		'verificationCode',
		'verified',
		'verifiedOn',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\CustomerGroup',
		'active',
		'changedBy',
		'changedByUser',
		'changedOn',
		'createdBy',
		'createdByUser',
		'createdOn',
		'customers',
		'deleted',
		'deletedBy',
		'deletedByUser',
		'deletedOn',
		'id',
		'label',
		'mediaAssignments',
		'title',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\CustomerGroupAccessSetting',
		'accessType',
		'changedBy',
		'changedByUser',
		'changedOn',
		'createdBy',
		'createdByUser',
		'createdOn',
		'deleted',
		'deletedBy',
		'deletedByUser',
		'deletedOn',
		'foreignKey',
		'id',
		'label',
		'scope',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\CustomerGroupAssignment',
		'changedBy',
		'changedByUser',
		'changedOn',
		'createdBy',
		'createdByUser',
		'createdOn',
		'customerGroup',
		'customerGroupId',
		'deleted',
		'deletedBy',
		'deletedByUser',
		'deletedOn',
		'foreignKey',
		'id',
		'label',
		'scope',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\CustomerGroupsCustomer',
		'customer',
		'customerGroup',
		'customerGroupId',
		'customerId',
		'id',
		'label',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\DashboardElement',
		'access',
		'active',
		'changedBy',
		'changedByUser',
		'changedOn',
		'createdBy',
		'createdByUser',
		'createdOn',
		'deleted',
		'deletedBy',
		'deletedByUser',
		'deletedOn',
		'id',
		'label',
		'mediaAssignments',
		'scope',
		'settings',
		'systemOrder',
		'title',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\Datatable',
		'active',
		'changedBy',
		'changedByUser',
		'changedOn',
		'createdBy',
		'createdByUser',
		'createdOn',
		'deleted',
		'deletedBy',
		'deletedByUser',
		'deletedOn',
		'id',
		'identifier',
		'label',
		'mediaAssignments',
		'title',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\Design',
		'changedBy',
		'changedByUser',
		'changedOn',
		'createdBy',
		'createdByUser',
		'createdOn',
		'css',
		'deleted',
		'deletedBy',
		'deletedByUser',
		'deletedOn',
		'description',
		'id',
		'identifier',
		'inUse',
		'isPreview',
		'label',
		'settings',
		'title',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\EmailTemplate',
		'active',
		'changedBy',
		'changedByUser',
		'changedOn',
		'createdBy',
		'createdByUser',
		'createdOn',
		'deleted',
		'deletedBy',
		'deletedByUser',
		'deletedOn',
		'fileName',
		'formConfirmationEmails',
		'formEmails',
		'id',
		'label',
		'layout',
		'mediaAssignments',
		'textHtml',
		'textPlain',
		'title',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\Form',
		'active',
		'bcc',
		'cc',
		'changedBy',
		'changedByUser',
		'changedOn',
		'conditionalRecipients',
		'conditionalRecipientsStrategy',
		'confirmationEmailTemplate',
		'confirmationEmailTemplateId',
		'contents',
		'createdBy',
		'createdByUser',
		'createdOn',
		'deleted',
		'deletedBy',
		'deletedByUser',
		'deletedOn',
		'emailTemplate',
		'emailTemplateId',
		'formElements',
		'formEntries',
		'globalContents',
		'id',
		'identifier',
		'label',
		'mediaAssignments',
		'multistep',
		'ownerEmail',
		'ownerName',
		'pages',
		'salutation',
		'salutationConfirmation',
		'sendConfirmationEmail',
		'sendEmail',
		'subject',
		'subjectConfirmation',
		'successMessage',
		'summarizeErrors',
		'surveys',
		'title',
		'transportProfile',
		'userEmail',
		'userName',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\FormConditionalRecipient',
		'changedBy',
		'changedByUser',
		'changedOn',
		'createdBy',
		'createdByUser',
		'createdOn',
		'deletedBy',
		'deletedByUser',
		'deletedOn',
		'field',
		'form',
		'formId',
		'id',
		'label',
		'mediaAssignments',
		'operator',
		'recipient',
		'systemOrder',
		'type',
		'value',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\FormElement',
		'active',
		'changedBy',
		'changedByUser',
		'changedOn',
		'childFormElements',
		'column',
		'columnIndent',
		'columnLast',
		'columnRtl',
		'columnWidth',
		'createdBy',
		'createdByUser',
		'createdOn',
		'cssClass',
		'deleted',
		'deletedBy',
		'deletedByUser',
		'deletedOn',
		'form',
		'formId',
		'id',
		'identifier',
		'label',
		'mediaAssignments',
		'options',
		'parentFormElement',
		'parentId',
		'placeholder',
		'required',
		'systemOrder',
		'text',
		'title',
		'titleEmail',
		'type',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\FormEntry',
		'body',
		'bodyConfirmation',
		'createdOn',
		'data',
		'deleted',
		'deletedBy',
		'deletedByUser',
		'deletedOn',
		'form',
		'formId',
		'id',
		'identifier',
		'ipHash',
		'label',
		'language',
		'languageShortcode',
		'mediaAssignments',
		'page',
		'pageId',
		'postHash',
		'subject',
		'subjectConfirmation',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\GlobalContent',
		'active',
		'attributes',
		'changedBy',
		'changedByUser',
		'changedOn',
		'childGlobalContents',
		'column',
		'columnIndent',
		'columnLast',
		'columnRtl',
		'columnWidth',
		'createdBy',
		'createdByUser',
		'createdOn',
		'css',
		'cssClass',
		'customerGroupAccessSettings',
		'customerGroupAssignments',
		'data',
		'deleted',
		'deletedBy',
		'deletedByUser',
		'deletedOn',
		'form',
		'formId',
		'globalContentTemplate',
		'globalContentTemplateId',
		'id',
		'identifier',
		'label',
		'link',
		'mediaAssignments',
		'parentGlobalContent',
		'parentId',
		'subtitle',
		'subtitleTag',
		'survey',
		'surveyId',
		'systemOrder',
		'text',
		'title',
		'titleTag',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\GlobalContentTemplate',
		'active',
		'changedBy',
		'changedByUser',
		'changedOn',
		'createdBy',
		'createdByUser',
		'createdOn',
		'deleted',
		'deletedBy',
		'deletedByUser',
		'deletedOn',
		'fileName',
		'globalContentTemplateElements',
		'globalContents',
		'id',
		'inContentRow',
		'label',
		'mediaAssignments',
		'systemOrder',
		'title',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\GlobalContentTemplateElement',
		'column',
		'columnSpan',
		'fieldset',
		'globalContentTemplate',
		'globalContentTemplateId',
		'id',
		'identifier',
		'label',
		'mediaAssignments',
		'required',
		'systemOrder',
		'title',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\I18n',
		'content',
		'field',
		'foreignKey',
		'id',
		'label',
		'locale',
		'model',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\Language',
		'active',
		'changedBy',
		'changedByUser',
		'changedOn',
		'configuration',
		'createdBy',
		'createdByUser',
		'createdOn',
		'dateFormat',
		'deleted',
		'deletedBy',
		'deletedByUser',
		'deletedOn',
		'formEntries',
		'id',
		'label',
		'locale',
		'mediaAssignments',
		'menuEntries',
		'pages',
		'realm',
		'shortcode',
		'systemOrder',
		'timeFormat',
		'timezone',
		'title',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\Lock',
		'createdBy',
		'createdByUser',
		'createdOn',
		'foreignKey',
		'id',
		'label',
		'scope',
		'uniqueId',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\Media',
		'alt',
		'averageColor',
		'avif',
		'avifName',
		'avifPath',
		'avifPathAbsolute',
		'changedBy',
		'changedByUser',
		'changedOn',
		'cleanName',
		'createdBy',
		'createdByUser',
		'createdOn',
		'crop',
		'deletedBy',
		'deletedByUser',
		'deletedOn',
		'extension',
		'filemtime',
		'focusPoint',
		'height',
		'id',
		'isAudio',
		'isImage',
		'isVideo',
		'label',
		'mediaAssignments',
		'mediaFolder',
		'mediaFolderId',
		'mediaResizedImages',
		'metaData',
		'mimeType',
		'name',
		'originalAvifName',
		'originalAvifPath',
		'originalAvifPathAbsolute',
		'originalCleanName',
		'originalExtension',
		'originalPathAbsolute',
		'originalPreviewName',
		'originalPreviewPath',
		'originalPreviewPathAbsolute',
		'originalWebpName',
		'originalWebpPath',
		'originalWebpPathAbsolute',
		'path',
		'pathAbsolute',
		'preview',
		'previewFilemtime',
		'previewName',
		'previewPath',
		'previewPathAbsolute',
		'systemOrder',
		'urlHistory',
		'usageCount',
		'webp',
		'webpName',
		'webpPath',
		'webpPathAbsolute',
		'width',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\MediaAssignment',
		'changedBy',
		'changedByUser',
		'changedOn',
		'createdBy',
		'createdByUser',
		'createdOn',
		'deleted',
		'deletedBy',
		'deletedByUser',
		'deletedOn',
		'foreignKey',
		'id',
		'label',
		'media',
		'mediaElement',
		'mediaElementAssignment',
		'mediaElementId',
		'mediaElementSelector',
		'mediaElementSelectorIdentifier',
		'mediaFolder',
		'mediaFolderId',
		'mediaId',
		'scope',
		'systemOrder',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\MediaElement',
		'active',
		'changedBy',
		'changedByUser',
		'changedOn',
		'column',
		'columnSpan',
		'createdBy',
		'createdByUser',
		'createdOn',
		'deleted',
		'deletedBy',
		'deletedByUser',
		'deletedOn',
		'id',
		'identifier',
		'internal',
		'label',
		'mediaAssignments',
		'mediaElementAssignments',
		'mediaElementSelectors',
		'systemOrder',
		'title',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\MediaElementAssignment',
		'foreignKey',
		'id',
		'label',
		'mediaAssignments',
		'mediaElement',
		'mediaElementId',
		'scope',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\MediaElementSelector',
		'column',
		'columnSpan',
		'id',
		'identifier',
		'label',
		'mediaAssignments',
		'mediaElement',
		'mediaElementId',
		'mediaSelector',
		'mediaSelectorId',
		'required',
		'systemOrder',
		'title',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\MediaFolder',
		'active',
		'attributes',
		'changedBy',
		'changedByUser',
		'changedOn',
		'childMediaFolders',
		'createdBy',
		'createdByUser',
		'createdOn',
		'deleted',
		'deletedBy',
		'deletedByUser',
		'deletedOn',
		'hidden',
		'id',
		'label',
		'language',
		'languageShortcode',
		'media',
		'mediaAssignments',
		'parentId',
		'parentMediaFolder',
		'parentsActive',
		'path',
		'systemOrder',
		'title',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\MediaResizedImage',
		'extension',
		'filemtime',
		'height',
		'id',
		'media',
		'mediaId',
		'name',
		'path',
		'pathAbsolute',
		'realHeight',
		'realWidth',
		'status',
		'strategy',
		'width',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\MediaSelector',
		'active',
		'changedBy',
		'changedByUser',
		'changedOn',
		'createdBy',
		'createdByUser',
		'createdOn',
		'deleted',
		'deletedBy',
		'deletedByUser',
		'deletedOn',
		'id',
		'identifier',
		'label',
		'mediaElementSelectors',
		'title',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\Menu',
		'active',
		'changedBy',
		'changedByUser',
		'changedOn',
		'createdBy',
		'createdByUser',
		'createdOn',
		'customerGroupAccessSettings',
		'customerGroupAssignments',
		'deleted',
		'deletedBy',
		'deletedByUser',
		'deletedOn',
		'id',
		'identifier',
		'label',
		'mediaAssignments',
		'menuEntries',
		'title',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\MenuEntry',
		'active',
		'changedBy',
		'changedByUser',
		'changedOn',
		'childMenuEntries',
		'createdBy',
		'createdByUser',
		'createdOn',
		'customerGroupAccessSettings',
		'customerGroupAssignments',
		'deleted',
		'deletedBy',
		'deletedByUser',
		'deletedOn',
		'external',
		'id',
		'label',
		'language',
		'languageShortcode',
		'link',
		'mediaAssignments',
		'menu',
		'menuId',
		'parentId',
		'parentMenuEntry',
		'systemOrder',
		'title',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\Page',
		'active',
		'attributes',
		'changedBy',
		'changedByUser',
		'changedOn',
		'childPages',
		'contents',
		'createdBy',
		'createdByUser',
		'createdOn',
		'customerGroupAccessSettings',
		'customerGroupAssignments',
		'deleted',
		'deletedBy',
		'deletedByUser',
		'deletedOn',
		'duplicate',
		'duplicateOf',
		'duplicatedBy',
		'form',
		'formId',
		'id',
		'label',
		'language',
		'languageShortcode',
		'link',
		'mediaAssignments',
		'metaDescription',
		'metaTitle',
		'pageRole',
		'pageRoleId',
		'pageTemplate',
		'pageTemplateId',
		'parentId',
		'parentPage',
		'parentsActive',
		'redirectLink',
		'robotsFollow',
		'robotsIndex',
		'slug',
		'survey',
		'surveyId',
		'systemOrder',
		'title',
		'urlHistory',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\PageRole',
		'active',
		'changedBy',
		'changedByUser',
		'changedOn',
		'createdBy',
		'createdByUser',
		'createdOn',
		'deleted',
		'deletedBy',
		'deletedByUser',
		'deletedOn',
		'id',
		'identifier',
		'includeInLinklist',
		'label',
		'mediaAssignments',
		'pageTemplates',
		'pages',
		'systemOrder',
		'title',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\PageTemplate',
		'active',
		'changedBy',
		'changedByUser',
		'changedOn',
		'contentAreas',
		'contentTemplateContentAreas',
		'createdBy',
		'createdByUser',
		'createdOn',
		'deleted',
		'deletedBy',
		'deletedByUser',
		'deletedOn',
		'fileName',
		'id',
		'label',
		'mediaAssignments',
		'pageRole',
		'pageRoleId',
		'pages',
		'systemOrder',
		'title',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\PageTemplateContentArea',
		'contentArea',
		'contentAreaId',
		'id',
		'mediaAssignments',
		'pageTemplate',
		'pageTemplateId',
		'systemOrder',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\PublicationData',
		'dateTime',
		'foreignKey',
		'id',
		'label',
		'scope',
		'type',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\Survey',
		'active',
		'changedBy',
		'changedByUser',
		'changedOn',
		'contents',
		'createdBy',
		'createdByUser',
		'createdOn',
		'deleted',
		'deletedBy',
		'deletedByUser',
		'deletedOn',
		'failureMessage',
		'finalAction',
		'form',
		'formId',
		'globalContents',
		'id',
		'identifier',
		'label',
		'mediaAssignments',
		'pages',
		'successMessage',
		'surveyEntries',
		'surveySurveyQuestions',
		'title',
		'type',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\SurveyAnswer',
		'active',
		'changedBy',
		'changedByUser',
		'changedOn',
		'createdBy',
		'createdByUser',
		'createdOn',
		'deleted',
		'deletedBy',
		'deletedByUser',
		'deletedOn',
		'id',
		'label',
		'mediaAssignments',
		'subtitle',
		'surveyQuestion',
		'surveyQuestionId',
		'surveySurveyAnswers',
		'surveySurveyQuestion',
		'systemOrder',
		'text',
		'title',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\SurveyEntry',
		'createdOn',
		'data',
		'deleted',
		'deletedBy',
		'deletedByUser',
		'deletedOn',
		'id',
		'identifier',
		'ipHash',
		'label',
		'mediaAssignments',
		'page',
		'pageId',
		'postHash',
		'survey',
		'surveyId',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\SurveyQuestion',
		'active',
		'changedBy',
		'changedByUser',
		'changedOn',
		'createdBy',
		'createdByUser',
		'createdOn',
		'deleted',
		'deletedBy',
		'deletedByUser',
		'deletedOn',
		'id',
		'label',
		'mediaAssignments',
		'subtitle',
		'surveyAnswers',
		'surveySurveyQuestions',
		'text',
		'title',
		'type',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\SurveySurveyAnswer',
		'active',
		'changedBy',
		'changedByUser',
		'changedOn',
		'createdBy',
		'createdByUser',
		'createdOn',
		'deleted',
		'deletedBy',
		'deletedByUser',
		'deletedOn',
		'id',
		'label',
		'mediaAssignments',
		'nextAction',
		'nextActionTarget',
		'subtitle',
		'surveyAnswer',
		'surveyAnswerId',
		'surveySurveyQuestion',
		'surveySurveyQuestionId',
		'systemOrder',
		'text',
		'title',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\SurveySurveyQuestion',
		'active',
		'allowCustomAnswer',
		'changedBy',
		'changedByUser',
		'changedOn',
		'createdBy',
		'createdByUser',
		'createdOn',
		'customAnswerTitle',
		'deleted',
		'deletedBy',
		'deletedByUser',
		'deletedOn',
		'id',
		'identifier',
		'label',
		'mediaAssignments',
		'nextAction',
		'nextActionTarget',
		'subtitle',
		'survey',
		'surveyId',
		'surveyQuestion',
		'surveyQuestionId',
		'surveySurveyAnswers',
		'systemOrder',
		'text',
		'title',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\ThirdPartyConsent',
		'acceptType',
		'acceptedCategories',
		'consentId',
		'createdOn',
		'id',
		'label',
		'mediaAssignments',
		'rejectedCategories',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\UrlHistory',
		'changedBy',
		'changedByUser',
		'changedOn',
		'createdBy',
		'createdByUser',
		'createdOn',
		'deletedBy',
		'deletedByUser',
		'deletedOn',
		'foreignKey',
		'id',
		'label',
		'media',
		'mediaAssignments',
		'page',
		'scope',
		'status',
		'target',
		'url',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\UrlsNotFound',
		'createdOn',
		'id',
		'isRobot',
		'label',
		'mediaAssignments',
		'referrer',
		'url',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\User',
		'active',
		'changedBy',
		'changedByUser',
		'changedOn',
		'createdBy',
		'createdByUser',
		'createdOn',
		'deleted',
		'deletedBy',
		'deletedByUser',
		'deletedOn',
		'email',
		'failedAttempts',
		'firstname',
		'id',
		'label',
		'lastLogin',
		'lastname',
		'mediaAssignments',
		'password',
		'userConfiguration',
		'usergroups',
		'username',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\UserConfiguration',
		'id',
		'identifier',
		'mediaAssignments',
		'printableValue',
		'scope',
		'user',
		'userId',
		'value',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\Usergroup',
		'active',
		'changedBy',
		'changedByUser',
		'changedOn',
		'createdBy',
		'createdByUser',
		'createdOn',
		'deleted',
		'deletedBy',
		'deletedByUser',
		'deletedOn',
		'id',
		'label',
		'mediaAssignments',
		'title',
		'usergroupPermissions',
		'users',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\UsergroupPermission',
		'access',
		'id',
		'identifier',
		'label',
		'mediaAssignments',
		'scope',
		'settings',
		'usergroup',
		'usergroupId',
	);

	registerArgumentsSet(
		'entityFields:\Awyiss\Model\Entity\UsergroupsUser',
		'id',
		'label',
		'mediaAssignments',
		'user',
		'userId',
		'usergroup',
		'usergroupId',
	);

	registerArgumentsSet(
		'routePaths',

	);

	registerArgumentsSet(
		'tableNames',
		'attributes',
		'audit',
		'backend_menu_entries',
		'configuration',
		'content_areas',
		'content_template_content_areas',
		'content_template_elements',
		'content_templates',
		'contents',
		'customer_group_access_settings',
		'customer_group_assignments',
		'customer_groups',
		'customer_groups_customers',
		'customers',
		'dashboard_elements',
		'datatables',
		'designs',
		'email_templates',
		'form_conditional_recipients',
		'form_elements',
		'form_entries',
		'forms',
		'global_content_template_elements',
		'global_content_templates',
		'global_contents',
		'languages',
		'locks',
		'media',
		'media_assignments',
		'media_element_assignments',
		'media_element_selectors',
		'media_elements',
		'media_folders',
		'media_resized_images',
		'media_selectors',
		'menu_entries',
		'menus',
		'page_roles',
		'page_template_content_areas',
		'page_templates',
		'pages',
		'publication_data',
		'queue_processes',
		'queued_jobs',
		'survey_answers',
		'survey_entries',
		'survey_questions',
		'survey_survey_answers',
		'survey_survey_questions',
		'surveys',
		'third_party_consents',
		'url_history',
		'urls_not_found',
		'user_configuration',
		'usergroup_permissions',
		'usergroups',
		'usergroups_users',
		'users',
	);

	registerArgumentsSet(
		'validationWhen',
		'create',
		'update',
	);

}
