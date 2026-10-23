<?php
/**
 * Card markup.
 *
 * Every value that reaches the page goes through an escaping function here —
 * callers pass plain strings and never markup. The one exception is the
 * bullet/body text of a generic card, which allows the small tag set
 * wp_kses_post permits so a host plugin can emphasise a word or link a phrase.
 *
 * @package CoffeeWP\AdminPromo
 */

namespace CoffeeWP\AdminPromo;

class Renderer {

	/**
	 * Render one card.
	 *
	 * @param array  $card           Normalised card from Rail.
	 * @param string $default_accent Rail-level accent name, may be empty.
	 * @return string
	 */
	public static function card( array $card, string $default_accent = '' ): string {
		$type = $card['type'] ?? 'card';

		$name = $card['accent']
			?? ( $default_accent ?: Palette::for_role( $type ) );

		list( $stripe, $fill ) = Palette::resolve( (string) $name );

		$style = sprintf( '--cwp-stripe:%s;--cwp-fill:%s', $stripe, $fill );

		$body = self::body( $type, $card );

		if ( '' === $body ) {
			return '';
		}

		return sprintf(
			'<div class="cwp-promo cwp-promo--%1$s" style="%2$s">%3$s</div>',
			esc_attr( $type ),
			esc_attr( $style ),
			$body
		);
	}

	/**
	 * @param string $type Card type.
	 * @param array  $card Card data.
	 * @return string
	 */
	private static function body( string $type, array $card ): string {
		switch ( $type ) {
			case 'upgrade':
				return self::heading( $card )
					. self::bullets( $card['features'] ?? array() )
					. self::button( $card, true );

			case 'review':
				return self::heading( $card )
					. self::split_row( $card, ! empty( $card['stars'] ) );

			case 'support':
				return self::heading( $card )
					. self::split_row( $card, false );

			case 'family':
				return self::heading( $card )
					. self::plugin_list( $card['plugins'] ?? array() )
					. ( empty( $card['url'] ) ? '' : self::button( $card, true ) );

			case 'license':
				return self::heading( $card ) . self::license_body( $card );

			case 'byline':
				return self::byline( $card );

			default:
				return self::heading( $card )
					. self::paragraph( $card['body'] ?? '' )
					. self::bullets( $card['bullets'] ?? array() )
					. ( empty( $card['url'] ) ? '' : self::button( $card, ! empty( $card['wide'] ) ) );
		}
	}

	/**
	 * @param array $card Card data.
	 * @return string
	 */
	private static function heading( array $card ): string {
		$title = (string) ( $card['title'] ?? '' );

		return '' === $title
			? ''
			: '<div class="cwp-promo__title">' . esc_html( $title ) . '</div>';
	}

	/**
	 * @param string $text Paragraph text.
	 * @return string
	 */
	private static function paragraph( string $text ): string {
		return '' === $text ? '' : '<p>' . wp_kses_post( $text ) . '</p>';
	}

	/**
	 * @param array $items Bullet strings.
	 * @return string
	 */
	private static function bullets( array $items ): string {
		if ( ! $items ) {
			return '';
		}

		$out = '<ul class="cwp-promo__list">';

		foreach ( $items as $item ) {
			$out .= '<li>' . wp_kses_post( (string) $item ) . '</li>';
		}

		return $out . '</ul>';
	}

	/**
	 * Text left, button locked right — the layout the cards are built around.
	 *
	 * @param array $card  Card data.
	 * @param bool  $stars Append a five-star run.
	 * @return string
	 */
	private static function split_row( array $card, bool $stars ): string {
		$text = wp_kses_post( (string) ( $card['body'] ?? '' ) );

		if ( $stars ) {
			$text .= ' <span class="cwp-promo__stars" aria-hidden="true">'
				. str_repeat( '&#9733;', 5 )
				. '</span>';
		}

		return '<div class="cwp-promo__row">'
			. '<div class="cwp-promo__text">' . $text . '</div>'
			. self::button( $card, false )
			. '</div>';
	}

	/**
	 * @param array $card Card data.
	 * @param bool  $wide Full-width button.
	 * @return string
	 */
	private static function button( array $card, bool $wide ): string {
		$url   = (string) ( $card['url'] ?? '' );
		$label = (string) ( $card['label'] ?? '' );

		if ( '' === $url || '' === $label ) {
			return '';
		}

		return sprintf(
			'<a class="cwp-promo__btn%1$s" href="%2$s" target="_blank" rel="noopener noreferrer">%3$s<span class="screen-reader-text"> %4$s</span></a>',
			$wide ? ' is-wide' : '',
			esc_url( $url ),
			esc_html( $label ),
			esc_html__( '(opens in a new tab)', 'coffeewp-admin-promo' )
		);
	}

	/**
	 * @param array $plugins List of [name, url].
	 * @return string
	 */
	private static function plugin_list( array $plugins ): string {
		if ( ! $plugins ) {
			return '';
		}

		$out = '<ul class="cwp-promo__plugins">';

		foreach ( $plugins as $plugin ) {
			$name = (string) ( $plugin[0] ?? '' );
			$url  = (string) ( $plugin[1] ?? '' );

			if ( '' === $name || '' === $url ) {
				continue;
			}

			$out .= '<li><a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">'
				. esc_html( $name ) . '</a></li>';
		}

		return $out . '</ul>';
	}

	/**
	 * @param array $card Card data.
	 * @return string
	 */
	private static function license_body( array $card ): string {
		$out = '<div class="cwp-promo__license">'
			. '<span class="cwp-promo__key">' . esc_html( (string) ( $card['masked'] ?? '' ) ) . '</span>'
			. '<span class="cwp-promo__pill is-' . esc_attr( (string) ( $card['state'] ?? 'ok' ) ) . '">'
			. esc_html( (string) ( $card['status'] ?? '' ) )
			. '</span></div>';

		$note = (string) ( $card['note'] ?? '' );

		if ( '' !== $note ) {
			$out .= '<p class="cwp-promo__note">' . esc_html( $note ) . '</p>';
		}

		return $out;
	}

	/**
	 * @param array $card Card data.
	 * @return string
	 */
	private static function byline( array $card ): string {
		$name = (string) ( $card['name'] ?? '' );
		$url  = (string) ( $card['url'] ?? '' );

		if ( '' === $name || '' === $url ) {
			return '';
		}

		$host = wp_parse_url( $url, PHP_URL_HOST );
		$host = is_string( $host ) ? preg_replace( '/^www\./', '', $host ) : '';

		return '<div class="cwp-promo__row cwp-promo__row--byline">'
			. '<div class="cwp-promo__text">' . sprintf(
				/* translators: %s: vendor name. */
				esc_html__( 'Brewed by %s', 'coffeewp-admin-promo' ),
				esc_html( $name )
			) . '</div>'
			. self::button(
				array(
					'url'   => $url,
					'label' => '' !== $host ? $host : $name,
				),
				false
			)
			. '</div>';
	}
}
