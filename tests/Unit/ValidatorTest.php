<?php

declare(strict_types=1);

use App\Core\Validator;
use App\Exceptions\ValidationException;

test('Validator acepta datos válidos y normaliza', function (): void {
    $validator = new Validator();

    $clean = $validator->validate(
        ['titulo' => '  Acta  ', 'tipo_documento_id' => '4', 'fecha' => '2026-09-10'],
        [
            'titulo'            => ['required', 'string', 'max:255'],
            'tipo_documento_id' => ['required', 'integer'],
            'fecha'             => ['required', 'date'],
        ],
    );

    assertSame('Acta', $clean['titulo']);
    assertSame(4, $clean['tipo_documento_id']);
    assertSame('2026-09-10', $clean['fecha']);
});

test('Validator exige campos obligatorios', function (): void {
    $validator = new Validator();

    assertThrows(ValidationException::class, static function () use ($validator): void {
        $validator->validate([], ['titulo' => ['required']]);
    });
});

test('Validator rechaza fecha inválida', function (): void {
    $validator = new Validator();

    assertThrows(ValidationException::class, static function () use ($validator): void {
        $validator->validate(['fecha' => '10-09-2026'], ['fecha' => ['required', 'date']]);
    });
});

test('Validator respeta el máximo de caracteres', function (): void {
    $validator = new Validator();

    assertThrows(ValidationException::class, static function () use ($validator): void {
        $validator->validate(['titulo' => str_repeat('a', 6)], ['titulo' => ['required', 'string', 'max:5']]);
    });
});
