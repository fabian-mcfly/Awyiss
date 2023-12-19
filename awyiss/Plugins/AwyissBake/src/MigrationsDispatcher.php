<?php declare(strict_types=1);


namespace AwyissBake;


/**
 * @inheritDoc
 */
class MigrationsDispatcher extends \Migrations\MigrationsDispatcher {
    /**
	 * Uses \AwyissBake\Command\Phinx\Migrate
	 *
     * @var array
	 *
	 * @see \AwyissBake\Command\Phinx\Migrate
     */
    public static array $phinxCommands = [
        //'Create' => Phinx\Create::class,
        //'Dump' => Phinx\Dump::class,
        //'MarkMigrated' => Phinx\MarkMigrated::class,
        'Migrate' => Command\Phinx\Migrate::class,
        //'Rollback' => Phinx\Rollback::class,
        'Seed' => Command\Phinx\Seed::class,
        //'Status' => Phinx\Status::class,
        //'CacheBuild' => Phinx\CacheBuild::class,
        //'CacheClear' => Phinx\CacheClear::class,
    ];
}
