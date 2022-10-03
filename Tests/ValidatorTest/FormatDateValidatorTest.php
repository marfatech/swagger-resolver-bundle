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

use Linkin\Bundle\SwaggerResolverBundle\Validator\FormatDateValidator;
use OpenApi\Annotations\Property;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class FormatDateValidatorTest extends KernelTestCase
{
    private const FORMAT_DATE = 'date';

    /**
     * @var FormatDateValidator
     */
    private $sut;

    protected function setUp(): void
    {
        $this->sut = new FormatDateValidator();
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
                'type' => self::FORMAT_DATE,
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
            'Fail when value with incorrect datetime' => ['100'],
            'Fail when value with incorrect datetime - year' => ['01-01'],
            'Fail when value with incorrect datetime - month' => ['2019-13-01'],
            'Fail when value with incorrect datetime - day' => ['2019-01-32'],
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
            'Pass when value with correct date pattern' => ['2022-01-01'],
            'Pass when value with correct date pattern - february' => ['2029-02-28'],
            'Pass when value with correct date pattern - february+' => ['2020-02-29'],
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
                'format' => self::FORMAT_DATE,
            ]
        );
    }
}
