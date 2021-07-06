<?php
/*
Plugin Name: Weekly Plugin Update
Plugin URI: https://eatancepress.evdpl.com/en/
Description: This is Eatance Plugin Update.
Author: Eatance Team
Version: 1.0.0
Author URI: https://eatancepress.evdpl.com/en/
*/


//add_filter( 'auto_update_plugin', 'eatance_auto_update_plugins', 99, 2 );

add_filter( 'auto_update_plugin', '__return_true' )


add_filter('site_transient_update_plugins', 'weekly_edm_puc_push_update' );
 
function weekly_edm_puc_push_update( $transient ){
 
	if ( empty($transient->checked ) ) {
            return $transient;
        }
 
	// trying to get from cache first, to disable cache comment 10,20,21,22,24
	//if( false == $remote = get_transient( 'weekly_puc_upgrade_weekly-driver-management-update' ) ) {
 
		// info.json is the file with the actual plugin information on your server
		$remote = wp_remote_get( 'https://weekly.co/res-updates/manifest/weekly-driver-management.json', array(
			'timeout' => 10,
			'headers' => array(
				'Accept' => 'application/json'
			) )
		);
 
		if ( !is_wp_error( $remote ) && isset( $remote['response']['code'] ) && $remote['response']['code'] == 200 && !empty( $remote['body'] ) ) {
			set_transient( 'weekly_puc_upgrade_weekly-driver-management-update', $remote, 200 ); // 12 hours cache
		}
 
	//}
 
	if( $remote ) {
		
$current_plugin_version = et_get_plugin_version('/weekly-driver-management/weekly-driver-management.php');

 
		$remote = json_decode( $remote['body'] );
 
		// your installed plugin version should be on the line below! You can obtain it dynamically of course 


		
		if( $remote && version_compare( $current_plugin_version, $remote->version, '<' ) ) {
			$res_edd = new stdClass();
			$res_edd->slug = 'weekly-driver-management';
			$res_edd->plugin = 'weekly-driver-management/weekly-driver-management.php'; // it could be just YOUR_PLUGIN_SLUG.php if your plugin doesn't have its own directory
			$res_edd->new_version = $remote->version;
			$res_edd->tested = $remote->tested;
			$res_edd->package = $remote->download_url;
           		$transient->response[$res_edd->plugin] = $res_edd;
           		$transient->checked[$res_edd->plugin] = $remote->version;
           	}
 
	}
        return $transient;
}


add_action( 'upgrader_process_complete', 'weekly_edm_after_update', 10, 2 );
 
function weekly_edm_after_update( $upgrader_object, $options ) {
	if ( $options['action'] == 'update' && $options['type'] === 'plugin' )  {
		// just clean the cache when new plugin version is installed
		delete_transient( 'weekly_puc_upgrade_weekly-driver-management-update' );
	}
}


add_filter('plugins_api', 'weekly_edm_puc_plugin_info', 20, 3);

function weekly_edm_puc_plugin_info( $res_edd, $action, $args ){

	// do nothing if this is not about getting plugin information
	if( 'plugin_information' !== $action ) {
		return false;
	}

	$plugins = array ( 'weekly-book-management','weekly-plugin-update' );



	if ( in_array( $args->slug, $plugins ) ) {


			// trying to get from cache first
	if( false == $remote = get_transient( 'weekly_puc_upgrade_'. $args->slug.'-update' ) ) {

		// info.json is the file with the actual plugin information on your server
		$remote = wp_remote_get( 'https://weekly.co/res-updates/manifest/'.$args->slug.'.json', array(
			'timeout' => 10,
			'headers' => array(
				'Accept' => 'application/json'
			) )
		);

		if ( ! is_wp_error( $remote ) && isset( $remote['response']['code'] ) && $remote['response']['code'] == 200 && ! empty( $remote['body'] ) ) {
			set_transient( 'weekly_puc_upgrade_'. $args->slug.'-update', $remote, 43200 ); // 12 hours cache
		}
	
	}

	if( ! is_wp_error( $remote ) && isset( $remote['response']['code'] ) && $remote['response']['code'] == 200 && ! empty( $remote['body'] ) ) {

		$remote = json_decode( $remote['body'] );

		
		$res_edd = new stdClass();

		$res_edd->name = $remote->name;
		$res_edd->slug = $plugin_slug;
		$res_edd->version = $remote->version;
		$res_edd->tested = $remote->tested;
		$res_edd->requires = $remote->requires;
		$res_edd->author = '<a href="//weekly.co">weekly Team</a>';
		//$res_edd->author_profile = 'https://profiles.wordpress.org/rudrastyh';
		$res_edd->download_link = $remote->download_url;
		$res_edd->trunk = $remote->download_url;
		$res_edd->requires_php = '5.3';
		$res_edd->last_updated = $remote->last_updated;
		$res_edd->sections = array(
			'description' => $remote->sections->description,
			'installation' => $remote->sections->installation,
			'changelog' => $remote->sections->changelog
			// you can add your custom sections (tabs) here
		);

		if( !empty( $remote->sections->screenshots ) ) {
			$res_edd->sections['screenshots'] = $remote->sections->screenshots;
		}

		$res_edd->banners = array(
			'low' => 'https://via.placeholder.com/728x90.jpg',
			'high' => 'https://via.placeholder.com/1544x500.jpg'
		);
	
		return $res_edd;

	}
		// update plugin
		return true; 
	} else {
		// use default settings
		return false; 
	}



}