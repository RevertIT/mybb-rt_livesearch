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

use Exception;

class Core
{
    /**
     * @var array
     */
    public static array $PLUGIN_DETAILS = [
        'name' => 'RT Live Search',
        'description' => 'Is a plugin which utilizes native MyBB search functionality and provides result via ajax. Very light and highly customizable plugin for your search queries.',
        'website' => 'https://github.com/RevertIT/mybb-rt_livesearch',
        'author' => 'RevertIT',
        'authorsite' => 'https://github.com/RevertIT/',
        'version' => '1.8',
        'compatibility' => '18*',
        'codename' => 'rt_livesearch',
        'prefix' => 'rt_livesearch'
    ];

    /**
     * Get plugin info
     *
     * @param string $info
     * @return string
     */
    public static function get_plugin_info(string $info): string
    {
        return self::$PLUGIN_DETAILS[$info] ?? '';
    }

    /**
     * Check if plugin is installed
     *
     * @return bool
     */
    public static function is_installed(): bool
    {
        global $mybb;

        if (isset($mybb->settings['rt_livesearch_enabled']))
        {
            return true;
        }

        return false;
    }

    /**
     * Check if plugin is enabled
     *
     * @return bool
     */
    public static function is_enabled(): bool
    {
        global $mybb;

        if (isset($mybb->settings['rt_livesearch_enabled']) && (int) $mybb->settings['rt_livesearch_enabled'] !== 1)
        {
            return false;
        }

        return true;
    }

    /**
     * Set plugin cache
     *
     * @return void
     */
    public static function set_cache(): void
    {
        global $cache;

        if (!empty(self::$PLUGIN_DETAILS))
        {
            $cache->update(self::$PLUGIN_DETAILS['prefix'], self::$PLUGIN_DETAILS);
        }
    }

    /**
     * Delete plugin cache
     *
     * @return void
     */
    public static function remove_cache(): void
    {
        global $cache;

        if (!empty($cache->read(self::$PLUGIN_DETAILS['prefix'])))
        {
            $cache->delete(self::$PLUGIN_DETAILS['prefix']);
        }
    }

    /**
     * Add custom database columns on existing tables
     *
     * @return void
     */
    public static function add_database_columns(): void
    {
        global $db;

        if (!$db->field_exists('rt_ajax', 'searchlog'))
        {
            $db->add_column('searchlog', 'rt_ajax', "tinyint NOT NULL DEFAULT 0");
        }
    }

    /**
     * Remove custom database columns on existing tables
     *
     * @return void
     */
    public static function drop_database_columns(): void
    {
        global $mybb, $db, $lang, $page;

        $prefix = self::$PLUGIN_DETAILS['prefix'];

        if ($mybb->request_method !== 'post')
        {
            $lang->load($prefix);

            $page->output_confirm_action('index.php?module=config-plugins&action=deactivate&uninstall=1&plugin=' . self::$PLUGIN_DETAILS['prefix'], $lang->{$prefix . '_uninstall_message'}, $lang->uninstall);
        }

        // Drop tables
        if (!isset($mybb->input['no']))
        {
            if ($db->field_exists('rt_ajax', 'searchlog'))
            {
                $db->drop_column('searchlog', 'rt_ajax');
            }
        }
    }

    /**
     * Generate settings
     *
     * @return void
     */
    public static function add_settings(): void
    {
        global $PL;

        $PL->settings(self::$PLUGIN_DETAILS['prefix'],
            'RT LiveSearch Settings',
            'General settings for the RT LiveSearch',
            [
                "enabled" => [
                    'title' => 'Enable plugin?',
                    'description' => 'Useful way to disable plugin without deleting templates/settings.',
                    'optionscode' => 'yesno',
                    'value' => 1
                ],
                "keypress_enabled" => [
                    'title' => 'Enable KeyPress search',
                    'description' => 'Open quick search modal by pressing binded key.',
                    'optionscode' => 'yesno',
                    'value' => 1,
                ],
                "keypress_letter" => [
                    'title' => 'KeyPress letter',
                    'description' => 'By default, it is bind to "s" letter, if you want to change, enter <u>exactly 1 letter</u>.',
                    'optionscode' => 'text',
                    'value' => 's'
                ],
                "keypress_ctrl" => [
                    'title' => 'Require CTRL + letter',
                    'description' => 'Enabling this feature, will require user to press CTRL + bound key for pop-up search bar. Be aware, that default browser binding keys such as (ctrl + u, ctrl + t, etc.) will not be overwritten.',
                    'optionscode' => 'yesno',
                    'value' => 0
                ],
                "keypress_usergroups" => [
                    'title' => 'KeyPress Permissions',
                    'description' => 'Which usergroups can use keypress?',
                    'optionscode' => 'groupselect',
                    'value' => '-1',
                ],
                "quick_search_change" => [
                    'title' => 'Enable ajax quick search for native MyBB quick search box?',
                    'description' => 'This will attempt to find <b>{$quicksearch}</b> inside your templates and replace it with <b>{$rt_quicksearch}</b>.
					<br>Please check <b>rtlivesearch_quicksearch</b> template to make changes for styling if needed.',
                    'optionscode' => 'yesno',
                    'value' => 0
                ],
                "keypress_timeout" => [
                    'title' => 'Search timeout (in ms)',
                    'description' => 'Time between user input inside search area to fire up ajax, by default it is set to 1000ms = 1s.<br/><b>Notice:</b> Setting it to lower values will flood ajax requests.',
                    'optionscode' => 'numeric',
                    'value' => 1000
                ],
                "total_results" => [
                    'title' => 'Total results',
                    'description' => 'How many results should be returned with ajax search?',
                    'optionscode' => 'numeric',
                    'value' => 10,
                ],
            ]
        );
    }

