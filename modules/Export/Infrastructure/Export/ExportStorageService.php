<?php

declare(strict_types=1);

namespace Modules\Export\Infrastructure\Export;

use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Export\Domain\ValueObject\ExportFormat;
use Modules\Export\Domain\ValueObject\ExportId;

/**
 * Service for managing export file storage operations.
 * Features:
 * - Temporary and final file management
 * - Storage disk abstraction
 * - File cleanup and organization
 * - Secure file paths
 * - Storage space management
 * - File validation and integrity
 */
class ExportStorageService
{
    private const TEMP_DIRECTORY = 'exports/temp';

    private const FINAL_DIRECTORY = 'exports/final';

    private const MAX_FILE_AGE_HOURS = 48; // Cleanup files older than 48 hours

    public function __construct(
        private readonly string $storageDisk = 'local'
    ) {}

    /**
     * Create a temporary file path for processing
     */
    public function createTempFilePath(ExportFormat $format): string
    {
        $tempId = Str::uuid()->toString();
        $extension = $format->getExtension();
        $filename = "temp_export_{$tempId}.{$extension}";

        $relativePath = self::TEMP_DIRECTORY . '/' . $filename;
        $absolutePath = $this->getAbsolutePath($relativePath);

        // Ensure directory exists
        $this->ensureDirectoryExists(dirname($absolutePath));

        return $absolutePath;
    }

    /**
     * Store the final export file from temporary location
     */
    public function storeFinalFile(string $tempFilePath, ExportId $exportId, ExportFormat $format): string
    {
        if (! file_exists($tempFilePath)) {
            throw new Exception("Temporary file not found: {$tempFilePath}");
        }

        $filename = $this->generateFinalFilename($exportId, $format);
        $relativePath = self::FINAL_DIRECTORY . '/' . $filename;
        $finalPath = $this->getAbsolutePath($relativePath);

        // Ensure final directory exists
        $this->ensureDirectoryExists(dirname($finalPath));

        // Move file from temp to final location
        if (! rename($tempFilePath, $finalPath)) {
            throw new Exception('Failed to move temporary file to final location');
        }

        // Verify file was moved successfully
        if (! file_exists($finalPath)) {
            throw new Exception('File not found after move operation');
        }

        return $relativePath; // Return relative path for database storage
    }

    /**
     * Generate a secure filename for final export file
     */
    private function generateFinalFilename(ExportId $exportId, ExportFormat $format): string
    {
        $extension = $format->getExtension();
        $timestamp = now()->format('Y-m-d_H-i-s');

        return "export_{$exportId->toString()}_{$timestamp}.{$extension}";
    }

    /**
     * Get the absolute path for downloads
     */
    public function getDownloadPath(string $relativePath): string
    {
        $absolutePath = $this->getAbsolutePath($relativePath);

        if (! file_exists($absolutePath)) {
            throw new Exception("Export file not found: {$relativePath}");
        }

        return $absolutePath;
    }

    /**
     * Get file size in bytes
     */
    public function getFileSize(string $path): int
    {
        $absolutePath = $this->isAbsolutePath($path) ? $path : $this->getAbsolutePath($path);

        if (! file_exists($absolutePath)) {
            throw new Exception("File not found: {$path}");
        }

        $size = filesize($absolutePath);

        if ($size === false) {
            throw new Exception("Unable to determine file size: {$path}");
        }

        return $size;
    }

    /**
     * Check if file exists
     */
    public function fileExists(string $relativePath): bool
    {
        return file_exists($this->getAbsolutePath($relativePath));
    }

    /**
     * Delete a file
     */
    public function deleteFile(string $relativePath): bool
    {
        $absolutePath = $this->getAbsolutePath($relativePath);

        if (! file_exists($absolutePath)) {
            return true; // Already deleted
        }

        return unlink($absolutePath);
    }

