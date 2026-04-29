# Content Aggregator

Import posts from json or xml files.

## Description

Content Aggregator will import content from a XML or JSON file.

## Installation

1. Upload the `content-aggregator` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. A new 'Content Aggregator' menu is available in WordPress

## Changelog

### 2.1.0

- Declare compatibility with WordPress 6.9
- Improve auto-detection and parser registry support, including Atom feeds
- Fix parser validation and XML namespace handling regressions
- Harden CI linting and release checks
- Add WordPress.org directory assets and Playground blueprint

### 2.0.0

- Add labels to settings fields
- Add success message when adding source
- Clear scheduled hook when deactivate plugin
- Convert HTML to WP blocks if possible
- Redirect to source edit page when adding source
- Bump & enforce minimum PHP version

### 1.0.1

- Initial release
