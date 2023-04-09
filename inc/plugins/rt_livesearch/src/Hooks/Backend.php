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

namespace rt\LiveSearch\Hooks;

use rt\LiveSearch\Core;

/**
 * Hook: admin_config_settings_change
 *
 * @return void
 */
function admin_config_settings_change(): void
{
    global $mybb, $lang, $gid;

    $lang->load(Core::get_plugin_info('prefix'));

    if (isset($mybb->input['upsetting']['rt_livesearch_keypress_letter']))
    {
        if (strlen(trim_blank_chrs($mybb->input['upsetting']['rt_livesearch_keypress_letter'])) > 1)
        {
            flash_message($lang->rt_livesearch_keypress_letter_length, 'error');
            admin_redirect("index.php?module=config-settings&action=change&gid=".(int)$mybb->input['gid']);
        }
        if (!preg_match('/[a-zA-Z]/', $mybb->input['upsetting']['rt_livesearch_keypress_letter']))
        {
            flash_message($lang->rt_livesearch_keypress_letter_alphabet, 'error');
            admin_redirect("index.php?module=config-settings&action=change&gid=".(int)$mybb->input['gid']);
        }
    }
}