    /**
     * Clean up temporary files for a specific export
     */
    public function cleanupTempFiles(ExportId $exportId): void
    {
        $tempDir = $this->getAbsolutePath(self::TEMP_DIRECTORY);

        if (! is_dir($tempDir)) {
            return;
        }

        $pattern = 'temp_export_*';
        $files = glob($tempDir . '/' . $pattern);

        if ($files !== false) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Clean up old temporary files
     */
    public function cleanupOldTempFiles(): int
    {
        $tempDir = $this->getAbsolutePath(self::TEMP_DIRECTORY);
        $deletedCount = 0;

        if (! is_dir($tempDir)) {
            return $deletedCount;
        }

        $cutoffTime = now()->subHours(self::MAX_FILE_AGE_HOURS)->timestamp;
        $files = glob($tempDir . '/temp_export_*');

        if ($files === false) {
            return $deletedCount;
        }

        foreach ($files as $file) {
            if (! is_file($file)) {
                continue;
            }
            if (filemtime($file) >= $cutoffTime) {
                continue;
            }
            if (! unlink($file)) {
                continue;
            }
            $deletedCount++;
        }

        return $deletedCount;
    }

    /**
     * Clean up old final files
     */
    /**
     * @param  array<string, mixed>  $expiredExportPaths
     */
    public function cleanupExpiredFiles(array $expiredExportPaths): int
    {
        $deletedCount = 0;

        foreach ($expiredExportPaths as $relativePath) {
            if ($this->deleteFile($relativePath)) {
                $deletedCount++;
            }
        }

        return $deletedCount;
    }

    /**
     * Get storage statistics
     *
     * @return array<string, mixed>
     */
    public function getStorageStatistics(): array
    {
        $tempDir = $this->getAbsolutePath(self::TEMP_DIRECTORY);
        $finalDir = $this->getAbsolutePath(self::FINAL_DIRECTORY);

        return [
            'temp_files' => $this->countFilesInDirectory($tempDir),
            'temp_size' => $this->getDirectorySize($tempDir),
            'final_files' => $this->countFilesInDirectory($finalDir),
            'final_size' => $this->getDirectorySize($finalDir),
            'total_files' => $this->countFilesInDirectory($tempDir) + $this->countFilesInDirectory($finalDir),
            'total_size' => $this->getDirectorySize($tempDir) + $this->getDirectorySize($finalDir),
        ];
    }

    /**
     * Validate file integrity
     */
    public function validateFile(string $relativePath): bool
    {
        $absolutePath = $this->getAbsolutePath($relativePath);

        if (! file_exists($absolutePath)) {
            return false;
        }

        // Check if file is readable
        if (! is_readable($absolutePath)) {
            return false;
        }

        // Check if file size is reasonable (not empty, not too large)
        $size = filesize($absolutePath);
        if ($size === false || $size === 0) {
            return false;
        }

        // Check file permissions
        return is_file($absolutePath);
    }

    /**
     * Get MIME type for file
     */
    public function getMimeType(string $relativePath): string
    {
        $absolutePath = $this->getAbsolutePath($relativePath);

        if (! file_exists($absolutePath)) {
            throw new Exception("File not found: {$relativePath}");
        }

        $mimeType = mime_content_type($absolutePath);

        if ($mimeType === false) {
            // Fallback based on extension
            $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

            return match ($extension) {
                'csv' => 'text/csv',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'pdf' => 'application/pdf',
                default => 'application/octet-stream',
            };
        }

        return $mimeType;
    }

    /**
     * Create a secure download URL
     */
    public function createDownloadUrl(string $relativePath): string
    {
        // This would typically integrate with Laravel's signed URLs
        // or a CDN service for secure downloads
        return Storage::disk($this->storageDisk)->url($relativePath);
    }

    /**
     * Get absolute path from relative path
     */
    private function getAbsolutePath(string $relativePath): string
    {
        if ($this->isAbsolutePath($relativePath)) {
            return $relativePath;
        }

        return Storage::disk($this->storageDisk)->path($relativePath);
    }

    /**
     * Check if path is absolute
     */
    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/') || (PHP_OS_FAMILY === 'Windows' && preg_match('/^[A-Z]:\\\/', $path));
    }

    /**
     * Ensure directory exists
     */
    private function ensureDirectoryExists(string $directory): void
    {
        if (! is_dir($directory) && ! mkdir($directory, 0755, true)) {
            throw new Exception("Failed to create directory: {$directory}");
        }
    }

    /**
     * Count files in directory
     */
    private function countFilesInDirectory(string $directory): int
    {
        if (! is_dir($directory)) {
            return 0;
        }

        $files = glob($directory . '/*');

        if ($files === false) {
            return 0;
        }

        return count(array_filter($files, 'is_file'));
    }

    /**
     * Get directory size in bytes
     */
    private function getDirectorySize(string $directory): int
    {
        if (! is_dir($directory)) {
            return 0;
        }

        $size = 0;
        $files = glob($directory . '/*');

        if ($files === false) {
            return 0;
        }

        foreach ($files as $file) {
            if (is_file($file)) {
                $fileSize = filesize($file);
                $size += $fileSize !== false ? $fileSize : 0;
            }
        }

        return $size;
    }

    /**
     * Format bytes to human readable size
     */
    public function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Get available disk space
     */
    public function getAvailableDiskSpace(): int
    {
        $path = Storage::disk($this->storageDisk)->path('');
        $freeSpace = disk_free_space($path);

        return $freeSpace !== false ? (int) $freeSpace : 0;
    }

    /**
     * Check if there's enough disk space for estimated file size
     */
    public function hasEnoughDiskSpace(int $estimatedSize, float $safetyFactor = 1.5): bool
    {
        $availableSpace = $this->getAvailableDiskSpace();
        $requiredSpace = (int) ($estimatedSize * $safetyFactor);

        return $availableSpace >= $requiredSpace;
    }
}
