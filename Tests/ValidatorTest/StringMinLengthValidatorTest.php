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

use Linkin\Bundle\SwaggerResolverBundle\Validator\StringMinLengthValidator;
use OpenApi\Annotations\Property;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

use function str_repeat;

class StringMinLengthValidatorTest extends KernelTestCase
{
    private const TYPE = 'string';

    /**
     * @var StringMinLengthValidator
     */
    private $sut;

    protected function setUp(): void
    {
        $this->sut = new StringMinLengthValidator();
    }

    /**
     * @dataProvider supportsDataProvider
     */
    public function testSchemaBuilder(string $type, ?int $minLength, bool $expectedResult): void
    {
        $schema = $this->createSchemaDefinition($type, $minLength);
        $isSupported = $this->sut->supports($schema);

        self::assertSame($isSupported, $expectedResult);
    }

    public function supportsDataProvider(): array
    {
        return [
            'Fail with unsupported type' => [
                'type' => '_invalid_type_',
                'minLength' => 90,
                'expectedResult' => false,
            ],
            'Success' => [
                'type' => self::TYPE,
                'minLength' => 90,
                'expectedResult' => true,
            ],
        ];
    }

    /**
     * @dataProvider failToPassValidationDataProvider
     */
    public function testFailToPassValidation(int $minLength, $value): void
    {
        $schemaProperty = $this->createSchemaDefinitionFailToPassValidation($minLength);

        $this->expectException(InvalidOptionsException::class);
        $this->sut->validate($schemaProperty, $value);
    }

    public function failToPassValidationDataProvider(): array
    {
        return [
            'F ail with latin string lower than allowed' => [
                'minLength' => 5,
                'value' => str_repeat('w', 4),
            ],
            'Fail with cyrillic string lower than allowed' => [
                'minLength' => 5,
                'value' => str_repeat('я', 4),
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
            'Pass validation with null' => [
                'maxLength' => 1,
                'value' => null,
            ],
            'Pass with string equal to allowed' => [
                'maxLength' => 3,
                'value' => '110',
            ],
            'Pass validation with latin string equal to allowed' => [
                'minLength' => 10,
                'value' => str_repeat('w', 10),
            ],
            'Pass validation with cyrillic string equal to allowed' => [
                'minLength' => 10,
                'value' => str_repeat('я', 10),
            ],
            'Pass validation with latin string' => [
                'minLength' => 10,
                'value' => str_repeat('w', 11),
            ],
            'Pass validation with cyrillic string' => [
                'minLength' => 10,
                'value' => str_repeat('я', 11),
            ],
        ];
    }

    private function createSchemaDefinition(string $type, ?int $minLength): Property
    {
        return new Property(
            [
                'type' => $type,
                'minLength' => $minLength,
            ]
        );
    }

    private function createSchemaDefinitionFailToPassValidation($minLength): Property
    {
        return new Property(
            [
                'type' => self::TYPE,
                'minLength' => $minLength,
            ]
        );
    }
}
