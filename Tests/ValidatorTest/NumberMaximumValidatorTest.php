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

use Linkin\Bundle\SwaggerResolverBundle\Validator\NumberMaximumValidator;
use OpenApi\Annotations\Property;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class NumberMaximumValidatorTest extends KernelTestCase
{
    private const TYPE_NUMBER = 'number';
    private const TYPE_INT = 'integer';

    /**
     * @var NumberMaximumValidator
     */
    private $sut;

    protected function setUp(): void
    {
        $this->sut = new NumberMaximumValidator();
    }

    /**
     * @dataProvider supportsDataProvider
     */
    public function testSchemaBuilder(string $type, $maximum, bool $expectedResult): void
    {
        $schema = $this->createSchemaDefinition($type, $maximum);
        $isSupported = $this->sut->supports($schema);

        self::assertSame($isSupported, $expectedResult);
    }

    public function supportsDataProvider(): array
    {
        return [
            'Fail with unsupported type' => [
                'type' => '_invalid_type_',
                'maximum' => 100,
                'expectedResult' => false,
            ],
            'Success with int' => [
                'type' => self::TYPE_INT,
                'maximum' => 100,
                'expectedResult' => true,
            ],
            'Success with float' => [
                'type' => self::TYPE_NUMBER,
                'maximum' => 10.99,
                'expectedResult' => true,
            ],
        ];
    }

    /**
     * @dataProvider failToPassValidationDataProvider
     */
    public function testFailToPassValidation(bool $isExclusiveMaximum, $maximum, $value): void
    {
        $schemaProperty = $this->createSchemaDefinitionFailToPassValidation($isExclusiveMaximum, $maximum);

        $this->expectException(InvalidOptionsException::class);
        $this->sut->validate($schemaProperty, $value);
    }

    public function failToPassValidationDataProvider(): array
    {
        return [
            'Fail with maximal int value and exclusive mode' => [
                'isExclusiveMaximum' => true,
                'maximum' => 10,
                'value' => 10,
            ],
            'Fail with int more than maximal value' => [
                'isExclusiveMaximum' => false,
                'maximum' => 10,
                'value' => 11,
            ],
            'Fail with negative maximal int value' => [
                'isExclusiveMaximum' => false,
                'maximum' => -10,
                'value' => -9,
            ],
            'Fail with int value as string' => [
                'isExclusiveMaximum' => false,
                'maximum' => -10,
                'value' => '-9',
            ],
            'Fail with maximal float value and exclusive mode' => [
                'isExclusiveMaximum' => true,
                'maximum' => 10.01,
                'value' => 10.01,
            ],
            'Fail with float more than maximal value' => [
                'isExclusiveMaximum' => false,
                'maximum' => 10.1,
                'value' => 10.1001,
            ],
            'Fail with negative maximal float value' => [
                'isExclusiveMaximum' => false,
                'maximum' => -10.1,
                'value' => -10.09,
            ],
            'Fail with float value as string' => [
                'isExclusiveMaximum' => false,
                'maximum' => -10.1,
                'value' => '-10.09',
            ],
        ];
    }

    /**
     * @dataProvider canPassValidationDataProvider
     */
    public function testCanPassValidation(bool $isExclusiveMaximum, $maximum, $value): void
    {
        $schemaProperty = $this->createSchemaDefinitionFailToPassValidation($isExclusiveMaximum, $maximum);

        $this->sut->validate($schemaProperty, $value);
        self::assertTrue(true);
    }

    public function canPassValidationDataProvider(): array
    {
        return [
            'Pass validation null value' => [
                'isExclusiveMaximum' => true,
                'maximum' => 10,
                'value' => null,
            ],
            'Pass validation with lower than maximal int value and exclusive mode' => [
                'isExclusiveMaximum' => true,
                'maximum' => 10,
                'value' => 9,
            ],
            'Pass validation with equal to maximal int value' => [
                'isExclusiveMaximum' => false,
                'maximum' => 10,
                'value' => 10,
            ],
            'Pass validation with negative maximal int value and exclusive mode' => [
                'isExclusiveMaximum' => true,
                'maximum' => -10,
                'value' => -11,
            ],
            'Pass validation with negative maximal int value' => [
                'isExclusiveMaximum' => false,
                'maximum' => -10,
                'value' => -10,
            ],
            'Pass validation with int value as string' => [
                'isExclusiveMaximum' => true,
                'maximum' => 0,
                'value' => '-10',
            ],
            'Pass validation with lower than maximal float value and exclusive mode' => [
                'isExclusiveMaximum' => true,
                'maximum' => 10.002,
                'value' => 10.001,
            ],
            'Pass validation with equal to maximal float value' => [
                'isExclusiveMaximum' => false,
                'maximum' => 10.002,
                'value' => 10.002,
            ],
            'Pass validation with negative maximal float value and exclusive mode' => [
                'isExclusiveMaximum' => true,
                'maximum' => -1.0001,
                'value' => -1.0002,
            ],
            'Pass validation with negative maximal float value' => [
                'isExclusiveMaximum' => false,
                'maximum' => -1.0002,
                'value' => -1.0002,
            ],
            'Pass validation with float value as string' => [
                'isExclusiveMaximum' => true,
                'maximum' => 0,
                'value' => '-0.001',
            ],
        ];
    }

    private function createSchemaDefinition(string $type, $maximum): Property
    {
        return new Property(
            [
                'type' => $type,
                'maximum' => $maximum,
            ]
        );
    }

    private function createSchemaDefinitionFailToPassValidation(bool $isExclusiveMaximum, $maximum): Property
    {
        return new Property(
            [
                'type' => self::TYPE_INT,
                'maximum' => $maximum,
                'exclusiveMaximum' => $isExclusiveMaximum,
            ]
        );
    }
}
