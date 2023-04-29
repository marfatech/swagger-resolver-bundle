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

use Linkin\Bundle\SwaggerResolverBundle\Validator\FormatTimestampValidator;
use OpenApi\Annotations\Property;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class FormatTimestampValidatorTest extends KernelTestCase
{
    private const FORMAT_TIMESTAMP = 'timestamp';

    /**
     * @var FormatTimestampValidator
     */
    private $sut;

    protected function setUp(): void
    {
        $this->sut = new FormatTimestampValidator();
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
                'type' => self::FORMAT_TIMESTAMP,
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
            'Fail when timestamp is not numeric' => ['2020-10-10 10:00:00'],
            'Fail when true value' => [true],
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
            'Pass when true value' => [false],
            'Pass when empty string value' => [''],
            'Pass when empty zero value' => ['0'],
            'Pass when value with correct timestamp' => ['1629620000'],
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
                'format' => self::FORMAT_TIMESTAMP,
            ]
        );
    }
}
