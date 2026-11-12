<?php

namespace Tests\Feature;

use App\Features\Post\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicPostTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_check_returns_ok_status(): void
    {
        $response = $this->get('/health');
        $response->assertOk();
        $response->assertExactJson(['status' => 'ok']);
    }

    public function test_guest_can_open_home_and_article_list(): void
    {
        Post::factory()->published()->count(2)->create();
        Post::factory()->draft()->create();

        $this->get('/')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Public/Home')
            ->has('latest', 2)
            ->has('categories', 6));

        $this->get('/articles')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Public/Index')
            ->has('posts.data', 2));
    }

    public function test_article_list_can_be_filtered_by_search_and_category(): void
    {
        Post::factory()->published()->create(['title' => 'Belajar Laravel', 'category' => 'Teknologi']);
        Post::factory()->published()->create(['title' => 'Resep Sehat', 'category' => 'Gaya Hidup']);

        $this->get('/articles?search=laravel')->assertInertia(fn (Assert $p) => $p->has('posts.data', 1));
        $this->get('/articles?category=Gaya%20Hidup')->assertInertia(fn (Assert $p) => $p
            ->has('posts.data', 1)
            ->where('posts.data.0.title', 'Resep Sehat'));
    }

    public function test_published_article_is_readable_and_draft_is_not(): void
    {
        $published = Post::factory()->published()->create();
        $draft = Post::factory()->draft()->create();

        $this->get("/articles/{$published->slug}")->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Public/Show')
            ->where('post.slug', $published->slug)
            ->has('related'));

        $this->get("/articles/{$draft->slug}")->assertNotFound();
    }
}
