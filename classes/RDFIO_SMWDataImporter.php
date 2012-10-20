<?php

class RDFIOSMWDataImporter {

	public function __construct() {
		// ...
	}

	public function import( $wikiPages ) {

		foreach ( $wikiPages as $wikiTitle => $wikiPage ) {
			$facts = $wikiPage['facts'];
			$equivuris = $wikiPage['equivuris'];
			
			# Populate the facts array also with the equivalent URI "facts"
			foreach ( $equivuris as $equivuri ) {
				$facts[] = array( 'p' => "Equivalent URI", 'o' => $equivuri );
			}
			
			$mwTitleObj = Title::newFromText( $wikiTitle );

			if ( !$mwTitleObj->exists() ) {
				$this->createStubArticle( $mwTitleObj );
			} 
			
			$womWikiPage = WOMProcessor::getPageObject( $mwTitleObj );
			
			$womPropertyObjs = array();
			try{
				$objIds = WOMProcessor::getObjIdByXPath( $mwTitleObj, '//property' );
				// use page object functions
				foreach ( $objIds as $objId ) {
					$womPropertyObj = $womWikiPage->getObject( $objId );
					$womPropertyName = $womPropertyObj->getPropertyName();
					$womPropertyObjs[$womPropertyName] = $womPropertyObj;
				}
			} catch( Exception $e ) {
				// @TODO Take better care of this?
				// echo( '<pre>Exception when talking to WOM: ' . $e->getMessage() . '</pre>' ); 
			}
			
			$newPropertiesAsWikiText = "\n";

			foreach ( $facts as $fact ) {
				$pred = $fact['p'];
				$obj = $fact['o'];

				if ( array_key_exists( $pred, $womPropertyObjs ) ) {
					$womPropertyObj = $womPropertyObjs[$pred];
					$objTitle = Title::newFromText( $obj );
					$newSMWPageValue = SMWWikiPageValue::makePageFromTitle( $objTitle );
					$womPropertyObj->setSMWDataValue( $newSMWPageValue );
				} else {
					$newWomPropertyObj = new WOMPropertyModel( $pred, $obj, '' );

					$newPropertyAsWikiText = $newWomPropertyObj->getWikiText();
					$newPropertiesAsWikiText .= $newPropertyAsWikiText . "\n";
				}
			}			
			
			$mwArticleObj = new Article( $mwTitleObj );
			$content = $womWikiPage->getWikiText() . $newPropertiesAsWikiText;
			$summary = 'Update by RDFIO';
			$mwArticleObj->doEdit( $content, $summary );
		}
	}
	
	protected function createStubArticle( $mwTitleObj ) {
		$mwArticleObj = new Article( $mwTitleObj );
		$content = '';
		$summary = 'Page created by RDFIO';
		$mwArticleObj->doEdit( $content, $summary );
	}

}
