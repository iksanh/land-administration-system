<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_guests_to_login(): void
    {
        // "/" -> dashboard (auth) -> login for a guest.
        $this->get('/')->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_test_database_is_mysql_and_reachable(): void
    {
        $this->assertSame('mysql', DB::connection()->getDriverName());
        $this->assertSame('db_phpt_test', DB::connection()->getDatabaseName());
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('users'));
    }
}
