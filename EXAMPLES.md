# Valicomb Usage Examples

This document provides comprehensive examples for all built-in validation rules in Valicomb.

## Table of Contents

- [Valicomb Usage Examples](#valicomb-usage-examples)
  - [Table of Contents](#table-of-contents)
  - [Presence Rules](#presence-rules)
    - [required fields usage](#required-fields-usage)
    - [requiredWith fields usage](#requiredwith-fields-usage)
      - [Strict flag](#strict-flag)
    - [requiredWithout fields usage](#requiredwithout-fields-usage)
      - [Strict flag](#strict-flag-1)
  - [Comparison Rules](#comparison-rules)
    - [equals fields usage](#equals-fields-usage)
    - [different fields usage](#different-fields-usage)
    - [accepted fields usage](#accepted-fields-usage)
  - [Type Rules](#type-rules)
    - [numeric fields usage](#numeric-fields-usage)
    - [integer fields usage](#integer-fields-usage)
    - [boolean fields usage](#boolean-fields-usage)
    - [array fields usage](#array-fields-usage)
  - [String Length Rules](#string-length-rules)
    - [length fields usage](#length-fields-usage)
    - [lengthBetween fields usage](#lengthbetween-fields-usage)
    - [lengthMin fields usage](#lengthmin-fields-usage)
    - [lengthMax fields usage](#lengthmax-fields-usage)
  - [Numeric Range Rules](#numeric-range-rules)
    - [min fields usage](#min-fields-usage)
    - [max fields usage](#max-fields-usage)
  - [Collection Rules](#collection-rules)
    - [listContains fields usage](#listcontains-fields-usage)
    - [in fields usage](#in-fields-usage)
    - [notIn fields usage](#notin-fields-usage)
    - [subset fields usage](#subset-fields-usage)
    - [containsUnique fields usage](#containsunique-fields-usage)
  - [Network Rules](#network-rules)
    - [ip fields usage](#ip-fields-usage)
    - [ipv4 fields usage](#ipv4-fields-usage)
    - [ipv6 fields usage](#ipv6-fields-usage)
    - [email fields usage](#email-fields-usage)
    - [emailDNS fields usage](#emaildns-fields-usage)
    - [url fields usage](#url-fields-usage)
    - [urlActive fields usage](#urlactive-fields-usage)
  - [String Format Rules](#string-format-rules)
    - [alpha fields usage](#alpha-fields-usage)
    - [alphaNum fields usage](#alphanum-fields-usage)
    - [ascii fields usage](#ascii-fields-usage)
    - [slug fields usage](#slug-fields-usage)
    - [regex fields usage](#regex-fields-usage)
    - [contains fields usage](#contains-fields-usage)
  - [Date Rules](#date-rules)
    - [date fields usage](#date-fields-usage)
    - [dateFormat fields usage](#dateformat-fields-usage)
    - [dateBefore fields usage](#datebefore-fields-usage)
    - [dateAfter fields usage](#dateafter-fields-usage)
  - [Special Rules](#special-rules)
    - [Credit Card Validation usage](#credit-card-validation-usage)
    - [instanceOf fields usage](#instanceof-fields-usage)
    - [optional fields usage](#optional-fields-usage)
    - [arrayHasKeys fields usage](#arrayhaskeys-fields-usage)

---

## Presence Rules

### required fields usage
the `required` rule checks if a field exists in the data array, and is not null or an empty string.
```php
$v->rule('required', 'field_name');
```

Using an extra parameter, you can make this rule more flexible, and only check if the field exists in the data array.
```php
$v->rule('required', 'field_name', true);
```

Alternate syntax.
```php
$v = new Frostybee\Valicomb\Validator(['username' => 'spiderman', 'password' => 'Gr33nG0Blin', 'required_but_null' => null]);
$v->rules([
    'required' => [
        ['username'],
        ['password'],
        ['required_but_null', true] // boolean flag allows empty value so long as the field name is set on the data array
    ]
]);
$v->validate();
```

### requiredWith fields usage
The `requiredWith` rule checks that the field is required, not null, and not the empty string, if any other fields are present, not null, and not the empty string.
```php
// password field will be required when the username field is provided and not empty
$v->rule('requiredWith', 'password', 'username');
```

Alternate syntax.
```php
$v = new Frostybee\Valicomb\Validator(['username' => 'spiderman', 'password' => 'Gr33nG0Blin']);
$v->rules([
    'requiredWith' => [
        ['password', 'username']
    ]
]);
$v->validate();
```

*Note* You can provide multiple values as an array. In this case if ANY of the fields are present the field will be required.
```php
// in this case the password field will be required if the username or email fields are present
$v->rule('requiredWith', 'password', ['username', 'email']);
```

Alternate syntax.
```php
$v = new Frostybee\Valicomb\Validator(['username' => 'spiderman', 'password' => 'Gr33nG0Blin']);
$v->rules([
    'requiredWith' => [
        ['password', ['username', 'email']]
    ]
]);
$v->validate();
```

#### Strict flag
The strict flag will change the `requiredWith` rule to `requiredWithAll` which will require the field only if ALL of the other fields are present, not null, and not the empty string.
```php
// in this example the suffix field is required only when both the first_name and last_name are provided
$v->rule('requiredWith', 'suffix', ['first_name', 'last_name'], true);
```
Alternate syntax.
```php
$v = new Frostybee\Valicomb\Validator(['first_name' => 'steve', 'last_name' => 'holt', 'suffix' => 'Mr']);
$v->rules([
    'requiredWith' => [
        ['suffix', ['first_name', 'last_name'], true]
    ]
]);
$v->validate();
```

Likewise, in this case `validate()` would still return true, as the suffix field would not be required in strict mode, as not all of the fields are provided.
```php
$v = new Frostybee\Valicomb\Validator(['first_name' => 'steve']);
$v->rules([
    'requiredWith' => [
        ['suffix', ['first_name', 'last_name'], true]
    ]
]);
$v->validate();
```

### requiredWithout fields usage
The `requiredWithout` rule checks that the field is required, not null, and not the empty string, if any other fields are NOT present.
```php
// this rule will require the username field when the first_name is not present
$v->rule('requiredWithout', 'username', 'first_name')
```

Alternate syntax.
```php
// this will return true, as the username is provided when the first_name is not provided
$v = new Frostybee\Valicomb\Validator(['username' => 'spiderman']);
$v->rules([
    'requiredWithout' => [
        ['username', 'first_name']
    ]
]);
$v->validate();
```

*Note* You can provide multiple values as an array. In this case if ANY of the fields are NOT present the field will be required.
```php
// in this case the username field will be required if either the first_name or last_name fields are not present
$v->rule('requiredWithout', 'username', ['first_name', 'last_name']);
```

Alternate syntax.
```php
// this passes validation because although the last_name field is not present, the username is provided
$v = new Frostybee\Valicomb\Validator(['username' => 'spiderman', 'first_name' => 'Peter']);
$v->rules([
    'requiredWithout' => [
        ['username', ['first_name', 'last_name']]
    ]
]);
$v->validate();
```

#### Strict flag
The strict flag will change the `requiredWithout` rule to `requiredWithoutAll` which will require the field only if ALL of the other fields are not present.
```php
// in this example the username field is required only when both the first_name and last_name are not provided
$v->rule('requiredWithout', 'username', ['first_name', 'last_name'], true);
```
Alternate syntax.
```php
$v = new Frostybee\Valicomb\Validator(['username' => 'BatMan']);
$v->rules([
    'requiredWithout' => [
        ['username', ['first_name', 'last_name'], true]
    ]
]);
$v->validate();
```

Likewise, in this case `validate()` would still return true, as the username field would not be required in strict mode, as all of the fields are provided.
```php
$v = new Frostybee\Valicomb\Validator(['first_name' => 'steve', 'last_name' => 'holt']);
$v->rules([
    'requiredWithout' => [
        ['suffix', ['first_name', 'last_name'], true]
    ]
]);
$v->validate();
```

---

## Comparison Rules

### equals fields usage
The `equals` rule checks if two fields are equals in the data array, and that the second field is not null.
```php
$v->rule('equals', 'password', 'confirmPassword');
```

Alternate syntax.
```php
$v = new Frostybee\Valicomb\Validator(['password' => 'youshouldnotseethis', 'confirmPassword' => 'youshouldnotseethis']);
$v->rules([
    'equals' => [
        ['password', 'confirmPassword']
    ]
]);
$v->validate();
```

### different fields usage
The `different` rule checks if two fields are not the same, or different, in the data array and that the second field is not null.
```php
$v->rule('different', 'username', 'password');
```

Alternate syntax.
```php
$v = new Frostybee\Valicomb\Validator(['username' => 'spiderman', 'password' => 'Gr33nG0Blin']);
$v->rules([
    'different' => [
        ['username', 'password']
    ]
]);
$v->validate();
```

### accepted fields usage
The `accepted` rule checks if the field is either 'yes', 'on', 1, or true.
```php
$v->rule('accepted', 'remember_me');
```

Alternate syntax.
```php
$v = new Frostybee\Valicomb\Validator(['remember_me' => true]);
$v->rules([
    'accepted' => [
        ['remember_me']
    ]
]);
$v->validate();
```

---

## Type Rules

### numeric fields usage
The `numeric` rule checks if the field is number. This is analogous to php's is_numeric() function.
```php
$v->rule('numeric', 'amount');
```

Alternate syntax.
```php
$v = new Frostybee\Valicomb\Validator(['amount' => 3.14]);
$v->rules([
    'numeric' => [
        ['amount']
    ]
]);
$v->validate();
```

### integer fields usage
The `integer` rule checks if the field is an integer number.
```php
$v->rule('integer', 'age');
```

Alternate syntax.
```php
$v = new Frostybee\Valicomb\Validator(['age' => '27']);
$v->rules([
    'integer' => [
        ['age']
    ]
]);
$v->validate();
```

*Note* the optional boolean flag for strict mode makes sure integers are to be supplied in a strictly numeric form. So the following rule would evaluate to true:
```php
$v = new Frostybee\Valicomb\Validator(['negative' => '-27', 'positive'=>'27']);
$v->rule('integer', 'age', true);
$v->rule('integer', 'height', true);
$v->validate();
```

Whereas the following will evaluate to false, as the + for the positive number in this case is redundant:
```php
$v = new Frostybee\Valicomb\Validator(['negative' => '-27', 'positive'=>'+27']);
$v->rule('integer', 'age', true);
$v->rule('integer', 'height', true);
$v->validate();
```

### boolean fields usage
The `boolean` rule checks if the field is a boolean. This is analogous to php's is_bool() function.
```php
$v->rule('boolean', 'remember_me');
```

Alternate syntax.
```php
$v = new Frostybee\Valicomb\Validator(['remember_me' => true]);
$v->rules([
    'boolean' => [
        ['remember_me']
    ]
]);
$v->validate();
```

### array fields usage
The `array` rule checks if the field is an array. This is analogous to php's is_array() function.
```php
$v->rule('array', 'user_notifications');
```

Alternate Syntax.
```php
$v = new Frostybee\Valicomb\Validator(['user_notifications' => ['bulletin_notifications' => true, 'marketing_notifications' => false, 'message_notification' => true]]);
$v->rules([
    'array' => [
        ['user_notifications']
    ]
]);
$v->validate();
```

---

## String Length Rules

### length fields usage
The `length` rule checks if the field is exactly a given length and that the field is a valid string.
```php
$v->rule('length', 'username', 10);
```

Alternate syntax.
```php
$v = new Frostybee\Valicomb\Validator(['username' => 'bobburgers']);
$v->rules([
    'length' => [
        ['username', 10]
    ]
]);
$v->validate();
```

### lengthBetween fields usage
The `lengthBetween` rule checks if the field is between a given length tange and that the field is a valid string.
```php
$v->rule('lengthBetween', 'username', 1, 10);
```

Alternate syntax.
```php
$v = new Frostybee\Valicomb\Validator(['username' => 'bobburgers']);
$v->rules([
    'lengthBetween' => [
        ['username', 1, 10]
    ]
]);
$v->validate();
```

### lengthMin fields usage
The `lengthMin` rule checks if the field is at least a given length and that the field is a valid string.
```php
$v->rule('lengthMin', 'username', 5);
```

Alternate syntax.
```php
$v = new Frostybee\Valicomb\Validator(['username' => 'martha']);
$v->rules([
    'lengthMin' => [
        ['username', 5]
    ]
]);
$v->validate();
```

### lengthMax fields usage
The `lengthMax` rule checks if the field is at most a given length and that the field is a valid string.
```php
$v->rule('lengthMax', 'username', 10);
```

Alternate syntax.
```php
$v = new Frostybee\Valicomb\Validator(['username' => 'bruins91']);
$v->rules([
    'lengthMax' => [
        ['username', 10]
    ]
]);
$v->validate();
```

---

## Numeric Range Rules

### min fields usage
The `min` rule checks if the field is at least a given value and that the provided value is numeric.
```php
$v->rule('min', 'age', 18);
```

Alternate syntax.
```php
$v = new Frostybee\Valicomb\Validator(['age' => 28]);
$v->rules([
    'min' => [
        ['age', 18]
    ]
]);
$v->validate();
```

### max fields usage
The `max` rule checks if the field is at most a given value and that the provided value is numeric.
```php
$v->rule('max', 'age', 12);
```

Alternate syntax.
```php
$v = new Frostybee\Valicomb\Validator(['age' => 10]);
$v->rules([
    'max' => [
        ['age', 12]
    ]
]);
$v->validate();
```

---

## Collection Rules

### listContains fields usage
The `listContains` rule checks that the field is present in a given array of values.
```php
$v->rule('listContains', 'color', 'yellow');
```

Alternate syntax.
```php
$v = new Frostybee\Valicomb\Validator(['color' => ['blue', 'green', 'red', 'yellow']]);
$v->rules([
    'listContains' => [
        ['color', 'yellow']
    ]
]);
$v->validate();
```

### in fields usage
The `in` rule checks that the field is present in a given array of values.
```php
$v->rule('in', 'color', ['blue', 'green', 'red', 'purple']);
```

Alternate syntax.
```php
$v = new Frostybee\Valicomb\Validator(['color' => 'purple']);
$v->rules([
    'in' => [
        ['color', ['blue', 'green', 'red', 'purple']]
    ]
]);
$v->validate();
```

### notIn fields usage
The `notIn` rule checks that the field is NOT present in a given array of values.
```php
$v->rule('notIn', 'color', ['blue', 'green', 'red', 'yellow']);
```

Alternate syntax.
```php
$v = new Frostybee\Valicomb\Validator(['color' => 'purple']);
$v->rules([
    'notIn' => [
        ['color', ['blue', 'green', 'red', 'yellow']]
    ]
]);
$v->validate();
```

### subset fields usage
The `subset` rule checks that the field is either a scalar or array field and that all of it's values are contained within a given set of values.
```php
$v->rule('subset', 'colors', ['green', 'blue', 'orange']);
```

Alternate syntax.
```php
$v = new Frostybee\Valicomb\Validator(['colors' => ['green', 'blue']]);
$v->rules([
    'subset' => [
        ['colors', ['orange', 'green', 'blue', 'red']]
    ]
]);
$v->validate();
```
This example would return false, as the provided color, purple, does not exist in the array of accepted values we're providing.
```php
$v = new Frostybee\Valicomb\Validator(['colors' => ['purple', 'blue']]);
$v->rules([
    'subset' => [
        ['colors', ['orange', 'green', 'blue', 'red']]
    ]
]);
$v->validate();
```

### containsUnique fields usage
The `containsUnique` rule checks that the provided field is an array and that all values contained within are unique, i.e. no duplicate values in the array.
```php
$v->rule('containsUnique', 'colors');
```

Alternate syntax.
```php
$v = new Frostybee\Valicomb\Validator(['colors' => ['purple', 'blue']]);
$v->rules([
    'containsUnique' => [
        ['colors']
    ]
]);
$v->validate();
```
This example would return false, as the values in the provided array are duplicates.
```php
$v = new Frostybee\Valicomb\Validator(['colors' => ['purple', 'purple']]);
$v->rules([
    'containsUnique' => [
        ['colors']
    ]
]);
$v->validate();
```

---

## Network Rules

### ip fields usage
The `ip` rule checks that the field is a valid ip address. This includes IPv4, IPv6, private, and reserved ranges.
```php
$v->rule('ip', 'user_ip');
```

Alternate syntax.
```php
$v = new Frostybee\Valicomb\Validator(['user_ip' => '127.0.0.1']);
$v->rules([
    'ip' => [
        ['user_ip']
    ]
]);
$v->validate();
```

### ipv4 fields usage
The `ipv4` rule checks that the field is a valid IPv4 address.
```php
$v->rule('ipv4', 'user_ip');
```

Alternate syntax.
```php
$v = new Frostybee\Valicomb\Validator(['user_ip' => '127.0.0.1']);
$v->rules([
    'ipv4' => [
        ['user_ip']
    ]
]);
$v->validate();
```

### ipv6 fields usage
The `ipv6` rule checks that the field is a valid IPv6 address.
```php
$v->rule('ipv6', 'user_ip');
```

Alternate syntax.
```php
$v = new Frostybee\Valicomb\Validator(['user_ip' => '0:0:0:0:0:0:0:1']);
$v->rules([
    'ipv6' => [
        ['user_ip']
    ]
]);
$v->validate();
```

### email fields usage
The `email` rule checks that the field is a valid email address.
```php
$v->rule('email', 'user_email');
```

Alternate syntax.
```php
$v = new Frostybee\Valicomb\Validator(['user_email' => 'someone@example.com']);
$v->rules([
    'email' => [
        ['user_email']
    ]
]);
$v->validate();
```

### emailDNS fields usage
The `emailDNS` rule validates the field is a valid email address with an active DNS record or any type.
```php
$v->rule('emailDNS', 'user_email');
```

Alternate syntax.
```php
$v = new Frostybee\Valicomb\Validator(['user_email' => 'some_fake_email_address@gmail.com']);
$v->rules([
    'emailDNS' => [
        ['user_email']
    ]
]);
$v->validate();
```

### url fields usage
The `url` rule checks the field is a valid url.
```php
$v->rule('url', 'website');
```

Alternate syntax.
```php
$v = new Frostybee\Valicomb\Validator(['website' => 'https://example.com/contact']);
$v->rules([
    'url' => [
        ['website']
    ]
]);
$v->validate();
```

### urlActive fields usage
The `urlActive` rule checks the field is a valid url with an active A, AAAA, or CNAME record.
```php
$v->rule('urlActive', 'website');
```

Alternate syntax.
```php
$v = new Frostybee\Valicomb\Validator(['website' => 'https://example.com/contact']);
$v->rules([
    'urlActive' => [
        ['website']
    ]
]);
$v->validate();
```

---

## String Format Rules

### alpha fields usage
The `alpha` rule checks the field is alphabetic characters only.
```php
$v->rule('alpha', 'username');
```

Alternate syntax.
```php
$v = new Frostybee\Valicomb\Validator(['username' => 'batman']);
$v->rules([
    'alpha' => [
        ['username']
    ]
]);
$v->validate();
```

### alphaNum fields usage
The `alphaNum` rule checks the field contains only alphabetic or numeric characters.
```php
$v->rule('alphaNum', 'username');
```

Alternate syntax.
```php
$v = new Frostybee\Valicomb\Validator(['username' => 'batman123']);
$v->rules([
    'alphaNum' => [
        ['username']
    ]
]);
$v->validate();
```

### ascii fields usage
The `ascii` rule checks the field contains only characters in the ascii character set.
```php
$v->rule('ascii', 'username');
```

Alternate syntax.
```php
$v = new Frostybee\Valicomb\Validator(['username' => 'batman123']);
$v->rules([
    'ascii' => [
        ['username']
    ]
]);
$v->validate();
```

### slug fields usage
The `slug` rule checks that the field only contains URL slug characters (a-z, 0-9, -, _).
```php
$v->rule('slug', 'username');
```

Alternate syntax.
```php
$v = new Frostybee\Valicomb\Validator(['username' => 'L337-H4ckZ0rz_123']);
$v->rules([
    'slug' => [
        ['username']
    ]
]);
$v->validate();
```

### regex fields usage
The `regex` rule ensures the field matches a given regex pattern.
(This regex checks the string is alpha numeric between 5-10 characters).
```php
$v->rule('regex', 'username', '/^[a-zA-Z0-9]{5,10}$/');
```

Alternate syntax.
```php
$v = new Frostybee\Valicomb\Validator(['username' => 'Batman123']);
$v->rules([
    'regex' => [
        ['username', '/^[a-zA-Z0-9]{5,10}$/']
    ]
]);
$v->validate();
```

### contains fields usage
The `contains` rule checks that a given string exists within the field and checks that the field and the search value are both valid strings.
```php
$v->rule('contains', 'username', 'man');
```

Alternate syntax.
```php
$v = new Frostybee\Valicomb\Validator(['username' => 'Batman123']);
$v->rules([
    'contains' => [
        ['username', 'man']
    ]
]);
$v->validate();
```

*Note* You can use the optional strict flag to ensure a case-sensitive match.
The following example will return true:
```php
$v = new Frostybee\Valicomb\Validator(['username' => 'Batman123']);
$v->rules([
    'contains' => [
        ['username', 'man']
    ]
]);
$v->validate();
```
Whereas, this would return false, as the M in the search string is not uppercase in the provided value:
```php
$v = new Frostybee\Valicomb\Validator(['username' => 'Batman123']);
$v->rules([
    'contains' => [
        ['username', 'Man', true]
    ]
]);
$v->validate();
```

---

## Date Rules

### date fields usage
The `date` rule checks if the supplied field is a valid \DateTime object or if the string can be converted to a unix timestamp via strtotime().
```php
$v->rule('date', 'created_at');
```

Alternate syntax.
```php
$v = new Frostybee\Valicomb\Validator(['created_at' => '2018-10-13']);
$v->rules([
    'date' => [
        ['created_at']
    ]
]);
$v->validate();
```

### dateFormat fields usage
The `dateFormat` rule checks that the supplied field is a valid date in a specified date format.
```php
$v->rule('dateFormat', 'created_at', 'Y-m-d');
```

Alternate syntax.
```php
$v = new Frostybee\Valicomb\Validator(['created_at' => '2018-10-13']);
$v->rules([
    'dateFormat' => [
        ['created_at', 'Y-m-d']
    ]
]);
$v->validate();
```

### dateBefore fields usage
The `dateBefore` rule checks that the supplied field is a valid date before a specified date.
```php
$v->rule('dateBefore', 'created_at', '2018-10-13');
```

Alternate syntax.
```php
$v = new Frostybee\Valicomb\Validator(['created_at' => '2018-09-01']);
$v->rules([
    'dateBefore' => [
        ['created_at', '2018-10-13']
    ]
]);
$v->validate();
```

### dateAfter fields usage
The `dateAfter` rule checks that the supplied field is a valid date after a specified date.
```php
$v->rule('dateAfter', 'created_at', '2018-10-13');
```

Alternate syntax.
```php
$v = new Frostybee\Valicomb\Validator(['created_at' => '2018-09-01']);
$v->rules([
    'dateAfter' => [
        ['created_at', '2018-01-01']
    ]
]);
$v->validate();
```

---

## Special Rules

### Credit Card Validation usage

Credit card validation currently allows you to validate a Visa `visa`,
Mastercard `mastercard`, Dinersclub `dinersclub`, American Express `amex`
or Discover `discover`

This will check the credit card against each card type

```php
$v->rule('creditCard', 'credit_card');
```

To optionally filter card types, add the slug to an array as the next parameter:

```php
$v->rule('creditCard', 'credit_card', ['visa', 'mastercard']);
```

If you only want to validate one type of card, put it as a string:

```php
$v->rule('creditCard', 'credit_card', 'visa');
```

If the card type information is coming from the client, you might also want to
still specify an array of valid card types:

```php
$cardType = 'amex';
$v->rule('creditCard', 'credit_card', $cardType, ['visa', 'mastercard']);
$v->validate(); // false
```

### instanceOf fields usage
The `instanceOf` rule checks that the field is an instance of a given class.
```php
$v->rule('instanceOf', 'date', \DateTime);
```

Alternate syntax.
```php
$v = new Frostybee\Valicomb\Validator(['date' => new \DateTime()]);
$v->rules([
    'instanceOf' => [
        ['date', 'DateTime']
    ]
]);
$v->validate();
```
*Note* You can also compare the value against a given object as opposed to the string class name.
This example would also return true:
```php
$v = new Frostybee\Valicomb\Validator(['date' => new \DateTime()]);
$existingDateObject = new \DateTime();
$v->rules([
    'instanceOf' => [
        ['date', $existingDateObject]
    ]
]);
$v->validate();
```

### optional fields usage
The `optional` rule ensures that if the field is present in the data set that it passes all validation rules.
```php
$v->rule('optional', 'username');
```

Alternate syntax.
This example would return true either when the 'username' field is not present or in the case where the username is only alphabetic characters.
```php
$v = new Frostybee\Valicomb\Validator(['username' => 'batman']);
$v->rules([
    'alpha' => [
        ['username']
    ],
    'optional' => [
        ['username']
    ]
]);
$v->validate();
```
This example would return false, as although the field is optional, since it is provided it must pass all the validation rules, which in this case it does not.
```php
$v = new Frostybee\Valicomb\Validator(['username' => 'batman123']);
$v->rules([
    'alpha' => [
        ['username']
    ],
    'optional' => [
        ['username']
    ]
]);
$v->validate();
```

### arrayHasKeys fields usage

The `arrayHasKeys` rule ensures that the field is an array and that it contains all the specified keys.
Returns false if the field is not an array or if no required keys are specified or if some key is missing.

```php
$v = new Frostybee\Valicomb\Validator([
    'address' => [
        'name' => 'Jane Doe',
        'street' => 'Doe Square',
        'city' => 'Doe D.C.'
    ]
]);
$v->rule('arrayHasKeys', 'address', ['name', 'street', 'city']);
$v->validate();
```
