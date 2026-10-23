<?php

/**
 * Promo Rail configuration for PHP Compatibility Scanner
 *
 * Every piece of promotional copy and every outbound URL in the plugin lives
 * here, in one file, so the Free build's forbidden-string verifier can allow
 * this path and keep scanning everywhere else. See
 * scripts/free-forbidden-strings.json.
 *
 * The rail is registered by the Free build with the upgrade card included.
 * The paid build does not register a second rail — it edits this one through
 * the coffeewp_promo_cards_phpcompat filter, which keeps all upgrade-state
 * detection out of the Free tree.
 */

namespace EH\PHPCompatibilityChecker\Admin;

use CoffeeWP\AdminPromo\Rail;

/**
 * Promo Class
 *
 * Builds the sidebar rail shown on the plugin's admin screens.
 */
class Promo
{
	/**
	 * Rail namespace — also the suffix of the cards filter.
	 */
	const NS = 'phpcompat';

	/**
	 * Prefix of the ?ref= tag on every outbound coffeewp.dev link.
	 *
	 * Shape is `cwpc-<edition>-<slot>`, matching Media Cleanup's `cwpm-` so
	 * both plugins report the same way in analytics. One slot per spot, so a
	 * click can be attributed to the exact link that produced it.
	 *
	 * Fixed labels only. Never append the site URL, an email address, a
	 * licence key or anything else user- or site-specific: this is a static
	 * string in a link, not telemetry, and it must stay that way.
	 */
	const REF_PREFIX = 'cwpc';

	/**
	 * Landing page for the paid edition.
	 *
	 * Every link to it goes through pro_link() or self::link(), so the URL
	 * itself is written once in this file. See the file header: the Free
	 * build's forbidden-string verifier allows this path and scans everywhere
	 * else, which only holds while the URLs actually live here.
	 */
	const PRO_URL = 'https://coffeewp.dev/php-compatibility-scanner/';

	/**
	 * Edition reported in the ref tag.
	 *
	 * Filtered rather than detected. Free must not contain code that knows Pro
	 * exists (wp.org rule, enforced by scripts/free-forbidden-strings.json), so
	 * the Free tree states 'free' and the paid build overrides it. Without this
	 * the family and byline links — which Pro inherits from this file rather
	 * than rebuilding — would report `free` on a paid install.
	 *
	 * @return string
	 */
	private static function edition()
	{
		$edition = apply_filters('phpcompat_promo_ref_edition', 'free');

		$edition = strtolower((string) $edition);
		$edition = preg_replace('/[^a-z0-9-]/', '', $edition);

		return '' === $edition ? 'free' : $edition;
	}

	/**
	 * Tag an outbound coffeewp.dev link with its slot.
	 *
	 * Only for links to our own site. wordpress.org links (reviews, the
	 * support forum) are deliberately left untagged.
	 *
	 * @param string $url  Destination.
	 * @param string $slot Short fixed label for this specific link.
	 * @return string
	 */
	private static function link($url, $slot)
	{
		return add_query_arg(
			'ref',
			self::REF_PREFIX . '-' . self::edition() . '-' . $slot,
			$url
		);
	}

