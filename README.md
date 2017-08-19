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

$f = function ($str) {
    sleep(3);
    return $str;
};

$s = time();
$snidel = new Snidel();
$snidel->process($f, 'foo');
$snidel->process($f, 'bar');
$snidel->process($f, 'baz');

// `Snidel::results()` returns `\Generator`
foreach ($snidel->results() as $r) {
    echo $r->getProcess()->getPid();
    echo $r->getOutput();
    echo $r->getReturn();
}

// If you don't need the results, let's use `Snidel::wait()`
// $snidel->wait();

echo (time() - $s) . 'sec elapsed' . PHP_EOL;
// 3sec elapsed.
```

### Constructor parameters

```php
new Snidel([
    'concurrency' => 2,
    'logger' => $monolog,
    'taskQueue'   => [
        'className' => '\Ackintosh\Snidel\Queue\Sqs\Task',
    ],
    'resultQueue' => [
        'className' => '\Ackintosh\Snidel\Queue\Sqs\Result',
    ],
]);
```

### Same arguments as `call_user_func_array`

```php
// multiple arguments
$snidel->process($f, ['foo', 'bar']);

// global function
$snidel->process('myfunction');

// instance method
$snidel->process([$instance, 'method']);

```

### Tag the task

```php
$snidel->process($f, 'foo', 'tag1');
$snidel->process($f, 'bar', 'tag1');
$snidel->process($f, 'baz', 'tag2');

foreach ($snidel->results as $r) {
    switch ($r->getTask()->getTag()) {
        case 'tag1':
            // ...
            break;
        case 'tag2':
            // ...
            break;
        default:
            // ...
            break;
    }
}
```

### With Logger

Snidel supports logging with logger which implements [PSR-3: Logger Interface](http://www.php-fig.org/psr/psr-3/).

```php
// e.g. MonoLog
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

$monolog = new Logger('sample');
$stream = new StreamHandler('php://stdout', Logger::DEBUG);
$stream->setFormatter(new LineFormatter("%datetime% > %level_name% > %message% %context%\n"));
$monolog->pushHandler($stream);

$snidel = new Snidel(['logger' => $monolog]);
$snidel->process($f);

// 2017-03-22 13:13:43 > DEBUG > forked worker. pid: 60018 {"role":"master","pid":60017}
// 2017-03-22 13:13:43 > DEBUG > forked worker. pid: 60019 {"role":"master","pid":60017}
// 2017-03-22 13:13:43 > DEBUG > has forked. pid: 60018 {"role":"worker","pid":60018}
// 2017-03-22 13:13:43 > DEBUG > has forked. pid: 60019 {"role":"worker","pid":60019}
// 2017-03-22 13:13:44 > DEBUG > ----> started the function. {"role":"worker","pid":60018}
// 2017-03-22 13:13:44 > DEBUG > ----> started the function. {"role":"worker","pid":60019}
// ...

```

### Error informations of children

```php
$snidel->process(function ($arg1, $arg2) {
    exit(1);
}, ['foo', 'bar']);
$snidel->get();

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

| Snidel | PHP |
|:----------|:------------|
| 0.1 ~ [0.8](https://github.com/ackintosh/snidel/releases/tag/0.8.0) | >= 5.3 |
| [0.9](https://github.com/ackintosh/snidel/releases/tag/0.9.0) ~ | >= 5.6 |

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
- https://ackintosh.github.io/blog/2017/07/17/snidel_0_9_0/

## License

[The MIT License](http://opensource.org/licenses/MIT)
