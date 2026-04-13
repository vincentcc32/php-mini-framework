<?php

namespace Core;

use PDO;
use Exception;

class QueryBuilder
{
  protected PDO $pdo;
  protected string $table;
  protected array $columns = ['*'];
  protected array $joins = [];
  protected array $where = [];
  protected array $bindings = [];
  protected ?string $groupBy = null;
  protected array $having = [];
  protected ?string $orderBy = null;
  protected ?int $limit = null;

  public function __construct(PDO $pdo)
  {
    $this->pdo = $pdo;
  }

  public function table(string $table): self
  {
    $this->table = $table;
    return $this;
  }

  public function select(array $columns = ['*']): self
  {
    $this->columns = $columns;
    return $this;
  }

  // --- CÁC PHƯƠNG THỨC JOIN ---

  public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self
  {
    $this->joins[] = "{$type} JOIN {$table} ON {$first} {$operator} {$second}";
    return $this;
  }

  public function leftJoin(string $table, string $first, string $operator, string $second): self
  {
    return $this->join($table, $first, $operator, $second, 'LEFT');
  }

  public function rightJoin(string $table, string $first, string $operator, string $second): self
  {
    return $this->join($table, $first, $operator, $second, 'RIGHT');
  }

  // --- ĐIỀU KIỆN & SẮP XẾP ---

  public function where(string $column, string $operator, $value): self
  {
    $this->where[] = "{$column} {$operator} ?";
    $this->bindings[] = $value;
    return $this;
  }

  public function groupBy(string $column): self
  {
    $this->groupBy = "GROUP BY {$column}";
    return $this;
  }

  public function having(string $column, string $operator, $value): self
  {
    $this->having[] = "{$column} {$operator} ?";
    $this->bindings[] = $value;
    return $this;
  }

  public function orderBy(string $column, string $direction = 'ASC'): self
  {
    $this->orderBy = "ORDER BY {$column} {$direction}";
    return $this;
  }

  public function limit(int $limit): self
  {
    $this->limit = $limit;
    return $this;
  }

  // --- THỰC THI (CRUD) ---

  public function get(): array
  {
    $sql = $this->buildSelect();
    return $this->query($sql, $this->bindings)->fetchAll(PDO::FETCH_OBJ);
  }

  public function first()
  {
    $this->limit(1);
    $result = $this->get();
    return $result[0] ?? null;
  }

  public function insert(array $data): bool
  {
    $columns = implode(', ', array_keys($data));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));
    $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
    return (bool) $this->query($sql, array_values($data));
  }

  public function insertMany(array $data): bool
  {
    if (empty($data)) return false;
    $columns = array_keys($data[0]);
    $colString = implode(', ', $columns);
    $placeholders = [];
    $allValues = [];
    foreach ($data as $row) {
      $placeholders[] = '(' . implode(', ', array_fill(0, count($row), '?')) . ')';
      foreach ($columns as $col) $allValues[] = $row[$col];
    }
    $sql = "INSERT INTO {$this->table} ({$colString}) VALUES " . implode(', ', $placeholders);
    return (bool) $this->query($sql, $allValues);
  }

  public function update(array $data): bool
  {
    $setStr = "";
    foreach ($data as $key => $value) $setStr .= "{$key} = ?, ";
    $setStr = rtrim($setStr, ', ');
    $sql = "UPDATE {$this->table} SET {$setStr}";
    if (!empty($this->where)) $sql .= " WHERE " . implode(' AND ', $this->where);
    return (bool) $this->query($sql, array_merge(array_values($data), $this->bindings));
  }

  public function delete(): bool
  {
    $sql = "DELETE FROM {$this->table}";
    if (!empty($this->where)) $sql .= " WHERE " . implode(' AND ', $this->where);
    return (bool) $this->query($sql, $this->bindings);
  }

  public function raw(string $sql, array $params = [])
  {
    return $this->query($sql, $params);
  }

  // --- XỬ LÝ SQL ---

  protected function query(string $sql, array $params = [])
  {
    try {
      $stmt = $this->pdo->prepare($sql);
      $stmt->execute($params);
      return $stmt;
    } catch (\PDOException $e) {
      throw new Exception("SQL Error: " . $e->getMessage() . " | SQL: " . $sql);
    }
  }

  protected function buildSelect(): string
  {
    $sql = "SELECT " . implode(', ', $this->columns) . " FROM {$this->table}";

    if (!empty($this->joins)) $sql .= " " . implode(' ', $this->joins);
    if (!empty($this->where)) $sql .= " WHERE " . implode(' AND ', $this->where);
    if ($this->groupBy) $sql .= " {$this->groupBy}";
    if (!empty($this->having)) $sql .= " HAVING " . implode(' AND ', $this->having);
    if ($this->orderBy) $sql .= " {$this->orderBy}";
    if ($this->limit) $sql .= " LIMIT {$this->limit}";

    return $sql;
  }
}
