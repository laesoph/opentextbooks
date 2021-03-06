<?php
/**
 * Project: opentextbooks
 * Project Sponsor: BCcampus <https://bccampus.ca>
 * Copyright 2012-2016 Brad Payne <https://bradpayne.ca>
 * Date: 2016-05-31
 * Licensed under GPLv3, or any later version
 *
 * @author Brad Payne
 * @package OPENTEXTBOOKS
 * @license https://www.gnu.org/licenses/gpl-3.0.txt
 * @copyright (c) 2012-2016, Brad Payne
 */

namespace BCcampus\OpenTextBooks\Controllers\Sitemap;

use BCcampus\OpenTextBooks\Polymorphism;
use BCcampus\OpenTextBooks\Controllers\Catalogue;
use BCcampus\OpenTextBooks\Models;

/**
 * Description of Textbooks
 *
 * @author bpayne
 */
class Textbooks extends Polymorphism\SitemapAbstract {

	protected $domain = 'https://open.bccampus.ca/';
	protected $directory = 'find-open-textbooks/';
	protected $now;

	/**
	 * @return string
	 */
	protected function getXmlItemBody() {
		$this->setCurrentTime();
		$xmlbody = '';
		$r       = $this->getResults();

		if ( is_array( $r ) ) {

			foreach ( $r as $item ) {
				$freq = $this->calcChangeFreq( $item['modifiedDate'], $this->now );

				$xmlbody .= "\t<url>" . "\n";
				$xmlbody .= "\t\t<loc>{$this->domain}{$this->directory}?uuid={$item['uuid']}</loc>" . "\n";
				$xmlbody .= "\t\t<lastmod>{$item['modifiedDate']}</lastmod>" . "\n";
				$xmlbody .= "\t\t<changefreq>{$freq}</changefreq>" . "\n";
				$xmlbody .= "\t\t<priority>{$this->calcPriority( $freq )}</priority>" . "\n";
				$xmlbody .= "\t</url>" . "\n";

			}
		}

		return $xmlbody;

	}

	/**
	 * @return array|string
	 */
	protected function getResults() {
		$env                    = include( OTB_DIR . '.env.php' );
		$args['collectionUuid'] = $env['solr']['UUID'];
		$rest_api               = new Models\EquellaApi();
		$data                   = new Models\OtbBooks( $rest_api, $args );

		$results = $data->getPrunedResults();

		if ( $results ) {
			return $results;
		} else {
			return '';
		}
	}

	/**
	 *
	 */
	protected function setCurrentTime() {
		$this->now = time();
	}


}
