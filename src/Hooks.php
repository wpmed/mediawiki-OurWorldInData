<?php

namespace MediaWiki\Extension\OurWorldInData;

use Config;
use Html;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\Utils\UrlUtils;
use MWException;
use Parser;
use PPFrame;

class Hooks implements ParserFirstCallInitHook {
	/** @var Config Main config */
	/** @var UrlUtils url parsing utility */
	private Config $config;
	private UrlUtils $urlUtils;

	/**
	 * @param Config $config Main config
	 * @param UrlUtils $urlUtils url parsing utility
	 */
	public function __construct( Config $config, UrlUtils $urlUtils ) {
		$this->config = $config;
		$this->urlUtils = $urlUtils;
	}

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
	 * @param string $input url to embed
	 * @param array $args Arguments in tag are appended to arguments in url
	 * @param Parser $parser Parser
	 * @param PPFrame $frame Parser Frame
	 * @return string|array HTML that will not be processed further
	 */
	public function renderOurWorldInData( string $input, array $args, Parser $parser, PPFrame $frame ) {
		// for now trailing descriptive text is not expected so just trim
		// $inp_parts = preg_split( "/\\s/", $input );
		// $graph_url = $inp_parts[0];
		$graph_url = trim( $input );
		$url_parts = $this->urlUtils->parse( $graph_url );
		// N.B parsing returns null if no protocol as per comment in source code
		if ( $url_parts === NULL ) {
			$url_parts = $this->urlUtils->parse( 'http:' . $graph_url );
		}

		if ( preg_match( ',^/grapher/(.*)$,', $url_parts['path'], $matches ) ) {
				$graph_path = $matches[1];
		}
		else {
				$graph_path = $url_parts['path'];
		}

		// from LocalSettings.php not the extension.json
		$urlPattern = $this->config->get( 'OurWorldInDataUrl' );
		$baseUrl = str_replace( '$1', rawurlencode( $graph_path ), $urlPattern );

		// merge any query parameters in tag with any in url
		// N.B. those in the tag will prevail if there are duplicates
		// So use the tag to override the url parameters from the source
		if ( isset( $url_parts['query'] ) ) {
			// $query_array = array_merge( $args, wfCgiToArray( $url_parts['query'] ) );
			$query_array = array_merge( wfCgiToArray( $url_parts['query'] ), $args );
		}
		else {
			$query_array = $args;
		}
		// add query to url
		$url = wfAppendQuery( $baseUrl, $query_array );

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
