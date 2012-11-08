<?php
/*
Plugin Name: Random quote
Description: Easily integrate a random quote section into your theme either by using the widget, or by creating your own markup. Quotes can be easily added and removed one by one, or imported from a CSV file. Supports loading new quotes with AJAX by clicking on the quote or using an interval.
Version: 1.0
Author: Marian Cerny
Author URI: http://mariancerny.com
*/

class mc_random_quote
{

// ****************************************************************
// ----------------------------------------------------------------
//				INITIALIZE AND EDIT ADMIN MENU
// ----------------------------------------------------------------	
// ****************************************************************

	function __construct()
	{	
		define( 'PLUGIN_NAME', 'AJAX Random Quote' );
		define( 'PLUGIN_SLUG', 'ajax-random-quote' );
		define( 'PLUGIN_URL', plugins_url( '', __FILE__ ) );
	
		add_action( 'init', array( $this, 'register_quotes' ) );
		add_action( 'manage_posts_custom_column', array( $this, 'add_custom_columns' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles_and_scripts' ) );
		add_action( 'admin_menu', array( $this, 'add_settings' ) );
		
		add_action( 'widgets_init', create_function( '', 'register_widget( "mc_random_quote_widget" );' ) );
		add_action( 'wp_ajax_get_quote', array( $this, 'ajax_get_quote' ) );
		add_action( 'wp_ajax_nopriv_get_quote', array( $this, 'ajax_get_quote' ) );
		
		add_filter( 'enter_title_here', array( $this, 'change_default_title' ) );
		add_filter( 'manage_mc_quote_posts_columns', array( $this, 'edit_columns' ) );
		add_filter( 'post_row_actions', array( $this, 'remove_quick_edit' ) );
		add_filter( 'user_can_richedit', array( $this, 'disable_wysiwyg' ) );
		
	}
	
	/* ENQUEUE STYLES AND SCRIPTS */
	function enqueue_styles_and_scripts()
	{
		wp_enqueue_style('mc_random_quote_widget', plugins_url( 'assets/mc_random_quote-widget.css' , __FILE__ ));
		
		
		$phpvars = array (
			ajax_url => admin_url ('admin-ajax.php')
		);
		
		wp_enqueue_script('mc_random_quote_script', plugins_url( 'assets/mc_random_quote-script.js' , __FILE__ ));
		wp_localize_script( 'mc_random_quote_script', 'phpvars', $phpvars );
	}
	
	
	/* REGISTER POST TYPE */
	function register_quotes()
	{
		$args = array(
			'labels' => array(
				'name' => 'Quotes',
				'singular_name' => 'Quote',
				'add_new' => 'Add quote',
				'add_new_item' => 'Add quote',
				'edit_item' => 'Edit quote',
			),
			'public' => true, 
			'exclude_from_search' => true, 
			'supports' => array(
				'editor', 'title'
			),
		); 
		register_post_type( 'mc_quote', $args );
	}
	
	/* CHANGE 'TITLE' TO 'AUTHOR NAME' ON 'ADD QUOTE' SCREEN */
	function change_default_title( $title )
	{
		 $screen = get_current_screen();
	 
		 if  ( 'mc_quote' == $screen->post_type ) {
			  $title = 'Author name';
		 }
	 
		 return $title;
	} 
	
	/* CUSTOMIZING THE LIST OF QUOTES */
	function edit_columns($columns) 
	{
		$columns = array(
			"cb" => "<input type=\"checkbox\" />",
			"title" => "Author",
			"description" => "Quote",
		);
		return $columns;
	}
	
	/* SHOW PREVIEW IN QUOTE LIST VIEW */
	function add_custom_columns($column) 
	{
		global $post;
		switch ($column) {
			case "description":
				the_excerpt();
				break;
		}
	}
	
	/* DISABLE RICH EDIT AND ENQUEUE SCRIPT */
	function disable_wysiwyg( $default ) 
	{
		global $post;
		if ( get_post_type( $post ) == 'mc_quote' )
		{
			wp_enqueue_style('mc_main', plugin_dir_url( __FILE__ ) .'style.css');	
			return false;
		}
		return $default;
	}
	
	/* REMOVE QUICK EDIT AND VIEW BUTTONS */
	function remove_quick_edit( $actions ) 
	{
		global $post;
		if( $post->post_type == 'mc_quote' ) {
			unset( $actions['view'] );
			unset($actions['inline hide-if-no-js']);
		}
		return $actions;
	}
	
	/* ADD PLUGIN SETTINGS MENU INSIDE QUOTES MENU */
	function add_settings()
	{
		add_submenu_page( 
			'edit.php?post_type=mc_quote',
			'Random Quote Settings', 
			'Settings', 
			'manage_options', 
			PLUGIN_SLUG, 
			array( $this, 'print_options_page' ) 
		);
		
		add_submenu_page( 
			'edit.php?post_type=mc_quote',
			'Import from CSV', 
			'Import Quotes from CSV', 
			'manage_options', 
			PLUGIN_SLUG, 
			array( $this, 'print_import_page' ) 
		);
		
		add_settings_section(
			'mc_rq_widget_settings', 
			'Widget Settings', 
			array( $this, 'print_widget_settings_section' ),
			PLUGIN_SLUG
		);
		
		add_settings_section(
			'mc_rq_import', 
			'CSV Import', 
			array( $this, 'print_csv_import_section' ),
			PLUGIN_SLUG
		);
		
		add_settings_field(
			'mc_rq_reload_interval',
			'Reload interval',
			array( $this, 'print_reload_interval_option' ),
			PLUGIN_SLUG,
			'mc_rq_widget_settings'
		);
		
		add_settings_field(
			'mc_rq_csv_import',
			'Reload interval',
			array( $this, 'print_csv_import_option' ),
			PLUGIN_SLUG,
			'mc_rq_import'
		);
		
		register_setting( mc_rq_widget_settings, mc_rq_reload_interval );
		register_setting( mc_rq_import, mc_rq_csv_import );
	}
	
	function print_widget_settings_section()
	{
		echo 'Configure the widget here';
	}
	
	function print_reload_interval_option()
	{
		echo "<input 
			name='mc_rq_reload_interval' 
			id='mc_rq_reload_interval' 
			type='number' 
			value='" .  get_option('mc_rq_reload_interval') ."'
			/> Explanation text";
	}
	
	function print_options_page()
	{
		if ( !current_user_can( 'manage_options' ) )  
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		
		?>
		<div class="wrap">
		<h2><?php echo PLUGIN_NAME; ?> Settings</h2>
        <p></p>
		
       <form method="post" action="options.php">
		
			<?php settings_fields( 'mc_rq_widget_settings' ); ?>
			<?php do_settings_sections( PLUGIN_SLUG  ); ?>     
			<?php submit_button(); ?>
		
		</form>
		</div>
		<?php
	}
	

// ****************************************************************
// ----------------------------------------------------------------
//						MAIN FUNCTIONALITY
// ----------------------------------------------------------------	
// ****************************************************************

	function get_random_quote()
	{
		$a_args = array(
			'post_type' => 'mc_quote',
			'posts_per_page' => 1,
			'orderby' => 'rand'
		);
		
		$o_query = get_posts($a_args); 
			
		$a_quote = array(
			'author' => $o_query[0]->post_title,
			'text' => $o_query[0]->post_content
		);
		
		return $a_quote;		
	}
	
	function ajax_get_quote() 
	{
		$a_quote = $this->get_random_quote();
		?>
		<p class='mc_random_quote_text'>
			<?php echo $a_quote['text']; ?>
		</p>       
		<p class='mc_random_quote_author'>
			<?php echo $a_quote['author']; ?>
		</p>
		<?php
		exit;
	}

}



// ****************************************************************
// ----------------------------------------------------------------
//							WIDGET
// ----------------------------------------------------------------	
// ****************************************************************

class mc_random_quote_widget extends WP_Widget
{
	
	function __construct()
	{
		parent::__construct(
	 		'mc_random_quote_widget', // Base ID
			'Random Quote Widget', // Name
			array( 'description' => 'A Random Quote Widget', 'text_domain' ) // Args
		);
	}
	
	public function widget( $args, $instance ) 
	{
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );

		echo $before_widget;
		$a_quote = get_random_quote();
		?>            
			<div class='mc_random_quote_container'>
            
				<div  class='mc_random_quote_header'>
					<?php 
					if ( ! empty( $title ) )
						echo $before_title . 
							"<span class='mc_random_quote_heading'>" . 
							$title . 
							"</span>" .
							$after_title;
					?>
					<!--<span class='symbol'>V</span>-->
				</div>
                
				<div class='mc_random_quote_content'>
                	<p class='mc_random_quote_text'>
                    	<?php echo $a_quote['text']; ?>
                    </p>       
					<p class='mc_random_quote_author'>
						<?php echo $a_quote['author']; ?>
					</p>
				</div> 
                
			</div>
		<?php
		echo $after_widget;
	}
	
	public function update( $new_instance, $old_instance ) 
	{
		$instance = array();
		$instance['title'] = strip_tags( $new_instance['title'] );

		return $instance;
	}
	
	public function form( $instance ) 
	{
		if ( isset( $instance[ 'title' ] ) ) 
			$title = $instance[ 'title' ];
		else 
			$title = __( 'New title', 'text_domain' );
		
		?>
		<!-- title -->
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>">Title:</label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<?php 
	}
	
}



// ****************************************************************
// ----------------------------------------------------------------
//						OTHER FUNCTIONALITY
// ----------------------------------------------------------------	
// ****************************************************************

$mc_random_quote = new mc_random_quote;

function get_random_quote() 
{
	$mc_random_quote = new mc_random_quote;
	return $mc_random_quote->get_random_quote();
}

?>
