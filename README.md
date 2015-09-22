# Snidel

A multi-process container. It looks like multi-thread-ish.

[![Latest Stable Version](https://poser.pugx.org/ackintosh/snidel/v/stable)](https://packagist.org/packages/ackintosh/snidel) [![License](https://poser.pugx.org/ackintosh/snidel/license)](https://packagist.org/packages/ackintosh/snidel) [![Build Status](https://travis-ci.org/ackintosh/snidel.svg?branch=master)](https://travis-ci.org/ackintosh/snidel)

## Installing Snidel via Composer

```
$ composer require ackintosh/snidel
```

## Sample

```php
<?php
$func = function ($str) {
    sleep(3);
    return $str;
};

$s = time();
$snidel = new Snidel();
$snidel->fork($func, array('foo'));
$snidel->fork($func, array('bar'));
$snidel->fork($func, array('baz'));

$snidel->join();

var_dump($snidel->get());
// array(3) {
//   [0]=>
//   string(3) "foo"
//   [1]=>
//   string(3) "bar"
//   [2]=>
//   string(3) "baz"
// }

echo (time() - $s) . 'sec elapsed' . PHP_EOL;
// 3sec elapsed.
```

## Requirements

Snidel works with PHP 5.2 or above.

- PCNTL functions

## Author

Akihito Nakano