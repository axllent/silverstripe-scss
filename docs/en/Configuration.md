# Configuration

## ThemeDir

Silverstripe-scss allows setting variables for compiling your scss files.
One of the most common issues is the full path of your relative images as the compiled scc files
are rewritten to `assets/_css_/`.

Silverstripe-scss comes with a built-in scss variable `$ThemeDir` provided you have specified a
`theme_dir` in your config (eg: `app/_config/scss.yml`):

```
Axllent\Scss\ScssCompiler:
  theme_dir: 'themes/site'
```

In your `scss` file you would use it like:

```
.myblock {
    background-url: url('#{$ThemeDir}/images/logo.png');
}
```

Only one `theme_dir` is supported.

## Custom variables

You can set multiple variables in your YAML configuration file (eg: `app/_config/scss.yml`).

```
Axllent\Scss\ScssCompiler:
  theme_dir: 'themes/site'
  sourcemap: 'file'
  variables:
    'HeaderFont': 'Arial, sans-serif, "Times New Roman"'
    'HeaderFontSize': '18px'
```

This allows you to add your own variables which you can then use in your `*.scss` stylesheets.
The above example would provide you with two variables, namely `$HeaderFont` and `$HeaderFontSize`.
These variables will also overrule any pre-defined variabled in your scss files.

```css
header h1 {
    font-family: $HeaderFont;
    font-size: $HeaderFontSize;
}
```

## Editor CSS

The scss compiler will automatically add any pre-compiled `editor.scss` file (used on the front-end) to TinyMCE.
This means your site must have an `editor.scss` in your `Requirements` if you want this to work.

## Source maps

Source maps are very helpful when building, debugging or maintaining a website. They let you use browser development tools (such as Chrome's inspector) to see the exact file and line where your SCSS selectors are declared. By default sourcemap is enabled (file), and only in `dev` mode.

Available settings are:
* `file` (separate file in the same location as the compiled stylesheet)
* `inline` (embedded in the compile stylesheet)
* false (enabled by default)

```
Axllent\Scss\ScssCompiler:
  sourcemap: 'file' # 'file' | 'inline' | false
```
