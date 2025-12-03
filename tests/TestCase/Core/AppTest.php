<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Core;


use Awyiss\Authorization\Policy\Backend\GenericDatatablesPolicy;
use Awyiss\Authorization\Policy\Backend\GenericPagesPolicy;
use Awyiss\Core\App;
use Awyiss\Module\ModuleInterface;
use Awyiss\Module\RoutePlannerModule;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Http\ServerRequest;
use Customer\Authorization\Policy\Backend\_IgnoredTestPolicy;
use Customer\Authorization\Policy\Backend\AbstractTestPolicy;
use Customer\Model\Enum\PageRole;
use Customer\Module\NewsListingModule;
use RuntimeException;


/**
 * App Test Case
 *
 * @see \Awyiss\Core\App
 */
class AppTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Core\App::className()
	 */
	public function testClassNameWithExistingClass(): void {
		$result = App::className('ServerRequest', 'Http');
		$this->assertSame(ServerRequest::class, trim($result, '\\'));

		$result = App::className('RoutePlanner', 'Module', 'Module');
		$this->assertSame(RoutePlannerModule::class, trim($result, '\\'));

		$result = App::className('NewsListing', 'Module', 'Module');
		$this->assertSame(NewsListingModule::class, trim($result, '\\'));

		$result = App::className('PageRole', 'Model/Enum');
		$this->assertSame(PageRole::class, trim($result, '\\'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Core\App::className()
	 */
	public function testClassNameWithNonExistingClass(): void {
		$result = App::className('NonExistingClass', 'Http');
		$this->assertNull($result);

		$result = App::className('NonExistingClass', 'NonExistingType');
		$this->assertNull($result);

		$result = App::className('PageRole', 'Model/Enum', 'NonExistingSuffix');
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Core\App::classes()
	 */
	public function testClassesWithExistingClasses(): void {
		$result = App::classes('NewsListing', 'Module', 'Module');
		$this->assertSame([
			'NewsListingModule' => '\Customer\Module\NewsListingModule',
		], $result);

		$result = App::classes('NewsListingModule', 'Module');
		$this->assertSame([
			'NewsListingModule' => '\Customer\Module\NewsListingModule',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Core\App::classes()
	 */
	public function testClassesWithExistingClassesChecksForInterface(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('The provided class `\Awyiss\Module\ModuleInterface` does not implement `Awyiss\Module\ModuleInterface`');
		App::classes('ModuleInterface', 'Module', '', ModuleInterface::class);
	}


	/**
	 * @return void
	 * @see \Awyiss\Core\App::classes()
	 */
	public function testClassesWithNonExistingClasses(): void {
		$result = App::classes('NonExistingClass', 'Module', 'Module');
		$this->assertSame([], $result);

		$result = App::classes('NonExistingClassModule', 'Module');
		$this->assertSame([], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Core\App::classes()
	 */
	public function testClassesWithExistingClassesWithPlaceholder(): void {
		$result = App::classes('*', 'Module', 'Module');
		$this->assertSame([
			'EmptyModule' => '\Customer\Module\EmptyModule',
			'NewsListingModule' => '\Customer\Module\NewsListingModule',
			'TestModule' => '\Customer\Module\TestModule',
			'BreadcrumbsModule' => '\Awyiss\Module\BreadcrumbsModule',
			'InstagramFeedModule' => '\Awyiss\Module\InstagramFeedModule',
			'RoutePlannerModule' => '\Awyiss\Module\RoutePlannerModule',
		], $result);

		$result = App::classes('*', 'Module');
		$this->assertSame([
			'EmptyModule' => '\Customer\Module\EmptyModule',
			'NewsListingModule' => '\Customer\Module\NewsListingModule',
			'TestModule' => '\Customer\Module\TestModule',
			'BreadcrumbsModule' => '\Awyiss\Module\BreadcrumbsModule',
			'InstagramFeedModule' => '\Awyiss\Module\InstagramFeedModule',
			'ModuleInterface' => '\Awyiss\Module\ModuleInterface',
			'ModulesProvider' => '\Awyiss\Module\ModulesProvider',
			'RoutePlannerModule' => '\Awyiss\Module\RoutePlannerModule',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Core\App::classes()
	 */
	public function testClassesWithExistingClassesWithPlaceholderChecksForInterface(): void {
		$result = App::classes('*', 'Module', '', ModuleInterface::class);
		$this->assertSame([
			'EmptyModule' => '\Customer\Module\EmptyModule',
			'NewsListingModule' => '\Customer\Module\NewsListingModule',
			'TestModule' => '\Customer\Module\TestModule',
			'BreadcrumbsModule' => '\Awyiss\Module\BreadcrumbsModule',
			'InstagramFeedModule' => '\Awyiss\Module\InstagramFeedModule',
			'RoutePlannerModule' => '\Awyiss\Module\RoutePlannerModule',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Core\App::classes()
	 */
	public function testClassesWithExistingClassesExcludeBlocklisted(): void {
		$result = App::classes(
			'*',
			'Authorization/Policy/Backend',
			'Policy',
		);

		$this->assertContains('\\' . GenericDatatablesPolicy::class, $result);
		$this->assertContains('\\' . GenericPagesPolicy::class, $result);

		$result = App::classes(
			'*',
			'Authorization/Policy/Backend',
			'Policy',
			null,
			null,
			['GenericDatatablesPolicy', 'GenericPagesPolicy']
		);

		$this->assertNotContains('\\' . GenericDatatablesPolicy::class, $result);
		$this->assertNotContains('\\' . GenericPagesPolicy::class, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Core\App::classes()
	 */
	public function testClassesWithExistingClassesExcludeUnderscoredAndAbstractClasses(): void {
		$result = App::classes('*', 'Authorization/Policy/Backend', 'Policy');
		$this->assertNotContains('\\' . _IgnoredTestPolicy::class, $result);
		$this->assertNotContains('\\' . AbstractTestPolicy::class, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Core\App::classes()
	 */
	public function testClassesWithExistingClassesAndSubfolders(): void {
		$result = App::classes('*', 'Command', 'Command');
		$this->assertSame([
			'TestCommand' => '\Customer\Command\TestCommand',
			'I18nExtractCommand' => '\Awyiss\Command\I18nExtractCommand',
			'IntegrityCheckCommand' => '\Awyiss\Command\IntegrityCheckCommand',
		], $result);

		$result = App::classes('*', 'Command', 'Command', null, '*');
		$this->assertSame([
			'Awyiss\BackupCommand' => '\Awyiss\Command\Awyiss\BackupCommand',
			'Awyiss\InstallCommand' => '\Awyiss\Command\Awyiss\InstallCommand',
			'Awyiss\ResetPasswordCommand' => '\Awyiss\Command\Awyiss\ResetPasswordCommand',
			'Bake\AllCommand' => '\Awyiss\Command\Bake\AllCommand',
			'Bake\ControllerCommand' => '\Awyiss\Command\Bake\ControllerCommand',
			'Bake\EnumCommand' => '\Awyiss\Command\Bake\EnumCommand',
			'Bake\MigrationCommand' => '\Awyiss\Command\Bake\MigrationCommand',
			'Bake\ModelCommand' => '\Awyiss\Command\Bake\ModelCommand',
			'Bake\PolicyCommand' => '\Awyiss\Command\Bake\PolicyCommand',
			'Bake\SeedCommand' => '\Awyiss\Command\Bake\SeedCommand',
			'Bake\TemplateAllCommand' => '\Awyiss\Command\Bake\TemplateAllCommand',
			'Bake\TemplateCommand' => '\Awyiss\Command\Bake\TemplateCommand',
			'Media\ClearCacheCommand' => '\Awyiss\Command\Media\ClearCacheCommand',
			'Media\ConvertFilesCommand' => '\Awyiss\Command\Media\ConvertFilesCommand',
			'Media\DetectAvailableCommandsCommand' => '\Awyiss\Command\Media\DetectAvailableCommandsCommand',
			'Scss\CompileCommand' => '\Awyiss\Command\Scss\CompileCommand',
			'Twig\ClearCacheCommand' => '\Awyiss\Command\Twig\ClearCacheCommand',
		], $result);
	}
}
