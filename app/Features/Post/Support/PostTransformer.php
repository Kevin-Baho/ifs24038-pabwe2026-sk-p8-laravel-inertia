<?php

namespace App\Features\Post\Support;

use App\Features\Post\Models\Post;
use Illuminate\Support\Facades\Storage;

/**
 * Mengubah model Post menjadi array props untuk Inertia (lapisan presentasi).
 */
class PostTransformer
{
    /** Baris tabel member (termasuk konten agar modal Ubah tidak perlu request tambahan). */
    public static function row(Post $post): array
    {
        return [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'category' => $post->category->value,
            'category_badge' => $post->category->badgeClass(),
            'excerpt' => $post->excerpt,
            'content' => $post->content,
            'cover_url' => $post->coverImageUrl(),
            'is_published' => $post->is_published,
            'published_at' => $post->published_at?->format('d M Y H:i'),
            'created_at' => $post->created_at?->format('d M Y H:i'),
            'updated_at' => $post->updated_at?->format('d M Y H:i'),
            'public_url' => $post->is_published ? route('articles.show', $post->slug) : null,
        ];
    }

    /** Baris monitoring admin (tanpa konten; read-only). */
    public static function adminRow(Post $post): array
    {
        $row = self::row($post);
        unset($row['content']);

        return $row + [
            'author' => $post->user ? [
                'id' => $post->user->id,
                'name' => $post->user->name,
                'username' => $post->user->username,
            ] : null,
        ];
    }

    /** Kartu artikel publik. */
    public static function card(Post $post): array
    {
        return [
            'id' => $post->id,
            'slug' => $post->slug,
            'title' => $post->title,
            'excerpt' => $post->excerpt,
            'category' => $post->category->value,
            'category_badge' => $post->category->badgeClass(),
            'cover_url' => $post->coverImageUrl(),
            'author' => self::author($post),
            'published_at' => $post->published_at?->translatedFormat('d F Y'),
            'reading_time' => $post->readingTimeMinutes(),
        ];
    }

    /** Halaman baca (publik / detail member / detail admin). */
    public static function detail(Post $post): array
    {
        return self::card($post) + [
            'content' => $post->content,
            'is_published' => $post->is_published,
            'created_at' => $post->created_at?->format('d M Y H:i'),
            'updated_at' => $post->updated_at?->format('d M Y H:i'),
        ];
    }

    private static function author(Post $post): ?array
    {
        $user = $post->user;
        if (! $user) {
            return null;
        }

        return [
            'name' => $user->name,
            'username' => $user->username,
            'avatar_url' => $user->profile_photo_path
                ? Storage::disk(config('filesystems.media_disk', 'public'))->url($user->profile_photo_path)
                : null,
        ];
    }
}
