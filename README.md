# What Template

What Template displays the current template name in the WordPress admin bar with helpful details about your theme and template. It works with "classic" PHP-based template themes, block themes (FSE), and hybrid themes.

## Features

- **Full theme support**: Works with classic, block/FSE, and hybrid themes
- **Template identification**: Shows the template's friendly name and type icon for quick reference
- **Detailed information**: Admin bar menu dropdown displays:
  - Template file or slug
  - Theme and parent slugs
  - Template source (e.g., theme or plugin) and customization status
  - Direct link to edit template in the Site Editor or Theme File Editor
  - (Desktop) Hovering on the theme or template name shows relative path to file(s)

## Installation

### From WordPress.org

1. Visit the [What Template plugin page on WordPress.org](https://wordpress.org/plugins/what-template/)
2. Use the automatic installer from your WordPress admin, or
3. Download and upload to your `/wp-content/plugins/` directory

### For Development

Clone this repository directly into your `wp-content/plugins/` directory:

```sh
cd wp-content/plugins
git clone https://github.com/ironprogrammer/what-template.git
```

or keep it separate and symlink it into your local dev environment:

```sh
cd path/to/external/plugins
git clone https://github.com/ironprogrammer/what-template.git
ln -s $PWD/what-template ~/path/to/wordpress-site/wp-content/plugins/what-template
```

The plugin includes a Git loader at the root level, so it will work immediately after cloning.

## Requirements

- **WordPress**: 5.9 or higher
- **PHP**: 7.4 or higher
- **Tested up to**: WordPress 6.9

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Credits

Icons from [Noun Project](https://thenounproject.com/) (CC BY 3.0) by Jamison Wieser:
- [Source Code](https://thenounproject.com/browse/icons/term/source-code/)
- [Stylesheet](https://thenounproject.com/browse/icons/term/stylesheet/)
- [Plug-In](https://thenounproject.com/browse/icons/term/plug-in/)
- [Slides](https://thenounproject.com/browse/icons/term/slides/)

## License

This plugin is licensed under the GPL v2 or later.

> This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.
>
> This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
