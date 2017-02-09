<?php

namespace Citation;

/**
 * Hook handlers for the Citation extension
 *
 * @ingroup Extensions
 */

class Hooks {

	/**
	 * Register the magic word
	 * @todo It is not clear why this is used. For now it is removed from the extension setup.
	 * @param string[] &$aCustomVariableIds
	 */
	public static function onMagicWordwgVariableIDs( array &$aCustomVariableIds ) {
		$aCustomVariableIds[] = 'quote';
		return true;
	}

	/**
	 * Setup for the extension
	 */
	public static function onExtensionSetup() {
		global $wgDebugComments;

		// turn on comments while in development
		$wgDebugComments = true;
	}

	/**
	 * Setup for the tests
	 * @param string[] $files
	 */
	public static function onUnitTestsList( array &$files ) {
		$files[] = __DIR__ . '/../tests/phpunit/';
		return true;
	}

	/**
	 * Register the quote tag function
	 * @param Parser $parser
	 */
	public static function onParserFirstCallInit( \Parser $parser ) {
		$parser->setHook( 'quote', 'Citation\\Quote::handler' );
		return true;
	}
}
