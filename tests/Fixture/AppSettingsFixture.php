<?php
declare(strict_types=1);

namespace DbConfig\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * AppSettingsFixture
 */
class AppSettingsFixture extends TestFixture
{
    /**
     * Table name
     *
     * @var string
     */
    public string $table = 'app_settings';

    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'module' => 'App',
                'config_key' => 'App.defaultTimezone',
                'value' => 'UTC',
                'type' => 'string',
            ],
            [
                'id' => 2,
                'module' => 'App',
                'config_key' => 'App.defaultLocale',
                'value' => 'en_US',
                'type' => 'string',
            ],
            [
                'id' => 3,
                'module' => 'App',
                'config_key' => 'Cache.default.duration',
                'value' => '3600',
                'type' => 'integer',
            ],
        ];
        parent::init();
    }
}
