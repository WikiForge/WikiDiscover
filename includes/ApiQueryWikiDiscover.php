<?php

use MediaWiki\MediaWikiServices;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\ParamValidator\TypeDef\IntegerDef;
use Wikimedia\Rdbms\IDatabase;

class ApiQueryWikiDiscover extends ApiQueryGeneratorBase {

	/**
	 * @param ApiQuery $query
	 * @param string $moduleName Name of this module
	 */
	public function __construct( $query, $moduleName ) {
		parent::__construct( $query, $moduleName, 'wd' );
	}

	public function execute() {
		$this->run();
	}

	/** @inheritDoc */
	public function getCacheMode( $params ) {
		return 'public';
	}

	/**
	 * @param ApiPageSet $resultPageSet
	 */
	public function executeGenerator( ApiPageSet $resultPageSet ) {
		$this->run( $resultPageSet );
	}

	/**
	 * Get the Query database connection (read-only)
	 *
	 * @see ApiQueryBase::getDB
	 * @return IDatabase
	 */
	protected function getDB() {
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$lbFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();

		return $lbFactory->getMainLB( $config->get( 'CreateWikiDatabase' ) )
			->getMaintenanceConnectionRef( DB_REPLICA, [], $config->get( 'CreateWikiDatabase' ) );
	}

	/**
	 * @param ApiPageSet|null $resultPageSet
	 */
	private function run( ApiPageSet $resultPageSet = null ) {
		$params = $this->extractRequestParams();

		$state = $params['state'];
		$siteprop = $params['siteprop'];
		$limit = $params['limit'];
		$wikislist = $params['wikislist'];

		$this->addTables( 'cw_wikis' );

		if ( $state !== 'all' ) {
			if ( in_array( 'closed', $state ) ) {
				$this->addWhereFld( 'wiki_closed', 1 );
			}

			if ( in_array( 'inactive', $state ) ) {
				$this->addWhereFld( 'wiki_inactive', 1 );
			}

			if ( in_array( 'active', $state ) ) {
				$this->addWhere( 'wiki_closed = 0 AND wiki_inactive = 0' );
			}

			if ( in_array( 'private', $state ) ) {
				$this->addWhereFld( 'wiki_private', 1 );
			}

			if ( in_array( 'public', $state ) ) {
				$this->addWhereFld( 'wiki_private', 0 );
			}

			if ( in_array( 'deleted', $state ) ) {
				$this->addWhereFld( 'wiki_deleted', 1 );
			}
		}

		$this->addFieldsIf( 'wiki_url', in_array( 'url', $siteprop ) );
		$this->addFieldsIf( 'wiki_dbname', in_array( 'dbname', $siteprop ) );
		$this->addFieldsIf( 'wiki_sitename', in_array( 'sitename', $siteprop ) );
		$this->addFieldsIf( 'wiki_language', in_array( 'languagecode', $siteprop ) );

		$this->addOption( 'LIMIT', $limit );

		if ( $wikislist ) {
			$this->addWhereFld( 'wiki_dbname', explode( ',', $wikislist ) );
		}
	}

	/** @inheritDoc */
	protected function getAllowedParams() {
		return [
			'state' => [
				ParamValidator::PARAM_ISMULTI => true,
				ParamValidator::PARAM_TYPE => [
					'all',
					'closed',
					'inactive',
					'active',
					'private',
					'public',
					'deleted'
				],
				ParamValidator::PARAM_DEFAULT => 'all',
			],
			'siteprop' => [
				ParamValidator::PARAM_ISMULTI => true,
				ParamValidator::PARAM_TYPE => [
					'url',
					'dbname',
					'sitename',
					'languagecode',
				],
				ParamValidator::PARAM_DEFAULT => 'url|dbname|sitename|languagecode',
			],
			'limit' => [
				ParamValidator::PARAM_TYPE => 'limit',
				IntegerDef::PARAM_MIN => 1,
				IntegerDef::PARAM_MAX => 5000,
				IntegerDef::PARAM_MAX2 => 5000,
				ParamValidator::PARAM_DEFAULT => 5000,
			],
			'wikislist' => [
				ParamValidator::PARAM_TYPE => 'string',
			],
		];
	}

	/** @inheritDoc */
	protected function getExamplesMessages() {
		return [
			'action=wikidiscover' => 'apihelp-wikidiscover-example'
		];
	}
}