<?php

/**
 * Plugin Class for PHP Compatibility Checker
 *
 * Main plugin class that handles initialization, admin assets, and core functionality.
 */

namespace EH\PHPCompatibilityChecker;

/**
 * Main Plugin Class
 *
 * Handles plugin initialization, admin menu creation, and asset management.
 */
class Plugin
{

	/**
	 * Plugin instance
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Plugin menu title
	 *
	 * @var string
	 */
	private $menu_title;

	/**
	 * Get plugin instance
	 *
	 * @return Plugin Plugin instance
	 */
	public static function get_instance()
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct()
	{
		$this->menu_title = __('PHP Compatibility Scanner', 'eli-php-compatibility-scanner');
		$this->init_hooks();
	}

	/**
	 * Get the plugin menu title
	 *
	 * @return string The menu title
	 */
	public function get_menu_title()
	{
		return $this->menu_title;
	}

	/**
	 * Initialize WordPress hooks and plugin functionality
	 */
	private function init_hooks()
	{
		add_action('admin_menu', array($this, 'add_admin_menu'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

		add_action('current_screen', array(Admin\Promo::class, 'register'));

		$this->init_ajax_handlers();
	}

	/**
	 * Register the top-level admin menu and its Scan submenu.
	 */
	public function add_admin_menu()
	{
		add_menu_page(
			$this->menu_title,
			$this->menu_title,
			'manage_options',
			'php-compatibility-scanner',
			array($this, 'render_admin_page'),
			$this->menu_icon()
		);

		add_submenu_page(
			'php-compatibility-scanner',
			$this->menu_title,
			__('Scan', 'eli-php-compatibility-scanner'),
			'manage_options',
			'php-compatibility-scanner',
			array($this, 'render_admin_page')
		);
	}

	/**
	 * Base64 SVG data-URI for the top-level menu icon — an original PHP open-tag
	 * mark: a solid badge with a "<P" glyph (no PHP trademark). WordPress detects
	 * the data:image/svg+xml;base64, prefix and renders it as the menu glyph. A
	 * filled badge (not a thin outline) is used so the icon reads with the same
	 * visual weight as WordPress's filled dashicons. Colors are baked in;
	 * background-image icons are not auto-recolored per admin color scheme.
	 *
	 * @return string
	 */
	private function menu_icon()
	{
		return 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCI+PHJlY3QgeD0iMiIgeT0iMy41IiB3aWR0aD0iMjAiIGhlaWdodD0iMTciIHJ4PSIzLjUiIGZpbGw9IiNjM2M0YzciLz48ZyBmaWxsPSJub25lIiBzdHJva2U9IiMxZDIzMjciIHN0cm9rZS13aWR0aD0iMi4xIiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiPjxwYXRoIGQ9Ik05IDguOCA1LjggMTIgOSAxNS4yIi8+PHBhdGggZD0iTTEyLjkgOC40aDIuMWEyIDIgMCAwIDEgMCA0aC0yLjFWOC40Wm0wIDRWMTUuNiIvPjwvZz48L3N2Zz4=';
	}

	/**
	 * Render the admin page using the AdminPage class
	 */
	public function render_admin_page()
	{
		$admin_page = new Admin\AdminPage();
		$admin_page->render();
	}

	/**
	 * Enqueue admin assets only on the plugin page
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets($hook)
	{
		if ('toplevel_page_php-compatibility-scanner' !== $hook) {
			return;
		}

		$plugin_url = plugin_dir_url(__DIR__);

		wp_enqueue_style(
			'phpcompat-checker-admin',
			$plugin_url . 'assets/dist/admin.css',
			array(),
			PHPCOMPAT_CHECKER_VERSION
		);

		wp_enqueue_script(
			'phpcompat-checker-admin',
			$plugin_url . 'assets/dist/admin.js',
			array('jquery'),
			PHPCOMPAT_CHECKER_VERSION,
			true
		);

		wp_localize_script(
			'phpcompat-checker-admin',
			'PHPCompatChecker',
			array(
				'ajaxUrl' => admin_url('admin-ajax.php'),
				'nonce'   => wp_create_nonce('phpcompat_checker_ajax'),
			)
		);
	}

	/**
	 * Initialize AJAX handlers through the AjaxHandler class
	 */
	private function init_ajax_handlers()
	{
		$ajax_handler = new Ajax\AjaxHandler();
		$ajax_handler->init();
	}
}
