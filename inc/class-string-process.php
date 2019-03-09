<?php

class Press_Search_String_Process {
	/**
	 * The single instance of the class
	 *
	 * @var Press_Search_String_Process
	 * @since 0.1.0
	 */
	protected static $_instance = null;


	/**
	 * Instance
	 *
	 * @return Press_Search_String_Process
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Get all stop words
	 *
	 * @return array
	 */
	public function get_stop_words() {
		$stop_words = array();
		$default_stop_words = '';
		if ( file_exists( PRESS_SEARCH_DIR . 'inc/default-stop-words.php' ) ) {
			$default_stop_words = include PRESS_SEARCH_DIR . 'inc/default-stop-words.php';
		}

		$extra_stop_words = press_search_get_setting( 'stopwords', $default_stop_words );
		if ( '' !== $extra_stop_words ) {
			$extra_stop_words = preg_replace( '/,\s+$/', '', $extra_stop_words );
			$extra_stop_words = explode( ',', $extra_stop_words );

			if ( is_array( $extra_stop_words ) && ! empty( $extra_stop_words ) ) {
				$extra_stop_words = array_map( array( $this, 'replace_str_spaces' ), $extra_stop_words );
				$stop_words = array_unique( $extra_stop_words );
			}
		}
		return $stop_words;
	}

	/**
	 * Remove all stop words in array
	 *
	 * @param array $arr_string
	 * @return array
	 */
	public function remove_arr_stop_words( $arr_string = array() ) {
		$stop_words = $this->get_stop_words();
		foreach ( $arr_string as $k => $v ) {
			if ( in_array( $v, $stop_words ) ) {
				unset( $arr_string[ $k ] );
			}
		}
		return $arr_string;
	}

	/**
	 * Check string is cjk
	 *
	 * @param string $string
	 * @return boolean
	 */
	public function is_cjk( $string = '' ) {
		return $this->is_chinese( $string ) || $this->is_japanese( $string ) || $this->is_korean( $string );
	}

	/**
	 * Check string contain chinese char
	 *
	 * @param string $string
	 * @return boolean
	 */
	public function is_chinese( $string = '' ) {
		return preg_match( '/\p{Han}+/u', $string );
	}

	/**
	 * Check string contain japanese char
	 *
	 * @param string $string
	 * @return boolean
	 */
	public function is_japanese( $string = '' ) {
		return preg_match( '/[\x{4E00}-\x{9FBF}\x{3040}-\x{309F}\x{30A0}-\x{30FF}]/u', $string );
	}

	/**
	 * Check string contain korea char
	 *
	 * @param string $string
	 * @return boolean
	 */
	public function is_korean( $string = '' ) {
		return preg_match( '/[\x{3130}-\x{318F}\x{AC00}-\x{D7AF}]/u', $string );
	}

	/**
	 * Count words from a string
	 *
	 * @param string $text
	 * @param bool   $to_lower_case
	 * @return mixed 0 if not found any word or array with key is the string and value is the string sequence
	 */
	public function count_words_from_str( $text = '', $to_lower_case = true ) {
		if ( $to_lower_case ) {
			$text = mb_strtolower( $text );
		}
		$check = strpos( _x( 'words', 'Word count type. Do not translate!' ), 'characters' );
		if ( $this->is_cjk( $text ) ) {
			$check = strpos( _x( 'characters_excluding_spaces', 'Word count type. Do not translate!' ), 'characters' );
		}
		$text = wp_strip_all_tags( $text );
		if ( 0 === $check && preg_match( '/^utf\-?8$/i', get_option( 'blog_charset' ) ) ) {
			$text = trim( preg_replace( "/[\n\r\t,. ]+/", '', $text ), ' ' );
			preg_match_all( '/./u', $text, $words_array );
			$words_array = $words_array[0];
		} else {
			$words_array = preg_split( "/[\n\r\t., ]+/", $text, -1, PREG_SPLIT_NO_EMPTY );
		}
		$words_array = $this->remove_arr_stop_words( $words_array );
		$count = array_count_values( $words_array );
		return $count;
	}

	/**
	 * Replace string spaces with new char
	 *
	 * @param string $string
	 * @param string $replace_to_str
	 * @param bool   $to_lower_case
	 * @return string
	 */
	public function replace_str_spaces( $string = '', $replace_to_str = '', $to_lower_case = true ) {
		if ( $to_lower_case ) {
			$string = mb_strtolower( $string );
		}
		return preg_replace( '/\s+/', $replace_to_str, $string );
	}

	/**
	 * Remove all html comment from string
	 *
	 * @param string $content
	 * @return string
	 */
	public function remove_html_comment( $content = '' ) {
		return preg_replace( '/<!--(.*)-->/Uis', '', $content );
	}

	/**
	 * Remove urls from string
	 *
	 * @param string $string
	 * @return string
	 */
	public function remove_urls( $string = '' ) {
		$string = preg_replace( '/\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|$!:,.;]*[A-Z0-9+&@#\/%=~_|$]/i', '', $string );
		return $string;
	}

	public function get_paragraph_contain_keyword( $keyword, $str ) {
		$keywords = explode( ' ', preg_quote( $keywords ) );
		$regex = '/[A-Z][^\\.;]*(' . implode( '|', $keywords ) . ')[^\\.;]*/';

		if ( preg_match( $regex, $str, $match ) ) {
			return $match[0];
			// Maybe trim paragraph.
			$start = strpos( $str, $match[0] );
			$end = strpos( $html, '.', $start );
			$paragraph = substr( $html, $start, $end - $start + 4 );
		}
		return false;
	}

	public function highlight_keywords( $origin_string = '', $keywords = '' ) {
		$hightlight_terms = press_search_get_setting( 'searching_hightlight_terms', 'bold' );
		if ( 'bold' == $hightlight_terms ) {
			$hightlight_tag = 'b';
		} else {
			$hightlight_tag = 'strong';
		}
		$keywords = explode( ' ', preg_quote( $keywords ) );
		$origin_string = preg_replace( '/(' . implode( '|', $keywords ) . ')/iu', '<' . $hightlight_tag . ' class="keyword-hightlight">\0</' . $hightlight_tag . '>', $origin_string );
		return $origin_string;
	}

}
