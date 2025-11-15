# Valicomb: Simple, Modern PHP Validation

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-blue.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-BSD--3--Clause-green.svg)](LICENSE)

**Valicomb** is a simple, minimal, and elegant PHP validation library with **zero dependencies**. Completely rewritten for PHP 8.2+ with security-first design, strict type safety, and modern PHP features.

> Note: This is a maintained fork of [vlucas/valitron](https://github.com/vlucas/valitron)

## Features and Improvements

- **Modern PHP 8.2+** - Full type safety with `declare(strict_types=1)`
- **Security-First** - Protection against ReDoS, type juggling, path traversal, and more
- **Zero Dependencies** - Only requires `ext-mbstring`
- **Simple API**: Fluent, chainable interface
- **I18n Support**: 22 built-in languages
- **35+ Validators**: Comprehensive validation rules out of the box
- Easy custom validation rules
- PHPStan Level 8 compliant

## Requirements

- PHP 8.2 or higher
- `ext-mbstring` extension

## Installation

Install via Composer:

```bash
composer require frostybee/valicomb
```

## 🚀 Quick Start

```php
use Valitron\Validator;

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
for Valitron to work, but Valitron will use it if available, and it is highly
recommended.

## 💡 Usage Examples

### Multiple Rules on One Field

```php
$v = new Validator($_POST);
$v->rule('required', 'email')
  ->rule('email', 'email')
  ->rule('lengthMin', 'email', 5);
```

### Alternative Syntax

```php
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

### Custom Error Messages

```php
$v = new Validator(['email' => 'invalid']);
$v->rule('email', 'email')->message('Please enter a valid email address');

// Or set custom message when defining rule
$v->rule('required', 'name')->message('{field} is absolutely required');
```

### Field Labels

```php
$v = new Validator($_POST);
$v->labels([
    'email' => 'Email Address',
    'password' => 'Password'
]);
```

### Custom Validation Rules

```php
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

```php
$v = new Validator($_POST);
$v->stopOnFirstFail(true);
$v->rule('required', ['email', 'password']);
$v->rule('email', 'email');
```

### Form Data Handling

Valitron properly handles form data where all values come as strings:

```php
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

## 🔒 Security Features

### ReDoS Protection

Regular expression validation includes automatic protection against catastrophic backtracking:

```php
$v = new Validator(['field' => 'aaaaaaaaaaaa!']);
$v->rule('regex', 'field', '/^(a+)+$/'); // Throws RuntimeException on ReDoS pattern
```

### Type Juggling Prevention

All comparisons use strict equality (`===`) to prevent type juggling attacks:

```php
$v = new Validator([
    'field1' => '0e123456',
    'field2' => '0e789012'
]);
$v->rule('equals', 'field1', 'field2'); // Returns false (strict comparison)
```

### URL Prefix Validation

URL validation uses proper prefix checking to prevent bypass attacks:

```php
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
$v = new Validator(['num' => '1000']);
$v->rule('integer', 'num', true); // Works correctly (strict mode)
```

## 🌍 Internationalization

Valitron supports 22 languages out of the box:

```php
// Set default language
Validator::lang('es'); // Spanish

// Or per-instance
$v = new Validator($data, [], 'fr'); // French
```

Available languages: ar, cs, da, de, en, es, fa, fi, fr, hu, id, it, ja, nl, no, pl, pt, ru, sv, tr, uk, zh

## 🎯 Advanced Features

### Conditional Validation

```php
$v = new Validator($_POST);

// Required only if other field is present
$v->rule('requiredWith', 'billing_address', ['same_as_shipping']);

// Required only if other field is absent
$v->rule('requiredWithout', 'phone', ['email']);
```

### Optional Fields

```php
$v = new Validator($_POST);
$v->rule('optional', 'middle_name');
$v->rule('alpha', 'middle_name'); // Only validated if present
```

### Credit Card Validation

```php
// Any valid card
$v->rule('creditCard', 'card_number');

// Specific card type
$v->rule('creditCard', 'card_number', 'visa');

// Multiple allowed types
$v->rule('creditCard', 'card_number', ['visa', 'mastercard']);
```

### Instance Validation

```php
$v = new Validator(['date' => new DateTime()]);
$v->rule('instanceOf', 'date', DateTime::class);
```

### Reusing Validators

```php
$baseValidator = new Validator([]);
$baseValidator->rule('required', 'email')
              ->rule('email', 'email');

// Use with different data
$v1 = $baseValidator->withData(['email' => 'test@example.com']);
$v1->validate();

$v2 = $baseValidator->withData(['email' => 'another@example.com']);
$v2->validate();
```

## 🔍 Error Handling

### Get All Errors

```php
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
$emailErrors = $v->errors('email');
// ['Email is required', 'Email is not a valid email address']
```

### Custom Error Messages

```php
$v = new Validator($_POST);
$v->rule('required', 'email')->message('We need your email!');
$v->rule('email', 'email')->message('That doesn\'t look like a valid email');
```

### Message Placeholders

```php
$v->rule('lengthBetween', 'username', 3, 20)
  ->message('Username must be between {0} and {1} characters');
```

## 🧪 Testing

Run the test suite:

```bash
# Run all tests
composer test

# Run PHPStan static analysis
composer analyse

# Run both
composer check
```

## 📋 Available Composer Commands

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

## 🏗️ Architecture

### Type Safety

Every method includes full type declarations:

```php
protected function validateEmail(string $field, mixed $value): bool
{
    // Implementation with strict types
}
```

### Modern PHP Features

- **Union Types**: `string|array $fields`
- **Match Expressions**: For error messages
- **Named Arguments**: Full support
- **Null-Safe Operator**: `$value?->method()`
- **Constructor Property Promotion**: Where applicable

### PHPStan Level 8

The entire codebase passes PHPStan at the maximum strictness level, ensuring complete type safety and correctness.

## 📖 API Reference

### Constructor

```php
public function __construct(
    array $data = [],
    array $fields = [],
    ?string $lang = null,
    ?string $langDir = null
)
```

### Core Methods

- `rule(string|callable $rule, string|array $fields, mixed ...$params): self`
- `rules(array $rules): void`
- `validate(): bool`
- `errors(?string $field = null): array`
- `data(): array`

### Configuration

- `labels(array $labels): self`
- `stopOnFirstFail(bool $stop = true): self`
- `setPrependLabels(bool $prepend): self`

### Static Methods

- `static addRule(string $name, callable $callback, string $message = ''): void`
- `static lang(?string $lang = null): string`
- `static langDir(?string $langDir = null): string`

## 🤝 Contributing

Contributions are welcome! Please ensure:

1. Code follows PSR-12 standards
2. All tests pass (`composer test`)
3. PHPStan analysis passes (`composer analyse`)
4. New features include tests
5. Security considerations are documented

## 📄 License

Valicomb is open-source software licensed under the [BSD 3-Clause License](LICENSE).

## 🙏 Credits

Originally created by [Vance Lucas](https://www.vancelucas.com/)

Modernized for PHP 8.2+ with security enhancements and strict type safety.

## 📚 Resources

- [Documentation](https://github.com/vlucas/valitron)
- [Issue Tracker](https://github.com/vlucas/valitron/issues)
- [Changelog](CHANGELOG.md)

## 💬 Support

- GitHub Issues: [Report bugs or request features](https://github.com/vlucas/valitron/issues)
- Stack Overflow: Tag your questions with `valitron`

---

## Running Tests

The test suite depends on the Composer autoloader to load and run the
Valitron files. Please ensure you have downloaded and installed Composer
before running the tests:

1. Download Composer `curl -s http://getcomposer.org/installer | php`
2. Run 'install' `php composer.phar install`
3. Run the tests `composer test`

### Quality Checks

```bash
composer test          # Run PHPUnit test suite
composer analyse       # Run PHPStan static analysis (Level 8)
composer cs-check      # Check code style (PSR-12)
composer cs-fix        # Auto-fix code style issues
composer check-all     # Run all quality checks
```

**For a complete list of available commands and workflows, see [docs/COMPOSER_COMMANDS.MD](docs/COMPOSER_COMMANDS.MD)**

## Contributing

1. Fork it
2. Create your feature branch (`git checkout -b my-new-feature`)
3. Make your changes
4. Run the tests, adding new ones for your own code if necessary (`phpunit`)
5. Commit your changes (`git commit -am 'Added some feature'`)
6. Push to the branch (`git push origin my-new-feature`)
7. Create new Pull Request
8. Pat yourself on the back for being so awesome

## Security Disclosures and Contact Information

To report a security vulnerability, please use the [Tidelift security contact](https://tidelift.com/security). Tidelift will coordinate the fix and disclosure.
