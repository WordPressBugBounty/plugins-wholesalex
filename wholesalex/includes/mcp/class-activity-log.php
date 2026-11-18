<?php
/**
 * Activity log for the WholesaleX AI tools.
 *
 * @package WholesaleX
 * @since   3.2.0
 */

namespace WHOLESALEX\MCP;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-oauth-store.php';

/**
 * Records every change made through the AI tools and every refused attempt.
 *
 * The store owner sees the most recent entries on the AI Tools screen, and
 * every entry is also written to the WooCommerce log under the
 * "wholesalex-mcp" source. Entries hold the tool name, the WordPress user,
 * the affected record ID and the outcome. Tool input is never stored, so the
 * log cannot leak customer data or free text sent by an AI assistant.
 */
final class Activity_Log {

	/**
	 * Option holding the most recent entries. It is not autoloaded.
	 *
	 * @var string
	 */
	const OPTION = 'wholesalex_mcp_activity';

	/**
	 * Maximum number of stored entries.
	 *
	 * @var int
	 */
	const MAX_ENTRIES = 50;

	/**
	 * Outcome: the tool finished successfully.
	 *
	 * @var string
	 */
	const SUCCESS = 'success';

	/**
	 * Outcome: the tool ran and returned an error.
	 *
	 * @var string
	 */
	const ERROR = 'error';

	/**
	 * Outcome: the request was refused before the tool ran.
	 *
	 * @var string
	 */
	const DENIED = 'denied';

	/**
	 * Record one entry.
	 *
	 * @param string   $tool      Ability name, for example "wholesalex/create-role".
	 * @param string   $outcome   One of the SUCCESS, ERROR or DENIED constants.
	 * @param string   $object_id Affected record ID, when known.
	 * @param string   $code      Error code, when the outcome is not a success.
	 * @param int|null $user_id Who the entry is about. Defaults to the current user; pass an ID when no one is signed in, such as an app removing itself.
	 * @return void
	 */
	public static function record( string $tool, string $outcome, string $object_id = '', string $code = '', ?int $user_id = null ): void {
		$entry = array(
			'time'    => time(),
			'user_id' => null === $user_id ? get_current_user_id() : $user_id,
			'tool'    => sanitize_text_field( $tool ),
			'outcome' => in_array( $outcome, array( self::SUCCESS, self::ERROR, self::DENIED ), true ) ? $outcome : self::ERROR,
			'object'  => substr( sanitize_text_field( $object_id ), 0, 100 ),
			'code'    => substr( sanitize_key( $code ), 0, 100 ),
		);

		// Compare-and-swap, so two calls at the same moment cannot overwrite each other's entry.
		$skipped = false;
		$saved   = OAuth_Store::change_option(
			self::OPTION,
			static function ( array $entries ) use ( $entry, &$skipped ) {
				$skipped = false;
				$latest  = $entries[0] ?? null;

				// A client retrying the same refused or failed call is recorded once per five minutes.
				if ( is_array( $latest ) && self::SUCCESS !== $entry['outcome'] && $entry['time'] - (int) ( $latest['time'] ?? 0 ) < 300 ) {
					$same = true;
					foreach ( array( 'user_id', 'tool', 'outcome', 'object', 'code' ) as $key ) {
						$same = $same && (string) ( $latest[ $key ] ?? '' ) === (string) $entry[ $key ];
					}
					if ( $same ) {
						$skipped = true;
						return null;
					}
				}

				array_unshift( $entries, $entry );
				return array_slice( $entries, 0, self::MAX_ENTRIES );
			}
		);
		if ( $skipped || ! $saved ) {
			return;
		}

		if ( function_exists( 'wc_get_logger' ) ) {
			$message = sprintf(
				'%1$s %2$s by user #%3$d%4$s%5$s',
				$entry['tool'],
				$entry['outcome'],
				$entry['user_id'],
				'' !== $entry['object'] ? ' on ' . $entry['object'] : '',
				'' !== $entry['code'] ? ' (' . $entry['code'] . ')' : ''
			);
			$level   = self::SUCCESS === $entry['outcome'] ? 'info' : 'warning';
			wc_get_logger()->log( $level, $message, array( 'source' => 'wholesalex-mcp' ) );
		}
	}

	/**
	 * Return the most recent entries, newest first.
	 *
	 * @param int $limit Maximum number of entries.
	 * @return array[]
	 */
	public static function entries( int $limit = 20 ): array {
		$entries = get_option( self::OPTION, array() );
		$entries = is_array( $entries ) ? array_values( array_filter( $entries, 'is_array' ) ) : array();

		return array_slice( $entries, 0, max( 0, $limit ) );
	}

	/**
	 * Delete all stored entries.
	 *
	 * @return void
	 */
	public static function clear(): void {
		delete_option( self::OPTION );
	}
}
