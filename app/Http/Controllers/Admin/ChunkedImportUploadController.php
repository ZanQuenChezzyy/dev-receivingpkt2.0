<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChunkedImportUploadController extends Controller
{
    public const string TEMP_DIRECTORY = 'temp-monitoring-barang-imports';

    public const int MAX_FILE_BYTES = 52_428_800;

    public const int MAX_CHUNK_KILOBYTES = 1536;

    /**
     * @var array<int, string>
     */
    public const array ALLOWED_EXTENSIONS = ['xlsx', 'csv'];

    /**
     * Menerima potongan file upload (.xlsx / .csv) dan menggabungkannya setelah semua bagian lengkap.
     */
    public function store(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->getKey();

        $validated = $request->validate([
            'upload_id' => ['required', 'uuid'],
            'chunk_index' => ['required', 'integer', 'min:0', 'max:1024'],
            'total_chunks' => ['required', 'integer', 'min:1', 'max:1024'],
            'total_size' => ['required', 'integer', 'min:1', 'max:'.self::MAX_FILE_BYTES],
            'original_name' => ['required', 'string', 'max:255'],
            'chunk' => ['required', 'file', 'max:'.self::MAX_CHUNK_KILOBYTES],
        ]);

        $uploadId = strtolower((string) $validated['upload_id']);
        $chunkIndex = (int) $validated['chunk_index'];
        $totalChunks = (int) $validated['total_chunks'];
        $expectedSize = (int) $validated['total_size'];
        $originalName = basename((string) $validated['original_name']);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if ($chunkIndex >= $totalChunks) {
            return response()->json([
                'message' => 'Indeks potongan file tidak valid.',
            ], 422);
        }

        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return response()->json([
                'message' => 'Format file harus .xlsx atau .csv.',
            ], 422);
        }

        $disk = Storage::disk('local');

        if ($chunkIndex === 0) {
            $this->cleanupStaleTempEntries($disk);
        }

        $chunkDir = $this->buildChunkDirectory($userId, $uploadId);
        $disk->putFileAs($chunkDir, $request->file('chunk'), "{$chunkIndex}.part");

        if (! $this->hasAllChunks($disk, $chunkDir, $totalChunks)) {
            return response()->json([
                'status' => 'uploading',
                'chunk_index' => $chunkIndex,
                'total_chunks' => $totalChunks,
            ]);
        }

        $finalRelativePath = self::buildFinalRelativePath($userId, $uploadId, $extension);

        try {
            $assembledSize = $this->assembleChunks($disk, $chunkDir, $totalChunks, $expectedSize, $finalRelativePath);
        } catch (\Throwable $e) {
            $disk->delete($finalRelativePath);
            $disk->deleteDirectory($chunkDir);

            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        $disk->deleteDirectory($chunkDir);

        return response()->json([
            'status' => 'completed',
            'path' => $finalRelativePath,
            'name' => $originalName,
            'size' => $assembledSize,
        ]);
    }

    /**
     * Menghapus file sementara hasil unggahan bertahap atau potongan yang dibatalkan.
     */
    public function destroy(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->getKey();

        $validated = $request->validate([
            'path' => ['nullable', 'string', 'max:255'],
            'upload_id' => ['nullable', 'uuid'],
        ]);

        $disk = Storage::disk('local');

        $ownedPath = self::normalizeOwnedRelativePath($validated['path'] ?? null, $userId);
        if ($ownedPath !== null && $disk->exists($ownedPath)) {
            $disk->delete($ownedPath);
        }

        $uploadId = isset($validated['upload_id']) ? strtolower((string) $validated['upload_id']) : null;
        if ($uploadId !== null && Str::isUuid($uploadId)) {
            $chunkDir = $this->buildChunkDirectory($userId, $uploadId);
            if ($disk->exists($chunkDir)) {
                $disk->deleteDirectory($chunkDir);
            }
        }

        return response()->json(['status' => 'deleted']);
    }

    /**
     * Memastikan path relatif berada di folder impor sementara dan dimiliki oleh user terkait.
     */
    public static function normalizeOwnedRelativePath(?string $relativePath, ?int $userId): ?string
    {
        if ($userId === null || $relativePath === null) {
            return null;
        }

        $normalized = ltrim(str_replace('\\', '/', trim($relativePath)), '/');
        if ($normalized === '' || str_contains($normalized, '..')) {
            return null;
        }

        $expectedPrefix = self::TEMP_DIRECTORY.'/'.$userId.'-';
        if (! str_starts_with($normalized, $expectedPrefix)) {
            return null;
        }

        $extension = strtolower(pathinfo($normalized, PATHINFO_EXTENSION));
        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return null;
        }

        return $normalized;
    }

    public static function buildFinalRelativePath(int $userId, string $uploadId, string $extension): string
    {
        return self::TEMP_DIRECTORY."/{$userId}-{$uploadId}.{$extension}";
    }

    protected function buildChunkDirectory(int $userId, string $uploadId): string
    {
        return self::TEMP_DIRECTORY."/chunks/{$userId}/{$uploadId}";
    }

    protected function hasAllChunks(Filesystem $disk, string $chunkDir, int $totalChunks): bool
    {
        for ($i = 0; $i < $totalChunks; $i++) {
            if (! $disk->exists("{$chunkDir}/{$i}.part")) {
                return false;
            }
        }

        return true;
    }

    protected function assembleChunks(
        Filesystem $disk,
        string $chunkDir,
        int $totalChunks,
        int $expectedSize,
        string $finalRelativePath
    ): int {
        $finalAbsolutePath = $disk->path($finalRelativePath);
        $finalDir = dirname($finalAbsolutePath);

        if (! is_dir($finalDir)) {
            mkdir($finalDir, 0755, true);
        }

        $actualTotalSize = 0;
        $outHandle = fopen($finalAbsolutePath, 'wb');
        if ($outHandle === false) {
            throw new \RuntimeException('Gagal membuat file gabungan di penyimpanan sementara.');
        }

        try {
            for ($i = 0; $i < $totalChunks; $i++) {
                $partRelativePath = "{$chunkDir}/{$i}.part";
                $partAbsolutePath = $disk->path($partRelativePath);
                $partSize = @filesize($partAbsolutePath);

                if ($partSize === false) {
                    throw new \RuntimeException("Potongan file ke-{$i} tidak ditemukan.");
                }

                $actualTotalSize += $partSize;
                if ($actualTotalSize > self::MAX_FILE_BYTES) {
                    throw new \RuntimeException('Total ukuran file melebihi batas maksimal 50 MB.');
                }

                $inHandle = fopen($partAbsolutePath, 'rb');
                if ($inHandle === false) {
                    throw new \RuntimeException("Gagal membaca potongan file ke-{$i}.");
                }

                try {
                    stream_copy_to_stream($inHandle, $outHandle);
                } finally {
                    fclose($inHandle);
                }
            }
        } catch (\Throwable $e) {
            fclose($outHandle);
            @unlink($finalAbsolutePath);

            throw $e;
        }

        fclose($outHandle);

        if ($actualTotalSize !== $expectedSize) {
            @unlink($finalAbsolutePath);

            throw new \RuntimeException('Ukuran file hasil penggabungan tidak cocok dengan ukuran file asli.');
        }

        return $actualTotalSize;
    }

    protected function cleanupStaleTempEntries(Filesystem $disk): void
    {
        $cutoff = now()->subHours(2)->getTimestamp();

        foreach ($disk->files(self::TEMP_DIRECTORY) as $file) {
            try {
                if ($disk->lastModified($file) < $cutoff) {
                    $disk->delete($file);
                }
            } catch (\Throwable) {
                continue;
            }
        }

        $chunksRoot = self::TEMP_DIRECTORY.'/chunks';
        if (! $disk->exists($chunksRoot)) {
            return;
        }

        foreach ($disk->allFiles($chunksRoot) as $chunkFile) {
            try {
                if ($disk->lastModified($chunkFile) < $cutoff) {
                    $disk->delete($chunkFile);
                }
            } catch (\Throwable) {
                continue;
            }
        }
    }
}