	/**
	 * An anchor to the paid edition's landing page, tagged for its slot.
	 *
	 * Lives here rather than at the call site so the URL and the product name
	 * stay in this one file, which is the arrangement the Free build's
	 * forbidden-string verifier is configured around.
	 *
	 * @param string $slot Short fixed label for the spot that renders it.
	 * @return string Escaped anchor HTML.
	 */
	public static function pro_link($slot)
	{
		return sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url(self::link(self::PRO_URL, $slot)),
			esc_html__('PHP Compatibility Scanner Pro', 'eli-php-compatibility-scanner')
		);
	}

	/**
	 * Screen id of the Scan page.
	 */
	const SCAN_SCREEN = 'toplevel_page_php-compatibility-scanner';

	/**
	 * Screen id of the Pro Settings page. Naming it here is harmless in Free —
	 * the page simply never exists, so the rail's stylesheet never loads there.
	 */
	const SETTINGS_SCREEN = 'php-compat-scanner_page_phpcompat-scanner-settings';

	/**
	 * Build and register the rail.
	 *
	 * Hooked on `current_screen`, and bails on every screen that is not one of
	 * this plugin's own. Two reasons, and the second is the important one:
	 *
	 * 1. Before this, the promo package loaded on EVERY admin screen — three
	 *    class files off disk plus every card built through __() on dashboard,
	 *    plugins, posts and settings, to render nothing. Rail::on() already
	 *    limited the stylesheet, so the band never needed any of it.
	 * 2. Another CoffeeWP plugin bundles the same library and requires it on
	 *    its own path. require_once dedupes by path, not by class name, so
	 *    whichever plugin declares second used to fatal with "Cannot declare
	 *    class CoffeeWP\AdminPromo\Palette". `current_screen` runs after every
	 *    plugin's load and after init, so a sibling that requires its copy
	 *    earlier always wins and load_lib() below simply reuses its classes.
	 *
	 * Moving this back to `init` silently reopens that fatal in whichever
	 * plugin loads second. There is a regression test on the hook name.
	 *
	 * No-ops when the library is absent — a clone without
	 * --recurse-submodules leaves lib/coffeewp-admin-promo empty, and a
	 * missing sidebar is a better outcome than a fatal on the scan page.
	 *
	 * @param \WP_Screen|null $screen Passed by the current_screen action.
	 * @return void
	 */
	public static function register($screen = null)
	{
		if (!self::is_plugin_screen($screen)) {
			return;
		}

		self::load_lib();

		if (!class_exists(Rail::class)) {
			return;
		}

		Rail::make(self::NS)
			->on(self::SCAN_SCREEN, self::SETTINGS_SCREEN)
			->upgrade(
				__('Your host blocks scans? Use the cloud', 'eli-php-compatibility-scanner'),
				array(
					__('Cloud scanning that works on any host', 'eli-php-compatibility-scanner'),
					__('View and download reports', 'eli-php-compatibility-scanner'),
					__('Priority email support', 'eli-php-compatibility-scanner'),
				),
				self::link(self::PRO_URL, 'upgrade')
			)
			->review('eli-php-compatibility-scanner')

			->support(
				'https://wordpress.org/support/plugin/eli-php-compatibility-scanner/',
				__("Tell us what happened on the support forum, we're glad to help where we can.", 'eli-php-compatibility-scanner')
			)
			->family(
				array(
					array(

						__('Media Cleanup – Scan and Remove Unused Media and Orphan Thumbnails', 'eli-php-compatibility-scanner'),
						self::link('https://coffeewp.dev/media-cleanup/', 'mediacleanup'),
					),
				),
				self::link('https://coffeewp.dev/plugins/', 'plugins')
			)
			->byline('CoffeeWP', self::link('https://coffeewp.dev/', 'byline'))
			->register();
	}

	/**
	 * Is the screen we are on one this plugin draws the rail on?
	 *
	 * Returns true when no screen can be determined, so a direct call from the
	 * page template still works on a request where `current_screen` never ran.
	 *
	 * @param \WP_Screen|null $screen Screen passed by the action, if any.
	 * @return bool
	 */
	private static function is_plugin_screen($screen = null)
	{
		if (!$screen instanceof \WP_Screen) {
			$screen = function_exists('get_current_screen') ? get_current_screen() : null;
		}

		if (!$screen instanceof \WP_Screen || '' === (string) $screen->id) {
			return true;
		}

		return in_array($screen->id, array(self::SCAN_SCREEN, self::SETTINGS_SCREEN), true);
	}

	/**
	 * Require the promo library, one guarded file at a time.
	 *
	 * The directory IS the class list — globbing instead of naming Palette,
	 * Renderer and Rail means a class added to the package upstream cannot
	 * arrive here unguarded. An empty glob also covers a clone made without
	 * --recurse-submodules, so no separate file_exists() check is needed.
	 *
	 * class_exists(..., false) is deliberate: autoloading must stay off, or
	 * the check would try to load the very class we are about to require.
	 *
	 * @return void
	 */
	private static function load_lib()
	{
		$src = PHPCOMPAT_CHECKER_PLUGIN_DIR . 'lib/coffeewp-admin-promo/src/';

		foreach ((array) glob($src . '*.php') as $file) {
			$fqcn = 'CoffeeWP\\AdminPromo\\' . basename($file, '.php');

			if (!class_exists($fqcn, false)) {
				require_once $file;
			}
		}
	}

	/**
	 * Render the rail into the current page's sidebar column.
	 *
	 * Safe to call when the library is missing — Rail::get() on an
	 * unregistered namespace returns an empty rail.
	 *
	 * @return void
	 */
	public static function render()
	{
		if (!class_exists(Rail::class)) {
			return;
		}

		Rail::get(self::NS)->render();
	}
}
