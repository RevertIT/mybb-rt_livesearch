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
 * PluginLibrary check loader
 *
 * @return void
 */
function load_pluginlibrary(): void
{
    global $lang, $PL;

    $lang->load(Core::get_plugin_info('prefix'));

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
            flash_message("PluginLibrary version is outdated, please update the plugin.", "error");
            admin_redirect("index.php?module=config-plugins");
        }
    }
    else
    {
        flash_message("PluginLibrary is missing.", "error");
        admin_redirect("index.php?module=config-plugins");
    }
}

/**
 * General PHP version check
 *
 * @return void
 */
function check_php_version(): void
{
    if (version_compare(PHP_VERSION, '8.0.0', '<'))
    {
        flash_message("PHP version must be at least 8.0.", "error");
        admin_redirect("index.php?module=config-plugins");
    }
}

/**
 * Determine the 'health' of the plugin
 *
 * @return string|null
 */
function check_plugin_status(): ?string
{
    global $cache, $lang;

    $lang->load(Core::get_plugin_info('prefix'));

    if (Core::is_current() !== true)
    {
        $installed = $cache->read(Core::get_plugin_info('prefix'))['version'] ?? 0;
        $current = Core::get_plugin_info('version');

        $outdated = $lang->sprintf($lang->{Core::get_plugin_info('prefix') . '_plugin_outdated'}, $installed, $current);

        return <<<UPDATE
			<br><span style="color: darkorange; font-weight: 700">{$outdated}</span>
			UPDATE;
    }
    if (Core::is_healthy() !== true)
    {
        return <<<ERROR
			<br><span style="color: red; font-weight: 700">{$lang->{Core::get_plugin_info('prefix') . '_plugin_unhealthy'}}</span>
			ERROR;
    }

    return null;
}

/**
 * Autoload hooks via namespace
 *
 * @copyright MyBB-Group
 *
 * @param string $namespace
 * @return void
 */
function autoload_hooks_via_namespace(string $namespace): void
{
    global $plugins;

    $namespace = strtolower($namespace);
    $user_functions = get_defined_functions()['user'];

    foreach ($user_functions as $function)
    {
        $namespace_prefix = strlen($namespace) + 1;

        if (substr($function, 0, $namespace_prefix) === $namespace . '\\')
        {
            $hook_name = substr_replace($function, '', 0, $namespace_prefix);
            $plugins->add_hook($hook_name, $namespace . '\\' . $hook_name);
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