<?php

function db_is_postgres(PDO $conn): bool
{
    return $conn->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql';
}

function db_query(PDO $conn, string $sql, array $params = []): PDOStatement
{
    $stmt = $conn->prepare($sql);
    $stmt->execute(array_values($params));

    return $stmt;
}

function db_fetch_all(PDO $conn, string $sql, array $params = []): array
{
    $stmt = db_query($conn, $sql, $params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return is_array($rows) ? $rows : [];
}

function db_fetch_one(PDO $conn, string $sql, array $params = []): ?array
{
    $stmt = db_query($conn, $sql, $params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row === false ? null : $row;
}

function db_fetch_value(PDO $conn, string $sql, array $params = []): mixed
{
    $stmt = db_query($conn, $sql, $params);
    $value = $stmt->fetchColumn();

    return $value === false ? null : $value;
}

function db_exec(PDO $conn, string $sql): void
{
    $conn->exec($sql);
}
