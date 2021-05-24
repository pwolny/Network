<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Network\EntryPoints;

use MediaWiki\Extension\Network\Extension;
use MediaWiki\Extension\Network\NetworkFunction\NetworkConfig;
use MediaWiki\Extension\Network\NetworkFunction\NetworkPresenter;
use MediaWiki\Extension\Network\NetworkFunction\NetworkUseCase;
use MediaWiki\Extension\Network\NetworkFunction\RequestModel;
use Parser;

class NetworkFunction {

	/**
	 * @var NetworkConfig
	 */
	private $config;

	public function __construct( NetworkConfig $config ) {
		$this->config = $config;
	}

	public static function onParserFirstCallInit( Parser $parser ): void {
		$parser->setFunctionHook(
			'network',
			function() {
				return ( new self( new NetworkConfig() ) )->handleParserFunctionCall( ...func_get_args() );
			}
		);
	}

	/**
	 * @param Parser $parser
	 * @param string[] ...$arguments
	 * @return array|string
	 */
	public function handleParserFunctionCall( Parser $parser, ...$arguments ) {
		$parser->getOutput()->addModules( [ 'ext.network' ] );
		$parser->getOutput()->addJsConfigVars( 'networkExcludedNamespaces', $this->config->getExcludedNamespaces() );
		$parser->getOutput()->addJsConfigVars( 'networkExcludeTalkPages', $this->config->getExcludeTalkPages() );

		$requestModel = new RequestModel();
		$requestModel->functionArguments = $arguments;
		$requestModel->defaultEnableDisplayTitle = $this->config->getDefaultEnableDisplayTitle();
		$requestModel->defaultLabelMaxLength = $this->config->getDefaultLabelMaxLength();

		/**
		 * @psalm-suppress PossiblyNullReference
		 */
		$requestModel->renderingPageName = $parser->getTitle()->getFullText();
		$presenter = Extension::getFactory()->newNetworkPresenter();

		$this->newUseCase( $presenter, $this->config )->run( $requestModel );

		return $presenter->getParserFunctionReturnValue();
	}

	private function newUseCase( NetworkPresenter $presenter, NetworkConfig $config ): NetworkUseCase {
		return Extension::getFactory()->newNetworkFunction( $presenter, $config );
	}

}
