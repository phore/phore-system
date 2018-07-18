# Phore System :: Wrapper to exec()

This documentation is written along the guidelines of educational grade documentation discussed in the 
[infracamp](https://github.com/infracamp/infracamp/blob/master/DOCUMENTATION_GUIDE.md) project. Please ask and
document issues.

## Goals

- Secure and easy-to-use wrapper around `exec()`

## Quickstart

```php
$return = phore_exec("ls -l :path", ["path"=>"some Path "])
echo $return;
```

## Installation

We suggest using [composer](http://getcomposer.com):

```
composer require phore/system
``` 

                            