    /**
     * Delete settings
     *
     * @return void
     */
    public static function remove_settings(): void
    {
        global $PL;

        $PL->settings_delete(self::$PLUGIN_DETAILS['prefix'], true);
    }

    /**
     * Add templates
     *
     * @return void
     */
    public static function add_templates(): void
    {
        global $PL;

        $PL->templates(
        // Prevent underscore on template prefix
            str_replace('_', '', self::$PLUGIN_DETAILS['prefix']),
            self::$PLUGIN_DETAILS['name'],
            load_template_files('inc/plugins/rt/src/LiveSearch/templates/')
        );
    }

    /**
     * Delete templates
     *
     * @return void
     */
    public static function remove_templates(): void
    {
        global $PL;

        $PL->templates_delete(str_replace('_', '', self::$PLUGIN_DETAILS['prefix']), true);
    }

    /**
     * Frontend head html injection
     *
     * @return string|null
     */
    public static function head_html_front(): ?string
    {
        global $mybb;

        $html = null;

        $html .= '<script src="'.$mybb->asset_url.'/jscripts/'.self::$PLUGIN_DETAILS['prefix'].'.js?ver='.self::$PLUGIN_DETAILS['version'].'"></script>' . PHP_EOL;

        $html .= '</head>';

        return $html;
    }

    /**
     * Frontend body html injection
     *
     * @return string|null
     * @throws Exception
     */
    public static function body_html_front(): ?string
    {
        global $mybb;

        $html = null;

        if (self::function_enabled('keypress'))
        {
            $ctrlKeyRequired = (int) $mybb->settings['rt_livesearch_keypress_ctrl'] === 1 ? 1 : 0;
            $load = 'modal';
            $keypress_url = '/misc.php?action='.self::$PLUGIN_DETAILS['prefix'].'&load='.$load;
            $html .= <<<HTML
                <script>LiveSearch.keypress('{$keypress_url}', '{$mybb->settings['rt_livesearch_keypress_letter']}', {$ctrlKeyRequired});</script>
            HTML;
        }

        $html .= '</body>';

        return $html;
    }

    /**
     * Find and replace existing templates with new values
     *
     * @return void
     */
    public static function edit_installed_templates(): void
    {
        // header
        $replace = '{$rt_quicksearch}';
        edit_template("header", '{$quicksearch}', $replace);
    }

    /**
     * Return existing templates to old values
     *
     * @return void
     */
    public static function revert_installed_templates_changes(): void
    {
        // header
        $find = '{$rt_quicksearch}';
        edit_template("header", $find, '{$quicksearch}');
    }

    /**
     * Check if plugin function is enabled and if user has permission to use it
     *
     * @param string $function Plugin function (keypress|customajax)
     * @return bool
     * @throws Exception
     */
    public static function function_enabled(string $function): bool
    {
        global $mybb;

        return match ($function)
        {
            'keypress' => isset($mybb->settings['rt_livesearch_keypress_usergroups'], $mybb->settings['rt_livesearch_keypress_enabled']) &&
                (str_contains($mybb->settings['rt_livesearch_keypress_usergroups'], (string) $mybb->user['usergroup']) || $mybb->settings['rt_livesearch_keypress_usergroups'] === '-1') &&
                (int) $mybb->settings['rt_livesearch_keypress_enabled'] === 1 &&
                (int) $mybb->usergroup['cansearch'] === 1,
            default => (int) $mybb->usergroup['cansearch'] === 1,
        };
    }
}
