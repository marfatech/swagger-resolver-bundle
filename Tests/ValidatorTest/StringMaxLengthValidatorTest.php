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

use Linkin\Bundle\SwaggerResolverBundle\Validator\StringMaxLengthValidator;
use OpenApi\Annotations\Property;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use function str_repeat;

class StringMaxLengthValidatorTest extends KernelTestCase
{
    private const TYPE = 'string';

    /**
     * @var StringMaxLengthValidator
     */
    private $sut;

    protected function setUp(): void
    {
        $this->sut = new StringMaxLengthValidator();
    }

    /**
     * @dataProvider supportsDataProvider
     */
    public function testSchemaBuilder(string $type, ?int $maxLength, bool $expectedResult): void
    {
        $schema = $this->createSchemaDefinition($type, $maxLength);
        $isSupported = $this->sut->supports($schema);

        self::assertSame($isSupported, $expectedResult);
    }

    public function supportsDataProvider(): array
    {
        return [
            'Fail with unsupported type' => [
                'type' => '_invalid_type_',
                'maxLength' => 100,
                'expectedResult' => false,
            ],
            'Success' => [
                'type' => self::TYPE,
                'maxLength' => 100,
                'expectedResult' => true,
            ],
        ];
    }

    /**
     * @dataProvider failToPassValidationDataProvider
     */
    public function testFailToPassValidation(int $maxLength, $value): void
    {
        $schemaProperty = $this->createSchemaDefinitionFailToPassValidation($maxLength);

        $this->expectException(InvalidOptionsException::class);
        $this->sut->validate($schemaProperty, $value);
    }

    public function failToPassValidationDataProvider(): array
    {
        return [
            'Fail with string equal to allowed' => [
                'maxLength' => 3,
                'value' => '13678',
            ],
            'Fail with latin string greater than allowed' => [
                'maxLength' => 10,
                'value' => str_repeat('w', 11),
            ],
            'Fail with cyrillic string greater than allowed' => [
                'maxLength' => 10,
                'value' => str_repeat('я', 11),
            ],
        ];
    }

    /**
     * @dataProvider canPassValidationDataProvider
     */
    public function testCanPassValidation(int $maxLength, $value): void
    {
        $schemaProperty = $this->createSchemaDefinitionFailToPassValidation($maxLength);

        $this->sut->validate($schemaProperty, $value);
        self::assertTrue(true);
    }

    public function canPassValidationDataProvider(): array
    {
        return [
            'Pass with null value' => [
                'maxLength' => 1,
                'value' => null,
            ],
            'Pass with float equal to allowed' => [
                'maxLength' => 4,
                'value' => '1.11',
            ],
            'Pass validation with latin string equal to allowed' => [
                'maxLength' => 10,
                'value' => str_repeat('w', 10),
            ],
            'Pass validation with cyrillic string equal to allowed' => [
                'maxLength' => 10,
                'value' => str_repeat('я', 10),
            ],
            'Pass validation with latin string' => [
                'maxLength' => 10,
                'value' => str_repeat('w', 9),
            ],
            'Pass validation with cyrillic string' => [
                'maxLength' => 10,
                'value' => str_repeat('я', 9),
            ],
        ];
    }

    private function createSchemaDefinition(string $type, ?int $maxLength): Property
    {
        return new Property(
            [
                'type' => $type,
                'maxLength' => $maxLength,
            ]
        );
    }

    private function createSchemaDefinitionFailToPassValidation($maxLength): Property
    {
        return new Property(
            [
                'type' => self::TYPE,
                'maxLength' => $maxLength,
            ]
        );
    }
}
