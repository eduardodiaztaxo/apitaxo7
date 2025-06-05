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
     * @var array<string, \PDO> Conexiones estáticas para evitar múltiples conexiones al mismo archivo
     */
    protected static $connections = [];

    /**
     * SQLiteConnService constructor.
     *
     * @param string $sqlitePath Path to the SQLite database file
     */
    public function __construct(string $sqlitePath)
    {
        $this->sqlitePath = $sqlitePath;
        $this->pdo = null;
    }

    /**
     * Create or reuse an existing SQLite connection.
     *
     * @return void
     */
    public function createSQLiteDatabase()
    {
        $dir = dirname($this->sqlitePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Usa el path como clave para la conexión
        if (isset(self::$connections[$this->sqlitePath])) {
            // Ya existe conexión, la reutilizamos
            $this->pdo = self::$connections[$this->sqlitePath];
            return;
        }

        // No existe, creamos una nueva conexión
        $this->pdo = new \PDO('sqlite:' . $this->sqlitePath);

        // Guardamos la conexión para reutilizarla
        self::$connections[$this->sqlitePath] = $this->pdo;
    }

    /**
     * Set the SQLite connection (create a new one without reuse).
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
     * @return bool
     */
    public function deleteDB(): bool
    {
        if ($this->pdo) {
            $this->pdo = null;
            unset(self::$connections[$this->sqlitePath]); // Limpiamos conexión guardada
        }

        if (file_exists($this->sqlitePath)) {
            return unlink($this->sqlitePath);
        }

        return false;
    }
}
