<?php

namespace App\Services\Dump;

class SQLiteConnService
{
    /**
     * @var \PDO|null PDO connection instance
     */
    protected $pdo = null;

    /**
     * @var string|null Path to the SQLite database file
     */
    protected $sqlitePath = null;


    /**
     * SQLiteConnService constructor.
     *
     * @param string $sqlitePath Path to the SQLite database file
     */
    public function __construct(string $sqlitePath)
    {

        $this->pdo = null;

        $this->sqlitePath = $sqlitePath;
    }

    /**
     * Create a new SQLite database file.
     *
     * This method creates the directory for the SQLite database file if it does not exist,
     * and then initializes a new PDO connection to the SQLite database.
     *
     * @return void
     */
    public function createSQLiteDatabase()
    {


        $dir = dirname($this->sqlitePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }


        $this->pdo = new \PDO('sqlite:' . $this->sqlitePath);
    }


    /**
     * Set the SQLite connection.
     *
     * @return \PDO|null
     */
    public function setSQLiteConn()
    {


        $dir = dirname($this->sqlitePath);
        if (!is_dir($dir)) {
            return null;
        }

        $this->pdo = new \PDO('sqlite:' . $this->sqlitePath);

        return $this->pdo;
    }


    /**
     * Get the current SQLite connection.
     *
     * @return \PDO|null
     */
    public function getCurrentConn()
    {
        return $this->pdo;
    }

    /**
     * Delete the SQLite database file.
     *
     * This method closes the PDO connection if it exists and deletes the SQLite database file.
     *
     * @return bool
     */
    public function deleteDB(): bool
    {
        if ($this->pdo) {
            $this->pdo = null;
        }

        if (file_exists($this->sqlitePath)) {

            return unlink($this->sqlitePath);
        }

        return false;
    }
}
