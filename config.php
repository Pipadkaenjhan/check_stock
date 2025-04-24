<?php
class DatabaseConfig {
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $port;
    private $conn;

    public function __construct($prefix = 'DB1', $envPath = null) {
        $this->loadEnv($envPath ?? __DIR__ . '/.env');

        $this->host     = getenv("{$prefix}_HOST") ?: '';
        $this->dbname   = getenv("{$prefix}_NAME") ?: '';
        $this->username = getenv("{$prefix}_USER") ?: '';
        $this->password = getenv("{$prefix}_PASS") ?: '';
        $this->port     = getenv("{$prefix}_PORT") ?: '5432';

        if (empty($this->host) || empty($this->dbname) || empty($this->username)) {
            throw new Exception("Database configuration incomplete for prefix {$prefix}. Please check your .env file.");
        }
    }

    public static function getConnection($prefix = 'DB1', $envPath = null) {
        $db = new self($prefix, $envPath);
        return $db->connect();
    }

    private function loadEnv($path) {
        if (!file_exists($path)) {
            throw new Exception(".env file not found at: $path");
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (strpos($line, '#') === 0 || strpos($line, '=') === false) continue;

            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if (!empty($name)) {
                putenv("$name=$value");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }

    public function connect() {
        try {
            $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->dbname}";
            $this->conn = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            return $this->conn;
        } catch (PDOException $e) {
            throw new Exception("Database Connection Failed: " . $e->getMessage());
        }
    }

    public function close() {
        $this->conn = null;
    }
}
?>
