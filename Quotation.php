<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'Quotation', __DIR__ . '/extension.json' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['Quotation'] = __DIR__ . '/i18n';

	// $wgExtensionMessagesFiles['QuotationMagic'] = __DIR__ . '/Quotation.i18n.magic.php';
	wfWarn(
		'Deprecated PHP entry point used for Quotation extension. Please use wfLoadExtension ' .
		'instead, see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return true;
} else {
	die( 'This version of the Quotation extension requires MediaWiki 1.25+' );
}
