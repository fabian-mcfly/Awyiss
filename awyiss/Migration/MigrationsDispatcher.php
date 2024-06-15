<?php declare(strict_types=1);


namespace Awyiss\Migration;


use Awyiss\Command\Phinx\Migrate;
use Awyiss\Command\Phinx\Seed;
use Migrations\MigrationsDispatcher as BaseMigrationsDispatcher;


/**
 * @inheritDoc
 */
class MigrationsDispatcher extends BaseMigrationsDispatcher {
	/**
	 * Uses \AwyissBake\Command\Phinx\Migrate
	 *
	 * @inheritDoc
	 * @return array<string, string>
	 * @psalm-return array<string, class-string<\Phinx\Console\Command\AbstractCommand>|class-string<\Migrations\Command\Phinx\BaseCommand>>
	 */
	public static function getCommands(): array {
		return [
			//'Create' => Phinx\Create::class,
			//'Dump' => Phinx\Dump::class,
			//'MarkMigrated' => Phinx\MarkMigrated::class,
			'Migrate' => Migrate::class,
			//'Rollback' => Phinx\Rollback::class,
			'Seed' => Seed::class,
			//'Status' => Phinx\Status::class,
			//'CacheBuild' => Phinx\CacheBuild::class,
			//'CacheClear' => Phinx\CacheClear::class,
		];
	}
}
