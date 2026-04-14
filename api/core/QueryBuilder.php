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

  public function whereIn(string $column, array $values): self
  {
    $placeholders = implode(', ', array_fill(0, count($values), '?'));
    $this->where[] = "{$column} IN ({$placeholders})";
    $this->bindings = array_merge($this->bindings, $values);
    return $this;
  }

  public function whereNull(string $column): self
  {
    $this->where[] = "{$column} IS NULL";
    return $this;
  }

  public function whereNotNull(string $column): self
  {
    $this->where[] = "{$column} IS NOT NULL";
    return $this;
  }

  public function whereBetween(string $column, $start, $end): self
  {
    $this->where[] = "{$column} BETWEEN ? AND ?";
    $this->bindings[] = $start;
    $this->bindings[] = $end;
    return $this;
  }

  public function whereLike(string $column, string $pattern): self
  {
    $this->where[] = "{$column} LIKE ?";
    $this->bindings[] = $pattern;
    return $this;
  }

  public function whereOr(string $column, string $operator, $value): self
  {
    if (empty($this->where)) {
      return $this->where($column, $operator, $value);
    }
    $lastCondition = array_pop($this->where);
    $lastBinding = array_pop($this->bindings);
    $this->where[] = "({$lastCondition} OR {$column} {$operator} ?)";
    $this->bindings[] = $lastBinding;
    $this->bindings[] = $value;
    return $this;
  }

  public function whereOrIn(string $column, array $values): self
  {
    if (empty($this->where)) {
      return $this->whereIn($column, $values);
    }
    $lastCondition = array_pop($this->where);
    $lastBindings = array_splice($this->bindings, -count($values));
    $placeholders = implode(', ', array_fill(0, count($values), '?'));
    $this->where[] = "({$lastCondition} OR {$column} IN ({$placeholders}))";
    $this->bindings = array_merge($this->bindings, $lastBindings, $values);
    return $this;
  }

  public function whereAnd(string $column, string $operator, $value): self
  {
    if (empty($this->where)) {
      return $this->where($column, $operator, $value);
    }
    $lastCondition = array_pop($this->where);
    $lastBinding = array_pop($this->bindings);
    $this->where[] = "({$lastCondition} AND {$column} {$operator} ?)";
    $this->bindings[] = $lastBinding;
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

  public function offset(int $offset): self
  {
    if ($this->limit === null) $this->limit = 1000; // Giới hạn mặc định nếu chưa có limit
    $this->limit += $offset;
    return $this;
  }

  public function limit(int $limit): self
  {
    $this->limit = $limit;
    return $this;
  }

  public function paginate(int $perPage, int $page = 1): array
  {
    $this->limit($perPage)->offset(($page - 1) * $perPage);
    return $this->get();
  }



  // --- THỰC THI (CRUD) ---

  public function get(): array
  {
    $sql = $this->buildSelect();
    return $this->query($sql, $this->bindings)->fetchAll(PDO::FETCH_OBJ);
  }

  public function count(): int
  {
    $this->columns = ['COUNT(*) AS count'];
    $sql = $this->buildSelect();
    $result = $this->query($sql, $this->bindings)->fetch(PDO::FETCH_OBJ);
    return (int) $result->count;
  }

  public function exists(): bool
  {
    $this->columns = ['1'];
    $sql = $this->buildSelect();
    $result = $this->query($sql, $this->bindings)->fetch(PDO::FETCH_ASSOC);
    return !empty($result);
  }

  public function all(): array
  {
    return $this->get();
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

  public function updateMany(array $data): bool
  {
    if (empty($data)) return false;
    $sql = "UPDATE {$this->table} SET ";
    $setStr = "";
    foreach ($data as $key => $value) $setStr .= "{$key} = ?, ";
    $sql .= rtrim($setStr, ', ');
    if (!empty($this->where)) $sql .= " WHERE " . implode(' AND ', $this->where);
    $allValues = array_merge(array_values($data), $this->bindings);
    return (bool) $this->query($sql, $allValues);
  }

  public function deleteById($id): bool
  {
    $sql = "DELETE FROM {$this->table} WHERE id = ?";
    return (bool) $this->query($sql, [$id]);
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
