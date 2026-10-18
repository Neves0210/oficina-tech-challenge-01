<?php

namespace Tests\Unit;

use App\Domain\Customers\DocumentNumber;
use App\Domain\Vehicles\Plate;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RegistrationValuesTest extends TestCase
{
    #[DataProvider('validDocuments')]
    public function test_document_is_normalized_and_validated(string $input, string $value, string $type): void
    {
        $document = new DocumentNumber($input);
        $this->assertSame($value, $document->value);
        $this->assertSame($type, $document->type);
    }

    public static function validDocuments(): array
    {
        return [
            ['529.982.247-25', '52998224725', 'CPF'],
            ['11.222.333/0001-81', '11222333000181', 'CNPJ'],
            ['12.abc.345/01de-35', '12ABC34501DE35', 'CNPJ'],
        ];
    }

    #[DataProvider('invalidDocuments')]
    public function test_invalid_document_is_rejected(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DocumentNumber($value);
    }

    public static function invalidDocuments(): array
    {
        return array_map(fn ($value) => [$value], [
            '', '11111111111', '00000000000000', '52998224724',
            '11222333000180', '12ABC34501DE34', '52998224725x', 'ABC',
        ]);
    }

    #[DataProvider('plates')]
    public function test_plate_formats(string $input, string $expected): void
    {
        $this->assertSame($expected, (new Plate($input))->value);
    }

    public static function plates(): array
    {
        return [['abc-1234', 'ABC1234'], ['bra1a23', 'BRA1A23']];
    }

    public function test_invalid_plate_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Plate('AB12345');
    }
}
