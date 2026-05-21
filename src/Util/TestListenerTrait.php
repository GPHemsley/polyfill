<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Util;

use PHPUnit\Framework\SkippedTestError;
use PHPUnit\Util\Test;
use Symfony\Component\VarDumper\Caster\ReflectionCaster;
use Symfony\Component\VarDumper\Cloner\Stub;

/**
 * @author Nicolas Grekas <p@tchwork.com>
 */
class TestListenerTrait
{
    public static $enabledPolyfills;

    public function startTestSuite($mainSuite)
    {
        $warnings = [];

        $supported_versions = [
            '72', '73', '74',
            '80', '81', '82', '83', '84',
            '85', '86',
        ];

        foreach ($mainSuite->tests() as $suite) {
            $testClass = $suite->getName();
            if (!$tests = $suite->tests()) {
                continue;
            }
            // if (\in_array('class-polyfill', Test::getGroups($testClass), true)) {
            //     // TODO: check signatures for all polyfilled methods on PHP >= 8
            //     continue;
            // }
            $testedClass = new \ReflectionClass($testClass);
            if (preg_match('{^ \* @requires PHP (.*)}mi', $testedClass->getDocComment(), $m) && version_compare($m[1], \PHP_VERSION, '>')) {
                continue;
            }
            if (!preg_match('/^(.+)\\\\Tests(\\\\.*)Test$/', $testClass, $m)) {
                $mainSuite->addTest(TestListener::warning('Unknown naming convention for '.$testClass));
                continue;
            }
            if (!class_exists($m[1].$m[2])) {
                continue;
            }
            $testedClass = new \ReflectionClass($m[1].$m[2]);

            $bootstrap_files = [];
            foreach ($supported_versions as $i => $version) {
                if (0 === $i) { $version = ''; }

                $path = \dirname($testedClass->getFileName())."/bootstrap{$version}.php";

                if (file_exists($path)) {
                    $bootstrap_files[$version] = new \SplFileObject($path);
                }
            }

            $bootstrap = new \SplTempFileObject();
            $bootstrap_file = $bootstrap_files[''];
            while ($bootstrap_file->valid() && $line = $bootstrap_file->fgets()) {
                if (\preg_match('/if\s*\(\s*\\\PHP_VERSION_ID\s*>=\s*(\d+)\s*\)/', $line, $m) && \PHP_VERSION_ID >= $m[1]) {
                    $line = $bootstrap_file->fgets();

                    if (\preg_match('/return\s*;/', $line)) {
                        break;
                    }
                    
                    if (\preg_match('/return\s*require.*\/bootstrap(\d*)\.php.*;/', $line, $m)) {
                        $bootstrap_file = $bootstrap_files[$m[1]];
                        $bootstrap_file->fgets();
                        continue;
                    }
                }

                $bootstrap->fwrite($line);
            }

            $bootstrap->rewind();

            // var_dump($bootstrap->fpassthru()) && $bootstrap->rewind();

            $newWarnings = 0;
            $defLine = null;

            foreach (new \RegexIterator($bootstrap, '/define\(\'/') as $defLine) {
                preg_match("/define\('(?P<name>[^']++)'/", $defLine, $matches);
                if (\defined($matches['name'])) {
                    continue;
                }

                try {
                    eval($defLine);
                } catch (\PHPUnit\Framework\Exception $ex) {
                    $warnings[] = TestListener::warning($ex->getMessage());
                    ++$newWarnings;
                }
            }

            $bootstrap->rewind();

            foreach (new \RegexIterator($bootstrap, '/return p\\\\'.$testedClass->getShortName().'::/') as $defLine) {
                if (!preg_match('/^\s*function (?P<name>[^\(]++)(?P<signature>\(.*\)(?: ?: [^ ]++)?) \{ (?<return>return p\\\\'.$testedClass->getShortName().'::[^\(]++)(?P<args>\([^\n]*?\)); \}$/', $defLine, $f)) {
                    $warnings[] = TestListener::warning('Invalid line in '.$bootstrap->getPathname().': '.trim($defLine));
                    ++$newWarnings;
                    continue;
                }
                $testNamespace = substr($testClass, 0, strrpos($testClass, '\\'));
                if (\function_exists($testNamespace.'\\'.$f['name'])) {
                    continue;
                }

                try {
                    $r = new \ReflectionFunction($f['name']);
                    if ($r->isUserDefined()) {
                        throw new \ReflectionException();
                    }
                    if ('idn_to_ascii' === $f['name'] || 'idn_to_utf8' === $f['name']) {
                        $defLine = \sprintf('return PHP_VERSION_ID < 80000 && INTL_IDNA_VARIANT_2003 === $variant ? \\%s($domain, $options, $variant) : \\%1$s%s', $f['name'], $f['args']);
                    } elseif (false !== strpos($f['signature'], '&') && 'idn_to_ascii' !== $f['name'] && 'idn_to_utf8' !== $f['name']) {
                        $defLine = \sprintf('return \\%s%s', $f['name'], $f['args']);
                    } else {
                        $defLine = \sprintf("return \\call_user_func_array('%s', \\func_get_args())", $f['name']);
                    }
                } catch (\ReflectionException $e) {
                    $r = null;
                    $defLine = \sprintf("throw new \\%s('Internal function not found: %s')", SkippedTestError::class, $f['name']);
                }

                eval(<<<EOPHP
namespace {$testNamespace};

use Symfony\Polyfill\Util\TestListenerTrait;
use {$testedClass->getNamespaceName()} as p;

function {$f['name']}{$f['signature']}
{
    if ('{$testClass} with polyfills enabled' === TestListenerTrait::\$enabledPolyfills) {
        {$f['return']}{$f['args']};
    }

    {$defLine};
}
EOPHP
                );

                // if (\PHP_VERSION_ID >= 80000 && $r && false === strpos($bootstrap->getPath(), 'Php7') && false === strpos($bootstrap->getPath(), 'Php80')) {
                if (/* \PHP_VERSION_ID >= 80100 && */ $r) {
                    $originalSignature = ReflectionCaster::getSignature(ReflectionCaster::castFunctionAbstract($r, [], new Stub(), true));
                    $polyfillSignature = ReflectionCaster::castFunctionAbstract(new \ReflectionFunction($testNamespace.'\\'.$f['name']), [], new Stub(), true);
                    $polyfillSignature = ReflectionCaster::getSignature($polyfillSignature);

                    // if ('mb_get_info' === $r->name && false === strpos($originalSignature, '|null') && false !== strpos($polyfillSignature, '|null')) {
                    //     // Added to PHP 8.2.14/8.3.1
                    //     $originalSignature .= '|null';
                    // }

                    // if (str_ends_with($bootstrap->getPath(), 'bootstrap.php')) {
                    //     // mixed return type cannot be used before PHP 8
                    //     $originalSignature = str_replace(': mixed', '', $originalSignature);
                    // }

                    $map = [
                        '?' => '',
                        'array|string|null $string' => 'array|string $string',
                        'array|string|null $from_encoding = null' => 'array|string|null $from_encoding = null',
                        'array|string|null $from_encoding' => 'array|string $from_encoding',
                    ];

                    if (strtr($polyfillSignature, $map) !== str_replace('?', '', $originalSignature)) {
                        $warnings[] = TestListener::warning("Incompatible signature for PHP >= ".\PHP_MAJOR_VERSION.'.'.\PHP_MINOR_VERSION." in {$bootstrap->getPathname()}:\n- {$f['name']}$originalSignature\n+ {$f['name']}$polyfillSignature");
                    }
                }
            }
            if (!$newWarnings && null === $defLine) {
                $warnings[] = TestListener::warning('No polyfills found in bootstrap.php for '.$testClass);
            } else {
                $mainSuite->addTest(new TestListener($suite));
            }
        }
        foreach ($warnings as $w) {
            $mainSuite->addTest($w);
        }
    }

    public function addError($test, \Exception $e, $time)
    {
        if (false !== self::$enabledPolyfills) {
            $r = new \ReflectionProperty('Exception', 'message');
            \PHP_VERSION_ID < 80100 && $r->setAccessible(true);
            $r->setValue($e, 'Polyfills enabled, '.$r->getValue($e));
        }
    }
}
