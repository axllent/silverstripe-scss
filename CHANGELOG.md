# Changelog

Notable changes to this project will be documented in this file.

## [1.4.1]

- Add support for vendor URLs using `ModuleResourceLoader`


## [1.4.0]

- Add third `$options` arg to `css()` for SS 4.5.0 compatibility
- Set requirement silverstripe/framework:^4.5


## [1.3.1]

- Add `BaseFolder` scss variable (`Director::baseFolder()`)


## [1.3.0]

- Switch to scssphp/scssphp ([see why](https://github.com/leafo/scssphp/issues/649))


## [1.2.0]

- Do not combine requirements on ErrorPages


## [1.1.1]

- Use `css.map` as allowed filetype to cater for SS 4.1.1 change


## [1.1.0]

- Add source maps (inline or file), enabled by default
- Remove redundant code


## [1.0.1]

- Fix typo in docs
- Add optional `?flushstyles` method in dev mode to force css regeneration (ie: not entire site)


## [1.0.0]

- Initial release
