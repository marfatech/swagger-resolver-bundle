## [Unreleased]
### Fixed
- Fixed default properties of `NumberMinimumValidator`, `NumberMaximumValidator` and `ArrayUniqueItemsValidator`.
- Fixed multiple validators and constraints usage.

## [1.0.0] - 2022-07-21
### Added
- Support OpenApi 3.
- Support Symfony `~6.0`
### Removed
- [BC] Support Swagger 2.
- [BC] Set `nullable` type if property isn't required.
### Changed
- Updated `symfony/*` with pattern version `~4.4||~5.4||~6.0`.
- Removed compatibility with PHP lower than `8.0`.

## [0.5.1] - 2022-06-15
### Fixed
- Set `nullable` type if property isn't required.
- Forward all parent property meta while describe model.

## [0.5.0] - 2022-05-17
### Added
- Accept and read swagger extension `x-nullable`.
- Accept property annotation constraints.
- Swagger extension `x-class` has full class name of body model.
- Accept enum class to swagger enum configuration.
### Changed
- Required field can be with `null` value.
- Remove support of `symfony/*` with version `~3.4`.

## [0.4.11]
### Changed
- Updated `name` from `wakeapp/swagger-resolver-bundle` to `marfatech/swagger-resolver-bundle`.
- Updated `php` with pattern version `~7.4||~8.0`.

## [0.4.10] - 2021-02-24
### Added
- Support PHP ~8.0

## [0.4.9] - 2020-09-08
### Fixed
- Fix of empty required option in `AbstractMergeStrategy`

## [0.4.8] - 2020-09-04
### Fixed
- Added support of `symfony/option-resolver` with version `>=5.0`

## [0.4.7] - 2020-09-04
### Patch
- Added Symfony 5 support

## [0.4.6] - 2019-12-30
### Fixed
- Fixed deprecations for Symfony

## [0.4.5] - 2019-12-11
### Fixed
- Fixed priority of normalizers and validators.

## [0.4.4] - 2019-12-11
### Changed
- Improved performance of `SwaggerCachedConfiguration`.

## [0.4.3] - 2019-09-23
### Fixed
- Fixed rewriting `mapPathToRouteName` keys in `AbstractSwaggerConfigurationLoader`.

## [0.4.2] - 2019-09-09
### Changed
- Improved performance due to avoid of usage `RouterInterface::getRouteCollection` at runtime.

## [0.4.1] - 2019-04-13
### Added
- Added support of the several areas when `NelmioApiDocBundle` used for the configuration loading.

## [0.4.0] - 2019-03-24
### Added
- Added symfony cache warmer for the swagger configuration and enable him by default.
- Added console notification when some api definitions have not reference to the source file.
- Added automatic cache warm up in the debug mode, according to source file modification.
- Added composer requirement: `symfony/yaml`.
### Changed
- Extend `NormalizationFailedException` from `InvalidOptionsException` instead `RuntimeException`.
- Removed possibility set `MergeStrategyInterface` for single call `SwaggerResolverFactory::createForRequest`.
- Renamed `PathParameterMerger` into `OperationParameterMerger`.
- Reworked `SwaggerConfigurationLoaderInterface`.

## [0.3.0] - 2019-03-03
### Added
- Added normalizers usage and provides possibility for enable this for concrete parameter locations.
- Added new configuration parameter `enable_normalization`.
- Added `SwaggerNormalizerInterface` and implementation for `integer`, `number` and `boolean`.
- Added enums for typical swagger parameter options: 
    `ParameterCollectionFormatEnum`, `ParameterLocationEnum`, `ParameterTypeEnum`.
### Removed
- Removed `linkin_swagger_resolver.builder` alias.

## [0.2.0] - 2018-12-27
### Added
- Added possibility for creating `SwaggerResolver` object for all defined swagger request parameters.
- Added `SwaggerConfigurationLoaderInterface` into container as alias for the actual configuration loader service.
- Added possibility for use different strategies when performing resolving for the full request.
- Added new configuration parameter `path_merge_strategy`.
- Added auto-configuration for the `SwaggerValidatorInterface`.
### Changed
- Renamed `services.yml` into `services.yaml`.
### Removed
- Removed compatibility with Symfony lower than 3.4.

## [0.1.3] - 2018-12-06
### Fixed
- Fixed problem with object type mapping from the documentation to allowed types in PHP.

## [0.1.2] - 2018-10-17
### Added
- Added correct processing of the objects references.

## [0.1.1] - 2018-08-28
### Fixed
- Fixed incorrect type hinting for the `number`.

## [0.1.0] - 2018-08-27
### Added
- First release of this bundle.
