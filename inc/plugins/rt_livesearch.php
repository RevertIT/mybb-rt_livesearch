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
if (!defined("IN_MYBB"))
{
    die("Direct initialization of this file is not allowed.");
}

// Autoload classes
require_once MYBB_ROOT . 'inc/plugins/rt/vendor/autoload.php';

\rt\Autoload\psr4_autoloader(
    'rt',
    'src',
    'rt\\LiveSearch\\',
    [
        'rt/LiveSearch/functions.php',
    ]
);

$hooks = [];
// Hooks manager
if (defined('IN_ADMINCP'))
{
    $hooks[] = '\rt\LiveSearch\Hooks\Backend';
}
if (\rt\LiveSearch\Core::is_enabled())
{
    $hooks[] = '\rt\LiveSearch\Hooks\Frontend';
}

// Autoload plugin hooks
\rt\LiveSearch\autoload_plugin_hooks($hooks);

// Health checks
\rt\LiveSearch\load_plugin_version();
\rt\LiveSearch\load_pluginlibrary();

function rt_livesearch_info(): array
{
    return \rt\LiveSearch\Core::$PLUGIN_DETAILS;
}

function rt_livesearch_install(): void
{
    \rt\LiveSearch\check_php_version();
    \rt\LiveSearch\check_pluginlibrary();

    \rt\LiveSearch\Core::set_cache();
    \rt\LiveSearch\Core::add_database_columns();
}

function rt_livesearch_is_installed(): bool
{
    return \rt\LiveSearch\Core::is_installed();
}

function rt_livesearch_uninstall(): void
{
    \rt\LiveSearch\check_php_version();
    \rt\LiveSearch\check_pluginlibrary();

    \rt\LiveSearch\Core::drop_database_columns();
    \rt\LiveSearch\Core::remove_settings();
    \rt\LiveSearch\Core::remove_cache();
}

function rt_livesearch_activate(): void
{
    \rt\LiveSearch\check_php_version();
    \rt\LiveSearch\check_pluginlibrary();

    \rt\LiveSearch\Core::add_settings();
    \rt\LiveSearch\Core::add_templates();
    \rt\LiveSearch\Core::set_cache();
}

function rt_livesearch_deactivate(): void
{
    \rt\LiveSearch\check_php_version();
    \rt\LiveSearch\check_pluginlibrary();

    \rt\LiveSearch\Core::remove_templates();
    \rt\LiveSearch\Core::revert_installed_templates_changes();
}