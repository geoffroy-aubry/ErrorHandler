# ErrorHandler

[![Latest stable version](http://img.shields.io/packagist/v/geoffroy-aubry/ErrorHandler.svg "Latest stable version")](https://packagist.org/packages/geoffroy-aubry/ErrorHandler)
[![Build Status](https://secure.travis-ci.org/geoffroy-aubry/ErrorHandler.png?branch=stable)](http://travis-ci.org/geoffroy-aubry/ErrorHandler)
[![Coverage Status](http://img.shields.io/coveralls/geoffroy-aubry/ErrorHandler/stable.svg)](https://coveralls.io/r/geoffroy-aubry/ErrorHandler)
[![Dependency Status](https://www.versioneye.com/user/projects/5354e190fe0d071c050011c1/badge.png)](https://www.versioneye.com/user/projects/5354e190fe0d071c050011c1)

Simple error and exception handler:

 * converts error to an `ErrorException` instance according to error reporting level
 * when running the `PHP CLI`, reports errors/exceptions to `STDERR` (even fatal error)
   and uses exception code as exit status
 * allows to deactivate `@` operator
 * catches fatal error
 * accepts callback to be executed at the end of the internal shutdown function
 * accepts callback to display an apology when errors are hidden
 * allows to ignore errors on some paths, useful with old libraries and deprecated code…

## Installation

1. Class autoloading and dependencies are managed by [Composer](http://getcomposer.org/)
    so install it following the instructions
    on [Composer: Installation - *nix](http://getcomposer.org/doc/00-intro.md#installation-nix)
    or just run the following command:
    ```bash
    $ curl -sS https://getcomposer.org/installer | php
    ```

1. Add dependency to `GAubry\ErrorHandler` into require section of your `composer.json`:
    ```json
    {
        "require": {
            "geoffroy-aubry/errorhandler": "1.*"
        }
    }
    ```
    and run `php composer.phar install` from the terminal into the root folder of your project.

1. Include Composer's autoloader:
    ```php
    <?php
    
    require_once 'vendor/autoload.php';
    …
    ```
    
## Usage

1. Basic usage, in your bootstrap:
    ```php
    <?php
    
    use GAubry\ErrorHandler\ErrorHandler;
    
    $aConfig = array(
        'display_errors'        => true,
        'error_log_path'        => '/var/log/xyz.log',
        'error_reporting_level' => -1,
        'auth_error_suppr_op'   => false
    );
    new ErrorHandler($aConfig);
    
    …
    ```
    
1. Ignore errors on some paths, useful with old libraries and deprecated code:
    ```php
    $oErrorHandler = new ErrorHandler($aConfig);
    $oErrorHandler->addExcludedPath('[CouchbaseNative]', true);
    ```

## Documentation
[API documentation](http://htmlpreview.github.io/?https://github.com/geoffroy-aubry/ErrorHandler/blob/stable/doc/api/index.html)
is generated by [ApiGen](http://apigen.org/) in the `doc/api` folder.

```bash
$ php vendor/bin/apigen -c apigen.neon
```

## Copyrights & licensing
Licensed under the GNU Lesser General Public License v3 (LGPL version 3).
See [LICENSE](LICENSE) file for details.

## Change log
See [CHANGELOG](CHANGELOG.md) file for details.

## Continuous integration

[![Build Status](https://secure.travis-ci.org/geoffroy-aubry/ErrorHandler.png?branch=stable)](http://travis-ci.org/geoffroy-aubry/ErrorHandler)
[![Coverage Status](http://img.shields.io/coveralls/geoffroy-aubry/ErrorHandler/stable.svg)](https://coveralls.io/r/geoffroy-aubry/ErrorHandler)
[![Dependency Status](https://www.versioneye.com/user/projects/5354e190fe0d071c050011c1/badge.png)](https://www.versioneye.com/user/projects/5354e190fe0d071c050011c1)

Following commands are executed during each build and must report neither errors nor warnings:

 * Unit tests with [PHPUnit](https://github.com/sebastianbergmann/phpunit/):

    ```bash
    $ php vendor/bin/phpunit --configuration phpunit.xml
    ```

 *  Coding standards with [PHP CodeSniffer](http://pear.php.net/package/PHP_CodeSniffer):

    ```bash
    $ php vendor/bin/phpcs --standard=PSR2 src/ tests/ -v
    ```

 *  Code quality with [PHP Mess Detector](http://phpmd.org/):

    ```bash
    $ php vendor/bin/phpmd src/ text codesize,design,unusedcode,naming,controversial
    ```

## Git branching model
The git branching model used for development is the one described and assisted by `twgit` tool: [https://github.com/Twenga/twgit](https://github.com/Twenga/twgit).
