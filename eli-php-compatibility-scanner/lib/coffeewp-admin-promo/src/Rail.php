<?php
/**
 * Promo rail builder.
 *
 * Collects an ordered list of cards, then renders them where the host plugin
 * asks. The rail never emits page structure — the host plugin owns its own
 * grid, because grids are exactly where plugins differ. This class only ever
 * produces the stack of cards that goes inside the host's sidebar column.
 *
 * Usage:
 *
 *     Rail::make( 'phpcompat' )
 *         ->on( 'toplevel_page_php-compatibility-scanner' )
 *         ->upgrade( 'Scan without exec()', array( 'Cloud scan', 'Bulk queue' ), $url )
 *         ->review( 'eli-php-compatibility-scanner' )
 *         ->support( 'https://coffeewp.dev/support/' )
 *         ->register();
 *
 * then, inside the host plugin's sidebar column:
 *
 *     Rail::get( 'phpcompat' )->render();
 *
 * @package CoffeeWP\AdminPromo
 */

namespace CoffeeWP\AdminPromo;

class Rail {

	/**
	 * Registered rails, keyed by namespace.
	 *
	 * @var array<string, self>
	 */
	private static $rails = array();

	/** Namespace — prefixes CSS classes and filter names. */
	private $ns;

	/**
	 * Ordered cards. Each is a normalised array with at least 'type'.
	 *
	 * @var array<int, array>
	 */
	private $cards = array();

	/**
	 * Admin screen ids this rail's stylesheet is enqueued on.
	 *
	 * @var string[]
	 */
	private $screens = array();

	/** Whether enqueue hooks have been wired. */
	private $registered = false;

	/**
	 * @param string $ns Namespace, e.g. 'phpcompat'.
	 */
	private function __construct( string $ns ) {
		$this->ns = sanitize_key( $ns );
	}

	/**
	 * Start a rail.
	 *
	 * @param string $ns Namespace, e.g. 'phpcompat'.
	 * @return self
	 */
	public static function make( string $ns ): self {
		return new self( $ns );
	}

	/**
	 * Fetch a registered rail by namespace.
	 *
	 * Returns an empty throwaway rail when the namespace is unknown, so a
	 * template calling render() on a rail that failed to register produces
	 * nothing rather than a fatal.
	 *
	 * @param string $ns Namespace.
	 * @return self
	 */
	public static function get( string $ns ): self {
		$key = sanitize_key( $ns );

		return self::$rails[ $key ] ?? new self( $key );
	}

	/**
	 * Limit the inline stylesheet to specific admin screens.
	 *
	 * Accepts one screen id or several. With no screens set the CSS is
	 * enqueued on every admin page, which is wasteful — always name them.
	 *
	 * @param string ...$screens Admin screen ids (the $hook_suffix value).
	 * @return self
	 */
	public function on( string ...$screens ): self {
		foreach ( $screens as $screen ) {
			$this->screens[] = $screen;
		}

		return $this;
	}

	/**
	 * Upgrade card — features list plus a full-width call to action.
	 *
	 * Free builds only. Text and links only: nothing here may gate or detect
	 * a Pro feature, or wordpress.org treats the free plugin as carrying
	 * locked functionality.
	 *
	 * @param string   $title    Card heading.
	 * @param string[] $features Bullet list.
	 * @param string   $url      Where the button goes.
	 * @param string   $label    Optional. Button label. Default 'Get Pro'.
	 * @return self
	 */
	public function upgrade( string $title, array $features, string $url, string $label = '' ): self {
		return $this->push(
			array(
				'type'     => 'upgrade',
				'title'    => $title,
				'features' => $features,
				'url'      => $url,
				'label'    => $label ?: __( 'Get Pro', 'coffeewp-admin-promo' ),
			)
		);
	}

	/**
	 * Review card — text with the CTA locked to the right.
	 *
	 * @param string $slug  wordpress.org plugin slug, or a full URL.
	 * @param string $body  Optional. Overrides the default invitation.
	 * @param string $label Optional. Button label. Default 'Review'.
	 * @return self
	 */
	public function review( string $slug, string $body = '', string $label = '' ): self {
		$url = ( 0 === strpos( $slug, 'http' ) )
			? $slug
			: 'https://wordpress.org/support/plugin/' . rawurlencode( $slug ) . '/reviews/?filter=5#new-post';

		return $this->push(
			array(
				'type'  => 'review',
				'title' => __( 'Found this useful?', 'coffeewp-admin-promo' ),
				'body'  => $body ?: __( 'Spread the love with a quick rating on WordPress.org.', 'coffeewp-admin-promo' ),
				'url'   => $url,
				'label' => $label ?: __( 'Leave a rating', 'coffeewp-admin-promo' ),
				'stars' => true,
			)
		);
	}

	/**
	 * Support card.
	 *
	 * @param string $url   Support destination.
	 * @param string $body  Optional. Overrides the default invitation.
	 * @param string $label Optional. Button label. Default 'Get help'.
	 * @return self
	 */
	public function support( string $url, string $body = '', string $label = '' ): self {
		return $this->push(
			array(
				'type'  => 'support',
				'title' => __( 'Something not right?', 'coffeewp-admin-promo' ),
				'body'  => $body ?: __( "Send it over and we'll take a look.", 'coffeewp-admin-promo' ),
				'url'   => $url,
				'label' => $label ?: __( 'Get help', 'coffeewp-admin-promo' ),
			)
		);
	}

