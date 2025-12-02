<?php
// Database configuration and connection helper for Elsewedy Machinery System

declare(strict_types=1);

/**
 * Shared database connection settings.
 */
function get_db_config(): array
{
    return [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'port' => getenv('DB_PORT') ?: '5432',
        // Defaults mirror the sample credentials shared during setup instructions
        // so local users can connect without setting environment variables.
        'dbName' => getenv('DB_NAME') ?: 'elsewedy_machinery',
        'user' => getenv('DB_USER') ?: 'ahmedadmin',
        'password' => getenv('DB_PASSWORD') ?: 'AhmedAdmin123',
    ];
}

function get_pdo(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (!extension_loaded('pdo_pgsql')) {
        throw new RuntimeException('PostgreSQL PDO driver (pdo_pgsql) is not enabled in PHP.');
    }

    $config = get_db_config();
    $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $config['host'], $config['port'], $config['dbName']);

    try {
        $pdo = new PDO($dsn, $config['user'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        throw new RuntimeException('Database connection failed. ' . $e->getMessage(), 0, $e);
    }

    return $pdo;
}

function format_db_error(Throwable $e, string $tableHint = 'required tables'): string
{
    if ($e instanceof PDOException && $e->getCode() === '42501') {
        $config = get_db_config();
        $username = $config['user'];
        return sprintf(
            'Database user "%s" lacks privileges on %s. Run GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO "%s"; and ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO "%s";. Original error: %s',
            $username,
            $tableHint,
            $username,
            $username,
            $e->getMessage()
        );
    }

    return 'Unable to reach database. ' . $e->getMessage();
}