<?php

namespace Tests\Feature;

use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_cannot_open_admin_area(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)->get('/admin/users')->assertForbidden();
        $this->actingAs($member)->get('/admin/posts')->assertForbidden();
    }

    public function test_admin_sees_users_with_post_counts(): void
    {
        $admin = User::factory()->admin()->create();
        $writer = User::factory()->create();
        Post::factory()->published()->create(['user_id' => $writer->id]);
        Post::factory()->draft()->create(['user_id' => $writer->id]);

        $this->actingAs($admin)->get('/admin/users')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Admin/Users')
            ->has('users.data', 2)
            ->where('stats.posts.total', 2)
            ->where('stats.users.total', 2));
    }

    public function test_admin_monitors_all_posts_and_user_posts_read_only(): void
    {
        $admin = User::factory()->admin()->create();
        $writer = User::factory()->create();
        $post = Post::factory()->draft()->create(['user_id' => $writer->id]);
        Post::factory()->published()->create();

        $this->actingAs($admin)->get('/admin/posts')->assertInertia(fn (Assert $p) => $p
            ->component('Admin/Posts')->has('posts.data', 2));

        $this->actingAs($admin)->get("/admin/users/{$writer->id}/posts")->assertInertia(fn (Assert $p) => $p
            ->component('Admin/UserPosts')->has('posts.data', 1));

        $this->actingAs($admin)->get("/admin/posts/{$post->id}")->assertInertia(fn (Assert $p) => $p
            ->component('Post/Show')->where('readOnly', true));

        // Admin tidak punya jalur ubah/hapus artikel orang lain.
        $this->actingAs($admin)->put("/dashboard/posts/{$post->id}", ['title' => 'x', 'category' => 'Umum', 'content' => 'y'])->assertForbidden();
        $this->actingAs($admin)->delete("/dashboard/posts/{$post->id}")->assertForbidden();
        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    public function test_admin_exports_system_and_per_user_excel(): void
    {
        $admin = User::factory()->admin()->create();
        $writer = User::factory()->create();
        Post::factory()->count(2)->create(['user_id' => $writer->id]);

        foreach (['/admin/posts/export', "/admin/users/{$writer->id}/posts/export"] as $url) {
            $response = $this->actingAs($admin)->get($url);
            $response->assertOk();
            $this->assertStringContainsString('spreadsheetml', $response->headers->get('content-type'));
        }
    }

    public function test_admin_can_change_role_but_not_own_role_or_delete_self(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();

        $this->actingAs($admin)->patch("/admin/users/{$member->id}/role", ['role' => 'admin']);
        $this->assertSame('admin', $member->fresh()->role);

        $this->actingAs($admin)->patch("/admin/users/{$admin->id}/role", ['role' => 'member'])->assertSessionHas('error');
        $this->assertSame('admin', $admin->fresh()->role);

        $this->actingAs($admin)->delete("/admin/users/{$admin->id}")->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_seeder_creates_admin_from_env(): void
    {
        putenv('ADMIN_EMAIL=super@blogverse.test');
        putenv('ADMIN_USERNAME=superadmin');
        putenv('ADMIN_PASSWORD=secret123');
        putenv('ADMIN_NAME=Super Admin');

        $this->seed(\Database\Seeders\AdminSeeder::class);

        $this->assertDatabaseHas('users', [
            'email' => 'super@blogverse.test',
            'username' => 'superadmin',
            'role' => 'admin',
        ]);

        putenv('ADMIN_EMAIL');
        putenv('ADMIN_USERNAME');
        putenv('ADMIN_PASSWORD');
        putenv('ADMIN_NAME');
    }

    public function test_deleting_user_cascades_to_posts(): void
    {
        $admin = User::factory()->admin()->create();
        $writer = User::factory()->create();
        Post::factory()->count(2)->create(['user_id' => $writer->id]);

        $this->actingAs($admin)->delete("/admin/users/{$writer->id}")->assertRedirect(route('admin.users'));

        $this->assertDatabaseMissing('users', ['id' => $writer->id]);
        $this->assertDatabaseCount('posts', 0);
    }
}
