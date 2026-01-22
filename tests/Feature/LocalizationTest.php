<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_locale_switch_route()
    {
        $response = $this->get('/lang/ro');

        $response->assertRedirect();
        $this->assertEquals('ro', session('locale'));
    }

    public function test_middleware_sets_locale()
    {
        \Illuminate\Support\Facades\Route::get('/test-locale', function () {
            return \Illuminate\Support\Facades\App::getLocale();
        })->middleware('web');

        $response = $this->withSession(['locale' => 'de'])->get('/test-locale');

        $response->assertSee('de');
    }

    public function test_invalid_locale_is_ignored()
    {
        $this->get('/lang/xyz');

        $this->assertFalse(session()->has('locale') && session('locale') == 'xyz');
    }
}
