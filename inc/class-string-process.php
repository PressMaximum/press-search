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
			$stop_words = $this->explode_comma_str( $extra_stop_words );
		}
		return $stop_words;
	}

	public function explode_comma_str( $string = '' ) {
		$string = preg_replace( '/,\s+$/', '', $string );
		$string = explode( ',', $string );
		$return = array();
		if ( is_array( $string ) && ! empty( $string ) ) {
			$string = array_map( array( $this, 'replace_str_spaces' ), $string );
			$return = array_unique( $string );
		}
		return $return;
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

	public function remove_arr_number_one_digit( $arr = array() ) {
		if ( ! empty( $arr ) ) {
			foreach ( $arr as $k => $v ) {
				if ( is_numeric( $k ) && strlen( $k ) == 1 ) {
					unset( $arr[ $k ] );
				}
			}
		}
		return $arr;
	}

	public function clear_string( $text = '' ) {
		$text = strip_tags( $text );
		$text = preg_replace( '/#([a-fA-F0-9]{3}){1,2}\b/', '', $text ); // Remove css color hex.
		$text = preg_replace( '/&#?[a-z0-9]{2,8};/i', ' ', $text ); // Replace special char html encoded.
		$text = htmlspecialchars( $text );
		$text = str_replace( array( '&lt;', '&gt;', '~', '`', '!', '@', '#', '$', '%', '^', '*', '(', ')', '-', '_', '+', '=', '{', '}', '[', ']', '|', ':', ';', '"', "'", '?', '/', '>', '<', ',', '’', '”', '‘', '“', '′', '″' ), ' ', $text );
		$text = preg_replace( '/[^\p{L}\p{N}\s]/u', ' ', $text ); // Replace special html char.
		$text = str_replace( array( '&' ), ' ', $text );

		return $text;
	}


	/**
	 * Count words from a string
	 *
	 * @param string $text
	 * @param bool   $to_lower_case
	 * @return mixed 0 if not found any word or array with key is the string and value is the string sequence
	 */
	public function count_words_from_str( $text = '', $to_lower_case = true ) {
		$text = $this->clear_string( $text );
		$explode_words = $this->explode_words( $text, $to_lower_case );
		$words_array = $this->remove_arr_stop_words( $explode_words );
		$count = array_count_values( $words_array );
		$count = $this->remove_arr_number_one_digit( $count );
		return $count;
	}

	public function explode_words( $text = '', $to_lower_case = true ) {
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
		return $words_array;
	}
	public function count_number_words( $text = '' ) {
		$words_array = $this->explode_words( $text );
		return count( $words_array );
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

	public function highlight_keywords( $origin_string = '', $keywords = '' ) {
		$hightlight_terms = press_search_get_setting( 'searching_hightlight_terms', 'bold' );
		if ( 'bold' == $hightlight_terms ) {
			$hightlight_tag = 'b';
		} else {
			$hightlight_tag = 'strong';
		}
		if ( ! is_array( $keywords ) ) {
			$this->explode_keywords( $keywords );
		}

		$origin_string = preg_replace( '/(' . implode( '|', $keywords ) . ')/iu', '<' . $hightlight_tag . ' class="keyword-hightlight">\0</' . $hightlight_tag . '>', $origin_string );
		return $origin_string;
	}

	public function is_contain_keyword( $keywords = '', $string = '' ) {
		if ( ! is_array( $keywords ) ) {
			$keywords = $this->explode_keywords( $keywords );
		}
		if ( preg_match( '/(' . implode( '|', $keywords ) . ')/iu', $string ) ) {
			return true;
		}
		return false;
	}

	public function get_excerpt_contain_keyword( $keywords = '', $excerpt = '', $content = '' ) {
		if ( ! is_array( $keywords ) ) {
			$keywords = $this->explode_keywords( $keywords );
		}
		$regex = '/[A-Z][^\\.;]*(' . implode( '|', $keywords ) . ')[^\\.;]*/iu';
		$exerpt_char_length = strlen( $excerpt );
		$content_without_tags = wp_strip_all_tags( $content );
		if ( preg_match( $regex, $excerpt, $match ) ) { // Excerpt already contain keyword.
			return $excerpt;
		} elseif ( preg_match( $regex, $content_without_tags, $match ) ) { // Maybe the content contain keyword.
			$start = strpos( $content_without_tags, $match[0] );
			$paragraph = substr( $content_without_tags, $start, $exerpt_char_length );
			$count_exerpt_words = $this->count_number_words( $excerpt );
			$return = wp_trim_words( $paragraph, $count_exerpt_words, '' );
			return $return;
		} else { // Return excerpt.
			return $excerpt;
		}
	}

	public function explode_keywords( $keywords = '' ) {
		$search_keywords = explode( ' ', mb_strtolower( $keywords ) );
		$search_keywords = array_map( 'trim', $search_keywords );
		return $search_keywords;
	}
}



