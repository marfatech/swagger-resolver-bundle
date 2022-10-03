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

use Linkin\Bundle\SwaggerResolverBundle\Validator\StringPatternValidator;
use OpenApi\Annotations\Property;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class StringPatternValidatorTest extends KernelTestCase
{
    private const TYPE = 'string';

    /**
     * @var StringPatternValidator
     */
    private $sut;

    protected function setUp(): void
    {
        $this->sut = new StringPatternValidator();
    }

    /**
     * @dataProvider supportsDataProvider
     */
    public function testSchemaBuilder(string $type, ?string $pattern, bool $expectedResult): void
    {
        $schema = $this->createSchemaDefinition($type, $pattern);
        $isSupported = $this->sut->supports($schema);

        self::assertSame($isSupported, $expectedResult);
    }

    public function supportsDataProvider(): array
    {
        return [
            'Fail with unsupported type' => [
                'type' => '_invalid_type_',
                'pattern' => '\d',
                'expectedResult' => false,
            ],
            'Success' => [
                'type' => self::TYPE,
                'pattern' => '\d',
                'expectedResult' => true,
            ],
        ];
    }

    /**
     * @dataProvider failToPassValidationDataProvider
     */
    public function testFailToPassValidation(string $pattern, $value): void
    {
        $schemaProperty = $this->createSchemaDefinitionFailAndPassValidation($pattern);

        $this->expectException(InvalidOptionsException::class);
        $this->sut->validate($schemaProperty, $value);
    }

    public function failToPassValidationDataProvider(): array
    {
        return [
            'Fail with string not match pattern' => [
                'pattern' => '^[\d]+\.[\d]+\.[\d]+$',
                'value' => '1-2-3',
            ],
        ];
    }

    /**
     * @dataProvider canPassValidationDataProvider
     */
    public function testCanPassValidation(string $pattern, $value): void
    {
        $schemaProperty = $this->createSchemaDefinitionFailAndPassValidation($pattern);

        $this->sut->validate($schemaProperty, $value);
        self::assertTrue(true);
    }

    public function canPassValidationDataProvider(): array
    {
        return [
            'Pass validation with null' => [
                'pattern' => '^[\d]+$',
                'value' => null,
            ],
            'Pass validation with string' => [
                'pattern' => '^[\d]+\.[\d]+\.[\d]+$',
                'value' => '1.2.3',
            ],
            'Pass validation with string wrapped in backslashes' => [
                'pattern' => '/^[\d]+\.[\d]+\.[\d]+$/',
                'value' => '1.2.3',
            ],
            'Pass validation with specific symbols' => [
                'pattern' => '/.*\:\/\/.*/',
                'value' => 'https://some string',
            ],
        ];
    }

    private function createSchemaDefinition(string $type, ?string $pattern): Property
    {
        return new Property(
            [
                'type' => $type,
                'pattern' => $pattern,
            ]
        );
    }

    private function createSchemaDefinitionFailAndPassValidation($pattern): Property
    {
        return new Property(
            [
                'type' => self::TYPE,
                'pattern' => $pattern,
            ]
        );
    }
}
