# Type Casting

## Overview

DbConfig automatically casts configuration values to the appropriate PHP type based on the `type` column in the database.

## Supported Types

| Type | PHP Cast | Example |
|------|----------|---------|
| `string` | `(string)` | `"hello world"` |
| `int`, `integer` | `(int)` | `42` |
| `float` | `(float)` | `3.14` |
| `bool`, `boolean` | `filter_var()` | `true`, `false` |
| `json` | `json_decode()` | `["a", "b", "c"]` |

## Implementation

```php
public static function castValue($value, $type)
{
    return match (strtolower($type)) {
        'int', 'integer' => (int)$value,
        'float' => (float)$value,
        'bool', 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
        'json' => json_decode($value, true),
        default => $value,
    };
}
```

## Boolean Handling

Boolean values use `filter_var()` with `FILTER_VALIDATE_BOOLEAN`, which accepts:

**Truthy**: `"1"`, `"true"`, `"on"`, `"yes"`
**Falsy**: `"0"`, `"false"`, `"off"`, `"no"`, `""`

## JSON Values

JSON values are decoded as associative arrays (not objects):

```php
// Database value: {"key": "value", "list": [1, 2, 3]}
$config = Configure::read('MyPlugin.settings');
// Result: ['key' => 'value', 'list' => [1, 2, 3]]
```

## Default Behavior

If type is not recognized, the value is returned as-is (string).
