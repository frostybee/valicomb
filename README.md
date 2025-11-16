# Valicomb: Simple, Modern PHP Validation

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-blue.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-BSD--3--Clause-green.svg)](LICENSE)

**Valicomb** is a simple, minimal, and elegant PHP validation library with **zero dependencies**. Completely rewritten for PHP 8.2+ with security-first design, strict type safety, and modern PHP features.

> **Note:** This is a modernized and actively maintained fork of [vlucas/valitron](https://github.com/vlucas/valitron)

## Features

* **Modern PHP 8.2+ support** with strict types turned on, so everything is fully typed and predictable.
* **Security-first approach** that helps guard against things like ReDoS, type juggling issues, path traversal, and similar problems.
* **Thoroughly tested**, with over 430 tests making sure things behave the way they should.
* **No external dependencies**, other than the standard `ext-mbstring` extension.
* **Clean and straightforward API** that’s easy to read and chain together.
* **Built-in i18n support**, offering 22 languages out of the box.
* **35+ ready-to-use validation rules** covering the most common validation needs.
* **Easy to extend**, so you can add your own custom validation rules when needed.
* **Clean, modern codebase**, fully passing PHPStan Level 8 checks.

## Requirements

- PHP 8.2 or higher
- `ext-mbstring` extension

## Installation

Install via Composer:

```bash
composer require frostybee/valicomb
```

## Quick Start

```php
use Frostybee\Valicomb\Validator;

// Basic validation
$v = new Validator(['email' => 'test@example.com', 'age' => '25']);
$v->rule('required', ['email', 'age']);
$v->rule('email', 'email');
$v->rule('integer', 'age');

if ($v->validate()) {
    echo "Validation passed!";
} else {
    print_r($v->errors());
}
```

## Built-in Validation Rules

**For detailed usage examples of each rule, see [EXAMPLES.md](EXAMPLES.md)**

### String Validators
 * `required` - Field is required
 * `alpha` - Alphabetic characters only
 * `alphaNum` - Alphabetic and numeric characters only
 * `ascii` - ASCII characters only
 * `slug` - URL slug characters (a-z, 0-9, -, \_)
 * `email` - Valid email address
 * `emailDNS` - Valid email address with active DNS record
 * `contains` - Field is a string and contains the given string
 * `regex` - Field matches given regex pattern

### Numeric Validators
 * `integer` - Must be integer number
 * `numeric` - Must be numeric
 * `min` - Minimum value
 * `max` - Maximum value

### Length Validators
 * `length` - String must be certain length
 * `lengthBetween` - String must be between given lengths
 * `lengthMin` - String must be greater than given length
 * `lengthMax` - String must be less than given length

### URL Validators
 * `url` - Valid URL
 * `urlActive` - Valid URL with active DNS record

### Array Validators
 * `array` - Must be array
 * `in` - Performs in_array check on given array values
 * `notIn` - Negation of `in` rule (not in array of values)
 * `listContains` - Performs in_array check on given array values (the other way round than `in`)
 * `subset` - Field is an array or a scalar and all elements are contained in the given array
 * `containsUnique` - Field is an array and contains unique values
 * `arrayHasKeys` - Field is an array and contains all specified keys

### Date Validators
 * `date` - Field is a valid date
 * `dateFormat` - Field is a valid date in the given format
 * `dateBefore` - Field is a valid date and is before the given date
 * `dateAfter` - Field is a valid date and is after the given date

### Comparison Validators
 * `equals` - Field must match another field (email/password confirmation)
 * `different` - Field must be different than another field

### Type Validators
 * `boolean` - Must be boolean
 * `ip` - Valid IP address
 * `ipv4` - Valid IP v4 address
 * `ipv6` - Valid IP v6 address
 * `creditCard` - Field is a valid credit card number
 * `instanceOf` - Field contains an instance of the given class

### Conditional Validators
 * `optional` - Value does not need to be included in data array. If it is however, it must pass validation.
 * `accepted` - Checkbox or Radio must be accepted (yes, on, 1, true)
 * `requiredWith` - Field is required if any other fields are present
 * `requiredWithout` - Field is required if any other fields are NOT present

**NOTE**: If you are comparing floating-point numbers with min/max validators, you
should install the [BCMath](http://us3.php.net/manual/en/book.bc.php)
extension for greater accuracy and reliability. The extension is not required
for Valicomb to work, but Valicomb will use it if available, and it is highly
recommended.

## Usage Examples

### Multiple Rules on One Field

```php
use Frostybee\Valicomb\Validator;

$v = new Validator($_POST);
$v->rule('required', 'email')
  ->rule('email', 'email')
  ->rule('lengthMin', 'email', 5);
```

### Alternative Syntax

```php
use Frostybee\Valicomb\Validator;

$v = new Validator($_POST);
$v->rules([
    'required' => ['name', 'email'],
    'email' => 'email',
    'lengthBetween' => [
        ['name', 1, 100],
        ['bio', 10, 500]
    ]
]);
```

### Specifying Validation Rules as Arrays

Valicomb provides two ways to specify validation rules as arrays:

#### 1. Field-based array (field names as keys)
```php
$rules = [
    'name' => ['required', ['lengthMin', 2]]
];
$v->mapManyFieldsToRules($rules);
```

#### 2. Rule-based array (rule names as keys)
```php
$rules = [
    'required' => [['name']],
    'lengthMin' => [['name', 2]]
];
$v->rules($rules);
```

> - The field-based array is **more intuitive** when organizing validation rules by field.
> - The rule-based array is **more useful** when applying the same rule to many fields.

### Custom Error Messages

```php
use Frostybee\Valicomb\Validator;

$v = new Validator(['email' => 'invalid']);
$v->rule('email', 'email')->message('Please enter a valid email address');

// Or set custom message when defining rule
$v->rule('required', 'name')->message('{field} is absolutely required');
```

### Field Labels

```php
use Frostybee\Valicomb\Validator;

$v = new Validator($_POST);
$v->labels([
    'email' => 'Email Address',
    'password' => 'Password'
]);
```

### Custom Validation Rules

```php
use Frostybee\Valicomb\Validator;

$v = new Validator(['username' => 'admin']);

// Closure-based rule
$v->rule(function($field, $value, $params, $fields) {
    return $value !== 'admin';
}, 'username')->message('Username cannot be "admin"');

// Register global custom rule
Validator::addRule('strongPassword', function($field, $value, $params) {
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $value);
}, 'Password must be at least 8 characters with uppercase, lowercase, and number');
```

### Stop on First Failure

By default, validation continues checking all rules even after encountering failures. You can configure the validator to stop the validation process as soon as the first failure occurs, which can improve performance when validating large datasets.

```php
use Frostybee\Valicomb\Validator;

$v = new Validator($_POST);
$v->stopOnFirstFail(true);
$v->rule('required', ['email', 'password']);
$v->rule('email', 'email');
```

### Form Data Handling

Valicomb properly handles form data where all values come as strings:

```php
use Frostybee\Valicomb\Validator;

// $_POST data - everything is strings
$_POST = [
    'age' => '25',      // String, not int
    'price' => '19.99', // String, not float
    'active' => '1'     // String, not bool
];

$v = new Validator($_POST);
$v->rule('integer', 'age');   // Works with string '25'
$v->rule('numeric', 'price'); // Works with string '19.99'
$v->rule('boolean', 'active'); // Works with string '1'
```

### Nested Field Validation

```php
use Frostybee\Valicomb\Validator;

$data = [
    'user' => [
        'email' => 'test@example.com',
        'profile' => [
            'age' => 25
        ]
    ]
];

$v = new Validator($data);
$v->rule('email', 'user.email');
$v->rule('integer', 'user.profile.age');
```

### Array Field Validation

```php
use Frostybee\Valicomb\Validator;

$data = [
    'users' => [
        ['email' => 'user1@example.com'],
        ['email' => 'user2@example.com']
    ]
];

$v = new Validator($data);
$v->rule('email', 'users.*.email'); // Validates all emails
```

### List Contains Validation

Check if an array field contains a specific value:

```php
use Frostybee\Valicomb\Validator;

// Check if tags array contains 'php'
$v = new Validator(['tags' => ['php', 'javascript', 'python']]);
$v->rule('listContains', 'tags', 'php'); // true

// Strict type checking
$v = new Validator(['ids' => [1, 2, 3]]);
$v->rule('listContains', 'ids', '1', true); // false (strict: string !== int)

// For associative arrays, checks keys not values
$v = new Validator(['data' => ['name' => 'John', 'email' => 'john@example.com']]);
$v->rule('listContains', 'data', 'name'); // true (checks keys)
$v->rule('listContains', 'data', 'John'); // false (doesn't check values)
```

## Security Features

### ReDoS Protection

Regular expression validation includes automatic protection against catastrophic backtracking:

```php
use Frostybee\Valicomb\Validator;

$v = new Validator(['field' => 'aaaaaaaaaaaa!']);
$v->rule('regex', 'field', '/^(a+)+$/'); // Throws RuntimeException on ReDoS pattern
```

### Type Juggling Prevention

All comparisons use strict equality (`===`) to prevent type juggling attacks:

```php
use Frostybee\Valicomb\Validator;

$v = new Validator([
    'field1' => '0e123456',
    'field2' => '0e789012'
]);
$v->rule('equals', 'field1', 'field2'); // Returns false (strict comparison)
```

### URL Prefix Validation

URL validation uses proper prefix checking to prevent bypass attacks:

```php
use Frostybee\Valicomb\Validator;

// FAIL - http:// not at start
$v = new Validator(['url' => 'evil.com?redirect=http://trusted.com']);
$v->rule('url', 'url'); // Returns false

// PASS - proper URL
$v = new Validator(['url' => 'http://trusted.com']);
$v->rule('url', 'url'); // Returns true
```

### Path Traversal Protection

Language loading validates against directory traversal:

```php
use Frostybee\Valicomb\Validator;

// Blocked - invalid language
new Validator([], [], '../../etc/passwd'); // Throws InvalidArgumentException
```

### Email Security

Email validation includes:
- RFC 5321 length limits (254 chars total, 64 local, 255 domain)
- Dangerous character rejection
- Proper validation against injection attacks

### Integer Validation

Fixed regex properly validates all integers, not just single digits:

```php
use Frostybee\Valicomb\Validator;

$v = new Validator(['num' => '1000']);
$v->rule('integer', 'num', true); // Works correctly (strict mode)
```

## Internationalization

Valicomb supports 22 languages out of the box:

```php
use Frostybee\Valicomb\Validator;

// Set default language
Validator::lang('es'); // Spanish

// Or per-instance
$v = new Validator($data, [], 'fr'); // French
```

Available languages: ar, cs, da, de, en, es, fa, fi, fr, hu, id, it, ja, nl, no, pl, pt, ru, sv, tr, uk, zh

## Advanced Features

### Conditional Validation

```php
use Frostybee\Valicomb\Validator;

$v = new Validator($_POST);

// Required only if other field is present
$v->rule('requiredWith', 'billing_address', ['same_as_shipping']);

// Required only if other field is absent
$v->rule('requiredWithout', 'phone', ['email']);
```

### Optional Fields

```php
use Frostybee\Valicomb\Validator;

$v = new Validator($_POST);
$v->rule('optional', 'middle_name');
$v->rule('alpha', 'middle_name'); // Only validated if present
```

### Credit Card Validation

```php
use Frostybee\Valicomb\Validator;

$v = new Validator($_POST);

// Any valid card
$v->rule('creditCard', 'card_number');

// Specific card type
$v->rule('creditCard', 'card_number', 'visa');

// Multiple allowed types
$v->rule('creditCard', 'card_number', ['visa', 'mastercard']);
```

### Instance Validation

```php
use Frostybee\Valicomb\Validator;

$v = new Validator(['date' => new DateTime()]);
$v->rule('instanceOf', 'date', DateTime::class);
```

### Reusing Validators

```php
use Frostybee\Valicomb\Validator;

$baseValidator = new Validator([]);
$baseValidator->rule('required', 'email')
              ->rule('email', 'email');

// Use with different data
$v1 = $baseValidator->withData(['email' => 'test@example.com']);
$v1->validate();

$v2 = $baseValidator->withData(['email' => 'another@example.com']);
$v2->validate();
```

## Error Handling

### Get All Errors

```php
use Frostybee\Valicomb\Validator;

$v = new Validator($_POST);
$v->rule('required', 'email');
$v->rule('integer', 'age');

if (!$v->validate()) {
    $errors = $v->errors();
    // [
    //     'email' => ['Email is required', 'Email is not a valid email address'],
    //     'age' => ['Age must be an integer']
    // ]
}
```

### Get Errors for Specific Field

```php
use Frostybee\Valicomb\Validator;

$v = new Validator($_POST);
$v->rule('required', 'email')->rule('email', 'email');
$v->validate();

$emailErrors = $v->errors('email');
// ['Email is required', 'Email is not a valid email address']
```

### Custom Error Messages

```php
use Frostybee\Valicomb\Validator;

$v = new Validator($_POST);
$v->rule('required', 'email')->message('We need your email!');
$v->rule('email', 'email')->message('That doesn\'t look like a valid email');
```

### Message Placeholders

```php
use Frostybee\Valicomb\Validator;

$v = new Validator($_POST);
$v->rule('lengthBetween', 'username', 3, 20)
  ->message('Username must be between {0} and {1} characters');
```

## Testing & Development Commands

| Command | Description | When to Use |
|---------|-------------|-------------|
| **Testing** |
| `composer test` | Run PHPUnit test suite | Run tests before committing |
| **Code Quality** |
| `composer analyse` | Run PHPStan static analysis (Level 8) | Check type safety and find bugs |
| `composer cs-check` | Check code style (PSR-12 + custom rules) | Verify code formatting (dry-run) |
| `composer cs-fix` | Fix code style issues automatically | Auto-fix formatting issues |
| **Refactoring** |
| `composer refactor` | Apply Rector automated refactorings | Modernize code (review changes!) |
| `composer refactor-dry` | Preview Rector refactorings (dry-run) | See what Rector would change |
| **Combined Checks** |
| `composer check` | Run tests + analyse + cs-check | Quick quality check before commit |
| `composer check-all` | Run tests + analyse + cs-check + refactor-dry | Complete quality check (recommended) |
| **Performance** |
| `composer benchmark` | Run performance benchmarks | Measure validation performance |
| **Utilities** |
| `composer dump-autoload` | Rebuild autoloader | After adding new classes |
| `composer validate` | Validate composer.json syntax | Check composer.json is valid |
| `composer audit` | Check for security vulnerabilities | Security check for dependencies |
| `composer update` | Update all dependencies | Keep packages up to date |
| `composer install` | Install dependencies from lock file | Initial setup or after git pull |

### Quick Reference Workflow

```bash
# Before committing code
composer check-all          # Run all quality checks

# Fix any issues found
composer cs-fix            # Auto-fix code style
composer refactor          # Apply safe refactorings (review first!)

# Verify everything passes
composer check-all

# Run performance benchmarks (optional)
composer benchmark
```

## 🙏 Credits

Originally created by [Vance Lucas](https://www.vancelucas.com/)

Modernized for PHP 8.2+ with security enhancements and strict type safety.

## Support

- **GitHub Issues**: [Report bugs or request features](https://github.com/frostybee/valicomb/issues)
- **Stack Overflow**: Tag your questions with `valicomb` or `php-validation`

## Contributing

Contributions are welcome! Please ensure:

1. Fork the repository
2. Create your feature branch (`git checkout -b my-new-feature`)
3. Make your changes
4. Run all quality checks (`composer check-all`)
5. Code follows PSR-12 standards (`composer cs-fix`)
6. All tests pass (`composer test`)
7. PHPStan analysis passes (`composer analyse`)
8. Add tests for new features
9. Commit your changes (`git commit -am 'Add some feature'`)
10. Push to the branch (`git push origin my-new-feature`)
11. Create a Pull Request

### Development Workflow

```bash
# Before committing code
composer check-all          # Run all quality checks

# Fix any issues found
composer cs-fix            # Auto-fix code style

# Verify everything passes
composer check-all
```

## Security

To report a security vulnerability, please create a private security advisory on GitHub or email the maintainer directly. Do not create public issues for security vulnerabilities.

## License

Valicomb is open-source software licensed under the [BSD 3-Clause License](LICENSE).