	/**
	 * Cross-plugin card — a list of your other plugins, plus a catch-all link.
	 *
	 * Entries carry no free/paid label: the distinction is the store's job,
	 * and a badge here only invites the reader to filter the list before
	 * they have read it.
	 *
	 * @param array  $plugins  List of ['name', 'url'].
	 * @param string $all_url  Optional. "Browse all" destination.
	 * @param string $title    Optional. Card heading.
	 * @return self
	 */
	public function family( array $plugins, string $all_url = '', string $title = '' ): self {
		return $this->push(
			array(
				'type'    => 'family',
				'title'   => $title ?: __( "Awesome plugins you'll like", 'coffeewp-admin-promo' ),
				'plugins' => $plugins,
				'url'     => $all_url,
				'label'   => __( 'Browse all plugins', 'coffeewp-admin-promo' ),
			)
		);
	}

	/**
	 * Licence status card — Pro builds only.
	 *
	 * @param string $masked Masked key, e.g. '····-····-8F2C'.
	 * @param string $status Human status, e.g. 'Active'.
	 * @param string $state  One of 'ok', 'warn', 'bad'. Drives the pill colour.
	 * @param string $note   Optional. Renewal date or quota note.
	 * @return self
	 */
	public function license( string $masked, string $status, string $state = 'ok', string $note = '' ): self {
		return $this->push(
			array(
				'type'   => 'license',
				'title'  => __( 'Pro license', 'coffeewp-admin-promo' ),
				'masked' => $masked,
				'status' => $status,
				'state'  => in_array( $state, array( 'ok', 'warn', 'bad' ), true ) ? $state : 'ok',
				'note'   => $note,

				'accent' => ( 'ok' === $state ) ? 'purple' : 'red',
			)
		);
	}

	/**
	 * Byline strip — a quiet footer, no button.
	 *
	 * @param string $name Vendor name.
	 * @param string $url  Vendor site.
	 * @return self
	 */
	public function byline( string $name, string $url ): self {
		return $this->push(
			array(
				'type' => 'byline',
				'name' => $name,
				'url'  => $url,
			)
		);
	}

	/**
	 * Escape hatch — any card the named helpers don't cover.
	 *
	 * @param array $card {
	 *     @type string $title  Heading.
	 *     @type string $body   Optional. Paragraph text.
	 *     @type array  $bullets Optional. Bullet list.
	 *     @type string $url    Optional. Button destination.
	 *     @type string $label  Optional. Button label.
	 *     @type string $accent Optional. Palette name or hex.
	 *     @type bool   $wide   Optional. Full-width button. Default false.
	 * }
	 * @return self
	 */
	public function card( array $card ): self {
		$card['type'] = 'card';

		return $this->push( $card );
	}

	/**
	 * Override the default colour for every card that has not set its own.
	 *
	 * @param string $name Palette name or hex.
	 * @return self
	 */
	public function accent( string $name ): self {
		$this->default_accent = $name;

		return $this;
	}

	/** @var string */
	private $default_accent = '';

	/**
	 * Register the rail so templates can fetch it, and wire the stylesheet.
	 *
	 * @return self
	 */
	public function register(): self {
		self::$rails[ $this->ns ] = $this;

		if ( ! $this->registered ) {
			$this->registered = true;
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		}

		return $this;
	}

	/**
	 * Enqueue the inline stylesheet on this rail's screens.
	 *
	 * The CSS is inlined rather than served as a file: the package can sit at
	 * any depth inside a host plugin, and a Composer-style vendor path cannot
	 * be turned into a public URL reliably. wp_register_style() with a false
	 * src gives wp_add_inline_style() a handle to attach to.
	 *
	 * @param string $hook Current admin screen id.
	 * @return void
	 */
	public function enqueue( $hook ) {
		if ( $this->screens && ! in_array( $hook, $this->screens, true ) ) {
			return;
		}

		$handle = 'coffeewp-admin-promo';

		if ( ! wp_style_is( $handle, 'registered' ) ) {
			wp_register_style( $handle, false, array(), '1.0.0' );
			wp_enqueue_style( $handle );
			wp_add_inline_style( $handle, self::css() );
		}
	}

	/**
	 * Render the card stack. Echoes.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! $this->cards ) {
			return;
		}

		$cards = apply_filters( "coffeewp_promo_cards_{$this->ns}", $this->cards, $this->ns );

		echo '<div class="cwp-promo-rail">';

		foreach ( $cards as $card ) {

			echo Renderer::card( $card, $this->default_accent ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		echo '</div>';
	}

	/**
	 * Render the card stack to a string.
	 *
	 * @return string
	 */
	public function to_html(): string {
		ob_start();
		$this->render();

		return (string) ob_get_clean();
	}

	/**
	 * The stylesheet, read from disk beside this package.
	 *
	 * @return string
	 */
	public static function css(): string {
		$path = __DIR__ . '/../assets/promo.css';

		return is_readable( $path ) ? (string) file_get_contents( $path ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	}

	/**
	 * @param array $card Normalised card.
	 * @return self
	 */
	private function push( array $card ): self {
		$this->cards[] = $card;

		return $this;
	}
}
