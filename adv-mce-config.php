<?php
/*
Plugin Name: Advanced TinyMCE Config
Plugin URI: http://www.laptoptips.ca/projects/advanced-tinymce-configuration/
Description: Set advanced options for TinyMCE, the visual editor in WordPress.
Version: 1.2
Author: Andrew Ozz
Author URI: http://www.laptoptips.ca/

Released under the GPL v.2, http://www.gnu.org/copyleft/gpl.html

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
*/

function advmceconf_is_mce_version_4() {
	return ( isset( $GLOBALS['tinymce_version'] ) && substr( $GLOBALS['tinymce_version'], 0, 1 ) >= 4 );
}

add_action( 'pre_current_active_plugins', 'advmceconf_warn_plugins_screen' );
function advmceconf_warn_plugins_screen( $plugins ) {
	if ( advmceconf_is_mce_version_4() && get_option( 'advmceconf_version' ) < 12 ) {
		?>
		<div class="error"><p>
			<?php
			_e('The Advanced TinyMCE Config plugin requires attention: you are running TinyMCE 4.0 (WordPress 3.9 or newer) but the settings for the editor have not been updated. This can result in errors while editing, or the editor may fail completely.', 'advmceconf');
			?>
		</p></div>
		<?php
	}
}

add_filter( 'tiny_mce_before_init', 'advmceconf_config_mce', 1111 );
function advmceconf_config_mce( $config ) {
	global $plugin_page;

	if ( ! empty( $plugin_page ) && $plugin_page == 'advanced-tinymce-configuration/adv-mce-config.php' ) {
		global $advmceconf_show_defaults;

		$advmceconf_show_defaults = $config;
		return $config;
	}

	$options = get_option('advmceconf_options');

	if ( empty( $options ) || !is_array( $options ) )
		return $config;

	return array_merge( $config, $options );
}

add_action('admin_head-settings_page_advanced-tinymce-configuration/adv-mce-config', 'advmceconf_style');
function advmceconf_style() {
	?>
	<style type="text/css">
	.advmceconf-wrap {
		border: 1px solid #ddd;
		border-radius: 3px;
		background-color: #f8f8f8;
		padding: 0 12px;
		margin: 16px 0;
	}

	.advmceconf-table {
		table-layout: fixed;
		border-collapse: collapse;
		margin: 8px 0;
		width: 100%;
		clear: both;
	}

	#advmceconf-defaults {
		white-space: -moz-pre-wrap !important;
		word-wrap: break-word;
		white-space: pre-wrap;
	}

	.advmceconf-table td {
		padding: 7px 4px;
		line-height: 20px;
		vertical-align: top;
	}

	#advmceconf-defaults td {
		border-bottom: 1px solid #ddd;
	}

	.advmceconf-table th {
		font-weight: bold;
		text-shadow: rgba(255,255,255,1) 0 1px 0;
		padding: 10px 4px;
		border-bottom: 1px solid #ddd;
		text-align: left;
	}

	.advmceconf-table td.names input {
		width: 100%;
	}

	.advmceconf-table textarea {
		width: 99%;
		min-height: 80px;
	}

	.advmceconf-table th.names {
		width: 220px;
	}

	#advmceconf-set th.names {
		width: 250px;
	}

	#advmceconf-defaults .names {
		text-align: right;
	}

	.advmceconf-table .sep {
		width: 10px;
		text-align: center;
	}

	.advmceconf-table .actions {
		width: 100px;
		text-align: center;
	}

	.advmceconf-table td.actions {
		vertical-align: middle;
	}

	.advmceconf-hilite td {
		background: #fee;
	}
	</style>
	<?php
}

add_action( 'admin_menu', 'advmceconf_menu' );
function advmceconf_menu() {
    if ( function_exists('add_options_page') )
	   add_options_page( 'TinyMCE Config', 'TinyMCE Config', 'manage_options', __FILE__, 'advmceconf_admin' );
}

