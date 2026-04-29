=== Content Aggregator ===
Contributors: relisiuol
Requires at least: 6.2
Tested up to: 6.9
Requires PHP: 8.2
Stable tag: 2.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Tags: aggregator, json, xml

Import posts from json or xml files.

== Description ==

Content Aggregator will import content from a JSON or XML file.

== Installation ==

= Installation from within WordPress =

Not yet available.

= Manual installation =

1. Upload the `content-aggregator` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. A new 'Content Aggregator' menu is available in WordPress

== Changelog ==

= 2.1.0 - 2026-04-15 =

* Declare compatibility with WordPress 6.9
* Improve auto-detection and parser registry support, including Atom feeds
* Fix parser validation and XML namespace handling regressions
* Harden CI linting and release checks
* Add WordPress.org directory assets and Playground blueprint

= 2.0.0 - 2025-08-21 =

* Add labels to settings fields
* Add success message when adding source
* Clear scheduled hook when deactivate plugin
* Convert HTML to WP blocks if possible
* Redirect to source edit page when adding source
* Bump & enforce minimum PHP version

= 1.0.1 - 2025-02-21 =

* Initial release
