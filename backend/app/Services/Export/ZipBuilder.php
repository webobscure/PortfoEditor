<?php

declare(strict_types=1);

namespace App\Services\Export;

use RuntimeException;
use ZipArchive;

/**
 * Thin wrapper over ZipArchive that writes to a temporary file.
 *
 * Kept separate from the export service so the archive layout is described in
 * one place and so the service stays testable without touching zip internals.
 */
final class ZipBuilder
{
    private ZipArchive $zip;

    private string $path;

    public function __construct()
    {
        $path = tempnam(sys_get_temp_dir(), 'pf-export-');

        if ($path === false) {
            throw new RuntimeException('Unable to allocate a temporary file for the export.');
        }

        $this->path = $path;
        $this->zip = new ZipArchive;

        if ($this->zip->open($this->path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to open the export archive for writing.');
        }
    }

    public function addString(string $entry, string $contents): self
    {
        $this->zip->addFromString($entry, $contents);

        return $this;
    }

    public function addFile(string $entry, string $sourcePath): self
    {
        if (is_file($sourcePath)) {
            $this->zip->addFile($sourcePath, $entry);
        }

        return $this;
    }

    /** Finalises the archive and returns the path to it. */
    public function finish(): string
    {
        if (! $this->zip->close()) {
            throw new RuntimeException('Unable to finalise the export archive.');
        }

        return $this->path;
    }

    public function discard(): void
    {
        @$this->zip->close();

        if (is_file($this->path)) {
            @unlink($this->path);
        }
    }
}
