<?php

namespace Tests\Feature;

use App\Features\Profile\Services\ProfileService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_edit_screen_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profile/Edit')
                ->where('user.id', $user->id)
                ->where('user.email', $user->email)
            );
    }

    public function test_user_can_update_profile_information(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->patch('/profile', [
                'name' => 'Nama Baru',
                'username' => 'namabaru',
                'email' => 'baru@example.com',
            ]);

        $response->assertRedirect();
        $user->refresh();

        $this->assertSame('Nama Baru', $user->name);
        $this->assertSame('namabaru', $user->username);
        $this->assertSame('baru@example.com', $user->email);
    }

    public function test_user_can_update_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password_lama_123'),
        ]);

        $response = $this->actingAs($user)
            ->put('/profile/password', [
                'current_password' => 'password_lama_123',
                'password' => 'password_baru_456',
                'password_confirmation' => 'password_baru_456',
            ]);

        $response->assertRedirect();
        $user->refresh();

        $this->assertTrue(Hash::check('password_baru_456', $user->password));
    }

    public function test_user_cannot_update_password_with_incorrect_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password_benar'),
        ]);

        $response = $this->actingAs($user)
            ->from('/profile')
            ->put('/profile/password', [
                'current_password' => 'password_salah',
                'password' => 'password_baru_456',
                'password_confirmation' => 'password_baru_456',
            ]);

        $response->assertRedirect('/profile');
        $response->assertSessionHasErrors('current_password');
    }

    public function test_user_can_upload_and_delete_profile_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        // 1. Upload via file
        $file = UploadedFile::fake()->image('avatar.jpg', 600, 400);

        $response = $this->actingAs($user)
            ->post('/profile/photo', ['photo' => $file]);

        $response->assertRedirect();
        $user->refresh();

        $this->assertNotNull($user->profile_photo_path);
        Storage::disk('public')->assertExists($user->profile_photo_path);

        $oldPath = $user->profile_photo_path;

        // 2. Upload via base64 data URI string
        $image = imagecreatetruecolor(100, 100);
        ob_start();
        imagepng($image);
        $pngBinary = ob_get_clean();
        imagedestroy($image);

        $base64Data = 'data:image/png;base64,' . base64_encode($pngBinary);

        $this->actingAs($user)
            ->post('/profile/photo', ['photo' => $base64Data])
            ->assertRedirect();

        $user->refresh();
        $this->assertNotSame($oldPath, $user->profile_photo_path);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($user->profile_photo_path);

        // 3. Delete photo
        $this->actingAs($user)
            ->delete('/profile/photo')
            ->assertRedirect();

        $user->refresh();
        $this->assertNull($user->profile_photo_path);
    }

    public function test_profile_service_handles_file_path_and_raw_binary_and_invalid(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $service = app(ProfileService::class);

        // Valid GD image binary
        $img = imagecreatetruecolor(50, 50);
        ob_start();
        imagepng($img);
        $bin = ob_get_clean();
        imagedestroy($img);

        // Upload directly using raw binary
        $path1 = $service->updateProfilePhoto($user, $bin);
        $this->assertNotNull($path1);

        // Upload using local file path
        $tempFile = tempnam(sys_get_temp_dir(), 'avatar_test');
        file_put_contents($tempFile, $bin);

        $path2 = $service->updateProfilePhoto($user, $tempFile);
        $this->assertNotNull($path2);
        @unlink($tempFile);

        // Delete photo when already null
        $user->profile_photo_path = null;
        $user->save();
        $this->assertTrue($service->deleteProfilePhoto($user));

        // Expect exception on invalid image data
        $this->expectException(RuntimeException::class);
        $service->updateProfilePhoto($user, 'not_an_image_content');
    }
}
