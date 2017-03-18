# Snidel

A multi-process container. It looks like multi-thread-ish.

[![Latest Stable Version](https://poser.pugx.org/ackintosh/snidel/v/stable)](https://packagist.org/packages/ackintosh/snidel) [![License](https://poser.pugx.org/ackintosh/snidel/license)](https://packagist.org/packages/ackintosh/snidel) [![Build Status](https://travis-ci.org/ackintosh/snidel.svg?branch=master)](https://travis-ci.org/ackintosh/snidel) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ackintosh/snidel/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/ackintosh/snidel/?branch=master) [![Coverage Status](https://coveralls.io/repos/github/ackintosh/snidel/badge.svg?branch=master)](https://coveralls.io/github/ackintosh/snidel?branch=master)

## Installing Snidel via Composer

```
$ composer require ackintosh/snidel
```

## Architecture

![Master - Worker Architecture](images/0.8_pluggable_queue.png)

## Usage

### Basic

```php
<?php
use Ackintosh\Snidel;

$func = function ($str) {
    sleep(3);
    return $str;
};

$s = time();
$snidel = new Snidel();
$snidel->fork($func, 'foo');
$snidel->fork($func, 'bar');
$snidel->fork($func, 'baz');

$snidel->wait();// optional

// Snidel::get() returns instance of Snidel\Result\Collection
$collection = $snidel->get();

// Snidel\Result\Collection implements \Iterator
foreach ($collection as $result) {
    echo $result->getFork()->getPid();
    echo $result->getOutput();
    echo $result->getReturn();
}

var_dump($collection->toArray());
// * the order of results is not guaranteed. *
// array(3) {
//   [0]=>
//   string(3) "bar"
//   [1]=>
//   string(3) "foo"
//   [2]=>
//   string(3) "baz"
// }

echo (time() - $s) . 'sec elapsed' . PHP_EOL;
// 3sec elapsed.
```

### Same argument as `call_user_func_array`

```php
// multiple arguments
$snidel->fork($func, ['foo', 'bar']);

// global function
$snidel->fork('myfunction');

// instance method
$snidel->fork([$instance, 'method']);

```

### Get results with tags

```php
$snidel->fork($func, 'foo', 'tag1');
$snidel->fork($func, 'bar', 'tag1');
$snidel->fork($func, 'baz', 'tag2');

var_dump($snidel->get('tag1')->toArray());
// array(2) {
//   [0]=>
//   string(3) "foo"
//   [1]=>
//   string(3) "bar"
// }

// throws InvalidArgumentException when passed unknown tags.
$snidel->get('unknown_tags');
// InvalidArgumentException: There is no tags: unknown_tags
```

### Concurrency

```php
$snidel = new Snidel($concurrency = 3);

```

### Output log

```php
$fp = fopen('php://stdout', 'w');
$snidel->setLogDestination($fp);

// logs are output to the `php://stdout`
$snidel->fork($func, 'foo');

// [2015-12-01 00:00:00][info][26304(p)] created child process. pid: 26306
// [2015-12-01 00:00:00][info][26306(c)] --> waiting for the token to come around.
// [2015-12-01 00:00:00][info][26306(c)] ----> started the function.
// [2015-12-01 00:00:00][info][26306(c)] <-- return token.
// ...

```

### Error informations of children

```php
$snidel->fork(function ($arg1, $arg2) {
    exit(1);
}, ['foo', 'bar']);
$snidel->wait();

var_dump($snidel->getError());
// class Ackintosh\Snidel\Error#4244 (1) {
// ...
// }

foreach ($snidel->getError() as $pid => $e) {
    var_dump($pid, $e);
}
// int(51813)
// array(5) {
//   'status' =>  int(256)
//   'message' => string(50) "an error has occurred in child process.
//   'callable' => string(9) "*Closure*"
//   'args' =>
//     array(2) {
//       [0] => string(3) "foo"
//       [1] => string(3) "bar"
//     }
//   'return' => NULL
//   }
// }
```

## With Amazon SQS

[ackintosh/snidel-queue-sqs](https://github.com/ackintosh/snidel-queue-sqs)

![with amazon sqs](images/snidel-queue-sqs.png)


## Requirements

- [PCNTL functions](http://php.net/manual/en/ref.pcntl.php)
- [Semaphore functions](http://php.net/manual/en/ref.sem.php)

### Version Guidance

| Version   | PHP Version |
|:----------|:------------|
| 0.1 ~ [0.8](https://github.com/ackintosh/snidel/releases/tag/0.8.0)    | >= 5.3      |
| 0.9 ([under development](https://github.com/ackintosh/snidel/pull/16)) | >= 5.6      |

## Author

Akihito Nakano

blog entries by author about snidel. (japanese)

- https://ackintosh.github.io/blog/2015/09/29/snidel/
- https://ackintosh.github.io/blog/2015/11/08/snidel_0_2_0/
- https://ackintosh.github.io/blog/2016/04/04/snidel_0_4_0/
- https://ackintosh.github.io/blog/2016/04/04/snidel_0_5_0/
- https://ackintosh.github.io/blog/2016/05/04/snidel_0_6_0/
- https://ackintosh.github.io/blog/2016/09/09/snidel_0_7_0/
- https://ackintosh.github.io/blog/2017/03/10/snidel_0_8_0/

## License

[The MIT License](http://opensource.org/licenses/MIT)
