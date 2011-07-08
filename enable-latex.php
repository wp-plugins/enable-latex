<?php
/*
Plugin Name: Enable Latex
Description: <p>Enable the insertion of LaTeX formula in your post.</p><p>Just type <code>[latex size=0 color=000000 background=ffffff]\displaystyle f_{rec} = \frac{c+v_{mobile}}{c} f_{em}[/latex]</code> in your post to show the LaTeX formula.</p><p>You can configure: <ul><li>the color of the font,  </li><li>the color of the background, </li><li>the style of the image displayed. </li></ul></p><p>Plugin developped from the orginal plugin <a href="http://wordpress.org/extend/plugins/wp-latex/">WP-LaTeX</a>. </p><p>This plugin is under GPL licence. </p>
Version: 1.0.1
Author: SedLex
Author URI: http://www.sedlex.fr/
Plugin URI: http://www.sedlex.fr/cote_geek/
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
		// Configuration
		$this->pluginName = 'Enable Latex' ; 
		$this->tableSQL = "" ; 
		$this->path = __FILE__ ; 
		$this->pluginID = get_class() ; 
		
		//Init et des-init
		register_activation_hook(__FILE__, array($this,'install'));
		register_deactivation_hook(__FILE__, array($this,'uninstall'));
		
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
			<?php echo $this->signature ; ?>
			<p>This plugin will enable LaTeX formula in posts and pages.</p>
			<!--debut de personnalisation-->
		<?php
			
			//==========================================================================================
			//
			// Mise en place du systeme d'onglet
			//		(bien mettre a jour les liens contenu dans les <li> qui suivent)
			//
			//==========================================================================================
	?>		
			<script>jQuery(function($){ $('#tabs').tabs(); }) ; </script>		
			<div id="tabs">
				<ul class="hide-if-no-js">
					<li><a href="#tab-parameters"><? echo __('Parameters',$this->pluginName) ?></a></li>					
				</ul>
				<?php
				//==========================================================================================
				//
				// Premier Onglet 
				//		(bien verifier que id du 1er div correspond a celui indique dans la mise en 
				//			place des onglets)
				//
				//==========================================================================================
				?>
				
				<div id="tab-parameters" class="blc-section">
				
					<h3 class="hide-if-js"><? echo __('Parameters',$this->pluginName) ?></h3>
					<p><?php echo __('Here is the parameters of the plugin. Please modify them at your convenience.',$this->pluginName) ; ?> </p>
				
					<?php
					$params = new parametersSedLex($this, 'tab-parameters') ; 
					$params->add_title(__('Do you want to change the style of the image displayed for Latex formula:',$this->pluginName)) ; 
					$params->add_param('css', __('The style:',$this->pluginName)) ; 
					$comment = __('The standard CSS is:',$this->pluginName); 
					$comment .= "<br/><span style='margin-left: 30px;'><code>img.latex {</code></span><br/>" ; 
					$comment .= "<span style='margin-left: 60px;'><code>vertical-align: middle;</code></span><br/>" ; 
					$comment .= "<span style='margin-left: 60px;'><code>border: none;</code></span><br/>" ; 
					$comment .= "<span style='margin-left: 30px;'><code>}</code></span>" ; 
					$params->add_comment($comment) ; 
					$params->add_title(__('What is the color:',$this->pluginName)) ; 
					$params->add_param('font_color', __('For the font:',$this->pluginName), '',"@#[a-fA-F0-9]{6}@") ; 
					$params->add_param('background_color', __('For the background:',$this->pluginName), "","@#[a-fA-F0-9]{6}@") ; 
					$params->add_comment(__('Please add the # character before the code. If you do not know what code to use, please visit this website:',$this->pluginName)." <a href='http://html-color-codes.info/'>http://html-color-codes.info/</a>") ; 
					$params->add_title(__('Do you want to cache the images on the local disk?',$this->pluginName)) ; 
					$params->add_param('cache', __('Cache enabled:',$this->pluginName)) ; 
					
					$params->flush() ; 
					
					
					?>
				</div>
			</div>
			<!--fin de personnalisation-->
			<?php echo $this->signature ; ?>
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