<?php
// Common helper utilities for PHP views

declare(strict_types=1);

require_once __DIR__ . '/config.php';

const ALLOWED_TABLES = [
    'projects',
    'customers',
    'batches',
    'sub_batch_details',
    'budgets',
    'payments',
    'invoices',
    'users',
];

function fetch_table(string $table, string $orderBy = ''): array
{
    if (!in_array($table, ALLOWED_TABLES, true)) {
        return [];
    }

    $pdo = get_pdo();
    $orderSql = $orderBy !== '' ? " ORDER BY {$orderBy}" : '';
    $stmt = $pdo->query("SELECT * FROM {$table}{$orderSql}");

    return $stmt->fetchAll();
}

function to_options(array $rows, string $valueKey, string $labelKey): array
{
    $options = [];
    foreach ($rows as $row) {
        if (!isset($row[$valueKey], $row[$labelKey])) {
            continue;
        }
        $options[] = [
            'value' => (string) $row[$valueKey],
            'label' => (string) $row[$labelKey],
        ];
    }
    return $options;
}

function safe(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function current_user(): ?array
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return $_SESSION['user'] ?? null;
}

function require_login(): array
{
    $user = current_user();

    if (!$user) {
        header('Location: login.php');
        exit;
    }

    return $user;
}