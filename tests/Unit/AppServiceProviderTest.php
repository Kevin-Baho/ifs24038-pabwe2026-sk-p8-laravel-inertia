<?php

namespace Tests\Unit;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\Services\OpenAiPostGeneratorService;
use App\Features\Post\Contracts\PostRepositoryInterface;
use App\Features\Post\Models\Post;
use App\Features\Post\Policies\PostPolicy;
use App\Features\Post\Repositories\PostRepository;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    public function test_register_binds_interfaces_to_implementations(): void
    {
        $provider = new AppServiceProvider($this->app);
        $provider->register();

        $this->assertInstanceOf(PostRepository::class, $this->app->make(PostRepositoryInterface::class));
        $this->assertInstanceOf(OpenAiPostGeneratorService::class, $this->app->make(AiClientInterface::class));
    }

    public function test_boot_forces_https_when_configured(): void
    {
        $originalForceHttps = config('app.force_https');

        try {
            config(['app.force_https' => true]);

            $provider = new AppServiceProvider($this->app);
            $provider->boot();

            $this->assertStringStartsWith('https://', URL::to('/'));
        } finally {
            config(['app.force_https' => $originalForceHttps]);
            URL::forceScheme(null);
        }
    }

    public function test_boot_registers_post_policy(): void
    {
        $provider = new AppServiceProvider($this->app);
        $provider->boot();

        $policy = Gate::getPolicyFor(Post::class);
        $this->assertInstanceOf(PostPolicy::class, $policy);
    }

    public function test_ai_generate_rate_limiter_for_authenticated_user_and_guest(): void
    {
        $provider = new AppServiceProvider($this->app);
        $provider->boot();

        $limiter = RateLimiter::limiter('ai-generate');
        $this->assertNotNull($limiter);

        // 1. Authenticated user request
        $user = User::factory()->make(['id' => 99]);
        $userRequest = Request::create('/dashboard/posts/ai-generate', 'POST');
        $userRequest->setUserResolver(fn () => $user);

        $limitUser = $limiter($userRequest);
        $this->assertInstanceOf(Limit::class, $limitUser);
        $this->assertSame(10, $limitUser->maxAttempts);
        $this->assertSame(99, $limitUser->key);

        // 2. Guest request (resolves by IP)
        $guestRequest = Request::create('/dashboard/posts/ai-generate', 'POST', [], [], [], [
            'REMOTE_ADDR' => '192.168.1.100',
        ]);

        $limitGuest = $limiter($guestRequest);
        $this->assertInstanceOf(Limit::class, $limitGuest);
        $this->assertSame(10, $limitGuest->maxAttempts);
        $this->assertSame('192.168.1.100', $limitGuest->key);
    }
}
