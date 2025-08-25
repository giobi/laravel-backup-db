<?php

namespace Giobi\LaravelBackupDb\Tests;

use Giobi\LaravelBackupDb\LaravelBackupDbServiceProvider;

class ServiceProviderTest extends TestCase
{
    /** @test */
    public function it_can_be_instantiated()
    {
        $serviceProvider = new LaravelBackupDbServiceProvider($this->app);
        
        $this->assertInstanceOf(LaravelBackupDbServiceProvider::class, $serviceProvider);
    }

    /** @test */
    public function it_provides_config()
    {
        $this->assertTrue(config()->has('backup-manager'));
    }

    /** @test */
    public function it_has_correct_default_config()
    {
        $this->assertTrue(config('backup-manager.enable_routes'));
        $this->assertEquals('backups', config('backup-manager.route_prefix'));
        $this->assertEquals('admin', config('backup-manager.auth_gate'));
        $this->assertEquals('daily', config('backup-manager.log_channel'));
    }

    /** @test */
    public function it_registers_views()
    {
        $this->assertTrue(view()->exists('laravel-backup-db::index'));
    }
}