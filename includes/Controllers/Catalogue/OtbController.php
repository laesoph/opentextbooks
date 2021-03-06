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

namespace BCcampus\OpenTextBooks\Controllers\Catalogue;

use BCcampus\OpenTextBooks\Views;
use BCcampus\OpenTextBooks\Models;


class OtbController {
	/**
	 * Needs at least this, or nothing works
	 * Some vars need to be defined to avoid warnings.
	 *
	 * @var array
	 */
	protected $defaultArgs = array(
		'type_of'        => '',
		'collectionUuid' => '',
		'start'          => '',
		'view'           => '',
		'search'         => '',
		'subject'        => '',
	);

	/**
	 * @var array
	 */
	private $args = array();

	/**
	 * @var array
	 */
	private $expected = [ 'books', 'book_stats', 'subject_stats' ];

	/**
	 * OtbController constructor.
	 *
	 * @param $args
	 */
	public function __construct( $args ) {

		// sanity check
		if ( ! is_array( $args ) ) {
			// TODO: add proper error handling
			new Views\Errors( [ 'msg' => 'Sorry, this does not pass the smell test' ] );
		}

		/**
		 * Control the view returned by passing:
		 *
		 * ?uuid=c6d0e9bd-ba6b-4548-82d6-afbd0f166b65
		 * ?subject=Biology
		 * ?subject=Biology&search=micro
		 * ?search=something
		 * ?search=something&keyword=true
		 * ?search=something&contributor=true
		 * ?lists=ancillary|adopted|reviews|accessible|titles
		 */
		$args_get = array(
			// Strips characters that have a numerical value >127.
			'uuid'        => array(
				'filter' => FILTER_SANITIZE_STRING,
				'flags'  => FILTER_FLAG_STRIP_HIGH
			),
			// Strips characters that have a numerical value >127.
			'subject'     => array(
				'filter' => FILTER_SANITIZE_STRING,
				'flags'  => FILTER_FLAG_STRIP_HIGH,
			),
			// looking for boolean value, string true/false
			'keyword'     => array(
				'filter' => FILTER_SANITIZE_STRING,
				'flags'  => FILTER_FLAG_STRIP_HIGH
			),
			// looking for boolean value, string true/false
			'contributor' => array(
				'filter' => FILTER_SANITIZE_STRING,
				'flags'  => FILTER_FLAG_STRIP_HIGH
			),
			// Strips characters that have a numerical value >127.
			'lists'       => array(
				'filter' => FILTER_SANITIZE_STRING,
				'flags'  => FILTER_FLAG_STRIP_HIGH
			),
			// Remove all characters except digits, plus and minus sign.
			'start'       => array(
				'filter' => FILTER_SANITIZE_NUMBER_INT,
			),
			// Strips characters that have a numerical value >127.
			'search'      => array(
				'filter' => FILTER_SANITIZE_STRING,
				'flags'  => FILTER_FLAG_STRIP_HIGH
			),
			// Strips characters that have a numerical value >127.
			'type_of'     => array(
				'filter' => FILTER_SANITIZE_STRING,
				'flags'  => FILTER_FLAG_STRIP_HIGH
			),
		);

		// filter get input, delete empty values
		$get = ( false !== filter_input_array( INPUT_GET, $args_get, false ) ) ? filter_input_array( INPUT_GET, $args_get, false ) : '';

		// let the filtered get variables override the default arguments
		if ( is_array( $get ) ) {
			// filtered get overrides default
			$this->args = array_merge( $this->defaultArgs, $get );
			// programmer arguments override everything
			$this->args = array_merge( $this->args, $args );

		} else {
			// programmers can override everything if it's hardcoded
			$this->args = array_merge( $this->defaultArgs, $args );
		}

		if ( in_array( $this->args['type_of'], $this->expected ) ) {
			$this->decider();
		} else {
			return new Views\Errors( [ 'msg' => 'Whoops! Looks like you need to pass an expected parameter. Love ya!' ] );
		}
	}

	/**
	 *
	 */
	protected function decider() {

		$rest_api = new Models\EquellaApi();
		$data     = new Models\OtbBooks( $rest_api, $this->args );

		if ( $this->args['type_of'] == 'books' ) {
			$view           = new Views\Books( $data );
			$expected_lists = [ 'adopted', 'ancillary', 'reviewed', 'accessible', 'titles' ];

			// for lists of books matching certain criteria
			if ( ! empty( $this->args['lists'] ) && in_array( $this->args['lists'], $expected_lists ) ) {

				switch ( $this->args['lists'] ) {
					case 'titles':
						$env        = include( OTB_DIR . '.env.php' );
						$rpc_client = new Models\LimeSurveyApi( $env['limesurvey']['LS_URL'] );
						$reviews    = new Models\OtbReviews( $rpc_client, $this->args );

						$view->displayContactFormTitles( $reviews->getNumReviewsPerBook() );
						break;

					default:
						$view->displayTitlesByType( $this->args['lists'] );
				}

			} // for one book
			elseif ( ! empty( $this->args['uuid'] ) ) {
				$view->displayOneTextbook();
			} else {
				$view->displayBooks( $this->args['start'] );
			}
		}

		if ( $this->args['type_of'] == 'book_stats' ) {
			$view = new Views\StatsBooks( $data );

			switch ( $this->args['view'] ) {

				case 'single':
					if ( ! empty( $this->args['uuid'] ) ) {
						$view->displayStatsUuid();
					} else {
						new Views\Errors( [ 'msg' => 'sorry, try passing a uuid parameter. We love you.' ] );
					}
					break;
				default:
					$view->displayStatsTitles();
			}


		}

		if ( $this->args['type_of'] == 'subject_stats' ) {
			$view = new Views\StatsBooks( $data );
			$view->displaySubjectStats();
		}

	}

}