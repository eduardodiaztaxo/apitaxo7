<?php

namespace App\Services\Dump;

class SQLiteZipService
{
    /**
     * @var string Path to the SQLite database file
     */
    protected $sqlitePath;

    /**
     * @var string Path to the zip file created from the SQLite database
     */
    protected $zipFileName = null;

    /**
     * SQLiteZipService constructor.
     *
     * @param string $sqlitePath Path to the SQLite database file
     */
    public function __construct(string $sqlitePath)
    {
        $this->sqlitePath = $sqlitePath;
    }

    /**
     * Create a zip archive of the SQLite database.
     *
     * @return void
     */
    public function createZipArchive(): void
    {
        $zip = new \ZipArchive();
        $this->zipFileName = str_replace('.db', '.zip', $this->sqlitePath);

        if ($zip->open($this->zipFileName, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            $zip->addFile($this->sqlitePath, basename($this->sqlitePath));
            $zip->close();
        } else {
            throw new \Exception("Could not create zip archive: {$this->zipFileName}");
        }
    }

    /**
     * Get the path to the SQLite database file.
     *
     * @return string
     */
    public function getSQLitePath(): string
    {
        return $this->sqlitePath;
    }

    /**
     * Get the SQLite database zip path.
     *
     * @return string|null
     */
    public function getSQLiteZipPath(): string|null
    {
        return $this->zipFileName;
    }
}
