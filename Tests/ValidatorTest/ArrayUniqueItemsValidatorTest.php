<?php

declare(strict_types=1);

/*
 * This file is part of the SwaggerResolverBundle package.
 *
 * (c) MarfaTech <https://marfa-tech.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Linkin\Bundle\SwaggerResolverBundle\Tests\ValidatorTest;

use Linkin\Bundle\SwaggerResolverBundle\Validator\ArrayUniqueItemsValidator;
use OpenApi\Annotations\Property;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class ArrayUniqueItemsValidatorTest extends KernelTestCase
{
    private const TYPE_ARRAY = 'array';
    private const COLLECTION_FORMAT_CSV = 'csv';
    private const COLLECTION_FORMAT_MULTI = 'multi';


    /**
     * @var ArrayUniqueItemsValidator
     */
    private $sut;

    protected function setUp(): void
    {
        $this->sut = new ArrayUniqueItemsValidator();
    }

    /**
     * @dataProvider supportsDataProvider
     */
    public function testSchemaBuilder(string $format, ?bool $hasUniqueItems, bool $expectedResult): void
    {
        $schema = $this->createSchemaDefinition($format, $hasUniqueItems);
        $isSupported = $this->sut->supports($schema);

        self::assertSame($isSupported, $expectedResult);
    }

    public function supportsDataProvider(): array
    {
        return [
            'Fail with unsupported format' => [
                'type' => '_invalid_format_',
                'hasUniqueItems' => true,
                'expectedResult' => false,
            ],
            'Fail when unique items was not set' => [
                'type' => self::TYPE_ARRAY,
                'hasUniqueItems' => false,
                'expectedResult' => false,
            ],
            'Success with right format' => [
                'type' => self::TYPE_ARRAY,
                'hasUniqueItems' => true,
                'expectedResult' => true,
            ],
        ];
    }

    /**
     * @dataProvider failToPassValidationDataProvider
     */
    public function testFailToPassValidation(?string $collectionFormat, $value): void
    {
        $schemaProperty = $this->createSchemaDefinitionFailToPassValidation($collectionFormat);

        $this->expectException(InvalidOptionsException::class);

        $this->sut->validate($schemaProperty, $value);
    }

    public function failToPassValidationDataProvider(): array
    {
        return [
            'Fail when null collectionFormat and received array as string' => [
                'collectionFormat' => null,
                'value' => 'monday,tuesday,wednesday',
            ],
            'Fail when set collectionFormat and received plain array' => [
                'collectionFormat' => self::COLLECTION_FORMAT_CSV,
                'value' => ['monday', 'tuesday', 'wednesday'],
            ],
            'Fail when unexpected delimiter' => [
                'collectionFormat' => '__delimiter__',
                'value' => ['monday', 'tuesday',  'wednesday'],
            ],
            'Fail when not unique values in array' => [
                'collectionFormat' => self::COLLECTION_FORMAT_MULTI,
                'value' => 'days=monday&days=tuesday&days=wednesday&days=monday',
            ],
        ];
    }

    /**
     * @dataProvider canPassValidationDataProvider
     */
    public function testCanPassValidation(?string $collectionFormat, $value): void
    {
        $schemaProperty = $this->createSchemaDefinitionFailToPassValidation($collectionFormat);

        $this->sut->validate($schemaProperty, $value);
        self::assertTrue(true);
    }

    public function canPassValidationDataProvider(): array
    {
        return [
            'Pass when null value' => [
                'collectionFormat' => self::COLLECTION_FORMAT_CSV,
                'value' => null,
            ],
            'Pass when null collectionFormat and received plain array' => [
                'collectionFormat' => null,
                'value' => ['monday', 'tuesday',  'wednesday'],
            ],
            'Pass when CSV collectionFormat' => [
                'collectionFormat' => self::COLLECTION_FORMAT_CSV,
                'value' => 'monday,tuesday,wednesday',
            ],
            'Pass when valid multi format with unique items' => [
                'collectionFormat' => self::COLLECTION_FORMAT_MULTI,
                'value' => 'days=monday&days=tuesday&days=wednesday',
            ],
        ];
    }

    private function createSchemaDefinition(string $format, ?bool $hasUniqueItems): Property
    {
        return new Property(
            [
                'type' => $format,
                'uniqueItems' => $hasUniqueItems,
            ]
        );
    }

    private function createSchemaDefinitionFailToPassValidation(?string $collectionFormat): Property
    {
        return new Property(
            [
                'type' => self::TYPE_ARRAY,
                'uniqueItems' => true,
                'collectionFormat' => $collectionFormat,
            ]
        );
    }
}
