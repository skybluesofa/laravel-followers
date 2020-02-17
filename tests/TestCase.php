<?php

use Illuminate\Database\Eloquent\Factory;

abstract class TestCase extends Orchestra\Testbench\TestCase
{

    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app->make(Factory::class)->load(__DIR__.'/../src/database/factories');

        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $app['config']->set('followers.tables.followers', 'followers');
        $app['config']->set('followers.limits.following', '5');
    }

    /**
     * Setup DB before each test.
     */
    public function setUp() : void
    {
        parent::setUp();

        require_once(__DIR__.'/stubs/Stub_User.php');
        require_once(__DIR__.'/stubs/Widget.php');

        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__ . '/../src/database/migrations');
        $this->withFactories(__DIR__.'/../src/database/factories');
    }
}
