<?php

namespace App\Features\Post\Enums;

enum PostCategory: string
{
    case TEKNOLOGI = 'Teknologi';
    case PENDIDIKAN = 'Pendidikan';
    case GAYA_HIDUP = 'Gaya Hidup';
    case BISNIS = 'Bisnis';
    case OPINI = 'Opini';
    case UMUM = 'Umum';

    public function label(): string
    {
        return $this->value;
    }

    /** Kelas badge Tailwind untuk daftar dan halaman baca. */
    public function badgeClass(): string
    {
        return match ($this) {
            self::TEKNOLOGI => 'bg-sky-50 text-sky-800 ring-sky-200',
            self::PENDIDIKAN => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
            self::GAYA_HIDUP => 'bg-rose-50 text-rose-800 ring-rose-200',
            self::BISNIS => 'bg-amber-50 text-amber-900 ring-amber-200',
            self::OPINI => 'bg-violet-50 text-violet-800 ring-violet-200',
            self::UMUM => 'bg-slate-100 text-slate-700 ring-slate-200',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }

    /** @return array<int, array{value: string, label: string, badgeClass: string}> */
    public static function options(): array
    {
        return array_map(fn (self $c) => [
            'value' => $c->value,
            'label' => $c->label(),
            'badgeClass' => $c->badgeClass(),
        ], self::cases());
    }

    /** Pemetaan longgar (impor Excel / output AI) ke kategori valid; default Umum. */
    public static function fromLoose(?string $raw): self
    {
        $needle = mb_strtolower(trim((string) $raw));

        foreach (self::cases() as $case) {
            if (mb_strtolower($case->value) === $needle) {
                return $case;
            }
        }

        return self::UMUM;
    }
}
