<?php

declare(strict_types=1);

/**
 * Runner de pruebas sin Composer: define helpers de aserción, carga los casos de tests/Unit
 * y los ejecuta, mostrando PASS/FAIL y un resumen. Termina con código de salida 1 si algo falla.
 */
require __DIR__ . '/../src/autoload.php';
require __DIR__ . '/Support/Fakes.php';

$tests = [];

/** Registra un caso de prueba para ejecutarlo al final. */
function test(string $name, callable $fn): void
{
    global $tests;

    $tests[] = [$name, $fn];
}

/** Aborta la prueba actual con un mensaje. */
function fail(string $message): never
{
    throw new RuntimeException($message);
}

/** Verifica que el valor sea exactamente `true`. */
function assertTrue(mixed $actual, string $message = ''): void
{
    if ($actual !== true) {
        fail(trim($message . ' Se esperaba true, se obtuvo ' . var_export($actual, true) . '.'));
    }
}

/** Verifica igualdad estricta (tipo y valor). */
function assertSame(mixed $expected, mixed $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        fail(trim($message . sprintf(
            ' Se esperaba %s, se obtuvo %s.',
            var_export($expected, true),
            var_export($actual, true),
        )));
    }
}

/** Verifica que el valor sea null. */
function assertNull(mixed $actual, string $message = ''): void
{
    assertSame(null, $actual, $message);
}

/** Verifica la cantidad de elementos de un array. */
function assertCount(int $expected, array $actual, string $message = ''): void
{
    assertSame($expected, count($actual), $message);
}

/** Verifica que la ejecución lance una excepción del tipo indicado. */
function assertThrows(string $exceptionClass, callable $fn, string $message = ''): void
{
    try {
        $fn();
    } catch (Throwable $exception) {
        if ($exception instanceof $exceptionClass) {
            return;
        }

        fail(trim($message . sprintf(' Se esperaba %s, se obtuvo %s.', $exceptionClass, $exception::class)));
    }

    fail(trim($message . sprintf(' No se lanzó %s.', $exceptionClass)));
}

// Cada archivo Unit/*Test.php registra sus casos usando test().
foreach (glob(__DIR__ . '/Unit/*Test.php') as $file) {
    require $file;
}

$passed = 0;
$failed = 0;

foreach ($tests as [$name, $fn]) {
    try {
        $fn();
        $passed++;
        echo "PASS  {$name}\n";
    } catch (Throwable $exception) {
        $failed++;
        echo "FAIL  {$name}\n      {$exception->getMessage()}\n";
    }
}

echo "\n{$passed} passed, {$failed} failed\n";

exit($failed === 0 ? 0 : 1);
