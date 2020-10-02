<?php

namespace SESP\Tests\PropertyAnnotators;

use SESP\PropertyAnnotators\RedirectedFromPropertyAnnotator;
use SMW\DIProperty;
use SMWDIString as DIString;


/**
 * @covers \SESP\PropertyAnnotators\RedirectedFromPropertyAnnotator
 * @group semantic-extra-special-properties
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author Sebastian Schmid (gesinn.it GmbH & Co. KG)
 */
class RedirectedFromPropertyAnnotatorTest extends \PHPUnit_Framework_TestCase {

	private $property;
	private $appFactory;

	protected function setUp() {
		parent::setUp();

		$this->appFactory = $this->getMockBuilder( '\SESP\AppFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->property = new DIProperty( '___REDIFROM' );
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			RedirectedFromPropertyAnnotator::class,
			new RedirectedFromPropertyAnnotator( $this->appFactory )
		);
	}

	public function testIsAnnotatorFor() {

		$instance = new RedirectedFromPropertyAnnotator(
			$this->appFactory
		);

		$this->assertTrue(
			$instance->isAnnotatorFor( $this->property )
		);
	}

	public function testAddAnnotation() {

		$redirect = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$redirect->expects( $this->once() )
			->method( 'getPrefixedDBkey' )
			->will( $this->returnValue( new DIString( "UnitTest" ) ) );

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getRedirectsHere' )
			->will( $this->returnValue( [$redirect] ) );

		$title->expects( $this->once() )
			->method( 'isRedirect' )
			->will( $this->returnValue( false ) );

		$subject = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$subject->expects( $this->once() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getSubject' )
			->will( $this->returnValue( $subject ) );

		$semanticData->expects( $this->once() )
			->method( 'addPropertyObjectValue' )
			->with(
				$this->equalTo( $this->property ),
				$this->equalTo( new DIString( "UnitTest" ) ) );

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->setMethods( [ 'getTitle' ] )
			->getMock();

		$this->appFactory->expects( $this->once() )
			->method( 'newWikiPage' )
			->will( $this->returnValue( $wikiPage ) );

		$wikiPage->expects( $this->once() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$annotator = new RedirectedFromPropertyAnnotator(
			$this->appFactory
		);

		$annotator->addAnnotation( $this->property, $semanticData );
	}
}
