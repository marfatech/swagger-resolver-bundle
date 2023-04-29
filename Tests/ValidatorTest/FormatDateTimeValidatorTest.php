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

use Linkin\Bundle\SwaggerResolverBundle\Validator\FormatDateTimeValidator;
use OpenApi\Annotations\Property;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class FormatDateTimeValidatorTest extends KernelTestCase
{
    private const FORMAT_DATETIME = 'datetime';

    /**
     * @var FormatDateTimeValidator
     */
    private $sut;

    protected function setUp(): void
    {
        $this->sut = new FormatDateTimeValidator();
    }

    /**
     * @dataProvider supportsDataProvider
     */
    public function testSchemaBuilder(string $format, bool $expectedResult): void
    {
        $schema = $this->createSchemaDefinition($format);
        $isSupported = $this->sut->supports($schema);

        self::assertSame($isSupported, $expectedResult);
    }

    public function supportsDataProvider(): array
    {
        return [
            'Fail with unsupported format' => [
                'format' => '_invalid_format_',
                'expectedResult' => false,
            ],
            'Success with right format' => [
                'type' => self::FORMAT_DATETIME,
                'expectedResult' => true,
            ],
        ];
    }

    /**
     * @dataProvider failToPassValidationDataProvider
     */
    public function testFailToPassValidation($value): void
    {
        $schemaProperty = $this->createSchemaDefinitionFailToPassValidation();
        $this->expectException(InvalidOptionsException::class);
        $this->sut->validate($schemaProperty, $value);
    }

    public function failToPassValidationDataProvider(): array
    {
        return [
            'Fail when true value' => [true],
            'Fail when incorrect month pattern - number' => ['2022-571-01 10:00:01'],
            'Fail when incorrect day pattern - number' => ['2022-07-011 10:00:01'],
            'Fail when incorrect year pattern - number' => ['20220-07-011 10:00:01'],
            'Fail when incorrect hour pattern - number' => ['2022-07-01 1011:00:01'],
            'Fail when incorrect minutes pattern - number' => ['2022-07-01 10:0012:01'],
            'Fail when incorrect second pattern - number' => ['2022-07-01 10:00:90'],
        ];
    }

    /**
     * @dataProvider canPassValidationDataProvider
     */
    public function testCanPassValidation($value): void
    {
        $schemaProperty = $this->createSchemaDefinitionFailToPassValidation();

        $this->sut->validate($schemaProperty, $value);
        self::assertTrue(true);
    }

    public function canPassValidationDataProvider(): array
    {
        return [
            'Pass when null value' => [null],
            'Pass when empty string value' => [''],
            'Pass when value with UTC' => ['1985-06-04T23:20:50Z'],
            'Pass when value with offset' => ['1955-06-04T16:39:57-08:00'],
            'Pass when value with UTC and leap second' => ['1990-06-04T23:59:60Z'],
            'Pass when value with offset and leap second' => ['1990-06-04T15:59:60-08:00'],
            'Pass when value with Netherlands time' => ['1930-06-04T12:00:27+03:00'],
        ];
    }

    private function createSchemaDefinition(string $format): Property
    {
        return new Property(
            [
                'format' => $format,
            ]
        );
    }

    private function createSchemaDefinitionFailToPassValidation(): Property
    {
        return new Property(
            [
                'format' => self::FORMAT_DATETIME,
            ]
        );
    }
}