function advmceconf_admin() {
	global $advmceconf_show_defaults;

	if ( ! current_user_can('manage_options') )
		wp_die('Access denied');

	$message = '';
	$options = get_option( 'advmceconf_options', array() );
	$version = get_option( 'advmceconf_version', 0 );

	if ( ! empty($_POST['advmceconf_save']) ) {
		check_admin_referer('advmceconf-save-options');
		$old_options = $options;

		if ( empty( $_POST['advmceconf_options'] ) || ! is_array( $_POST['advmceconf_options'] ) )
			$new_options = array();
		else
			$new_options = $_POST['advmceconf_options'];

		if ( ! empty( $_POST['advmceconf-new'] ) && isset( $_POST['advmceconf-new-val'] ) )
			$new_options[ $_POST['advmceconf-new'] ] = $_POST['advmceconf-new-val'];

		foreach ( $new_options as $key => $val ) {
			$key = preg_replace( '/[^a-z0-9_]+/i', '', $key );
			if ( empty($key) )
				continue;

			if ( isset( $_POST[$key] ) && empty( $_POST[$key] ) ) {
				unset( $options[$key] );
				continue;
			}

			$val = stripslashes($val);
			if ( 'true' == $val )
				$options[$key] = true;
			elseif ( 'false' == $val )
				$options[$key] = false;
			else
				$options[$key] = $val;
		}

		if ( $options != $old_options ) {
			update_option('advmceconf_options', $options);
			$message = '<div class="updated fade"><p>' . __('Options saved.', 'advmceconf') . '</p></div>';
		}

		if ( $version < 12 && advmceconf_is_mce_version_4() ) {
			update_option( 'advmceconf_version', 12 );
			$version = 12;
		} elseif ( ! $version ) {
			update_option( 'advmceconf_version', 11 );
			$version = 11;
		}
	}

	?>
	<div class="wrap">
	<?php screen_icon(); ?>
	<h2><?php _e('Advanced TinyMCE Settings', 'advmceconf'); ?></h2>
	<?php

	if ( $version < 12 && advmceconf_is_mce_version_4() ) {
		?>
		<div class="error"><p>
		<?php
			_e('You are running TinyMCE 4.0 (WordPress 3.9 or newer) but the settings for the editor have not been updated. This can result in errors while editing, or the editor may fail completely. ', 'advmceconf');
			_e('(This notice will dissapear after the settings are updated.)', 'advmceconf');
		?>
		</p></div>
		<?php
	}

	if ( $message )
		echo $message;

	?>
	<div class="advmceconf-wrap">
	<p><?php _e('To add a setting to TinyMCE type the name on the left and the value on the right. Do not add quotes around the setting name or value. To remove a setting, delete both the name and value. To add boolean values type <code>true</code> or <code>false</code>, these strings are converted to boolean.', 'advmceconf'); ?></p>
	<p><?php _e('Description of all settings is available in the', 'advmceconf'); ?> <a href="http://www.tinymce.com/wiki.php" target="_blank"><?php _e('TinyMCE documentation', 'advmceconf'); ?></a>.</p>
	<p><?php _e('Several of the more commonly used settings are:', 'advmceconf'); ?></p>
	<ul class="ul-disc">
	<?php

	if ( advmceconf_is_mce_version_4() ) {
		?>
		<li><a href="http://www.tinymce.com/wiki.php/Configuration:block_formats" target="_blank">block_formats</a></li>
		<li><a href="http://www.tinymce.com/wiki.php/Configuration:style_formats" target="_blank">style_formats</a></li>
		<li><a href="http://www.tinymce.com/wiki.php/Configuration:invalid_elements" target="_blank">invalid_elements</a></li>
		<li><a href="http://www.tinymce.com/wiki.php/Configuration:extended_valid_elements" target="_blank">extended_valid_elements</a></li>
		<?php
	} else {
		?>
		<li><a href="http://www.tinymce.com/wiki.php/Configuration3x:theme_advanced_blockformats" target="_blank">theme_advanced_blockformats</a></li>
		<li><a href="http://www.tinymce.com/wiki.php/Configuration3x:theme_advanced_styles" target="_blank">theme_advanced_styles</a></li>
		<li><a href="http://www.tinymce.com/wiki.php/Configuration3x:theme_advanced_text_colors" target="_blank">theme_advanced_text_colors</a></li>
		<li><a href="http://www.tinymce.com/wiki.php/Configuration3x:theme_advanced_background_colors" target="_blank">theme_advanced_background_colors</a></li>
		<li><a href="http://www.tinymce.com/wiki.php/Configuration3x:invalid_elements" target="_blank">invalid_elements</a></li>
		<li><a href="http://www.tinymce.com/wiki.php/Configuration3x:extended_valid_elements" target="_blank">extended_valid_elements</a></li>
		<?php
	}

	?>
	</ul>
	</div>

	<table id="advmceconf-defaults" class="advmceconf-table showhide" style="display: none;">
	<thead><tr>
	<th class="names"><?php _e('Name', 'advmceconf'); ?></th>
	<th class="sep">&nbsp;</th>
	<th><?php _e('Value', 'advmceconf'); ?></th>
	<th class="actions">&nbsp;</th>
	</tr></thead>
	<tbody>
	<?php

	ob_start();
	wp_editor( '', 'content', array( 'media_buttons' => false, 'quicktags' => false ) );
	ob_end_clean();

	if ( !empty($advmceconf_show_defaults) && is_array($advmceconf_show_defaults) ) {
		$n = 1;
		$change = esc_attr( __('Change', 'advmceconf') );

		foreach ( $advmceconf_show_defaults as $def_field => $def_value ) {
			if ( is_bool( $def_value ) )
				$def_value = $def_value ? 'true' : 'false';
			?>

			<tr>
			<td id="name-<?php echo $n; ?>" class="names"><?php echo $def_field; ?></td>
			<td class="sep">:</td>
			<td id="val-<?php echo $n; ?>"><?php echo htmlspecialchars( $def_value, ENT_QUOTES ); ?></td>
			<td class="actions"><input type="button" data-num="<?php echo $n; ?>" class="button" value="<?php echo $change; ?>" /></td>
			</tr>
			<?php

			$n++;
		}
	}

?>
</tbody>
</table>

<p>
	<button type="button" class="button showhide"><?php _e('Show the default TinyMCE settings', 'advmceconf'); ?></button>
	<button type="button" class="button showhide" style="display: none;"><?php _e('Hide the default TinyMCE settings', 'advmceconf'); ?></button>
</p>

<form method="post" action="" style="padding:10px 0">
	<table class="advmceconf-table" id="advmceconf-set">
	<thead><tr>
	<th class="names"><?php _e('Option name', 'advmceconf'); ?></th>
	<th><?php _e('Value', 'advmceconf'); ?></th>
	<th class="actions">&nbsp;</th>
	</tr></thead>
	<tbody>
	<?php

	$remove = esc_attr( __('Remove', 'advmceconf') );
	foreach ( $options as $field => $value ) {
		$field = esc_attr( $field );
		$id = "advmceconf_option-{$field}";
		$name = "advmceconf_options[{$field}]";

		if ( is_bool($value) )
			$value = $value ? 'true' : 'false';

		?>
		<tr>
		<td class="names"><input type="text" name="<?php echo $field; ?>" id="<?php echo $field; ?>" value="<?php echo $field; ?>" /></td>
		<td><textarea name="<?php echo $name; ?>" id="<?php echo $id; ?>" spellcheck="false"><?php echo htmlspecialchars( $value, ENT_NOQUOTES ); ?></textarea></td>
		<td class="actions"><input type="button" class="button remove" value="<?php echo $remove; ?>" /></td>
		</tr>
		<?php
	}

	?>
	<tr>
	<td class="names"><input type="text" name="advmceconf-new" id="advmceconf-new" value="" /></td>
	<td><textarea name="advmceconf-new-val" id="advmceconf-new-val" spellcheck="false"></textarea></td>
	<td>&nbsp;</td>
	</tr>
	</tbody>
	</table>

	<p class="submit">
		<?php wp_nonce_field('advmceconf-save-options'); ?>
		<input type="submit" value="<?php esc_attr_e('Save Changes', 'advmceconf'); ?>" class="button-primary" name="advmceconf_save" />
	</p>
</form>
</div>

<script type="text/javascript">
jQuery(document).ready( function($) {
	var defaults = [];

	$('#advmceconf-defaults td.names').each( function(n, el) {
		defaults.push( $(el).text() );
	});

	$('#advmceconf-set td.names').each( function(n, el) {
		var text = $('input', el).val();

		if ( text && $.inArray(text, defaults) > -1 ) {
			$(el).parent().addClass('advmceconf-hilite');
			$('td.names:contains("' + text + '")', '#showhide').parent().addClass('advmceconf-hilite');
		}
	});
	
	$('button.showhide').on( 'click', function(e) {
		$('table.showhide, button.showhide').toggle();
	});

	$('#advmceconf-defaults').on( 'click', function(e) {
		var num = $(e.target).data('num');

		if ( num ) {
			$('#advmceconf-new').val( $( '#name-' + num ).text() );
			$('#advmceconf-new-val').val( $( '#val-' + num ).text() );
			scrollTo(0,50000);
		}
	});
	
	$('#advmceconf-set').on( 'click', function(e) {
		var target = $(e.target);

		if ( target.hasClass('remove') )
			target.closest('tr').find('input[type="text"], textarea').val('');
	});
});
</script>
<?php
}
