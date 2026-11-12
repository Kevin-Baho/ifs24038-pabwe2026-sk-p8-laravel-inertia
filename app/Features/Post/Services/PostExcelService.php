<?php

namespace App\Features\Post\Services;

use App\Features\Post\Contracts\PostRepositoryInterface;
use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Models\Post;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PostExcelService
{
    public const MAX_IMPORT_ROWS = 500;

    /** Kolom ekspor member. Ekspor admin menambah Penulis & Username di akhir. */
    private const HEADERS = ['No', 'Judul', 'Kategori', 'Ringkasan', 'Konten', 'Status', 'Tanggal Publikasi', 'Dibuat'];

    public function __construct(
        protected PostRepositoryInterface $posts,
        protected PostService $postService,
    ) {}

    /** @param Collection<int, Post> $posts */
    public function buildSpreadsheet(Collection $posts, bool $withAuthor = false, string $sheetTitle = 'Artikel'): Spreadsheet
    {
        $headers = $withAuthor ? [...self::HEADERS, 'Penulis', 'Username'] : self::HEADERS;
        $lastCol = $this->columnLetter(count($headers));

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(mb_substr($sheetTitle, 0, 31));

        foreach ($headers as $i => $header) {
            $sheet->setCellValueExplicit($this->columnLetter($i + 1).'1', $header, DataType::TYPE_STRING);
        }

        $headerStyle = $sheet->getStyle("A1:{$lastCol}1");
        $headerStyle->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('3730A3');
        $headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row = 2;
        foreach ($posts as $index => $post) {
            $values = [
                $index + 1,
                $post->title,
                $post->category->value,
                $post->excerpt ?? '',
                $post->content,
                $post->is_published ? 'Published' : 'Draft',
                $post->published_at?->format('Y-m-d H:i') ?? '',
                $post->created_at?->format('Y-m-d H:i') ?? '',
            ];
            if ($withAuthor) {
                $values[] = $post->user?->name ?? '';
                $values[] = $post->user?->username ?? '';
            }

            foreach ($values as $i => $value) {
                // Semua sel ditulis sebagai teks eksplisit: mencegah injeksi formula (=, +, -, @).
                $sheet->setCellValueExplicit($this->columnLetter($i + 1).$row, (string) $value, DataType::TYPE_STRING);
            }
            $row++;
        }

        $lastRow = max(2, $row - 1);
        $sheet->getStyle("A1:{$lastCol}{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("A2:{$lastCol}{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

        foreach (range(1, count($headers)) as $n) {
            $sheet->getColumnDimension($this->columnLetter($n))->setAutoSize(true);
        }
        // Kolom teks panjang dibatasi lebarnya agar file tetap nyaman dibaca.
        $sheet->getColumnDimension('D')->setAutoSize(false)->setWidth(50);
        $sheet->getColumnDimension('E')->setAutoSize(false)->setWidth(70);
        $sheet->getStyle("D2:E{$lastRow}")->getAlignment()->setWrapText(true);
        $sheet->freezePane('A2');

        return $spreadsheet;
    }

    /** @param Collection<int, Post> $posts */
    public function exportToResponse(Collection $posts, string $filename, bool $withAuthor = false): StreamedResponse
    {
        $spreadsheet = $this->buildSpreadsheet($posts, $withAuthor);

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Impor artikel (selalu masuk sebagai milik $userId). Kolom dikenali dari nama header,
     * sehingga file hasil ekspor bisa langsung diimpor kembali.
     *
     * @return array{imported: int, skipped: int}
     */
    public function import(string $filePath, int $userId): array
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $rows = $reader->load($filePath)->getActiveSheet()->toArray(null, false, false, false);

        if ($rows === []) {
            return ['imported' => 0, 'skipped' => 0];
        }

        $map = $this->mapHeaders(array_shift($rows));
        $rows = array_slice($rows, 0, self::MAX_IMPORT_ROWS);

        $imported = 0;
        $skipped = 0;

        DB::transaction(function () use ($rows, $map, $userId, &$imported, &$skipped) {
            foreach ($rows as $row) {
                $title = trim((string) ($row[$map['title']] ?? ''));
                if ($title === '') {
                    $skipped++;
                    continue;
                }

                $excerpt = isset($map['excerpt']) ? trim((string) ($row[$map['excerpt']] ?? '')) : '';
                $content = isset($map['content']) ? trim((string) ($row[$map['content']] ?? '')) : '';

                $this->postService->create($userId, [
                    'title' => mb_substr($title, 0, 255),
                    'category' => PostCategory::fromLoose(isset($map['category']) ? (string) ($row[$map['category']] ?? '') : null)->value,
                    'excerpt' => $excerpt,
                    'content' => $content !== '' ? $content : ($excerpt !== '' ? $excerpt : $title),
                    'is_published' => $this->isPublishedLabel(isset($map['status']) ? (string) ($row[$map['status']] ?? '') : ''),
                ]);
                $imported++;
            }
        });

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    /**
     * @param array<int, mixed> $headerRow
     * @return array<string, int>
     */
    private function mapHeaders(array $headerRow): array
    {
        $aliases = [
            'title' => ['judul', 'title'],
            'category' => ['kategori', 'category'],
            'excerpt' => ['ringkasan', 'excerpt'],
            'content' => ['konten', 'content', 'isi'],
            'status' => ['status'],
        ];

        $map = [];
        foreach ($headerRow as $index => $label) {
            $label = mb_strtolower(trim((string) $label));
            foreach ($aliases as $field => $names) {
                if (! isset($map[$field]) && in_array($label, $names, true)) {
                    $map[$field] = (int) $index;
                }
            }
        }

        // Fallback untuk file tanpa header yang dikenali: kolom B = judul.
        $map['title'] ??= 1;

        return $map;
    }

    private function isPublishedLabel(string $value): bool
    {
        return in_array(mb_strtolower(trim($value)), ['published', 'terbit', 'diterbitkan', 'selesai', 'publikasi', 'ya', 'true', '1'], true);
    }

    private function columnLetter(int $index): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index);
    }
}
