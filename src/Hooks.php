<?php

namespace OurWorldInData;

use Html;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MWException;
use Parser;
use PPFrame;

class Hooks implements ParserFirstCallInitHook {
	/**
	 * Register the &lt;ourworldindata&gt; tag
	 *
	 * @param Parser $parser Parser
	 * @throws MWException On error
	 */
	public function onParserFirstCallInit( $parser ) {
		$parser->setHook( 'ourworldindata', [ $this, 'renderOurWorldInData' ] );
	}

	/**
	 * Handler for the &lt;ourworldindata&gt; tag
	 *
	 * @param string $input Dataset to embed
	 * @param array $args Arguments passed to dataset
	 * @param Parser $parser Parser
	 * @param PPFrame $frame Parser Frame
	 * @return array HTML that will not be processed further
	 */
	public function renderOurWorldInData( string $input, array $args, Parser $parser, PPFrame $frame ) {
		// do some light sanitization
		$sanInput = rawurlencode( $input );
		$sanArgs = [];
		foreach ( $args as $key => $value ) {
			$sanArgs[urlencode( $key )] = urlencode( $value );
		}

		$url = "https://ourworldindata.org/grapher/{$sanInput}?" . http_build_query( $sanArgs );
		$parser->getOutput()->addModuleStyles( [ 'ext.owid' ] );
		return [
			Html::element(
				'iframe',
				[
					'src' => $url,
					'loading' => 'lazy',
					'class' => 'owid-frame'
				]
			),
			'markerType' => 'nowiki'
		];
	}
}
