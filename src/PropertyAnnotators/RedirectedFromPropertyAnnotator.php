<?php

namespace SESP\PropertyAnnotators;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\SemanticData;
use SMWDataItem as DataItem;
use SMWDIString as DIString;
use SESP\PropertyAnnotator;
use SESP\AppFactory;
use SMW\SQLStore\PropertyTableDefinitionBuilder;
use SMW\ApplicationFactory;
use Wikimedia\Rdbms\Database;
use MediaWiki\MediaWikiServices;
use Title;
use WikiPage;
use Revision;
use ContentHandler;
use DataUpdate;

/**
 * @private
 * @ingroup SESP
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class RedirectedFromPropertyAnnotator implements PropertyAnnotator {

	/**
	 * Predefined property ID
	 */
	const PROP_ID = '___REDIFROM';

	/**
	 * @var AppFactory
	 */
	private $appFactory;

	/**
	 * @param AppFactory $appFactory
	 * @since 2.0
	 *
	 */
	public function __construct( AppFactory $appFactory ) {
		$this->appFactory = $appFactory;
	}

	/**
	 * @since 2.0
	 *
	 * {@inheritDoc}
	 */
	public function isAnnotatorFor( DIProperty $property ) {
		return $property->getKey() === self::PROP_ID;
	}

	/**
	 * @since 2.0
	 *
	 * {@inheritDoc}
	 */
	public function addAnnotation( DIProperty $property, SemanticData $semanticData ) {

		$dataItem = null;

		$page = $this->appFactory->newWikiPage( $semanticData->getSubject()->getTitle() );
		$title = $page->getTitle();

		if ( $title->isRedirect() ) {
			$this->dummyEdit( $page->getRedirectTarget() );
		}

		$redirects = $title->getRedirectsHere();

		foreach ( $redirects as $redirect ) {

			$dataItem = new DIString( $redirect->getPrefixedDBkey() );

			if ( $dataItem instanceof DataItem ) {
				$semanticData->addPropertyObjectValue( $property, $dataItem );
			}
		}
	}

	/**
	 * Save a null revision in the history of the target page
	 * to propagate the update of the property
	 *
	 * Consider calling doSecondaryDataUpdates() for MW 1.32+
	 * https://doc.wikimedia.org/mediawiki-core/master/php/classWikiPage.html#ac761e927ec2e7d95c9bb48aac60ff7c8
	 *
	 * @param Title $title
	 */
	private function dummyEdit( $title ) {
		$page = WikiPage::newFromID( $title->getArticleId() );
		if ( $page ) { // prevent NPE when page not found
			$content = $page->getContent( Revision::RAW );

			if ( $content ) {
				$text = ContentHandler::getContentText( $content );

				// since this is a null edit, the edit summary will be ignored.
				$page->doEditContent( ContentHandler::makeContent( $text, $page->getTitle() ), "[RedirectFromPropertyAnnotator] Null edit." );
				$page->doPurge();
			}
		}

	}
}
