<?php

namespace Citation;
use Html;

/**
 * The String objects that holds the actual quote.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.1
 *
 * @file
 * @ingroup Citation
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 */
class Quote {

	public $quotes;

	public function __construct( array $quotes = array() ) {
		$this->quotes = $quotes;
	}

	public function validate( array $fragments, array $opts = array() ) {
		$opts = array_merge( array( 'initial' => 140, 'final' => 140, 'middle' => 70 ), $opts );
		$initial = '.{0,' . $opts{'initial'} . '}';
		$middle = '.{0,' . $opts{'middle'} . '}';
		$final = '.{0,' . $opts{'final'} . '}';

		foreach ( $this->quotes as $quote ) {
			if ( preg_replace( '/\s*\[.*?\]\s*/', '', $quote ) === '' ) {
				return null;
			}
		}
		$patterns = array();
		foreach ( $this->quotes as $quote ) {
			$escaped = preg_quote( $quote, '/' );
			$escaped = preg_replace( '/^\s*\\\\\[.*?\\\\\]\s*|^\s*/', $initial, $escaped );
			$escaped = preg_replace( '/\s*\\\\\[.*?\\\\\]\s*[.,:;!?]?$|\s*[.,:;!?]?$/', $final, $escaped );
			$escaped = preg_replace( '/\s*\\\\\[.*?\\\\\]\s*/', $middle, $escaped );
			$patterns[] = $escaped;
		}
		$pattern = '/' . implode( '|', $patterns ) . '/';
		$valid = array();
		foreach ( $fragments as $str ) {
			$valid[] = preg_match( $pattern, $str, $matches ) > 0 ? $matches[0] : false;
		}
		return $valid;
	}

	public function getQuotes() {
		return $this->quotes;
	}

	public static function buildArgArrays( array $args = array(), $signature = '' ) {

		$quotes = array();
		$params = array( 'signature' => $signature );

		foreach ( $args as $arg ) {

			$arg = preg_replace( '/^\s+|\s+$/', '', $arg );
			$parts = preg_split( '/\s*=\s*/', $arg, 2 );

			if ( count( $parts ) === 1 ) {
				switch ( $arg ) {
				case 'block':
					$params['format'] = $arg;
					break;
				case 'inline':
					$params['format'] = $arg;
					break;
				default:
					$quotes[] = $arg;
					break;
				}
			}
			switch ( $parts[0] ) {
			case 'href':
				$params['href'] = $parts[1];
				break;
			case 'src':
				$params['src'] = $parts[1];
				break;
			}
		}

		if ( !array_key_exists( 'href', $params ) && array_key_exists( 'src', $params ) ) {
			$params['href'] = $params['src'];
		}
		if ( !array_key_exists( 'format', $params ) ) {
			$params['format'] = 'block';
		}

		$params['quote'] = new \Citation\Quote( $quotes );
		$params['signature'] = $signature;

		return $params;
	}

	/**
	 * Handler for "quote" parser function
	 *
	 * @param \Parser &$parser
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	public static function handler( \Parser &$parser ) {
		global $wgMemc;

		$args = func_get_args();
		array_shift( $args );

		$key = call_user_func_array( 'wfMemcKey', $args);
		$params = static::buildArgArrays( $args, $key );

		$decorator = new \Citation\Decorator();

		if ( !array_key_exists( 'src', $params ) ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ": no source" );
			return $decorator->format( $params );
		}

		$previous = $wgMemc->get( $key );

		if( $previous !== false ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ": cached source" );
			$parser->getOutput()->setProperty( $key, $previous );
			return $decorator->format( $params, $previous );
		}

		if ( defined( 'CITATION_DELAYED_VALIDATION' ) && CITATION_DELAYED_VALIDATION === true ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ": pending source" );
			$job = new \Citation\Job\ValidationJob(
				$parser->getTitle(),
				$params
			);
			$status = \JobQueueGroup::singleton()->push( $job );
			//\JobQueueGroup::singleton()->deduplicateRootJob( $job );

			$previous = $parser->getOutput()->getProperty( $key );
			return $decorator->format( $params, $previous === false ? array() : $previous ); // TODO: should be given "pending-validation"
		}
		else {
			wfDebugLog( __CLASS__, __FUNCTION__ . ": new source" );
			$validation = new \Citation\Job\Validation(
				$parser->getTitle(),
				$params
			);
			$status = $validation->execute( $parser->getTitle() );

			$previous = $wgMemc->get( $key );
			return $decorator->format( $params, $previous === false ? array() : $previous );
		}

	}

}
