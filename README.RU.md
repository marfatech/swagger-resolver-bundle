Swagger Resolver Bundle [![In English](https://img.shields.io/badge/Switch_To-English-green.svg?style=flat-square)](./README.md)
=======================

[![Latest Stable Version](https://poser.pugx.org/marfatech/swagger-resolver-bundle/v/stable)](https://packagist.org/packages/marfatech/swagger-resolver-bundle)
[![Total Downloads](https://poser.pugx.org/marfatech/swagger-resolver-bundle/downloads)](https://packagist.org/packages/marfatech/swagger-resolver-bundle)

[![knpbundles.com](http://knpbundles.com/marfatech/swagger-resolver-bundle/badge-short)](http://knpbundles.com/marfatech/swagger-resolver-bundle)

Введение
--------

Бандл предоставляет возможность валидировать данные в соответствии с описанной документацией OpenApi 3.
Единожды описав документацию api при помощи OpenApi вы получаете проверку данных на соответствие описанным требованиям.
Обновляется документация - обновляются требования, все в одном месте!

**Документация кэшируется** посредством стандартного компонента [Symfony Cache](https://symfony.com/doc/current/components/cache.html).

*Примечание:* в качестве ответа приходит объект [OptionsResolver](https://github.com/symfony/options-resolver).
Объект содержит в себе созданный набор требований к данным.

*Внимание:* помните что внося изменения в предустановленный набор требований к данным
вы рискуете получить расхождение с актуальной документацией.

### Интеграции

Бандл предоставляет автоматическую интеграцию с [NelmioApiDocBundle](https://github.com/nelmio/NelmioApiDocBundle),
поддерживает загрузку конфигурации из [swagger-php](https://github.com/zircote/swagger-php), а также загрузку
конфигурации непосредственно из файла `json` или `yaml`(`yml`).
При отсутствии дополнительной конфигурации бандл автоматически подключит самый оптимальный доступный способ загрузки
конфигурации. Порядок приоритета:
1. `NelmioApiDocBundle` - не требует дополнительной конфигурации.
2. `swagger-php` - Использует параметры `openapi_annotation.[area].scan` и `openapi_annotation.[area].exclude`.
3. `json` - Использует параметр `configuration_file.[area].file`.

Установка
---------

### Шаг 1: Загрузка бандла

Откройте консоль и, перейдя в директорию проекта, выполните следующую команду для загрузки наиболее подходящей
стабильной версии этого бандла:
```bash
    composer require marfatech/swagger-resolver-bundle
```
*Эта команда подразумевает что [Composer](https://getcomposer.org) установлен и доступен глобально.*

### Шаг 2: Подключение бандла

После включите бандл добавив его в список зарегистрированных бандлов в `app/AppKernel.php` файл вашего проекта:

```php
<?php declare(strict_types=1);
// app/AppKernel.php

class AppKernel extends Kernel
{
    // ...

    public function registerBundles()
    {
        $bundles = [
            // ...

            new Linkin\Bundle\SwaggerResolverBundle\LinkinSwaggerResolverBundle(),
        ];

        return $bundles;
    }

    // ...
}
```

Конфигурация
------------

Чтобы начать использовать бандл, требуется предварительная конфигурация только в случае загрузки конфигурации с помощью `swagger-php` или файла конфигурации.
В остальных случаях конфигурация не потребуется.

```yaml
# config/packages/linkin_swagger_resolver.yaml
linkin_swagger_resolver:
    # список локаций параметров по умолчания, для которых включена нормализация
    enable_normalization:
        - 'query'
        - 'path'
        - 'header'
    # стратегия для слияния параметров запроса
    path_merge_strategy:                linkin_swagger_resolver.merge_strategy.strict
    configuration_file:
        default:                        # область api
            file:       ~               # полный путь к файлу конфигурации
    openapi_annotation:                 # настройки для swagger-php
        default:                        # область api
            scan:       ~               # массив полных путей для сканирования аннотаций
            exclude:    ~               # массив полных путей которые стоит исключить
```

Для кеширования схем потребуется настройка компонента [Symfony Cache](https://symfony.com/doc/current/cache.html#creating-custom-namespaced-pools).

```yaml
# config/packages/cache.yaml
framework:
    cache:
        pools:
            linkin_swagger_resolver.cache:
                adapter: cache.adapter.filesystem
```

Использование
-------------

### Шаг 1: Подготовка swagger документации

Подготовка OpenApi документации отличается в зависимости от используемых инструментов в вашем проекте.

**NelmioApiDocBundle** 

Если в вашем проекте подключен [NelmioApiDocBundle](https://github.com/nelmio/NelmioApiDocBundle), то дополнительная конфигурация не требуется.

**swagger-php** 

В случае отсутствия [NelmioApiDocBundle](https://github.com/nelmio/NelmioApiDocBundle) the bundle needs to be configured to be loaded based on the `swagger-php` annotations.
To optimize scanning, you can exclude some directories:

```yaml
# config/packages/linkin_swagger_resolver.yaml
linkin_swagger_resolver:
    openapi_annotation:
        default:
            scan:
                - '%kernel.project_dir%/src/Acme/ApiBundle'
            exclude:
                - '%kernel.project_dir%/src/Acme/ApiBundle/Resources'
                - '%kernel.project_dir%/src/Acme/ApiBundle/Repository'
```

**JSON/YAML** or *(yml)*

В случае отсутствия [NelmioApiDocBundle](https://github.com/nelmio/NelmioApiDocBundle) бандл необходимо конфигурировать для загрузки `json/yaml/yml` файла.

```yaml
# config/packages/linkin_swagger_resolver.yaml
linkin_swagger_resolver:
    configuration_file:
        default:
            file: '%kernel.project_dir%/web/swagger.json'
```

**Custom**

При необходимости использовать собственный загрузчик вам необходимо подменить фабрику `linkin_swagger_resolver.openapi_configuration_factory` в контейнере symfony на свою.

### Шаг 2: Валидация данных

#### Валидация модели

```php
<?php declare(strict_types=1);

/** @var \Linkin\Bundle\SwaggerResolverBundle\Factory\OpenApiResolverFactory $factory */
$factory = $container->get('linkin_swagger_resolver.openapi_resolver_factory');
// загрузка по полному имени класса модели
$optionsResolver = $factory->createForSchema(AcmeApiModel::class);
// загрузка имени класса модели
$optionsResolver = $factory->createForSchema('AcmeApiModel');

/** @var \Symfony\Component\HttpFoundation\Request $request */
$data = $optionsResolver->resolve(json_decode($request->getContent(), true));
```

#### Валидация всего запроса

```php
<?php declare(strict_types=1);

/** @var \Linkin\Bundle\SwaggerResolverBundle\Factory\OpenApiResolverFactory $factory */
$factory = $container->get('linkin_swagger_resolver.openapi_resolver_factory');
$request = $container->get('request_stack')->getCurrentRequest();
// загрузка всех параметров вызванного метода запроса
$optionsResolver = $factory->createForRequest($request);

$data = $optionsResolver->resolve(json_decode($request->getContent(), true));
```

Дополнительно
-------------

### Собственный валидатор

Бандл производит валидацию данных посредством системы валидаторов.
Со списком всех валидаторов вы можете ознакомиться перейдя в папку [Validator](./Validator).
Валидаторы являются тегированными сервисами. Чтобы создать свой собственный валидатор, достаточно создать
класс, реализующий интерфейс [SwaggerValidatorInterface](./Validator/OpenApiValidatorInterface.php) и
зарегистрировать его с тегом `linkin_swagger_resolver.validator`.

### Собственный нормализатор

Бандл производит нормализацию данных посредством системы нормализаторов.
Со списком всех нормализаторов вы можете ознакомиться перейдя в папку [Normalizer](./Normalizer).
Нормализаторы являются тегированными сервисами. Чтобы создать свой собственный нормализатор, достаточно создать
класс, реализующий интерфейс [SwaggerNormalizerInterface](./Normalizer/OpenApiNormalizerInterface.php) и
зарегистрировать его с тегом `linkin_swagger_resolver.normalizer`.

### Симфони валадатор

Бандл предоставляет возможность использовать валидаторы для тела запроса от пакета [Symfony Validator](https://symfony.com/doc/current/validation.html).
Чтобы применить валидацию к свойству необходимо в аннотацию добавить требуемое ограничение.
Пример для `NotBlank`, `Email`, `NotCompromisedPassword`, `Length`.

```php
<?php

declare(strict_types=1);

namespace Acme\Dto;

use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @OA\Schema(
 *     type="object",
 *     description="Entry DTO for create user endpoint",
 *     required={
 *         "email",
 *         "password",
 *     },
 * )
 */
class UserEntryDto
{
    /**
     * @Assert\NotBlank(message="You should fill email")
     * @Assert\Email()
     *
     * @OA\Property(
     *     example="foo@acme.com",
     * )
     */
    private string $email;
    
    /**
     * @Assert\NotCompromisedPassword()
     * @Assert\Length(min=8, max=24)
     *
     * @OA\Property(
     *     example="qwerty123",
     * ) 
     */
    private string $password;
    
    public  function getEmail(): string
    {
        return $this->email;
    }
    
    public  function getPassword(): string
    {
        return $this->email;
    }
}
```

### Работа с перечислениями

Бандл предоставляет возможность использовать перечисления для тела запроса от пакета [Enumer Bundle](https://github.com/marfatech/enumer-bundle)`.
Чтобы применить перечисления к свойству необходимо в аннотацию добавить класс со списоком возможных значений.
Структуру класса перечисления можно найти в документации к [marfatech/enumer-bundle](https://github.com/marfatech/enumer-bundle/blob/master/README.md).
```php
<?php

declare(strict_types=1);

namespace Acme\Dto;

use Acme\Enum\UserStatusEnum;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;
use MarfaTech\Bundle\EnumerBundle\Enum\EnumInterface;

/**
 * @OA\Schema(
 *     type="object",
 *     description="Entry DTO for change status user endpoint",
 *     required={
 *         "status",
 *     },
 * )
 */
class UserStatusEntryDto
{
    /**
     * @OA\Property(
     *     enum=UserStatusEnum::class,
     *     example=UserStatusEnum::ACTIVE,
     * )
     */
    private string $status;
    
    public  function getStatus(): string
    {
        return $this->status;
    }
}
```

Лицензия
--------

[![license](https://img.shields.io/badge/License-MIT-green.svg?style=flat-square)](./LICENSE)
