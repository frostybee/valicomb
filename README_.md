## Valicomb: Easy Validation That Doesn't Suck

Valitron is a simple, minimal and elegant stand-alone validation library
with NO dependencies. Valitron uses simple, straightforward validation
methods with a focus on readable and concise syntax. Valitron is the
simple and pragmatic validation library you've been looking for.

[![Build
Status](https://github.com/vlucas/valitron/actions/workflows/test.yml/badge.svg)](https://github.com/vlucas/valitron/actions/workflows/test.yml)
[![Latest Stable Version](https://poser.pugx.org/vlucas/valitron/v/stable.png)](https://packagist.org/packages/vlucas/valitron)
[![Total Downloads](https://poser.pugx.org/vlucas/valitron/downloads.png)](https://packagist.org/packages/vlucas/valitron)

[Get supported vlucas/valitron with the Tidelift Subscription](https://tidelift.com/subscription/pkg/packagist-vlucas-valitron?utm_source=packagist-vlucas-valitron&utm_medium=referral&utm_campaign=readme)

## Why Valitron?

From the original author (Vance Lucas):
Valitron was created out of frustration with other validation libraries
that have dependencies on large components from other frameworks like
Symfony's HttpFoundation, pulling in a ton of extra files that aren't
really needed for basic validation. It also has purposefully simple
syntax used to run all validations in one call instead of individually
validating each value by instantiating new classes and validating values
one at a time like some other validation libraries require.

In short, Valitron is everything you've been looking for in a validation
library but haven't been able to find until now: simple pragmatic
syntax, lightweight code that makes sense, extensible for custom
callbacks and validations, well tested, and without dependencies. Let's
get started.

## Installation

Valicomb uses [Composer](http://getcomposer.org) to install and update:

```
curl -s http://getcomposer.org/installer | php
php composer.phar require frostybee/valicomb
```

The examples below use PHP 8.2 syntax, but Valicomb works on PHP 8.2+.

## Usage

Usage is simple and straightforward. Just supply an array of data you
wish to validate, add some rules, and then call `validate()`. If there
are any errors, you can call `errors()` to get them.

```php
$v = new Valicomb\Validator(array('name' => 'Chester Tester'));
$v->rule('required', 'name');
if($v->validate()) {
    echo "Yay! We're all good!";
} else {
    // Errors
    print_r($v->errors());
}
```

Using this format, you can validate `$_POST` data directly and easily,
and can even apply a rule like `required` to an array of fields:

```php
$v = new Valicomb\Validator($_POST);
$v->rule('required', ['name', 'email']);
$v->rule('email', 'email');
if($v->validate()) {
    echo "Yay! We're all good!";
} else {
    // Errors
    print_r($v->errors());
}
```

You may use dot syntax to access members of multi-dimensional arrays,
and an asterisk to validate each member of an array:

```php
$v = new Valicomb\Validator(array('settings' => array(
    array('threshold' => 50),
    array('threshold' => 90)
)));
$v->rule('max', 'settings.*.threshold', 100);
if($v->validate()) {
    echo "Yay! We're all good!";
} else {
    // Errors
    print_r($v->errors());
}
```

Or use dot syntax to validate all members of a numeric array:

```php
$v = new Valicomb\Validator(array('values' => array(50, 90)));
$v->rule('max', 'values.*', 100);
if($v->validate()) {
    echo "Yay! We're all good!";
} else {
    // Errors
    print_r($v->errors());
}
```

You can also access nested values using dot notation:

```php
$v = new Valicomb\Validator(array('user' => array('first_name' => 'Steve', 'last_name' => 'Smith', 'username' => 'Batman123')));
$v->rule('alpha', 'user.first_name')->rule('alpha', 'user.last_name')->rule('alphaNum', 'user.username');
if($v->validate()) {
    echo "Yay! We're all good!";
} else {
    // Errors
    print_r($v->errors());
}
```

Setting language and language dir globally:

```php

// boot or config file

use Valicomb\Validator as V;

V::langDir(__DIR__.'/validator_lang'); // always set langDir before lang.
V::lang('ar');

```

Disabling the {field} name in the output of the error message.

```php
use Valicomb\Validator as V;

$v = new Valicomb\Validator(['name' => 'John']);
$v->rule('required', ['name']);

// Disable prepending the labels
$v->setPrependLabels(false);

// Error output for the "false" condition
[
    ["name"] => [
        "is required"
    ]
]

// Error output for the default (true) condition
[
    ["name"] => [
        "name is required"
    ]
]

```

You can conditionally require values using required conditional rules. In this example, for authentication, we're requiring either a token when both the email and password are not present, or a password when the email address is present.
```php
// this rule set would work for either data set...
$data = ['email' => 'test@test.com', 'password' => 'mypassword'];
// or...
$data = ['token' => 'jashdjahs83rufh89y38h38h'];

$v = new Valicomb\Validator($data);
$v->rules([
    'requiredWithout' => [
        ['token', ['email', 'password'], true]
    ],
    'requiredWith' => [
        ['password', ['email']]
    ],
    'email' => [
        ['email']
    ]
    'optional' => [
        ['email']
    ]
]);
$this->assertTrue($v->validate());
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

## Adding Custom Validation Rules

To add your own validation rule, use the `addRule` method with a rule
name, a custom callback or closure, and a error message to display in
case of an error. The callback provided should return boolean true or
false.

```php
Valicomb\Validator::addRule('alwaysFail', function($field, $value, array $params, array $fields) {
    return false;
}, 'Everything you do is wrong. You fail.');
```

You can also use one-off rules that are only valid for the specified
fields.

```php
$v = new Valicomb\Validator(array("foo" => "bar"));
$v->rule(function($field, $value, $params, $fields) {
    return true;
}, "foo")->message("{field} failed...");
```

This is useful because such rules can have access to variables
defined in the scope where the `Validator` lives. The Closure's
signature is identical to `Validator::addRule` callback's
signature.

If you wish to add your own rules that are not static (i.e.,
your rule is not static and available to call `Validator`
instances), you need to use `Validator::addInstanceRule`.
This rule will take the same parameters as
`Validator::addRule` but it has to be called on a `Validator`
instance.

## Chaining rules

You can chain multiple rules together using the following syntax.
```php
$v = new Valicomb\Validator(['email_address' => 'test@test.com']);
$v->rule('required', 'email_address')->rule('email', 'email_address');
$v->validate();
```

## Alternate syntax for adding rules

As the number of rules grows, you may prefer the alternate syntax
for defining multiple rules at once.

```php
$rules = [
    'required' => 'foo',
    'accepted' => 'bar',
    'integer' =>  'bar'
];

$v = new Valicomb\Validator(array('foo' => 'bar', 'bar' => 1));
$v->rules($rules);
$v->validate();
```

If your rule requires multiple parameters or a single parameter
more complex than a string, you need to wrap the rule in an array.

```php
$rules = [
    'required' => [
        ['foo'],
        ['bar']
    ],
    'length' => [
        ['foo', 3]
    ]
];
```
You can also specify multiple rules for each rule type.

```php
$rules = [
    'length'   => [
        ['foo', 5],
        ['bar', 5]
    ]
];
```

Putting these techniques together, you can create a complete
rule definition in a relatively compact data structure.

You can continue to add individual rules with the `rule` method
even after specifying a rule definition via an array. This is
especially useful if you are defining custom validation rules.

```php
$rules = [
    'required' => 'foo',
    'accepted' => 'bar',
    'integer' =>  'bar'
];

$v = new Valicomb\Validator(array('foo' => 'bar', 'bar' => 1));
$v->rules($rules);
$v->rule('min', 'bar', 0);
$v->validate();
```

You can also add rules on a per-field basis:
```php
$rules = [
    'required',
    ['lengthMin', 4]
];

$v = new Valicomb\Validator(array('foo' => 'bar'));
$v->mapOneFieldToRules('foo', $rules);
$v->validate();
```

Or for multiple fields at once:

```php
$rules = [
    'foo' => ['required', 'integer'],
    'bar'=>['email', ['lengthMin', 4]]
];

$v = new Valicomb\Validator(array('foo' => 'bar', 'bar' => 'mail@example.com));
$v->mapManyFieldsToRules($rules);
$v->validate();
```

## Adding field label to messages

You can do this in two different ways, you can add a individual label to a rule or an array of all labels for the rules.

To add individual label to rule you simply add the `label` method after the rule.

```php
$v = new Valicomb\Validator(array());
$v->rule('required', 'name')->message('{field} is required')->label('Name');
$v->validate();
```

There is a edge case to this method, you wouldn't be able to use a array of field names in the rule definition, so one rule per field. So this wouldn't work:

```php
$v = new Valicomb\Validator(array());
$v->rule('required', array('name', 'email'))->message('{field} is required')->label('Name');
$v->validate();
```

However we can use a array of labels to solve this issue by simply adding the `labels` method instead:

```php
$v = new Valicomb\Validator(array());
$v->rule('required', array('name', 'email'))->message('{field} is required');
$v->labels(array(
    'name' => 'Name',
    'email' => 'Email address'
));
$v->validate();
```

This introduces a new set of tags to your error language file which looks like `{field}`, if you are using a rule like `equals` you can access the second value in the language file by incrementing the field with a value like `{field1}`.


## Re-use of validation rules

You can re-use your validation rules to quickly validate different data with the same rules by using the withData method:

```php
$v = new Valicomb\Validator(array());
$v->rule('required', 'name')->message('{field} is required');
$v->validate(); //false

$v2 = $v->withData(array('name'=>'example'));
$v2->validate(); //true
```

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
