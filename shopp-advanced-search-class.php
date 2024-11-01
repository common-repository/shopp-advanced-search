<?php
class AdvancedSearchResults extends SearchResults {
    static $_slug = "search-results";

    function smart ($options=array()) {
        parent::smart($options);
		$this->name = sprintf( __( 'Search Results for &quot;%s&quot;', 'Shopp' ), esc_html( $options['search'] ) );

		// If tags were specified then process them
		if ( ! empty( $_REQUEST['shopptag'] ) ) {
			$info = $this->_tagcat_helper( 'tag' );
			$this->name .= sprintf( __( ' tagged with %s', 'Shopp' ), esc_html( $info->name ) );
			$this->loading['where'] .= $info->where;
		}

		// If categories were specified then process them
		if ( ! empty( $_REQUEST['shoppcat'] ) ) {
			$info = $this->_tagcat_helper( 'cat' );
			$this->name .= sprintf( __( ' filed in %s', 'Shopp' ), esc_html( $info->name ) );
			$this->loading['where'] .= $info->where;
		}
    }

	/**
	 * Processes tags or categories, generating the proper where clause and
	 * creating a list of category or tag names to dispaly to the user as well
	 *
	 * @returns object 'name' is a list of tags or cats and 'where' is the added where clause
	 */
	private function _tagcat_helper( $tagcat ) {
		$tagcat = strtolower( $tagcat );
		if ( 'cat' == $tagcat ) {
			$class = 'Category';
			$type = 'category';
		} elseif ( 'tag' == $tagcat ) {
			$class = 'Tag';
			$type = 'tag';
		} else {
			return false;
		}
		$return = new stdClass();
		/**
		 * @todo it would be better if $options passed to $this->smart() contained
		 * these so I didn't have to directly access $_REQUEST
		 */
		if ( ! is_array( $_REQUEST['shopp'.$tagcat] ) ) {
			$_REQUEST['shopp'.$tagcat] = array( $_REQUEST['shopp'.$tagcat] );
		}
		$_REQUEST['shopp'.$tagcat] = array_map( 'absint', $_REQUEST['shopp'.$tagcat] );
		$catalogtable = DatabaseObject::tablename(Catalog::$table);

		// Turn ids into names to display to the user
		$names = array();
		foreach( $_REQUEST['shopp'.$tagcat] as $tcid ) {
			$tc = new $class( $tcid );
			$names[] = '&quot;' . $tc->name . '&quot;';
		}

		$return->name = $this->_string_list( $names );
		$return->where = " AND (p.id in (SELECT product FROM $catalogtable WHERE (parent IN (".implode( ',', $_REQUEST['shopp'.$tagcat] ).") AND type='{$type}')))";
		return $return;
	}

	/**
	 * Generates a user fiendly list from the array.  Something like:
	 * Given array( 'this' ) you get: this
	 * Given array( 'this', 'that' ) you get: this or that
	 * Given array( 'this', 'that', 'other' ) you get: this, that, or other
	 *
	 * @param array $list List of items to be made into a comma separated list
	 * @param string $opperator[optional] Usually 'or' or 'and' defaults to 'or'
	 *
	 * @return string User friendly list
	 */
	private function _string_list( $list, $opperator = 'or' ) {
		if ( ! is_array( $list ) ) {
			return false;
		}
		$num_items = count( $list );
		$separator = ( 3 <= $num_items )? ', ' : ' ';
		if ( 2 <= $num_items ) {
			$list[ $num_items - 1 ] = $opperator . ' ' . $list[ $num_items - 1 ];
        }
		return implode( $separator, $list );

	}
}
