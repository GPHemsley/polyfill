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

    public static function get_reflection_info($function_name) {
        try {
            $reflection = new \ReflectionFunction($function_name);
        } catch (\ReflectionException $e) {
            var_dump($e->getMessage());
            return null;
        }

        $reflection_info = [
            'fileName' => $reflection->getFileName(),
            'startLine' => $reflection->getStartLine(),
            'endLine' => $reflection->getEndLine(),
            'name' => $reflection->getName(),
            'shortName' => $reflection->getShortName(),
            'isVariadic' => $reflection->isVariadic(),
            'parameters' => [],
            'returnsReference' => $reflection->returnsReference(),
        ];

        if (\method_exists($reflection, 'hasTentativeReturnType') && \method_exists($reflection, 'getTentativeReturnType') && $reflection->hasTentativeReturnType()) {
            $tentativeReturnType = $reflection->getTentativeReturnType();

            $reflection_info['tentativeReturnType'] = (null !== $tentativeReturnType) ? (string) $tentativeReturnType : null;
        }

        if ($reflection->hasReturnType()) {
            $returnType = $reflection->getReturnType();

            $reflection_info['returnType'] = (null !== $returnType) ? (string) $returnType : null;
        }

        foreach ($reflection->getParameters() as $parameter) {
            $parameter_info = [
                'name' => $parameter->getName(),
                'isOptional' => $parameter->isOptional(),
                'isPassedByReference' => $parameter->isPassedByReference(),
                'isVariadic' => $parameter->isVariadic(),
            ];

            if ($parameter->hasType()) {
                $parameterType = $parameter->getType();

                $parameter_info['type'] = (null !== $parameterType) ? (string) $parameterType : null;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $parameter_info['default'] = [
                    'value' => $parameter->getDefaultValue(),
                ];

                if ($parameter->isDefaultValueConstant()) {
                    $parameter_info['default']['constant'] = $parameter->getDefaultValueConstantName();
                }
            }

            $reflection_info['parameters'][$parameter->getPosition()] = $parameter_info;
        }

        return $reflection_info;
    }

    public function startTestSuite($mainSuite)
    {
        $warnings = [];

        foreach ($mainSuite->tests() as $suite) {
            $testClass = $suite->getName();
            if (!$tests = $suite->tests()) {
                continue;
            }
            if (\in_array('class-polyfill', Test::getGroups($testClass), true)) {
                // TODO: check signatures for all polyfilled methods on PHP >= 8
                continue;
            }
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
            $bootstrap = \dirname($testedClass->getFileName()).'/bootstrap';
            if (\PHP_VERSION_ID >= 80500 && file_exists($bootstrap.'85.php')) {
                $bootstrap .= '85';
            } elseif (\PHP_VERSION_ID >= 80200 && file_exists($bootstrap.'82.php')) {
                $bootstrap .= '82';
            } elseif (\PHP_VERSION_ID >= 80100 && file_exists($bootstrap.'81.php')) {
                $bootstrap .= '81';
            } elseif (\PHP_VERSION_ID >= 80000 && file_exists($bootstrap.'80.php')) {
                $bootstrap .= '80';
            } elseif (\PHP_VERSION_ID < 80000 && file_exists($bootstrap.'72.php')) {
                $bootstrap .= '72';
            }
            $bootstrap = new \SplFileObject($bootstrap.'.php');
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
                    if (!$r->isUserDefined()) {
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
                if ($r) {
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

                    // $map = [
                    //     '?' => '',
                    //     'array|string|null $string' => 'array|string $string',
                    //     'array|string|null $from_encoding = null' => 'array|string|null $from_encoding = null',
                    //     'array|string|null $from_encoding' => 'array|string $from_encoding',
                    // ];

                    // if (strtr($polyfillSignature, $map) !== str_replace('?', '', $originalSignature)) {
                    if ($polyfillSignature !== $originalSignature) {
                        ob_start();
                        print_r(self::get_reflection_info($f['name']));
                        $reflection_info = ob_get_contents();
                        ob_end_clean();
                        $warnings[] = TestListener::warning("Incompatible signature for PHP version in {$bootstrap->getPathname()}:\n- {$f['name']}$originalSignature\n+ {$f['name']}$polyfillSignature\n" . $reflection_info);
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
