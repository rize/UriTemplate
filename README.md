# PHP URI Template

This is a URI Template implementation in PHP based on [RFC 6570 URI Template](http://tools.ietf.org/html/rfc6570). In addition to URI expansion, it also supports URI extraction (200+ test cases).

[![Build Status](https://travis-ci.org/rezigned/grunt-auto-config.png)](https://travis-ci.org/rezigned/grunt-auto-config) [![Total Downloads](https://poser.pugx.org/rize/uri-template/downloads.png)](https://packagist.org/packages/rize/uri-template)

## Usage

### Expansion

A very simple usage (string expansion).

```php
<?php

use Rize\UriTemplate;

$uri = new UriTemplate;
$uri->expand('/{username}/profile', ['username' => 'john']);

>> '/john/profile'
```

`Rize\UriTemplate` supports all `Expression Types` and `Levels` specified by RFC6570.

```php
<?php

$uri->expand('/search/{term:1}/{term}/{?q*,limit}', [
    'term'  => 'john',
    'q'     => ['a', 'b'],
    'limit' => 10,
])

>> '/search/j/john/?q=a,b&limit=10'
```

`Rize\UriTemplate` accepts `base-uri` as a 1st argument and `default params` as a 2nd argument. This is very useful when you're working with API endpoint.

Take a look at real world example.

```php
<?php

$uri = new UriTemplate('https://api.twitter.com/{version}', ['version' => 1.1]);
$uri->expand('/statuses/show/{id}.json', ['id' => '210462857140252672']);

>> https://api.twitter.com/1.1/statuses/show/210462857140252672.json
```

### Extraction

It also supports URI Extraction (extract all variables from URI). Let's take a look at the example.

```php
<?php

$params = $uri->extract('/search/{term:1}/{term}/{?q*,limit}', '/search/j/john/?q=a&q=b&limit=10');

>> print_r($params);
(
    [term:1] => j
    [term] => john
    [q] => Array
        (
            [0] => a
            [1] => b
        )

    [limit] => 10
)
```

Note that in the example above, result returned by `extract` method has an extra keys named `term:1` for `prefix` modifier. This key was added just for our convenience to access prefix data.

## Installation

```
{
    "require": {
        "rize/uri-template": "~0.1.0"
    }
}
```
