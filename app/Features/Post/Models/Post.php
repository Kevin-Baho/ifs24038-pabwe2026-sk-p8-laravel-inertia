<?php

namespace App\Features\Post\Models;

use App\Features\Post\Enums\PostCategory;
use App\Models\User;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'user_id', 'title', 'slug', 'category', 'excerpt', 'content',
    'cover_image_path', 'is_published', 'published_at',
])]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    protected $table = 'posts';

    protected static function booted(): void
    {
        // Slug dibuat otomatis dari judul bila belum diisi, dan dijamin unik.
        static::creating(function (Post $post) {
            if (blank($post->slug)) {
                $post->slug = static::generateUniqueSlug((string) $post->title);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => PostCategory::class,
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function coverImageUrl(): ?string
    {
        if ($this->cover_image_path) {
            return Storage::disk(config('filesystems.media_disk', 'public'))->url($this->cover_image_path);
        }

        return null;
    }

    /** Estimasi waktu baca (menit), 200 kata per menit. */
    public function readingTimeMinutes(): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags((string) $this->content)) / 200));
    }

    public static function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::limit(Str::slug($title) ?: 'artikel', 200, '');
        $slug = $base;
        $i = 2;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn (Builder $q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    protected static function newFactory(): PostFactory
    {
        return PostFactory::new();
    }
}
