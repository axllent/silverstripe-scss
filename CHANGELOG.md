# Changelog

Notable changes to this project will be documented in this file.

## [2.0.1]

- Replace deprecated LeftAndMainExtension with Extension


## [2.0.0]

- Drop support for Silverstripe 4
- Integration with axllent/silverstripe-minifier


## [1.8.3]

- Replace deprecated formatting configuration


## [1.8.2]

- Remove dynamically created properties (deprecated PHP8.2)


## [1.8.1]

- Add Silverstripe 5 support


## [1.8.0]

- Support for the latest `scssphp/scssphp` 1.* version


## [1.7.0]

- Support for `scssphp/scssphp` 1.6.*
- Change default sourcemap to inline


## [1.6.1]

- Lock `scssphp/scssphp` to the 1.4 minor versions due to [compile issues](https://github.com/scssphp/scssphp/issues/397).


## [1.6.0]

- Feature: support for SCSS files in `Requirements::themedCSS()` (thanks to [@gurucomkz](https://github.com/gurucomkz))
- Tidy up code & documentation


## [1.5.1]

- Add flush interface
- Fix filemtime() path bug


## [1.5.0]

- Switch to using separate `$processed_folder` (default `_css`) due to upstream changes in `/dev/build` always emptying `_combinedfiles` causing issues with errorpage regeneration
- Remove (now) redundant ErrorPageController extension


## [1.4.1]

- Add support for vendor URLs using `ModuleResourceLoader`


## [1.4.0]

- Add third `$options` arg to `css()` for SS 4.5.0 compatibility
- Set requirement silverstripe/framework:^4.5


## [1.3.1]

- Add `BaseFolder` SCSS variable (`Director::baseFolder()`)


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
