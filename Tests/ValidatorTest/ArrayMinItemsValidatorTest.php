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

use Linkin\Bundle\SwaggerResolverBundle\Validator\ArrayMinItemsValidator;
use OpenApi\Annotations\Property;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class ArrayMinItemsValidatorTest extends KernelTestCase
{
    private const TYPE_ARRAY = 'array';
    private const COLLECTION_FORMAT_CSV = 'csv';
    private const COLLECTION_FORMAT_MULTI = 'multi';

    private ArrayMinItemsValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ArrayMinItemsValidator();
    }

    /**
     * @dataProvider supportsDataProvider
     */
    public function testSupport(string $format, ?int $minItems, bool $expectedResult): void
    {
        $schema = $this->createSchemaDefinition($format, $minItems);
        $isSupported = $this->validator->supports($schema);

        self::assertSame($isSupported, $expectedResult);
    }

    public function supportsDataProvider(): array
    {
        return [
            'Fail with unsupported format' => [
                'type' => '_invalid_format_',
                'minItems' => 3,
                'expectedResult' => false,
            ],
            'Success when maxItems was not set' => [
                'type' => self::TYPE_ARRAY,
                'minItems' => null,
                'expectedResult' => true,
            ],
            'Success with right format' => [
                'type' => self::TYPE_ARRAY,
                'minItems' => 3,
                'expectedResult' => true,
            ],
        ];
    }

    /**
     * @dataProvider failToPassValidationDataProvider
     */
    public function testFailToPassValidation(?string $collectionFormat, int $minItems, $value): void
    {
        $schemaProperty = $this->createSchemaDefinitionFailToPassValidation($collectionFormat, $minItems);

        $this->expectException(InvalidOptionsException::class);

        $this->validator->validate($schemaProperty, $value);
    }

    public function failToPassValidationDataProvider(): array
    {
        return [
            'Fail when null collectionFormat and received array as string' => [
                'collectionFormat' => null,
                'minItems' => 3,
                'value' => 'monday,tuesday,wednesday',
            ],
            'Fail when set collectionFormat and received plain array' => [
                'collectionFormat' => self::COLLECTION_FORMAT_CSV,
                'minItems' => 3,
                'value' => ['monday', 'tuesday', 'wednesday'],
            ],
            'Fail when unexpected delimiter' => [
                'collectionFormat' => '__delimiter__',
                'minItems' => 3,
                'value' => ['monday', 'tuesday', 'wednesday'],
            ],
            'Fail when items lower than maximal count' => [
                'collectionFormat' => self::COLLECTION_FORMAT_MULTI,
                'minItems' => 3,
                'value' => 'days=monday',
            ],
        ];
    }

    /**
     * @dataProvider canPassValidationDataProvider
     */
    public function testCanPassValidation(?string $collectionFormat, int $minItems, mixed $value): void
    {
        $schemaProperty = $this->createSchemaDefinitionFailToPassValidation($collectionFormat, $minItems);

        $this->validator->validate($schemaProperty, $value);
        self::assertTrue(true);
    }

    public function canPassValidationDataProvider(): array
    {
        return [
            'Pass when CSV collectionFormat' => [
                'collectionFormat' => self::COLLECTION_FORMAT_CSV,
                'minItems' => 3,
                'value' => 'monday,tuesday,wednesday',
            ],
            'Pass when valid multi format and equal to maximal count' => [
                'collectionFormat' => self::COLLECTION_FORMAT_MULTI,
                'minItems' => 3,
                'value' => 'days=monday&days=tuesday&days=wednesday',
            ],
        ];
    }

    private function createSchemaDefinition(string $format, ?int $minItems): Property
    {
        return new Property(
            [
                'type' => $format,
                'minItems' => $minItems,
            ]
        );
    }

    private function createSchemaDefinitionFailToPassValidation(?string $collectionFormat, int $minItems): Property
    {
        return new Property(
            [
                'property' => 'example',
                'type' => self::TYPE_ARRAY,
                'minItems' => $minItems,
                'collectionFormat' => $collectionFormat,
            ]
        );
    }
}
