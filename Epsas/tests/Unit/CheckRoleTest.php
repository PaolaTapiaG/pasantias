<?php

namespace Tests\Unit;

use App\Http\Middleware\CheckRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Tests\TestCase;

class CheckRoleTest extends TestCase
{
    public function test_user_with_an_allowed_role_can_continue(): void
    {
        $user = Mockery::mock();
        $user->shouldReceive('hasAnyRole')->once()->with(['secretaria', 'administrador'])->andReturnTrue();

        Auth::shouldReceive('check')->once()->andReturnTrue();
        Auth::shouldReceive('user')->once()->andReturn($user);

        $response = (new CheckRole)->handle(
            Request::create('/admin/facturas', 'GET'),
            fn ($request) => response('ok'),
            'secretaria',
            'administrador'
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', $response->getContent());
    }

    public function test_user_without_an_allowed_role_is_forbidden(): void
    {
        $user = Mockery::mock();
        $user->shouldReceive('hasAnyRole')->once()->with(['tecnico'])->andReturnFalse();

        Auth::shouldReceive('check')->once()->andReturnTrue();
        Auth::shouldReceive('user')->once()->andReturn($user);

        try {
            (new CheckRole)->handle(
                Request::create('/admin/facturas', 'GET'),
                fn ($request) => response('ok'),
                'tecnico'
            );
            $this->fail('Expected a forbidden response.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_guest_is_redirected_to_login(): void
    {
        Auth::shouldReceive('check')->once()->andReturnFalse();

        $response = (new CheckRole)->handle(
            Request::create('/admin/facturas', 'GET'),
            fn ($request) => response('ok'),
            'secretaria'
        );

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('login'), $response->headers->get('Location'));
    }
}
