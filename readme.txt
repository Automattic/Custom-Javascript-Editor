=== Custom JavaScript Editor ===
Contributors: automattic, betzster, danielbachhuber
Tags: javascript
Requires at least: 3.4
Tested up to: 3.5.1
Stable tag: 1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add custom JavaScript to your site from an editor in the WordPress admin

== Description ==

Add custom JavaScript to your site from an editor in the WordPress admin.

Your code is stored and revisioned with a custom post type, so you can always go back to a previous working state.

If you'd like to check out the code and contribute, [join us on GitHub](https://github.com/Automattic/Custom-Javascript-Editor). Pull requests are more than welcome!

== Installation ==

1. Upload the `custom-javascript-editor` folder to your plugins directory (e.g. `/wp-content/plugins/`)
2. Activate the plugin through the 'Plugins' menu in WordPress

== Screenshots ==

1. The back end editor
2. The front end editor
3. The front end editor

== Upgrade Notice ==

= 1.2 =
* Don't output custom javascript on the login page, thanks Carl Danley.

= 1.1 =
* The editor has a syntax highlighter with configurable themes. jQuery Masonry is also bundled.

= 1.0 =
Enqueue any bundled JavaScript libraries on the frontend for use. Register your own with the 'cje_available_scripts' filter.

== Changelog ==

= 1.1 (Nov. 19, 2012) =
* The editor has a syntax highlighter with configurable themes.
* jQuery Masonry is available as a library to use
* Bug fix: Stop stripping arbitrary HTML markup

= 1.0 (Oct. 8, 2012) =
* Enqueue any bundled JavaScript libraries on the frontend for use. Register your own with the 'cje_available_scripts' filter. Thanks [flentini](https://github.com/flentini) for the original pull request

= 0.9.1 =
* Bug fix: Use html_entity_decode() for decoding stored JavaScript so it properly renders

= 0.9 =
* Initial release
