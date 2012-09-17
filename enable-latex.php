<?php
/*
Plugin Name: Enable Latex
Plugin Tag: latex, shortcode, tex, formula, math, physics
Description: <p>Insert LaTeX formulas in your posts.</p><p>Just type <code>[latex size=0 color=000000 background=ffffff]\displaystyle f_{rec} = \frac{c+v_{mobile}}{c} f_{em}[/latex]</code> in your post to show the LaTeX formula.</p><p>You can configure: </p><ul><li>the color of the font,  </li><li>the color of the background, </li><li>the style of the image displayed. </li></ul><p>Plugin developped from the orginal plugin <a href="http://wordpress.org/extend/plugins/wp-latex/">WP-LaTeX</a>.</p><p>This plugin is under GPL licence.</p>
Version: 1.2.4
Author: SedLex
Author Email: sedlex@sedlex.fr
Framework Email: sedlex@sedlex.fr
Author URI: http://www.sedlex.fr/
Plugin URI: http://wordpress.org/extend/plugins/enable-latex/
License: GPL3
*/

require_once('core.php') ; 

class enableLatex extends pluginSedLex {
	/** ====================================================================================================================================================
	* Initialisation du plugin
	* 
	* @return void
	*/
	static $instance = false;
	static $path = false;

	protected function _init() {
		global  $wpdb ; 
		// Configuration
		$this->pluginName = 'Enable Latex' ; 
		$this->tableSQL = "" ; 
		$this->table_name = $wpdb->prefix . "pluginSL_" . get_class() ; 
		$this->path = __FILE__ ; 
		$this->pluginID = get_class() ; 
		
		//Init et des-init
		register_activation_hook(__FILE__, array($this,'install'));
		register_deactivation_hook(__FILE__, array($this,'deactivate'));
		register_uninstall_hook(__FILE__, array($this,'uninstall_removedata'));
		
		//Paramètres supplementaires
		add_action('wp_print_styles', array( $this, 'ajoute_inline_css'));
		add_shortcode( 'latex', array( $this, 'latex_shortcode' ) );
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

	
	/** ====================================================================================================================================================
	* Add a button in the TinyMCE Editor
	*
	* To add a new button, copy the commented lines a plurality of times (and uncomment them)
	* 
	* @return array of buttons
	*/
	
	function add_tinymce_buttons() {
		$buttons = array() ; 
		$buttons[] = array(__('Add LateX tags', $this->pluginID), '[latex size=0 color=000000 background=ffffff]&#92;displaystyle f_{rec} = &#92;frac{c+v_{mobile}}{c} f_{em}[/latex]', '', WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)).'img/latex_button.png') ; 
		return $buttons ; 
	}

	/** ====================================================================================================================================================
	* Define the default option value of the plugin
	* 
	* @return variant of the option
	*/
	function get_default_option($option) {
		switch ($option) {
			case 'cache' 		: return false 	; break ; 
			case 'css' 			: return "*img.latex {\n   vertical-align: middle; \n   border: none; \n}" 	; break ; 
			case 'font_color' 		: return "#000000" 	; break ; 
			case 'background_color' : return "#FFFFFF" 	; break ; 
		}
		return null ;
	}

	/** ====================================================================================================================================================
	* Add CSS
	* 
	* @return void
	*/
	function ajoute_inline_css() {
		$this->add_inline_css($this->get_param('css')) ; 
	}
	
	/** ====================================================================================================================================================
	* The configuration page
	* 
	* @return void
	*/
	
	function configuration_page() {
		?>
		<div class="wrap">
			<div id="icon-themes" class="icon32"><br></div>
			<h2><?php echo $this->pluginName ?></h2>
		</div>
		<div style="padding:20px;">			
			<?php echo $this->signature ; ?>
			<p><?php echo __('This plugin will enable LaTeX formula in posts and pages.',$this->pluginID) ?></p>
		<?php
		
			// On verifie que les droits sont corrects
			$this->check_folder_rights( array(array(WP_CONTENT_DIR."/sedlex/latex_images", "rwx")) ) ; 
			
			//==========================================================================================
			//
			// Mise en place du systeme d'onglet
			//		(bien mettre a jour les liens contenu dans les <li> qui suivent)
			//
			//==========================================================================================
			$tabs = new adminTabs() ; 
			
			ob_start() ; 
				$params = new parametersSedLex($this, 'tab-parameters') ; 
				$params->add_title(__('Do you want to change the style of the image displayed for Latex formula:',$this->pluginID)) ; 
				$params->add_param('css', __('The style:',$this->pluginID)) ; 
				$comment = __('The standard CSS is:',$this->pluginID); 
				$comment .= "<br/><span style='margin-left: 30px;'><code>img.latex {</code></span><br/>" ; 
				$comment .= "<span style='margin-left: 60px;'><code>vertical-align: middle;</code></span><br/>" ; 
				$comment .= "<span style='margin-left: 60px;'><code>border: none;</code></span><br/>" ; 
				$comment .= "<span style='margin-left: 30px;'><code>}</code></span>" ; 
				$params->add_comment($comment) ; 
				$params->add_title(__('What is the color:',$this->pluginID)) ; 
				$params->add_param('font_color', __('For the font:',$this->pluginID), '',"@#[a-fA-F0-9]{6}@") ; 
				$params->add_param('background_color', __('For the background:',$this->pluginID), "","@#[a-fA-F0-9]{6}@") ; 
				$params->add_comment(sprintf(__('Please add the # character before the code. If you do not know what code to use, please visit this website: %s',$this->pluginID),"<a href='http://html-color-codes.info/'>http://html-color-codes.info/</a>")) ; 
				$params->add_title(__('Do you want to cache the images on the local disk?',$this->pluginID)) ; 
				$params->add_param('cache', __('Cache enabled:',$this->pluginID)) ; 
				$params->flush() ; 
					
			$tabs->add_tab(__('Parameters',  $this->pluginID), ob_get_clean() , WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_param.png") ; 	
			
			
			ob_start() ; 
				$plugin = str_replace("/","",str_replace(basename(__FILE__),"",plugin_basename( __FILE__))) ; 
				$trans = new translationSL($this->pluginID, $plugin) ; 
				$trans->enable_translation() ; 
			$tabs->add_tab(__('Manage translations',  $this->pluginID), ob_get_clean() , WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_trad.png") ; 	

			ob_start() ; 
				$plugin = str_replace("/","",str_replace(basename(__FILE__),"",plugin_basename( __FILE__))) ; 
				$trans = new feedbackSL($plugin, $this->pluginID) ; 
				$trans->enable_feedback() ; 
			$tabs->add_tab(__('Give feedback',  $this->pluginID), ob_get_clean() , WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_mail.png") ; 	

			ob_start() ; 
				$trans = new otherPlugins("sedLex", array('wp-pirates-search')) ; 
				$trans->list_plugins() ; 
			$tabs->add_tab(__('Other plugins',  $this->pluginID), ob_get_clean() , WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_plug.png") ; 	
			
			echo $tabs->flush() ; 
			
			echo $this->signature ; 					
			?>
		</div>
		<?php
	}
	
	//[latex size=0 color=000000 background=ffffff]\LaTeX[/latex]
	
	function latex_shortcode( $_atts, $latex ) {
		$atts = shortcode_atts( array(
			'size' => 0,
			'color' => $this->get_param('font_color'),
			'background' => $this->get_param('background_color'),
		), $_atts );
		
		if (!preg_match("@#[0-9]{6}@", $atts['color'])) {
			$this->get_default_option('font_color') ; 
		}
		if (!preg_match("@#[0-9]{6}@", $atts['background'])) {
			$this->get_default_option('background_color') ; 
		}
		if (!preg_match("@[0-9]*@", $atts['size'])) {
			$this->get_default_option('size') ; 
		}
	
		$latex = preg_replace( array( '#<br\s*/?>#i', '#</?p>#i' ), ' ', $latex );

		$latex = str_replace(
			array( '&lt;', '&gt;', '&quot;', '&#8220;', '&#8221;', '&#039;', '&#8125;', '&#8127;', '&#8217;', '&#038;', '&amp;', "\n", "\r", "\xa0", '&#8211;' ),
			array( '<',    '>',    '"',      '``',       "''",     "'",      "'",       "'",       "'",       '&',      '&',     ' ',  ' ',  ' ',    '-' ),
			$latex
		);

			
		$url = 'http://s.wordpress.com/latex.php?latex=' . rawurlencode( $latex ) . "&bg=".str_replace('#','',$atts['background'])."&fg=".str_replace('#','',$atts['color'])."&s=".$atts['size'];				
		
		if ($this->get_param('cache')) {
			$md5 = md5($url) ; 
			$path = WP_CONTENT_DIR."/sedlex/latex_images" ; 
			if (!is_dir($path)) {
				mkdir($path, 0755, true) ; 
			}
			$path = $path."/".$md5.".png" ; 
			if (!is_file($path)) {
				$img = file_get_contents($url);
				file_put_contents($path, $img);				
				chmod($path,0755); 
			}
			$url = WP_CONTENT_URL."/sedlex/latex_images/".$md5.".png" ; 
		}
		
		$alt = 'Latex formula' ;
		return "<img src='$url' alt='$alt' title='$alt' class='latex' />";
	}
	

}

$enableLatex = enableLatex::getInstance();

?>