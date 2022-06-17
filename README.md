Swagger Resolver Bundle [![На Русском](https://img.shields.io/badge/Перейти_на-Русский-green.svg?style=flat-square)](./README.RU.md)
=======================

[![Latest Stable Version](https://poser.pugx.org/marfatech/swagger-resolver-bundle/v/stable)](https://packagist.org/packages/marfatech/swagger-resolver-bundle)
[![Total Downloads](https://poser.pugx.org/marfatech/swagger-resolver-bundle/downloads)](https://packagist.org/packages/marfatech/swagger-resolver-bundle)

[![knpbundles.com](http://knpbundles.com/marfatech/swagger-resolver-bundle/badge-short)](http://knpbundles.com/marfatech/swagger-resolver-bundle)

Introduction
------------

Bundle provides possibility for validate data according to the OpenApi 3 documentation.
You describe your API documentation by OpenApi and provides verification of data for compliance
with the described requirements.
When documentation has been updated then verification will be updated too, all in one place!

**Documentation is cached** through the standard [Symfony Cache](https://symfony.com/doc/current/components/cache.html) mechanism.

*Note:* as result bundle returns [OptionsResolver](https://github.com/symfony/options-resolver) object.
The object contains the created set of data requirements.

*Attention:* remember, when you change generated `SwaggerResolver` object you risk to get 
divergence with actual documentation.

### Integrations

Bundle provides integration with [NelmioApiDocBundle](https://github.com/nelmio/NelmioApiDocBundle),
supports configuration loading by [swagger-php](https://github.com/zircote/swagger-php) and also supports
loading directly from the `json` or `yaml`(`yml`) configuration file.
When used default bundle configuration then swagger documentation will be load in most optimal available way.
Loaders priority: 
1. `NelmioApiDocBundle` - do not require any additional configuration.
2. `swagger-php` - Uses `openapi_annotation.[area].scan` and `openapi_annotation.[area].exclude` parameters.
3. `json` - Uses `configuration_file.[area].file` parameter.

Installation
-----------

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the following command to download
the latest stable version of this bundle:
```bash
    composer require marfatech/swagger-resolver-bundle
```
*is command requires you to have [Composer](https://getcomposer.org) install globally.*

### Step 2: Enable the Bundle

Then, enable the bundle by updating your `app/AppKernel.php` file to enable the bundle:

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

Configuration
------------

To start using the bundle, a preliminary configuration is required only in case of loading the configuration using `swagger-php` or a configuration file.
Otherwise, no configuration is required.

```yaml
# config/packages/linkin_swagger_resolver.yaml
linkin_swagger_resolver:
    # default parameter locations which can apply normalization
    enable_normalization:
        - 'query'
        - 'path'
        - 'header'
    # strategy for merge all request parameters.
    path_merge_strategy:                linkin_swagger_resolver.merge_strategy.strict
    configuration_file:
        default:                        # api area
            file:       ~               # full path to the configuration file
    openapi_annotation:                 # settings for the swagger-php
        default:                        # api area
            scan:       ~               # array of the full paths for the annotations scan
            exclude:    ~               # array of the full paths which should be excluded
```

Schema caching requires component configuration [Symfony Cache](https://symfony.com/doc/current/cache.html#creating-custom-namespaced-pools).

```yaml
# config/packages/cache.yaml
framework:
    cache:
        pools:
            linkin_swagger_resolver.cache:
                adapter: cache.adapter.filesystem
```

Usage
-----

### Step 1: Swagger documentation preparation

OpenApi documentation preparation differ according to used tools of your project.

**NelmioApiDocBundle** 

If your project has [NelmioApiDocBundle](https://github.com/nelmio/NelmioApiDocBundle) connected, then no additional configuration is required.

**swagger-php** 

In the absence of [NelmioApiDocBundle](https://github.com/nelmio/NelmioApiDocBundle), the bundle will degrades to the configuration
loading by `swagger-php` annotations. In this case, by default, will be used `src/` directory to scan.
To optimize scanning process you can describe directories in the configuration:

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

If [NelmioApiDocBundle](https://github.com/nelmio/NelmioApiDocBundle) is missing, the bundle must be configured to load the `json/yaml/yml` file.

```yaml
# config/packages/linkin_swagger_resolver.yaml
linkin_swagger_resolver:
    configuration_file:
        default:
            file: '%kernel.project_dir%/web/swagger.json'
```

**Custom**

If you need to use your own loader, you need to replace the `linkin_swagger_resolver.openapi_configuration_factory` factory in the symfony container with your own. 

### Step 2: Data validation

#### Validation for model

```php
<?php declare(strict_types=1);

/** @var \Linkin\Bundle\SwaggerResolverBundle\Factory\OpenApiResolverFactory $factory */
$factory = $container->get('linkin_swagger_resolver.openapi_resolver_factory');
// loading by fully qualified class name
$optionsResolver = $factory->createForSchema(AcmeApiModel::class);
// loading by class name
$optionsResolver = $factory->createForSchema('AcmeApiModel');

/** @var \Symfony\Component\HttpFoundation\Request $request */
$data = $optionsResolver->resolve(json_decode($request->getContent(), true));
```

#### Validation for request

```php
<?php declare(strict_types=1);

/** @var \Linkin\Bundle\SwaggerResolverBundle\Factory\OpenApiResolverFactory $factory */
$factory = $container->get('linkin_swagger_resolver.openapi_resolver_factory');
$request = $container->get('request_stack')->getCurrentRequest();
// Loading by request
$optionsResolver = $factory->createForRequest($request);

$data = $optionsResolver->resolve(json_decode($request->getContent(), true));
```

Advanced
--------

### Custom validator

Bundle validates the data through the validator system.
List of all validators, you can find out by going to the [Validator](./Validator) folder.
All validators registered as tagging services. To create your own validator it is enough to create class,
which implements [SwaggerValidatorInterface](./Validator/OpenApiValidatorInterface.php) and then
register it under the tag `linkin_swagger_resolver.validator`.

### Custom normalizer

Bundle validates the data through the normalizer system.
List of all normalizers, you can find out by going to the [Normalizer](./Normalizer) folder.
All normalizers registered as tagging services. To create your own normalizer it is enough to create class,
which implements [SwaggerNormalizerInterface](./Normalizer/OpenApiNormalizerInterface.php) and then
register it under the tag `linkin_swagger_resolver.normalizer`.

### Symfony validator

The bundle provides the ability to use validators for the request body from the [Symfony Validator](https://symfony.com/doc/current/validation.html) package.
To apply validation to a property, you need to add the required constraint to the annotation.
Example for `NotBlank`, `Email`, `NotCompromisedPassword`, `Length`.

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

### Working with enums

The bundle provides the ability to use enumerations for the request body from the `marfatech/enumer-bundle` package.
To apply enums to a property, you need to add a class with a list of possible values to the annotation.
The enumer class structure can be found in the documentation for [marfatech/enumer-bundle](https://github.com/marfatech/enumer-bundle/blob/master/README.md).
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

License
-------

[![license](https://img.shields.io/badge/License-MIT-green.svg?style=flat-square)](./LICENSE)
