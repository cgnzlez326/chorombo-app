<?php

declare(strict_types=1);

use App\Core\Validator;
use App\Exceptions\DuplicateException;
use App\Exceptions\ValidationException;
use App\Models\Documento;
use App\Services\DocumentoService;
use Tests\Support\FakeTipoDocumentoRepository;
use Tests\Support\FakeTransactionManager;
use Tests\Support\InMemoryDocumentoRepository;
use Tests\Support\InMemoryFileStorage;

function makeService(
    ?InMemoryDocumentoRepository $repository = null,
    ?InMemoryFileStorage $storage = null,
): DocumentoService {
    return new DocumentoService(
        $repository ?? new InMemoryDocumentoRepository(),
        new FakeTipoDocumentoRepository(),
        new Validator(),
        $storage ?? new InMemoryFileStorage(),
        new FakeTransactionManager(),
    );
}

/** @return array<string, string> */
function validData(array $overrides = []): array
{
    return array_merge(
        ['titulo' => 'Acta', 'tipo_documento_id' => '1', 'fecha' => '2026-09-10'],
        $overrides,
    );
}

test('Crea un documento válido', function (): void {
    $service = makeService();

    $documento = $service->create(validData(), null);

    assertSame(1, $documento->id);
    assertSame('Acta', $documento->titulo);
});

test('Rechaza creación sin título', function (): void {
    $service = makeService();

    assertThrows(ValidationException::class, static fn () => $service->create(validData(['titulo' => '']), null));
});

test('Rechaza tipo de documento inexistente', function (): void {
    $service = makeService();

    assertThrows(ValidationException::class, static fn () => $service->create(validData(['tipo_documento_id' => '999']), null));
});

test('Rechaza archivo duplicado y limpia el almacenado', function (): void {
    $repository = new InMemoryDocumentoRepository();
    $storage = new InMemoryFileStorage();
    $service = makeService($repository, $storage);

    $repository->create(new Documento(
        titulo: 'Existente',
        tipoDocumentoId: 1,
        fecha: '2026-09-01',
        archivo: 'old.pdf',
        archivoHash: 'hash_x',
    ));

    assertThrows(
        DuplicateException::class,
        static fn () => $service->create(validData(), ['name' => 'a.pdf', 'hash' => 'hash_x']),
    );

    assertCount(0, $storage->files);
    assertCount(1, $storage->deleted);
});

test('Elimina el archivo si falla la persistencia', function (): void {
    $repository = new InMemoryDocumentoRepository();
    $storage = new InMemoryFileStorage();
    $repository->failOnCreate = new RuntimeException('fallo simulado');
    $service = makeService($repository, $storage);

    assertThrows(
        RuntimeException::class,
        static fn () => $service->create(validData(), ['name' => 'a.pdf', 'hash' => 'hash_y']),
    );

    assertCount(0, $storage->files);
});

test('Actualiza reemplazando el archivo anterior', function (): void {
    $repository = new InMemoryDocumentoRepository();
    $storage = new InMemoryFileStorage();
    $service = makeService($repository, $storage);

    $documento = $service->create(validData(), ['name' => 'a.pdf', 'hash' => 'h1']);
    $anterior = $documento->archivo;

    $actualizado = $service->update(
        (int) $documento->id,
        validData(['titulo' => 'Acta v2']),
        ['name' => 'b.pdf', 'hash' => 'h2'],
    );

    assertSame('Acta v2', $actualizado->titulo);
    assertTrue(!$storage->exists((string) $anterior));
    assertTrue($storage->exists((string) $actualizado->archivo));
});

test('Elimina el documento y su archivo', function (): void {
    $repository = new InMemoryDocumentoRepository();
    $storage = new InMemoryFileStorage();
    $service = makeService($repository, $storage);

    $documento = $service->create(validData(), ['name' => 'a.pdf', 'hash' => 'h1']);
    $service->delete((int) $documento->id);

    assertNull($repository->find((int) $documento->id));
    assertCount(0, $storage->files);
});

test('Lista paginado con total real', function (): void {
    $service = makeService();
    $service->create(validData(['titulo' => 'Uno']), null);
    $service->create(validData(['titulo' => 'Dos']), null);
    $service->create(validData(['titulo' => 'Tres']), null);

    $result = $service->list(null, 1, 2);

    assertSame(3, $result['total']);
    assertCount(2, $result['items']);
    assertSame(1, $result['page']);
    assertSame(2, $result['per_page']);
});
