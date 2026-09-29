<?php

class LegacyMysqliConnection
{
    private PDO $pdo;
    public string $error = '';

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function prepare(string $sql)
    {
        try {
            return new LegacyMysqliStatement($this->pdo->prepare(self::translateSql($sql)));
        } catch (PDOException $exception) {
            $this->error = $exception->getMessage();
            return false;
        }
    }

    public function query(string $sql)
    {
        try {
            $statement = $this->pdo->query(self::translateSql($sql));
            return new LegacyMysqliResult($statement->fetchAll(PDO::FETCH_ASSOC));
        } catch (PDOException $exception) {
            $this->error = $exception->getMessage();
            return false;
        }
    }

    public static function translateSql(string $sql): string
    {
        $sql = preg_replace('/\bMONTH\s*\(\s*([a-zA-Z_][a-zA-Z0-9_.]*)\s*\)/i', 'EXTRACT(MONTH FROM $1)::int', $sql);
        $sql = preg_replace('/\bYEAR\s*\(\s*([a-zA-Z_][a-zA-Z0-9_.]*)\s*\)/i', 'EXTRACT(YEAR FROM $1)::int', $sql);
        $sql = preg_replace(
            '/ON\s+DUPLICATE\s+KEY\s+UPDATE\s+key_value\s*=\s*VALUES\(key_value\)/i',
            'ON CONFLICT (key_name) DO UPDATE SET key_value = EXCLUDED.key_value',
            $sql
        );
        return $sql;
    }
}

class LegacyMysqliStatement
{
    private PDOStatement $statement;
    private array $values = [];
    private array $rows = [];
    private int $rowIndex = 0;
    public int $num_rows = 0;
    public string $error = '';

    public function __construct(PDOStatement $statement)
    {
        $this->statement = $statement;
    }

    public function bind_param(string $types, &...$values): bool
    {
        $this->values = [];
        foreach ($values as $index => &$value) {
            $this->values[$index + 1] = [$types[$index] ?? 's', $value];
        }
        unset($value);
        return true;
    }

    public function execute(): bool
    {
        try {
            foreach ($this->values as $index => [$type, $value]) {
                $pdoType = match ($type) {
                    'i' => PDO::PARAM_INT,
                    'b' => PDO::PARAM_LOB,
                    default => $value === null ? PDO::PARAM_NULL : PDO::PARAM_STR,
                };
                $this->statement->bindValue($index, $value, $pdoType);
            }
            $success = $this->statement->execute();
            $this->rows = $this->statement->columnCount() > 0
                ? $this->statement->fetchAll(PDO::FETCH_ASSOC)
                : [];
            $this->num_rows = count($this->rows);
            $this->rowIndex = 0;
            return $success;
        } catch (PDOException $exception) {
            $this->error = $exception->getMessage();
            return false;
        }
    }

    public function get_result(): LegacyMysqliResult
    {
        return new LegacyMysqliResult($this->rows);
    }

    public function store_result(): bool
    {
        return true;
    }

    public function close(): bool
    {
        return $this->statement->closeCursor();
    }
}

class LegacyMysqliResult
{
    public int $num_rows;
    private array $rows;
    private int $rowIndex = 0;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
        $this->num_rows = count($rows);
    }

    public function fetch_assoc(): ?array
    {
        return $this->rows[$this->rowIndex++] ?? null;
    }
}