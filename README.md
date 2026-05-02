# Content Aggregator

Create WordPress posts from RSS, Atom, WordPress REST API, JSON, and XML sources.

## Description

Content Aggregator helps WordPress sites import external content as posts. It is built for sites that need to pull from feeds or remote endpoints on a schedule, while still controlling how imported posts are titled, dated, categorized, and published.

Out of the box, Content Aggregator includes parser definitions for RSS feeds, Atom feeds, and WordPress REST API post endpoints. Its parser registry uses JSON and XML mappings, so additional source formats can be supported without changing the import workflow.

### Key Features

- Manage import sources from the WordPress admin.
- Detect the source type automatically from a source URL.
- Import new items through WP-Cron on a configurable schedule.
- Limit how many sources are checked during each scheduled run.
- Configure post title, date, and content templates with placeholders.
- Assign categories and choose the post status for imported content.
- Set a default featured image when the remote item has no usable image.
- Avoid duplicate imports using the original item URL.
- Optionally enforce unique post titles.
- Optionally redirect imported posts to the original source URL.
- Configure the request User-Agent and SSL certificate path for remote fetches.
- Automatically disable sources that stay offline beyond the configured expiration delay.

### Supported Sources

Content Aggregator ships with support for common feed and WordPress content sources:

- RSS feeds.
- Atom feeds.
- WordPress REST API post endpoints.
- JSON and XML sources when a matching parser definition is available.

### Imported Post Templates

Each source can define templates for the imported post title, publication date, and content. Templates can use placeholders for the original title, source name, current import date, original publication date, original content, and original URL.

## Installation

1. Upload the `content-aggregator` folder to the `/wp-content/plugins/` directory or alternatively upload the coblocks.zip file via the plugin page of WordPress by clicking 'Add New' and selecting the zip from your computer.
2. Activate the Content Aggregator plugin through the 'Plugins' menu in WordPress.
3. A new 'Content Aggregator' menu is available in WordPress.

## Changelog

### 2.1.2

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
