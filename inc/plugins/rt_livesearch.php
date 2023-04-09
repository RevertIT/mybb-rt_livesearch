<?php
/**
 * RT LiveSearch
 *
 * Is a plugin which utilizes native MyBB search functionality and provides result via ajax.
 * Very light and highly customizable plugin for your search queries.
 *
 * @package rt_livesearch
 * @author  RevertIT <https://github.com/revertit>
 * @license http://opensource.org/licenses/mit-license.php MIT license
 */

declare(strict_types=1);

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
    die("Direct initialization of this file is not allowed.");
}

// Main files
require MYBB_ROOT . 'inc/plugins/rt_livesearch/src/Core.php';
require MYBB_ROOT . 'inc/plugins/rt_livesearch/src/functions.php';

// Hooks manager
require MYBB_ROOT . 'inc/plugins/rt_livesearch/src/Hooks/Backend.php';

if (\rt\LiveSearch\Core::is_enabled())
{
    require MYBB_ROOT . 'inc/plugins/rt_livesearch/src/Hooks/Frontend.php';
}

\rt\LiveSearch\autoload_hooks_via_namespace('rt\LiveSearch\Hooks');

function rt_livesearch_info(): array
{
    return [
        'name'			=> 	\rt\LiveSearch\Core::get_plugin_info('name'),
        'description'	=>  \rt\LiveSearch\Core::get_plugin_info('description') . \rt\LiveSearch\check_plugin_status(),
        'website'		=> 	\rt\LiveSearch\Core::get_plugin_info('website'),
        'author'		=>  \rt\LiveSearch\Core::get_plugin_info('author'),
        'authorsite'	=> 	\rt\LiveSearch\Core::get_plugin_info('authorsite'),
        'version'		=>  \rt\LiveSearch\Core::get_plugin_info('version'),
        'compatibility'	=>  \rt\LiveSearch\Core::get_plugin_info('compatibility'),
        'codename'		=>  \rt\LiveSearch\Core::get_plugin_info('codename'),
    ];
}

function rt_livesearch_install(): void
{
    \rt\LiveSearch\load_pluginlibrary();
    \rt\LiveSearch\check_php_version();

    \rt\LiveSearch\Core::set_cache();
    \rt\LiveSearch\Core::add_database_columns();
}

function rt_livesearch_is_installed(): bool
{
    return \rt\LiveSearch\Core::is_installed();
}

function rt_livesearch_uninstall(): void
{
    \rt\LiveSearch\load_pluginlibrary();
    \rt\LiveSearch\check_php_version();

    \rt\LiveSearch\Core::drop_database_columns();
    \rt\LiveSearch\Core::remove_settings();
    \rt\LiveSearch\Core::remove_cache();
}

function rt_livesearch_activate(): void
{
    \rt\LiveSearch\load_pluginlibrary();
    \rt\LiveSearch\check_php_version();

    \rt\LiveSearch\Core::add_settings();
    \rt\LiveSearch\Core::add_templates();
    \rt\LiveSearch\Core::set_cache();

    // TODO: Add hooks for custom search box via ajax function
    // \rt\LiveSearch\Core::edit_installed_templates();
}

function rt_livesearch_deactivate(): void
{
    \rt\LiveSearch\load_pluginlibrary();
    \rt\LiveSearch\check_php_version();

    \rt\LiveSearch\Core::remove_templates();
    \rt\LiveSearch\Core::revert_installed_templates_changes();
}