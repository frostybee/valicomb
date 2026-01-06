# Valicomb: Simple, Modern PHP Validation

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-blue.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-BSD--3--Clause-green.svg)](LICENSE)

**Valicomb** is a simple, minimal PHP validation library with **zero dependencies**. A modernized fork of [vlucas/valitron](https://github.com/vlucas/valitron) for PHP 8.2+ with security-first design, strict type safety, and modern PHP features.

## Features

* **Modern PHP 8.2+ support** with strict types turned on, so everything is fully typed and predictable.
* **Security-first approach** that helps guard against things like ReDoS, type juggling issues, path traversal, and similar problems.
* **Thoroughly tested**, with over 430 tests making sure things behave the way they should.
* **No external dependencies**, other than the standard `ext-mbstring` extension.
* **Clean and straightforward API** that's easy to read and chain together.
* **Built-in i18n support**, offering 22 languages out of the box.
* **54 ready-to-use validation rules** covering the most common validation needs.
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

// Create validator with data
$v = new Validator([
    'username' => 'frostybee',
    'email'    => 'bee@example.com',
    'password' => 'Secret123!'
]);

// Define rules by field
$v->forFields([
    'username' => ['required', ['lengthBetween', 3, 20]],
    'email'    => ['required', 'email'],
    'password' => ['required', ['lengthMin', 8]]
]);

if ($v->validate()) {
    echo "Validation passed!";
} else {
    print_r($v->errors());
}
```

## Reusing Validators

Define validation rules once, then validate multiple datasets with `withData()`:

```php
use Frostybee\Valicomb\Validator;

$baseValidator = new Validator([]);
$baseValidator->rule('required', 'email')->rule('email', 'email');

// Validate different datasets
$v1 = $baseValidator->withData(['email' => 'user1@example.com']);
$v1->validate(); // true

$v2 = $baseValidator->withData(['email' => 'invalid']);
$v2->validate(); // false
```

This is useful for form validation, API endpoints, and batch processing where the same rules apply to different data.

## Built-in Validation Rules

**For detailed usage examples of each rule, see the [documentation](https://frostybee.github.io/valicomb/rules/overview/)**

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
 * `startsWith` - Field starts with the given string
 * `endsWith` - Field ends with the given string
 * `uuid` - Field is a valid UUID (supports versions 1-5)
 * `passwordStrength` - Field meets password strength requirements

### Numeric Validators

 * `integer` - Must be integer number
 * `numeric` - Must be numeric
 * `min` - Minimum value
 * `max` - Maximum value
 * `between` - Value must be between min and max
 * `positive` - Must be a positive number (greater than zero)
 * `decimalPlaces` - Must have specified number of decimal places

### Length Validators

 * `length` - String must be certain length
 * `lengthBetween` - String must be between given lengths
 * `lengthMin` - String must be greater than given length
 * `lengthMax` - String must be less than given length

### URL/Network Validators

 * `url` - Valid URL
 * `urlActive` - Valid URL with active DNS record
 * `urlStrict` - Valid URL with strict validation
 * `phone` - Valid phone number format

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
 * `past` - Field is a valid date in the past
 * `future` - Field is a valid date in the future

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
 * `nullable` - Field can be null; if null, other validation rules are skipped
 * `accepted` - Checkbox or Radio must be accepted (yes, on, 1, true)
 * `requiredWith` - Field is required if any other fields are present
 * `requiredWithout` - Field is required if any other fields are NOT present

**NOTE**: If you are comparing floating-point numbers with min/max validators, you
should install the [BCMath](http://us3.php.net/manual/en/book.bc.php) extension for greater accuracy and reliability. The extension is not required for Valicomb to work, but Valicomb will use it if available, and it is highly
recommended.

## Usage Examples

### Defining Validation Rules

Valicomb provides three flexible ways to define validation rules:

#### 1. Field-based array (recommended)

Group rules by field - reads naturally as "for this field, apply these rules":

```php
use Frostybee\Valicomb\Validator;

$v = new Validator($_POST);
$v->forFields([
    'name' => ['required', ['lengthMin', 2]],
    'email' => ['required', 'email', ['lengthMax', 254]]
]);
```

#### 2. Fluent/Chained syntax (best for custom messages)

```php
use Frostybee\Valicomb\Validator;

$v = new Validator($_POST);
$v->rule('required', 'email')
  ->rule('email', 'email')
  ->rule('lengthMin', 'email', 5);
```

#### 3. Rule-based array (best when applying the same rule to many fields)

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

## Testing & Development Workflow

```bash
# Before committing
composer check-all

# Individual checks
composer test              # Run PHPUnit test suite
composer analyse           # Run PHPStan static analysis (Level 8)
composer cs-check          # Check code style (PSR-12)

# Fix any issues
composer cs-fix

# Verify fixes
composer check-all
```

### Additional Commands

- `composer benchmark` - Run performance benchmarks
- `composer validate` - Validate composer.json syntax
- `composer audit` - Check for security vulnerabilities

## Security

To report a security vulnerability, please create a private security advisory on GitHub or email the maintainer directly. Do not create public issues for security vulnerabilities.

## License

Valicomb is open-source software licensed under the [BSD 3-Clause License](LICENSE).
