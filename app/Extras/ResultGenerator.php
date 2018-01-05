<?php

namespace App\Extras;

use Exception;
use Illuminate\Support\Facades\DB;
use Imagick;
use ImagickDraw;

class ResultGenerator {
	
	private $db;
	private $http;
	
	public function __construct() {
		$this->db   = DB::connection( 'wpdb' );
		$this->http = ( new Request( env( "APP_URL" ) ) );
	}
	
	public function generate_result_user_image( $postId, $userInfo ) {
		
		$return  = array( $postId, $userInfo );
		$results = get_post_meta( $postId, 'results' );
		
		if ( ! extension_loaded( 'imagick' ) || empty( $results ) ) {
			return $return;
		}
		
		/** @noinspection PhpParamsInspection */
		$index         = array_rand( $results );
		$result        = $results[ $index ];
		$result['key'] = $index;
		
		$names = array(
			'user_first_name'   => $userInfo['first_name'],
			'user_last_name'    => $userInfo['last_name'],
			'friend_first_name' => '',
			'friend_last_name'  => '',
		);
		
		$profile = $this->get_user_profile_image( $userInfo['id'] );
		
		$return = $this->generate_fb_result( $postId, $result, $profile, $names );
		
		return array( $postId, $userInfo, $return );
	}
	
	public function generate_result_friend_image( $postId, $userInfo ) {
		
		return [];
	}
	
	private function get_user_profile_image( $userId ) {
		$options = array(
			CURLOPT_RETURNTRANSFER => true,   // return web page
			CURLOPT_HEADER         => true,  // don't return headers
			CURLOPT_FOLLOWLOCATION => true,   // follow redirects
			CURLOPT_MAXREDIRS      => 10,     // stop after 10 redirects
			CURLOPT_ENCODING       => "",     // handle compressed
			CURLOPT_USERAGENT      => "test", // name of client
			CURLOPT_AUTOREFERER    => true,   // set referrer on redirect
			CURLOPT_CONNECTTIMEOUT => 120,    // time-out on connect
			CURLOPT_TIMEOUT        => 120,    // time-out on response
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_SSL_VERIFYPEER => false,
		);
		
		$url = 'https://graph.facebook.com/' . $userId . '/picture?width=320&height=320';
		
		$ch = curl_init( $url );
		curl_setopt_array( $ch, $options );
		
		$response = curl_exec( $ch );
		
		$header_size = curl_getinfo( $ch, CURLINFO_HEADER_SIZE );
		$header      = substr( $response, 0, $header_size );
		$headers     = [];
		
		foreach ( explode( PHP_EOL, $header ) as $defaultKey => $line ) {
			$arr = explode( ": ", $line );
			if ( count( $arr ) != 2 ) {
				continue;
			}
			
			list( $key, $value ) = $arr;
			$headers[ $key ] = $value;
		}
		
		$location = isset( $headers["Location"] ) ? $headers["Location"] : "Not Found";
		$location = str_replace(PHP_EOL, '', $location);
		$location = str_replace('&amp;', '&', $location);
		$location = str_replace("\r", '', $location);
		
		return $location;
	}
	
	private function generate_fb_result( $postId, $result, $profile, $names ) {
		
		$return = array( 'src' => '', 'desc' => '', 'error' => '' );
		
		$options    = get_option( 'wp_quiz_pro_default_settings' );
		$settings   = get_post_meta( $postId, 'settings', true );
		$find       = array( '%%userfirstname%%', '%%userlastname%%', '%%friendfirstname%%', '%%friendlastname%%' );
		$replace    = array(
			$names['user_first_name'],
			$names['user_last_name'],
			$names['friend_first_name'],
			$names['friend_last_name']
		);
		$title      = str_replace( $find, $replace, $result['title'] );
		$desc       = str_replace( $find, $replace, $result['desc'] );
		$upload_dir = upload_dir();
		
		// Load images
		$profile = new Imagick( download_tmp_image($profile) );
		$profile->resizeImage( $result['proImageWidth'], $result['proImageHeight'], imagick::FILTER_LANCZOS, 0.9 );
		if (method_exists($profile, 'roundCorners'))
			$profile->roundCorners( $result['imageRadius'], $result['imageRadius'] );
		
		// Create new image from result
		$output = new Imagick( $result['image'] );
		$output->compositeImage( $profile, Imagick::COMPOSITE_DEFAULT, $result['pos_x'], $result['pos_y'] );
		
		// Annotate it
		if ( ! empty( $title ) ) {
			
			$draw = new ImagickDraw();
			$draw->setFillColor( $settings['title_color'] );
			$draw->setGravity( 1 );
			$draw->setFontSize( $settings['title_size'] );
			
			if ( isset( $options['defaults']['external_font'] ) && ! empty( $options['defaults']['external_font'] ) ) {
				$external_font = str_replace( url( '/' ), '', $options['defaults']['external_font'] );
				$draw->setFont( '../' . $external_font );
			} else {
				$draw->setFontFamily( $settings['title_font'] );
			}
			
			list( $lines, $line_height ) = $this->word_wrap_annotation( $output, $draw, $title,
				$result['titleImageWidth'] );
			
			for ( $i = 0; $i < count( $lines ); $i ++ ) {
				$output->annotateImage( $draw, $result['pos_title_x'], $result['pos_title_y'] + $i * $line_height, 0,
					$lines[ $i ] );
			}
		}
		
		// Save to new image
		$upload_dir['basedir'] = $upload_dir['basedir'] . '/quiz-result-images';
		$upload_dir['baseurl'] = $upload_dir['baseurl'] . '/quiz-result-images';
		$output_name           = "image-" . totally_random_file_name() . ".png";
		$output->writeImage( $upload_dir['basedir'] . '/' . $output_name );
		
		// Clean up
		$profile->destroy();
		$output->destroy();
		
		$return['src']  = $upload_dir['baseurl'] . '/' . $output_name;
		$return['desc'] = $desc;
		$return['key']  = $result['key'];
		
		
		return $return;
	}
	
	/**
	 * @param Imagick $image
	 * @param $draw
	 * @param $text
	 * @param $max_width
	 *
	 * @return array
	 */
	private function word_wrap_annotation( $image, $draw, $text, $max_width ) {
		
		$words       = preg_split( '%\s%', $text, - 1, PREG_SPLIT_NO_EMPTY );
		$lines       = array();
		$i           = 0;
		$line_height = 0;
		
		while ( count( $words ) > 0 ) {
			$metrics     = $image->queryFontMetrics( $draw, implode( ' ', array_slice( $words, 0, ++ $i ) ) );
			$line_height = max( $metrics['textHeight'], $line_height );
			
			if ( $metrics['textWidth'] > $max_width || count( $words ) < $i ) {
				if ( 1 === $i ) {
					$i ++;
				}
				
				$lines[] = implode( ' ', array_slice( $words, 0, -- $i ) );
				$words   = array_slice( $words, $i );
				$i       = 0;
			}
		}
		
		return array( $lines, $line_height );
	}
}