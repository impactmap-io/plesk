<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
	Module Name: GraphQL API module for Perfex CRM
    Module URI: https://codecanyon.net/item/perfex-graphql-api-query-all-crms-data-including-custom-modules/54869954
	Description: Access your complete Perfex database using dynamic GraphQL queries.
	Version: 1.0.1
	Requires at least: 2.9.*
	Author: Themesic Interactive
	Author URI: https://1.envato.market/themesic
*/

require_once __DIR__.'/vendor/autoload.php';

$CI = &get_instance();

define('GRAPHQL_MODULE', 'graphql');
define('GRAPHQL_MODULE_NAME', 'graphql');
$CI->load->helper(GRAPHQL_MODULE_NAME . '/graphql');
hooks()->add_action('admin_init', 'graphqlapi_init_menu_items');
modules\graphql\core\Apiinit::the_da_vinci_code(GRAPHQL_MODULE);
modules\graphql\core\Apiinit::ease_of_mind(GRAPHQL_MODULE);

/**
 * Load the module helper
 */
$CI->load->helper(GRAPHQL_MODULE_NAME . '/graphql');

/**
 * Register activation module hook
 */
register_activation_hook(GRAPHQL_MODULE_NAME, 'graphql_activation_hook');

function graphql_activation_hook()
{
    require_once(__DIR__ . '/install.php');
}

/**
 * Register language files, must be registered if the module is using languages
 */
register_language_files(GRAPHQL_MODULE_NAME, [GRAPHQL_MODULE_NAME]);


// Exclude GraphQL requests from CSRF
hooks()->add_action('admin_init', 'exclude_graphql');

function exclude_graphql() {
    $CI = &get_instance();
    $CI->load->config('migration');
    $update_info = $CI->config->item('migration_version');
    if(!get_option('current_perfex_version'))
    {
        update_option('current_perfex_version',$update_info);
    }
    if(!get_option('excluded_uri_for_graphqlintegration_once') || get_option('current_perfex_version') != $update_info)
    {
        
        
        $myfile = fopen(APPPATH."config/config.php", "a") or die("Unable to open file!");
        $txt = "if(!isset(\$config['csrf_exclude_uris']))
        {
            \$config['csrf_exclude_uris']=[];
        }";
        fwrite($myfile, "\n". $txt);
        $txt = "\$config['csrf_exclude_uris'] = array_merge(\$config['csrf_exclude_uris'],array('graphql'));";
        fwrite($myfile, "\n". $txt);
        fclose($myfile);
        update_option('current_perfex_version',$update_info);
        update_option('excluded_uri_for_graphqlintegration_once', 1);
    }
}


/**
 * Init graphqlapi module menu items in setup in admin_init hook
 * @return null
 */
function graphqlapi_init_menu_items()
{
    /**
    * If the logged in user is administrator, add custom menu in Setup
    */
    if (is_admin()) {
        $CI = &get_instance();
        $CI->app_menu->add_sidebar_menu_item('graphqlapi-options', [
            'collapse' => true,
            'name'     => _l('graphql'),
            'position' => 40,
            'icon'     => 'fa fa-plug',
        ]);
        
        $CI->app_menu->add_sidebar_children_item('graphqlapi-options', [
            'slug'     => 'graphqlusermanagement',
            'name'     => _l('graphql_token	'),
            'href'     => admin_url('graphql/graphqlusermanagement'), // Εδώ είναι το URL που θα καλέσει το controller
            'position' => 5,
        ]);
        
        $CI->app_menu->add_sidebar_children_item('graphqlapi-options', [
            'slug'     => 'graphqlapi-guide',
            'name'     => _l('graphqlapi_guide'),
            'href'     => 'https://perfexcrm.themesic.com/graphqlguide/',
            'position' => 10,
        ]);
    }
}

hooks()->add_action('app_init', GRAPHQL_MODULE.'_actLib');
function graphql_actLib()
{
    $CI = &get_instance();
    $CI->load->library(GRAPHQL_MODULE.'/Graphql_aeiou');
    $envato_res = $CI->graphql_aeiou->validatePurchase(GRAPHQL_MODULE);
    if (!$envato_res) {
        set_alert('danger', 'One of your modules failed its verification and got deactivated. Please reactivate or contact support.');
    }
}

hooks()->add_action('pre_activate_module', GRAPHQL_MODULE.'_sidecheck');
function graphql_sidecheck($module_name)
{
    if (GRAPHQL_MODULE == $module_name['system_name']) {
        modules\graphql\core\Apiinit::activate($module_name);
    }
}

hooks()->add_action('pre_deactivate_module', GRAPHQL_MODULE.'_deregister');
function graphql_deregister($module_name)
{
    if (GRAPHQL_MODULE == $module_name['system_name']) {
        delete_option(GRAPHQL_MODULE.'_verification_id');
        delete_option(GRAPHQL_MODULE.'_last_verification');
        delete_option(GRAPHQL_MODULE.'_product_token');
        delete_option(GRAPHQL_MODULE.'_heartbeat');
    }
}
