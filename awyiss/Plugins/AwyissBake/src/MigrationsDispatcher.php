<?php declare(strict_types=1);


namespace AwyissBake;


class MigrationsDispatcher extends \Migrations\MigrationsDispatcher {
    /**
     * @var array
     */
    public static $phinxCommands = [
        //'Create' => Phinx\Create::class,
        //'Dump' => Phinx\Dump::class,
        //'MarkMigrated' => Phinx\MarkMigrated::class,
        'Migrate' => Command\Phinx\Migrate::class,
        //'Rollback' => Phinx\Rollback::class,
        //'Seed' => Phinx\Seed::class,
        //'Status' => Phinx\Status::class,
        //'CacheBuild' => Phinx\CacheBuild::class,
        //'CacheClear' => Phinx\CacheClear::class,
    ];
}
