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

namespace rt\LiveSearch;

/**
 * Edit origin template
 *
 * @param string $title
 * @param string $find
 * @param string $replace
 * @return void
 */
function edit_template(string $title, string $find, string $replace): void
{
    // Include this file because it is where find_replace_templatesets is defined
    require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';

    // Edit the index template and add our variable to above {$forums}
    find_replace_templatesets($title, '#' . preg_quote($find) . '#', $replace);
}

/**
 * PHP version check
 *
 * @return void
 */
function check_php_version(): void
{
    if (version_compare(PHP_VERSION, '8.0.0', '<'))
    {
        flash_message("PHP version must be at least 8.0.x due to security reasons.", "error");
        admin_redirect("index.php?module=config-plugins");
    }
}

/**
 * PluginLibrary loader
 *
 * @return void
 */
function load_pluginlibrary(): void
{
    global $PL, $config, $mybb;

    if (!defined('PLUGINLIBRARY'))
    {
        define('PLUGINLIBRARY', MYBB_ROOT . 'inc/plugins/pluginlibrary.php');
    }

    if (file_exists(PLUGINLIBRARY))
    {
        if (!$PL)
        {
            require_once PLUGINLIBRARY;
        }
        if (version_compare((string) $PL->version, '13', '<'))
        {
            Core::$PLUGIN_DETAILS['description'] .= <<<DESC
			<br/>
			<b style="color: orange">
				<img src="{$mybb->settings['bburl']}/{$config['admin_dir']}/styles/default/images/icons/warning.png" alt="">
				PluginLibrary version is outdated. You can update it by <a href="https://community.mybb.com/mods.php?action=view&pid=573" target="_blank">clicking here</a>.
			</b>
			DESC;
        }
        else
        {
            Core::$PLUGIN_DETAILS['description'] .= <<<DESC
			<br/>
			<b style="color: green">
				<img src="{$mybb->settings['bburl']}/{$config['admin_dir']}/styles/default/images/icons/tick.png" alt="">
				PluginLibrary (ver-{$PL->version}) is installed.
			</b>
			DESC;
        }
    }
    else
    {
        Core::$PLUGIN_DETAILS['description'] .= <<<DESC
		<br/>
		<b style="color: orange">
			<img src="{$mybb->settings['bburl']}/{$config['admin_dir']}/styles/default/images/icons/warning.png" alt="">
			PluginLibrary is missing. You can download it by <a href="https://community.mybb.com/mods.php?action=view&pid=573" target="_blank">clicking here</a>.
		</b>
		DESC;
    }
}

/**
 * PluginLibrary install checker
 *
 * @return void
 */
function check_pluginlibrary(): void
{
    global $PL;

    if (!defined('PLUGINLIBRARY'))
    {
        define('PLUGINLIBRARY', MYBB_ROOT . 'inc/plugins/pluginlibrary.php');
    }

    if (file_exists(PLUGINLIBRARY))
    {
        if (!$PL)
        {
            require_once PLUGINLIBRARY;
        }
        if (version_compare((string) $PL->version, '13', '<'))
        {
            flash_message("PluginLibrary version is outdated. You can update it by <a href=\"https://community.mybb.com/mods.php?action=view&pid=573\">clicking here</a>.", "error");
            admin_redirect("index.php?module=config-plugins");
        }
    }
    else
    {
        flash_message("PluginLibrary is missing. You can download it by <a href=\"https://community.mybb.com/mods.php?action=view&pid=573\">clicking here</a>.", "error");
        admin_redirect("index.php?module=config-plugins");
    }
}

/**
 * Plugin version loader
 *
 * @return void
 */
function load_plugin_version(): void
{
    global $cache, $mybb, $config;

    $cached_version = $cache->read(Core::get_plugin_info('prefix'));
    $current_version = Core::get_plugin_info('version');

    if (isset($cached_version['version'], $current_version))
    {
        if (version_compare($cached_version['version'], Core::get_plugin_info('version'), '<'))
        {
            Core::$PLUGIN_DETAILS['description'] .= <<<DESC
			<br/>
			<b style="color: orange">
			<img src="{$mybb->settings['bburl']}/{$config['admin_dir']}/styles/default/images/icons/warning.png" alt="">
			RT LiveSearch version missmatch. You need to deactivate and activate plugin again.
			</b>
			DESC;
        }
        else
        {
            Core::$PLUGIN_DETAILS['description'] .= <<<DESC
			<br/>
			<b style="color: green">
			<img src="{$mybb->settings['bburl']}/{$config['admin_dir']}/styles/default/images/icons/tick.png" alt="">
			RT LiveSearch (ver-{$current_version}) is up-to-date and ready for use.
			</b>
			DESC;
        }
    }
}

/**
 * Autoload plugin hooks
 *
 * @param array $class Array of classes to load for hooks
 * @return void
 */
function autoload_plugin_hooks(array $class): void
{
    global $plugins;

    foreach ($class as $hook)
    {
        if (!class_exists($hook))
        {
            continue;
        }

        $user_functions = get_class_methods(new $hook());

        foreach ($user_functions as $function)
        {
            $plugins->add_hook($function, [new $hook(), $function]);
        }
    }
}

/**
 * Template files content loader
 *
 * @param string $path
 * @param string $ext
 * @return array
 */
function load_template_files(string $path, string $ext = '.tpl'): array
{
    $path = MYBB_ROOT . $path;
    $templates = [];

    foreach (new \DirectoryIterator($path) as $tpl)
    {
        if (!$tpl->isFile() || $tpl->getExtension() !== pathinfo($ext, PATHINFO_EXTENSION))
        {
            continue;
        }
        $name = basename($tpl->getFilename(), $ext);
        $templates[$name] = file_get_contents($tpl->getPathname());
    }

    return $templates;
}

/**
 * Cache templates on demand
 *
 * @param string|array $templates
 * @return void
 */
function load_templatelist(string|array $templates): void
{
    global $templatelist;

    $templates = match (is_array($templates))
    {
        true => implode(',', array_map(function ($template) {
            return str_replace('_', '', Core::get_plugin_info('prefix')) . '_' . $template;
        }, $templates)),
        default => str_replace('_', '', Core::get_plugin_info('prefix')) . '_' . $templates
    };

    $templatelist .= ',' . $templates;
}

/**
 * Load templates
 * 
 * @param string $name
 * @param bool $modal True if you want to load no html comments for modal
 * @return string
 */
function template(string $name, bool $modal = false): string
{
    global $templates;

    $name = str_replace('_', '', Core::get_plugin_info('prefix')) . '_' . $name;

    return match ($modal)
    {
        true => $templates->get($name, 1, 0),
        default => $templates->get($name)
    };
}