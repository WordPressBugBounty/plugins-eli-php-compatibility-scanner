<?php
/**
 * Named colours from the CoffeeWP store theme.
 *
 * Every entry is a pair. The brand hex draws the card's left edge and its
 * title; a darkened step fills the button. The split exists because only
 * PURPLE clears WCAG AA (4.5:1) against white button text at full strength —
 * the rest would fail as a button fill, so the brand colour stays where it is
 * most visible and drops a few steps where text sits on top of it.
 *
 * Measured contrast of white text on each fill:
 *   primary   4.60:1    secondary 4.59:1    purple 5.20:1
 *   amber     4.62:1    green     4.60:1    red    4.53:1
 *   orange    4.56:1
 *
 * @package CoffeeWP\AdminPromo
 */

namespace CoffeeWP\AdminPromo;

class Palette {

	/** Store primary. */
	const PRIMARY = array( '#12b6e5', '#0d7fa0' );

	/** Store secondary. */
	const SECONDARY = array( '#e8929e', '#a0656d' );

	/** Passes AA unmodified — no darkened step needed. */
	const PURPLE = array( '#7b5fa7', '#7b5fa7' );

	const AMBER = array( '#e5a317', '#996d0f' );

	const ORANGE = array( '#fc7528', '#be581e' );

	const GREEN = array( '#0ba959', '#098747' );

	/**
	 * Reserved for states that need acting on — a lapsing licence, a failed
	 * scan. Keep it out of the standing cards so it keeps meaning something.
	 */
	const RED = array( '#ed213d', '#e6203b' );

	/**
	 * Default colour per card type.
	 *
	 * @var array<string, string>
	 */
	const ROLES = array(
		'upgrade' => 'purple',
		'license' => 'purple',
		'review'  => 'green',
		'support' => 'orange',
		'family'  => 'primary',
		'byline'  => 'secondary',
		'card'    => 'primary',
	);

	/** Neutral pair — grey edge, but a usable button fill if a card asks for one. */
	const NEUTRAL = array( '#c3c4c7', '#50575e' );

	/**
	 * Resolve a colour name — or a raw hex — to its [stripe, fill] pair.
	 *
	 * Unknown names fall back to NEUTRAL rather than throwing: a mistyped
	 * colour should not take an admin screen down. A raw hex is accepted and
	 * used for both stripe and fill, on the caller's judgement.
	 *
	 * @param string $name Palette name ('purple') or hex ('#7b5fa7').
	 * @return array{0:string,1:string} [stripe, fill]
	 */
	public static function resolve( string $name ): array {
		$name = trim( $name );

		if ( '' !== $name && '#' === $name[0] ) {
			return self::is_hex( $name ) ? array( $name, $name ) : self::NEUTRAL;
		}

		$const = __CLASS__ . '::' . strtoupper( $name );

		return defined( $const ) ? constant( $const ) : self::NEUTRAL;
	}

	/**
	 * Default colour name for a card type.
	 *
	 * @param string $type Card type key.
	 * @return string
	 */
	public static function for_role( string $type ): string {
		return self::ROLES[ $type ] ?? 'primary';
	}

	/**
	 * All selectable names, for docs and tooling.
	 *
	 * @return string[]
	 */
	public static function names(): array {
		return array( 'primary', 'secondary', 'purple', 'amber', 'orange', 'green', 'red', 'neutral' );
	}

	/**
	 * @param string $value Candidate hex string.
	 * @return bool
	 */
	private static function is_hex( string $value ): bool {
		return (bool) preg_match( '/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value );
	}
}
