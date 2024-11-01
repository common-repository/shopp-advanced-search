<?php
/**
 * Plugin Name: Shopp Advanced Search
 * Plugin URI: http://www.mequodaprojects.com/wp/mequoda-shopp
 * Description: Mequoda extensions to Shopp
 * Version: 0.0.1
 * Author: Aaron D. Campbell
 * Author URI: http://xavisys.com/
 */
/**
 *	Changelog:
 *		0.0.1:
 *			Original version, includes source tracking for Shopp Orders
 */
/**
 * mqShopp is the class that handles ALL of the plugin functionality.
 * It helps us avoid name collisions
 * http://codex.wordpress.org/Writing_a_Plugin#Avoiding_Function_Name_Collisions
 */
class shoppAdvancedSearch
{
	/**
	 * Static property to hold our singleton instance
	 * @var shoppAdvancedSearch
	 */
	static $instance = false;

	/**
	 * @var bool Used to make sure we only display the css once
	 */
	private $_css_shown = false;

	/**
	 * This is our constructor, which is private to force the use of getInstance()
	 * @return void
	 */
	private function __construct() {
		/**
		 * Add filters and actions
		 */
		add_action( 'shopp_register_smartcategories', array( $this, 'shopp_register_smartcategories' ) );
		add_action( 'shopp_init', array( $this, 'shopp_init' ) );
		add_filter( 'shopp_tag_catalog_advancedsearchform', array( $this, 'advanced_search_form' ), null, 3 );
		add_shortcode( 'shopp_advanced_searchform', array( $this, 'shopp_advanced_searchform' ) );
	}

	/**
	 * Function to instantiate our class and make it a singleton
	 */
	public static function getInstance() {
		if ( !self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Shows a search form that can be used for advance search.  Called like this:
	 * return shopp( 'catalog', 'advancedsearchform' );
	 */
	public function advanced_search_form( $result, $options, $Object ) {
		$searchform = '
		<form action="/" id="searchform" method="get" role="search">
			<div>
				<label for="s" class="screen-reader-text">%s</label>
				<input type="text" id="s" name="s" value="" style="width:130px;" />
			</div>
		';
		$searchform = sprintf( $searchform, esc_html( 'Search store:', 'Shopp' ) );

		$formend = '
			<input type="submit" value="%s" id="searchsubmit">
			<input type="hidden" value="true" name="catalog" />
		</form>
		';
		$formend = sprintf( $formend, esc_attr( 'Search', 'Shopp' ) );

		// Display categories unless told otherwise
		if ( empty( $options['cats'] ) || 'false' != $options['cats'] ) {
			$searchform .= '<h4>' . esc_html__( 'Only search these categories', 'Shopp' ) . '</h4>';
			$searchform .= $this->_categories_checkboxes();
		}
		// Display tags unless told otherwise
		if ( empty( $options['tags'] ) || 'false' != $options['tags'] ) {
			$searchform .= '<h4>' . esc_html__( 'Only search products tagged with', 'Shopp' ) . '</h4>';
			$searchform .= $this->_tags_checkboxes();
		}
		$searchform .= $formend;

		if ( ! empty( $options['return'] ) && $options['return'] ) {
			return $searchform;
		}
		echo $searchform;
	}

	/**
	 * Add a list of tags with checkboxes
	 */
	private function _tags_checkboxes() {
		$Catalog = new Catalog();
		$Catalog->load_tags();
		ob_start();
		$this->_checkbox_css();
		?>
		<div id="tag-menu" class="multiple-select">
			<ul>
				<?php
				foreach ( $Catalog->tags as $tag ) {
				?>
				<li id="tag-element-<?php echo $tag->id; ?>"><input type="checkbox" name="shopptag[]" value="<?php echo $tag->id; ?>" id="tag-<?php echo $tag->id; ?>" class="tag-toggle" /><label for="tag-<?php echo $tag->id; ?>"><?php echo esc_html($tag->name); ?></label></li>
				<?php
				}
				?>
			</ul>
		</div>

		<?php
		return ob_get_clean();
	}

	/**
	 * Add in the CSS for the checkboxes but only add it once
	 */
	private function _checkbox_css() {
		if ( ! $this->_css_shown ) {
			$this->_css_shown = true;
	?>
	<style type="text/css">
		.multiple-select {
			overflow-x:hidden;
			overflow-y:auto;
			height:150px;
			border:1px solid #dfdfdf;
			background:#ffffff;
			padding:0;
			margin:0;
		}
		.multiple-select ul {
			list-style-type:none;
			left:0;
			line-height:1;
			margin:0;
			padding:0;
		}
		.multiple-select ul li {
			margin:0;
			padding:2px 2px 0 0;
			clear:both;
		}
		.multiple-select ul li input {
			margin:0 5px;
		}
		.multiple-select ul ul {
			padding-left:20px;
		}
	</style>
	<?php
		}
	}

	/**
	 * Displays a hierarchical list of categories with checkboxes.
	 */
	private function _categories_checkboxes( $input_name = 'shoppcat' ) {
		$db =& DB::get();
		$category_table = DatabaseObject::tablename(Category::$table);
		$categories = $db->query("SELECT id,name,parent FROM $category_table ORDER BY parent,name",AS_ARRAY);
		/**
		 * Give plugins the ability to filter categories (in tree form so it's
		 * easy to remove a category and all it's descendants)
		 */
		$categories = apply_filters( 'shopp_advancedsearchform_categories', sort_tree($categories), $categories );
		if (empty($categories)) $categories = array();

		$selectedCategories = array();
		ob_start();
		$this->_checkbox_css();
		?>
		<div id="category-menu" class="multiple-select">
			<ul>
				<?php
				$depth = 0;
				foreach ($categories as $category) {
					if ($category->depth > $depth) {
						echo "<li><ul>";
					} else if ($category->depth < $depth) {
						for ($i = $category->depth; $i < $depth; $i++)
							echo '</ul></li>';
					}
				?>
				<li id="category-element-<?php echo $category->id; ?>"><input type="checkbox" name="<?php esc_attr_e( $input_name ); ?>[]" value="<?php echo $category->id; ?>" id="category-<?php echo $category->id; ?>" class="category-toggle" /><label for="category-<?php echo $category->id; ?>"><?php echo esc_html($category->name); ?></label></li>
				<?php
					$depth = $category->depth;
				}
				for ($i = 0; $i < $depth; $i++)
					echo '</ul></li>';
				?>
			</ul>
		</div>

	<?php
		return ob_get_clean();
	}

	/**
	 * Processes our shortcode and calls the Shopp advanced search form
	 */
	public function shopp_advanced_searchform( $attr, $content = '' ) {
		$defaults = array(
			'return'	=> 'true',
			'cats'		=> 'true',
			'tags'		=> 'true',
		);

        $attr = shortcode_atts( $defaults, $attr );

		return shopp( 'catalog', 'advancedsearchform', $attr );
	}

	/**
	 * Include search class AFTER SearchResults class exists
	 */
	public function shopp_init() {
		require_once( 'shopp-advanced-search-class.php' );
	}

	/**
	 * Add the AdvancedSearchResults smart category
	 */
	public function shopp_register_smartcategories() {
		Shopp::add_smartcategory('AdvancedSearchResults');
	}
}

// Instantiate our class
$shoppAdvancedSearch = shoppAdvancedSearch::getInstance();
