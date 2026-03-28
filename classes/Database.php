<?php
/**
 * Amani Escrow — Database Connection (PDO Singleton)
 *
 * cPanel reliability features:
 *   - Socket vs TCP auto-detection
 *   - localhost → 127.0.0.1 fallback (some hosts resolve localhost to a broken socket)
 *   - Optional explicit port
 *   - All errors go to error_log(); screen output only if APP_DB_DEBUG is true
 *   - Checks for pdo_mysql extension before attempting connection
 */
class Database
{
    /** @var self|null */
    private static $instance = null;
    /** @var PDO */
    private $pdo;

    private function __construct()
    {
        // ── 1) Verify the extension exists ──────────────────────
        if (!extension_loaded('pdo_mysql')) {
            $msg = 'Amani Escrow: PHP extension "pdo_mysql" is not loaded. '
                 . 'Enable it in cPanel → Select PHP Version → Extensions → pdo_mysql.';
            error_log($msg);
            if (self::showDebug()) {
                die($msg);
            }
            die('System temporarily unavailable. [EXT]');
        }

        // ── 2) Build DSN(s) to try ─────────────────────────────
        $dsns = self::buildDsnList();

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET,
        ];

        // ── 3) Try each DSN in order ────────────────────────────
        $lastError = null;
        foreach ($dsns as $label => $dsn) {
            try {
                $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                // Connection succeeded — done
                return;
            } catch (PDOException $e) {
                $lastError = $e;
                error_log("Amani DB [{$label}]: " . $e->getMessage() . " (DSN: {$dsn})");
                // continue to next DSN
            }
        }

        // ── 4) All DSNs failed ──────────────────────────────────
        $safeMsg = 'System temporarily unavailable.';
        if (self::showDebug()) {
            $safeMsg = 'DB Error: ' . ($lastError ? $lastError->getMessage() : 'Unknown')
                     . "\n\nDSNs tried:\n" . implode("\n", array_map(
                         fn($l, $d) => "  [{$l}] {$d}",
                         array_keys($dsns),
                         array_values($dsns)
                     ))
                     . "\n\nUser: " . DB_USER
                     . "\nDatabase: " . DB_NAME
                     . "\n\n⚠ Set APP_DB_DEBUG = false in config/config.php after fixing.";
        }
        die('<pre style="font-family:monospace;background:#111;color:#C8F545;padding:2rem;border-radius:12px;max-width:700px;margin:4rem auto;white-space:pre-wrap">'
            . htmlspecialchars($safeMsg) . '</pre>');
    }

    /**
     * Build an ordered list of DSNs to attempt.
     *
     * Priority:
     *   1. If DB_SOCKET is set → unix_socket DSN
     *   2. DB_HOST as given (with optional port)
     *   3. If DB_HOST is "localhost", also try 127.0.0.1 via TCP
     */
    private static function buildDsnList(): array
    {
        $dsns = [];
        $db   = DB_NAME;
        $cs   = DB_CHARSET;

        // Socket connection
        if (!empty(DB_SOCKET)) {
            $dsns['socket'] = "mysql:unix_socket=" . DB_SOCKET . ";dbname={$db};charset={$cs}";
        }

        // Host-based connection
        $hostPart = 'host=' . DB_HOST;
        if (!empty(DB_PORT)) {
            $hostPart .= ';port=' . DB_PORT;
        }
        $dsns['host'] = "mysql:{$hostPart};dbname={$db};charset={$cs}";

        // Fallback: if host is "localhost", also try TCP 127.0.0.1
        // (Some cPanel setups resolve "localhost" to a socket that doesn't exist)
        if (strtolower(DB_HOST) === 'localhost') {
            $tcp = 'host=127.0.0.1';
            if (!empty(DB_PORT)) {
                $tcp .= ';port=' . DB_PORT;
            }
            $dsns['tcp-fallback'] = "mysql:{$tcp};dbname={$db};charset={$cs}";
        }

        return $dsns;
    }

    /**
     * Whether to show detailed errors on screen.
     */
    private static function showDebug(): bool
    {
        return (defined('APP_DB_DEBUG') && APP_DB_DEBUG === true)
            || (defined('APP_ENV') && APP_ENV === 'development');
    }

    // ── PUBLIC API ──────────────────────────────────────────────

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->pdo;
    }

    public function query(string $sql, array $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetch(string $sql, array $params = [])
    {
        return $this->query($sql, $params)->fetch();
    }

    public function fetchAll(string $sql, array $params = [])
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetchColumn(string $sql, array $params = [])
    {
        return $this->query($sql, $params)->fetchColumn();
    }

    public function insert(string $table, array $data)
    {
        $cols = implode(',', array_keys($data));
        $phs  = implode(',', array_fill(0, count($data), '?'));
        $this->query("INSERT INTO `{$table}` ({$cols}) VALUES ({$phs})", array_values($data));
        return $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $whereParams = [])
    {
        $set = implode(',', array_map(fn($k) => "`{$k}`=?", array_keys($data)));
        return $this->query(
            "UPDATE `{$table}` SET {$set} WHERE {$where}",
            array_merge(array_values($data), $whereParams)
        )->rowCount();
    }

    public function delete(string $table, string $where, array $params = [])
    {
        return $this->query("DELETE FROM `{$table}` WHERE {$where}", $params)->rowCount();
    }

    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    public function beginTransaction() { $this->pdo->beginTransaction(); }
    public function commit()           { $this->pdo->commit(); }
    public function rollback()         { $this->pdo->rollBack(); }

    // Prevent cloning / unserialization of the singleton
    private function __clone() {}
    public function __wakeup() { throw new \RuntimeException('Cannot unserialize singleton'); }
}
