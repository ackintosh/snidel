# Snidel

A multi-process container. Snidel makes it easier for all PHP developers to work with parallel processing *without any extensions*.

[![Latest Stable Version](https://poser.pugx.org/ackintosh/snidel/v/stable)](https://packagist.org/packages/ackintosh/snidel) [![License](https://poser.pugx.org/ackintosh/snidel/license)](https://packagist.org/packages/ackintosh/snidel) [![Tests](https://github.com/ackintosh/snidel/actions/workflows/tests.yml/badge.svg?branch=master)](https://github.com/ackintosh/snidel/actions/workflows/tests.yml) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ackintosh/snidel/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/ackintosh/snidel/?branch=master) [![Coverage Status](https://coveralls.io/repos/github/ackintosh/snidel/badge.svg?branch=master)](https://coveralls.io/github/ackintosh/snidel?branch=master) [![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.1-8892BF.svg?style=flat-square)](https://php.net/)

**Please consider [donating](https://github.com/sponsors/ackintosh) to this project's author, [Akihito Nakano](#author), to show your :heart: and support.**

[Sponsor @ackintosh on GitHub Sponsors](https://github.com/sponsors/ackintosh)

## What Snidel solves?

(en)

Not a few people, start their programming carrier with PHP, and go on. Parallel processing, they are not familiar with it, and may be a hurdle for them.

Or else, people who limited to develop with a language that is not PHP (e.g. A language that has superior feature for parallel processing). (It's me in past.)

To make parallel processing more easily and instinctively to them to use, I started developing Snidel.

Snidel can be one of your options when you are considering "How to do it parallelly?". It's an honer for me.

(ja)

PHPでプログラミングに入門して、PHPでキャリアを積み重ねているプログラマがある程度いると思います(私がそうです)。そのような方にとって並列処理は馴染みがなかったり、敷居の高さを感じるのではないでしょうか。

或いは諸事情によりPHP以外の言語(例えば、並列処理のための優れた機構を持った言語)を利用する事を制限されている中で開発を進めなければならない状況にある方もいらっしゃるでしょう(以前の私がそうでした)。

そのような方が、手軽に・直感的に並列処理を使って問題解決できることを目的として Snidel の開発をはじめました。

"この処理を並列に実行したいんだけどどうしよう？" といった場合に Snidel がみなさんの選択肢のひとつになれたら幸いです。


## Installing Snidel via Composer

```
$ composer require ackintosh/snidel:~0.11.0
```

## Architecture

![Master - Worker Architecture](images/0.8_pluggable_queue.png)

## Benefits

It is also possible parallel processing via build-in functions (e.g. `exec`):

```php
initialize_data_required_for_the_slow_jobs();

exec('php slow_job1.php &');
exec('php slow_job2.php &');
```

For the developers who feels "pain" with the above, Snidel can provides pretty good experience and will streamline their PHP programming.

We will walk through usage to show how Snidel melt parallel processing into your programming. The experience using Snidel should resolve your pain. Let's get started!

## Usage

### Basic Usage

```php
<?php
use Ackintosh\Snidel;

$f = function ($s) {
    sleep(3);
    echo 'echo: ' . $s;
    return 'return: ' . $s;
};

$s = time();
$snidel = new Snidel();
$snidel->process($f, ['foo']);
$snidel->process($f, ['bar']);
$snidel->process($f, ['baz']);

// `Snidel::results()` returns `\Generator`
foreach ($snidel->results() as $r) {
    // string(9) "echo: foo"
    var_dump($r->getOutput());
    // string(11) "return: foo"
    var_dump($r->getReturn());
}

// If you don't need the results, let's use `Snidel::wait()` instead of `Snidel::results()`
// $snidel->wait();

echo (time() - $s) . 'sec elapsed' . PHP_EOL;
// 3sec elapsed.
```

### Constructor parameters

All parameters are optional.

```php
new Snidel([
    'concurrency' => 3,
    // Please refer to `Logging`
    'logger' => $monolog,
    // Please refer to `Using custom queue`
    'driver' => $driver,
    // a polling duration(in seconds) of queueing
    'pollingDuration' => 1,
]);
```

### Same arguments as `call_user_func_array`

```php
// multiple arguments
$snidel->process($f, ['arg1', 'arg2']);

// global function
$snidel->process('myfunction');

// instance method
$snidel->process([$instance, 'method']);

```

### Tagging the task

```php
$f = function ($arg) {
    return $arg;
};

$snidel->process($f, 'arg-A_tag1', 'tag1');
$snidel->process($f, 'arg-B_tag1', 'tag1');
$snidel->process($f, 'arg_tag2', 'tag2');

foreach ($snidel->results as $r) {
    // `Task::getTag()` returns the tag passed as 3rd parameter of `Snidel::process()`
    switch ($r->getTask()->getTag()) {
        case 'tag1':
            $r->getReturn(); // arg-A_tag1 | arg-B_tag1
            break;
        case 'tag2':
            $r->getReturn(); // arg_tag2
            break;
        default:
            $r->getReturn();
            break;
    }
}
```

### Logging

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

### Using custom queue

Snidel depends on [Bernard](https://github.com/bernardphp/bernard) as a queue abstraction layer. Bernard is a multi-backend PHP library for creating background jobs for later processing.  
By default Snidel builds the flatfile driver, but from a race condition perspective, we recommend using a more reliable queue in production.  

##### Amazon SQS

```php
$connection = Aws\Sqs\SqsClient::factory([
    'key'    => 'your-aws-access-key',
    'secret' => 'your-aws-secret-key',
    'region' => 'the-aws-region-you-choose'
]);
$driver = new Bernard\Driver\SqsDriver($connection);

new Snidel([
    'driver' => $driver,
]);
```

For details on the driver, please see [here](http://bernard.readthedocs.io/drivers.html).

## Articles

Here are articles that introducing Snidel. Thank you!

- [PHP-Дайджест № 134 (24 июня – 8 июля 2018) / Блог компании Zfort Group / Хабр https://habr.com/ru/company/zfort/blog/416543/]

## Requirements

- [PCNTL functions](http://php.net/manual/en/ref.pcntl.php)

### Version Guidance

| Snidel | PHP |
|:----------|:------------|
| 0.1 ~ [0.8](https://github.com/ackintosh/snidel/releases/tag/0.8.0) | >= 5.3 |
| [0.9](https://github.com/ackintosh/snidel/releases/tag/0.9.0) ~ | >= 5.6 |
| [0.13](https://github.com/ackintosh/snidel/releases/tag/0.13.0) | >= 7.1 |

## Docker

We suggest you give it a try with Docker as Snidel requires some php extensions shown in [Requirements](#requirements).

### Run unit tests in docker container

```bash
curl -Ss https://getcomposer.org/installer | php
docker build -t snidel .
docker run --rm -v ${PWD}:/snidel snidel php composer.phar install
docker run --rm -v ${PWD}:/snidel snidel vendor/bin/phpunit
```

## Author

**Snidel** &copy; ackintosh, Released under the [MIT](./LICENSE) License.  
Authored and maintained by ackintosh

> GitHub [@ackintosh](https://github.com/ackintosh) / Twitter [@NAKANO_Akihito](https://twitter.com/NAKANO_Akihito) / [Blog (ja)](https://ackintosh.github.io/)

Blog entries by author about Snidel (ja):

- https://ackintosh.github.io/blog/2015/09/29/snidel/
- https://ackintosh.github.io/blog/2015/11/08/snidel_0_2_0/
- https://ackintosh.github.io/blog/2016/04/04/snidel_0_4_0/
- https://ackintosh.github.io/blog/2016/04/04/snidel_0_5_0/
- https://ackintosh.github.io/blog/2016/05/04/snidel_0_6_0/
- https://ackintosh.github.io/blog/2016/09/09/snidel_0_7_0/
- https://ackintosh.github.io/blog/2017/03/10/snidel_0_8_0/
- https://ackintosh.github.io/blog/2017/07/17/snidel_0_9_0/

## Acknowledgments

Thanks to [JetBrains](https://jetbrains.com/) for supporting us with a [Free Open Source License](https://www.jetbrains.com/buy/opensource).
