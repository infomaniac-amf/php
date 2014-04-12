## Intro

**AMF** (Action Message Format) is a binary data serialization protocol. Simply put, it transforms objects in memory into a binary string, and can reverse this binary string into an object in memory. It can be used just like `JSON`, and this library has been build to provide a similar API to that exposed by the `JSON` functionality in `PHP`.

### Purpose

The purpose of this library is to provide a consistent and symmetric implementation of the `AMF` specification in both `PHP` & `JSON`.

### Why use AMF?

Well, it's up to you. `JSON` is perfectly suited to the web, however it does have some shortcomings which are addressed by `AMF`. For starters, `JSON` cannot handle complex object graphs with circular references; additionally, it cannot serialize dates & byte arrays - you would need to do some additional work to support these in `JSON` (convert date to unix timestamp, byte arrays to base64).

### Should I stop using JSON?

Hells no. `JSON` is great; `AMF` can simply provide you with some additional funcitonality which could help you build your web app.

## Getting Started

To begin using this library, you will need to install it via [Composer](https://getcomposer.org/doc/00-intro.md):

```json
{
	"require": {
		"infomaniac-amf/php": "dev-master"
	}
}
```

Once you have run `composer install`, you will be able to use the library by simply including `Composer`'s autoloader:

```php
<?php
require_once 'vendor/autoload.php';
```

## Usage

Here is a simple example of encoding an array to `AMF`:

```php
<?php
require_once 'vendor/autoload.php';

$data = array(
	'any' => 'data',
	'you' => 'like'
);

header('Content-Type: application/x-amf');
echo amf_encode($data);
```

This will produce a binary string which represents your given data.

If you were to inspect the HTTP traffic of a page that produces `AMF` data, using a tool such as [Charles Proxy](http://charlesproxy.com), you would see the following output:

![](http://f.cl.ly/items/360l1Y3O1m2r0K2u1L2z/Image%202014.04.12%2011%3A25%3A40%20AM.png)

To decode this string, simply do the following:

```php
$data = array(
	'any' => 'data',
	'you' => 'like'
);

$encodedData = amf_encode($data);
$data = amf_decode($encodedData);
```

If you were to `var_dump` this data, it would look identical to the input data given to the `amf_encode` function.

```
array (size=2)
  'any' => string 'data' (length=4)
  'you' => string 'like' (length=4)
```

## Data Encoding (Serialization)

The `AMF` spec allows for the serialization of several different data-types.

Here is a link to the latest specification:
[AMF3 Spec - January 2013](http://www.adobe.com/go/amfspec)

This library implements **10** of the **18** data-types described in the specification. The reason for the support of only a *subset* of these types can be seen in two lights: _utility_ and _limitation_. Here is an exhaustive list of the data-types available:

| Data-Type      | Included | Reason for exclusion                                              |
|----------------|----------|-------------------------------------------------------------------|
| Undefined      | ✔        | -                                                                 |
| Null           | ✔        | -                                                                 |
| False          | ✔        | -                                                                 |
| True           | ✔        | -                                                                 |
| Integer        | ✔        | -                                                                 |
| Double         | ✔        | -                                                                 |
| String         | ✔        | -                                                                 |
| XML Document   | ✗        | Who needs XML?                                                    |
| Date           | ✔        | -                                                                 |
| Array          | ✔        | -                                                                 |
| Object         | ✔        | -                                                                 |
| XML            | ✗        | Who needs XML?                                                    |
| ByteArray      | ✔        | -                                                                 |
| Vector<int>    | ✗        | Not high priority - also, possible browser incompat issue with JS |
| Vector<uint>   | ✗        | Not high priority - also, possible browser incompat issue with JS |
| Vector<double> | ✗        | Not high priority - also, possible browser incompat issue with JS |
| Vector<object> | ✗        | Not high priority - also, possible browser incompat issue with JS |
| Dictionary     | ✗        | PHP cannot use objects are array keys                             |

## License

This project is licensed under the `MIT` license.

## Acknowledgements

While writing this library, I used several libraries to validate my progress, and to help me come unstuck. I would like to extend a special thanks to the following libraries and individuals:

- [SabreAMF](https://github.com/evert/SabreAMF)
- [AmfPhp](https://github.com/silexlabs/amfphp-2.0)
- [Charles Proxy](http://charlesproxy.com)'s wonderful AMF implementation
- Arseny Vakhrushev ([neoxic](https://github.com/neoxic/php-amf3)) for his patience, guidance, advice and help
- [Robert Cesaric](https://twitter.com/cesaric), [Grant McMullin](https://twitter.com/thatgrantoke) and [Andre Venter](https://twitter.com/thinkadoo) for their insight and advice
- My esteemed colleagues at [Zando](http://zando.co.za)
