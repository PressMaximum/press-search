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
	 * Count words from a string
	 *
	 * @param string $string
	 * @return mixed 0 if not found any word or array with key is the string and value is the string sequence
	 */
	public function count_words_from_str( $string = '' ) {
		$count = 0;
		// Strip all html tags include remove <script> and <style> tag content.
		$string = wp_strip_all_tags( $string );
		// Strip all html comment.
		$string = $this->remove_html_comment( $string );
		preg_match_all( '~\w+(?:-\w+)*~', $string, $matches );
		if ( isset( $matches[0] ) && ! empty( $matches[0] ) ) {
			$count = array_count_values( $matches[0] );
		}
		return $count;
	}

	/**
	 * Replace string spaces with new char
	 *
	 * @param string $string
	 * @param string $replace_to_str
	 * @return string
	 */
	public function replace_str_spaces( $string = '', $replace_to_str = '' ) {
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

}
