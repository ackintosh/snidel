# Snidel
---
A multi-process container. It looks like multi-thread-ish.


## Sample

```
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