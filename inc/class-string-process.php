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
	 * Count words from a string
	 *
	 * @param string $string
	 * @param bool   $to_lower_case
	 * @return mixed 0 if not found any word or array with key is the string and value is the string sequence
	 */
	public function count_words_from_str( $string = '', $to_lower_case = true ) {
		if ( $to_lower_case ) {
			$string = strtolower( $string );
		}
		$count = 0;
		// Strip all html tags include remove <script> and <style> tag content.
		$string = wp_strip_all_tags( $string );
		// Strip all html comment.
		$string = $this->remove_html_comment( $string );
		preg_match_all( '~\w+(?:-\w+)*~', $string, $matches );
		if ( isset( $matches[0] ) && ! empty( $matches[0] ) ) {
			$words = $this->remove_arr_stop_words( $matches[0] );
			$count = array_count_values( $words );
		}
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
			$string = strtolower( $string );
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

}
