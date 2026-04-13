<?php

namespace Core;

abstract class Validation
{
  protected $errors = [];
  protected $messages = [];

  public function validate($data)
  {
    $rules = $this->rules();
    $messages = $this->messages();
    foreach ($rules as $key => $rule) {
      $ruleParts = explode('|', $rule);
      foreach ($ruleParts as $part) {
        if (strpos($part, ':') !== false) {
          list($ruleName, $param) = explode(':', $part);
        } else {
          $ruleName = $part;
          $param = null;
        }

        if (!$this->applyRule($data, $key, $ruleName, $param)) {
          $this->errors[$key][] = $messages["$key.$ruleName"] ?? "$key failed validation for rule $ruleName";
        }
      }
    }
    return empty($this->errors);
  }

  protected function applyRule($data, $key, $rule, $param = null)
  {
    $value = $data[$key] ?? null;
    switch ($rule) {
      case 'required':
        return !empty($value);
      case 'email':
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
      case 'min':
        return strlen($value) >= $param;
      case 'max':
        return strlen($value) <= $param;
      case 'numeric':
        return is_numeric($value);
      case 'string':
        return is_string($value);
      case 'boolean':
        return is_bool($value);
      case 'array':
        return is_array($value);
      case 'confirmed':
        $confirmationField = $param ?? $key . '_confirmation';
        return isset($data[$confirmationField]) && $value === $data[$confirmationField];
      case 'integer':
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
      case 'float':
        return filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
      case 'date':
        return strtotime($value) !== false;
      case 'alpha':
        return preg_match('/^[a-zA-Z]+$/', $value);
      case 'alpha_num':
        return preg_match('/^[a-zA-Z0-9]+$/', $value);
      case 'nullable':
        return $value === null;
      case 'unique':
        // This would require a database check, which is beyond the scope of this example
        list($table, $column) = explode(',', $param);
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE $column = ?");
        $stmt->execute([$value]);
        return $stmt->fetchColumn() == 0;
      case 'exists':
        // This would require a database check, which is beyond the scope of this example
        list($table, $column) = explode(',', $param);
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE $column = ?");
        $stmt->execute([$value]);
        return $stmt->fetchColumn() > 0;
      case 'image':
        // This would require checking the file type, which is beyond the scope of this example
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        return isset($_FILES[$key]) && in_array($_FILES[$key]['type'], $allowedTypes);
        return true;
      case 'mimes':
        // This would require checking the file type, which is beyond the scope of this example
        $allowedExtensions = explode(',', $param);
        $ext = pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION);
        return in_array(strtolower($ext), $allowedExtensions);
      default:
        return true;
    }
  }
  public function getErrors()
  {
    return $this->errors;
  }
  public function old($key, $default = null)
  {
    return $_POST[$key] ?? $default;
  }
  abstract protected function rules();
  abstract protected function messages();
}
