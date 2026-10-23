=== Eli's PHP Compatibility Scanner ===
Contributors: eliehanna
Tags: php-compatibility, php-8, php-version, php-upgrade, compatibility
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A PHP compatibility checker for your plugins and themes. See which files and lines will break on PHP 8 before you upgrade.

== Description ==

Before you upgrade PHP, check your plugins and themes. The scanner reads their code and lists every file and line that will break, or start throwing warnings, on the PHP version you pick (7.4 to 8.4). It checks any plugin or theme installed on the site, including premium and custom ones.

It is a PHP compatibility checker built on PHP_CodeSniffer and the PHPCompatibility ruleset. The plugin ships the current development version of that ruleset instead of its last stable release from 2019, so changes made in PHP 8.1, 8.2, 8.3 and 8.4 are covered.

PHP 8.2 stops receiving security fixes on 31 December 2026. If a site you look after still runs 8.2 or older, now is a good time to check what an upgrade would break.

**Where it runs**

The free scanner runs on your own setup: LocalWP, XAMPP, MAMP, Docker, or any server where PHP's `exec()` function is enabled. Many managed hosts (WP Engine, Kinsta, SiteGround and others) turn `exec()` off, and the free scanner needs it.

If your site is on one of those hosts, [PHP Compatibility Scanner Pro](https://coffeewp.dev/php-compatibility-scanner/?ref=cwpc-free-readme) runs the scan on a remote service instead, so it does not need `exec()` and the heavy work happens off your live server. Pro is a separate plugin, available from coffeewp.dev.

**How it works**

PHP_CodeSniffer turns your code into PHP tokens instead of searching it as text. A function name inside a comment or a string is not reported, and each finding names the PHP version that deprecated or removed what your code uses.

**How to Use:**

1. Open **PHP Compatibility Scanner** in the WordPress admin menu
2. Review the **System Check** section to ensure your environment is compatible
3. Select the **plugins and/or themes** you want to scan by checking their boxes
4. Configure your scan options:
   - **Target PHP Version**: Choose the PHP version you want to test against (7.4 - 8.4)
   - **Batch Size**: Select how many files to process at once (default: 50 files)
   - **Skip Vendor Directory**: Keep checked to skip third-party dependencies
5. Click **Start Scan** to begin the compatibility check
6. View results in real-time as each plugin/theme is scanned
7. Review any **errors** (❌) or **warnings** (⚠️) found in your code
8. Click on any target to expand and see detailed compatibility issues

== Installation ==

**Prerequisites**

* **Development Environment**: LocalWP, XAMPP, MAMP, or self-hosted server
* **PHP 7.4+**: Required for plugin operation
* **WordPress 5.8+**: Minimum WordPress version

**Setup Instructions**

1. **Download/Install** the plugin to your `wp-content/plugins/` directory
2. **Activate Plugin** in WordPress Admin → Plugins
3. **Open the scanner** via **PHP Compatibility Scanner** in the WordPress admin menu

**Note**: All required dependencies (PHP_CodeSniffer and PHPCompatibility) are included with the plugin, no additional setup required.

== Frequently Asked Questions ==

= What is this plugin for? =

This plugin helps WordPress developers check if their custom code (plugins and themes) will work with different PHP versions. It's especially useful when planning to upgrade PHP on production servers or when developing for clients with specific PHP version requirements.

= Why won't it work on my hosting provider? =

Most managed hosting providers (WP Engine, Kinsta, SiteGround, etc.) disable the `exec()` function for security reasons. This plugin needs to run PHP commands to analyze your code, which requires this function to be enabled.

= Can I scan a site on managed hosting? =

Not with this plugin: it needs `exec()` and access to a PHP binary, and managed hosts disable both. [PHP Compatibility Scanner Pro](https://coffeewp.dev/php-compatibility-scanner/?ref=cwpc-free-readme) sends the plugin or theme to a remote scan service instead, so the scan never runs on your server. It is a separate plugin, available from coffeewp.dev.

= What PHP versions can I test against? =

The plugin can test your code against PHP versions 7.4 through 8.4, helping you identify compatibility issues before upgrading.

= How accurate are the results? =

The scan runs PHP_CodeSniffer with the PHPCompatibility standard, a ruleset maintained specifically to track what changed in each PHP release.

PHP_CodeSniffer parses your code into PHP tokens instead of searching it for text patterns, so every match is judged in context. A function name that appears inside a comment or a string is not reported, and a call is only flagged against the PHP versions that actually changed it. Scanners built on pattern matching cannot make that distinction, which is where both false alarms and missed issues come from.

= Which PHP version should I check against? =

The one your host will move you to. PHP 8.2 gets security fixes until 31 December 2026, PHP 8.3 until 31 December 2027 and PHP 8.4 until 31 December 2028. Many hosts upgrade older sites automatically around those dates, so check before they do.

= Can I scan multiple plugins at once? =

Yes. You can select multiple plugins and themes to scan simultaneously. The plugin processes them in batches to manage memory usage efficiently.

= What if the scan takes too long? =

You can stop a long-running scan at any time. Lowering the batch size also helps on servers with tight memory or execution limits.

= Are my files modified during scanning? =

No, your files are never modified. The plugin only reads your code to analyze it. Any temporary files created during scanning are automatically cleaned up.

== Screenshots ==

1. The system check confirms your setup can run a scan before you start.
2. Pick the plugins and themes to check and the PHP version you plan to move to.
3. Results for each plugin and theme, with the file, the line and what changed in PHP.
4. A finished scan with no issues found for the selected PHP version.

== Changelog ==

= 1.2.1 =
* Fix: no longer causes a fatal error when another CoffeeWP plugin that bundles the same promotional sidebar library is active. Whichever plugin loaded second could stop the site.
* Improved: the sidebar library now loads only on the scanner's own screens, instead of on every admin page.
* Links to coffeewp.dev now carry a short fixed tag identifying which link was clicked. No site address, email or other information about you is added.

= 1.2.0 =
* New: the scanner has its own top-level admin menu, with Scan and Settings screens instead of a buried submenu.
* New: Scan Options shows a loading skeleton while the system check runs, so the screen no longer looks empty on slow hosts.
* New: when the system check fails, the screen explains what failed and what to try, instead of only reporting that scanning is unavailable.
* Improved: reworked scan page: clearer mode buttons, a results modal, and corrected summary counts.
* Improved: support links now point to the right place for free users.
* Fix: no longer fatals when another edition of the scanner is already active.
* Fix: hardened handling of admin AJAX request input.

= 1.1.3 =
* Fix: "Unable to run phpcs --version with detected PHP binary" on LocalWP/macOS. PHP_BINARY pointed to php-fpm, a server SAPI that cannot run CLI scripts; the scanner now resolves the CLI php binary from the same PHP install.
* Tested up to WordPress 7.0.

Older releases are listed in changelog.txt, included with the plugin.

== Upgrade Notice ==

= 1.2.1 =
Fixes a fatal error when another CoffeeWP plugin bundling the same sidebar library is active.

== Contributing ==

This plugin is actively developed for WordPress development environments. Contributions are welcome, especially:

* Additional PHP version compatibility rules
* Performance optimizations
* UI/UX improvements
* Hosted environment compatibility solutions

== Technical Details ==

**How the scan runs**

1. **Bundled dependencies**: PHP_CodeSniffer and the PHPCompatibility ruleset ship with the plugin
2. **Batch processing**: files are scanned in configurable batches (10 to 100) to manage memory usage
3. **Command execution**: PHPCS is invoked through PHP's `exec()` function
4. **Server-side state**: file lists are stored in WordPress options to keep AJAX requests small
5. **Progressive UI**: batch results appear in real time, with stop and start controls

**Dependencies**

* **PHP_CodeSniffer**: ^3.13 (static analysis engine)
* **PHPCompatibility**: dev-develop (compatibility ruleset)

**License**

GPLv2 or later - same as WordPress core.
