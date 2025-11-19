# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

A PHP Composer library that displays a five-star review banner in WordPress admin to encourage users to leave reviews on WordPress.org. Designed for free WordPress plugins.

**Namespace**: `PublishPress\WordPressReviews`
**PHP Version**: >= 7.2
**Code Standard**: PSR-12

## Development Commands

```bash
# Lint PHP code style (PSR-12)
vendor/bin/phpcs

# Auto-fix code style issues
vendor/bin/phpcbf

# Check PHP syntax
vendor/bin/phplint

# Install dependencies
composer install
```

## Architecture

### Core Class: `ReviewsController`

Single-class library (`ReviewsController.php`) that manages the entire review notification lifecycle:

1. **Initialization**: Constructor accepts plugin slug, name, and optional icon URL
2. **Trigger System**: Time-based triggers (1 week, 1 month, 3 months after installation) with customizable priorities
3. **Display Logic**: Determines when to show notices based on user dismissals and trigger conditions
4. **AJAX Handling**: Processes user dismissals (maybe_later, am_now, already_did)
5. **Data Persistence**: Stores installation date in options, user dismissals in user meta

### Data Storage Keys

Options (site-level):
- `{pluginSlug}_wp_reviews_installed_on` - Installation timestamp

User Meta (per-user):
- `_{pluginSlug}_wp_reviews_dismissed_triggers` - Dismissed trigger priorities
- `_{pluginSlug}_wp_reviews_last_dismissed` - Last dismissal timestamp
- `_{pluginSlug}_wp_reviews_already_did` - Permanent dismissal flag

### Customization Filters

Primary filters for plugin customization:
- `{pluginSlug}_wp_reviews_meta_map` - Override database key names
- `{pluginSlug}_wp_reviews_allow_display_notice` - Control banner display
- `{pluginSlug}_wp_reviews_triggers` - Customize trigger configuration

## Security Requirements

The library implements strict security measures that must be maintained:

- **Nonce verification**: All AJAX requests validated with `wp_verify_nonce()`
- **Input sanitization**: All `$_REQUEST` values must use `sanitize_key()`, `sanitize_text_field()`, or `intval()`
- **Capability checks**: Only administrators can see/interact with notices
- **URL escaping**: Use `esc_url_raw()` for URLs

## Usage Pattern

```php
$reviews = new \PublishPress\WordPressReviews\ReviewsController(
    'my-plugin-slug',
    'My Plugin Name',
    'https://example.com/icon.png' // Optional
);
$reviews->init();
```

## Key Implementation Details

- Uses static caching for computed values (trigger selection)
- Supports legacy filter naming for backward compatibility
- Class guard prevents duplicate declarations
- Mixed WordPress hooks/filters pattern with OOP design
