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
 * Hook: global_start
 *
 * @return void
 * @throws \Exception
 */
function global_start(): void
{
    global $mybb, $rt_livesearch;

    // Cache templates
    switch(\THIS_SCRIPT)
    {
        case 'misc.php':
            if (Core::function_enabled('keypress') &&
                $mybb->get_input('action') === Core::get_plugin_info('prefix') &&
                $mybb->get_input('load') === 'modal'
            )
            {
                \rt\LiveSearch\load_templatelist('keypress_modal');
            }
            break;
        case 'search.php':
            if ($mybb->get_input('ext') === Core::get_plugin_info('prefix'))
            {
                \rt\LiveSearch\load_templatelist('search_results_threads_thread');
            }
            break;
    };
}

/**
 * Hook: pre_output_page
 *
 * @param string $content
 * @return string
 * @throws \Exception
 */
function pre_output_page(string $content): string
{
    global $mybb;

    $head = Core::head_html_front();
    $content = str_replace('</head>', $head, $content);

    $body = Core::body_html_front();
    $content = str_replace('</body>', $body, $content);

    return $content;
}

/**
 * Hook: misc_start
 *
 * @return void
 */
function misc_start(): void
{
    global $mybb, $lang, $theme;

    if ($mybb->get_input('action') === Core::get_plugin_info('prefix'))
    {
        $lang->load(Core::get_plugin_info('prefix'));
        $plugin_info = Core::get_plugin_info();

        if ($mybb->get_input('load') === 'modal')
        {
            $lang->load("search");

            eval('$page = "' . \rt\LiveSearch\template('keypress_modal', true) . '";');
            output_page($page);
            exit;
        }
    }
}

/**
 * Hook: search_do_search_start
 *
 * @return void
 */
function search_do_search_start(): void
{
    global $mybb;

    // Use @var rt_livesearch_keypress_timeout for timeout when doing ajax search
    if ($mybb->get_input('ext') === Core::get_plugin_info('prefix'))
    {
        $mybb->settings['searchfloodtime'] = round($mybb->settings['rt_livesearch_keypress_timeout'] / 1000);
    }
}

/**
 * Hook: search_do_search_process
 *
 * @return void
 */
function search_do_search_process(): void
{
    global $mybb, $searcharray;

    // Set 'rt_ajax' column to 1 when searching via ajax into 'searchlog' table
    if ($mybb->get_input('ext') === Core::get_plugin_info('prefix'))
    {
        $searcharray['rt_ajax'] = 1;
    }
}

/**
 * Hook: search_do_search_end
 *
 * @return void
 */
function search_do_search_end(): void
{
    global $mybb, $sid, $sortorder, $sortby, $lang;

    if ($mybb->get_input('ext') === Core::get_plugin_info('prefix'))
    {
        $prefix = Core::get_plugin_info('prefix');

        if (!verify_post_check($mybb->get_input('my_post_key'), true))
        {
            error($lang->invalid_post_code);
        }

        $json_data = (object) [
            'status' => true,
            'url' => "{$mybb->settings['bburl']}/search.php?action=results&sid={$sid}&sortby={$sortby}&order={$sortorder}&ext={$prefix}",
            'redirect_url' => "{$mybb->settings['bburl']}/search.php?action=results&sid={$sid}&sortby={$sortby}&order={$sortorder}",
        ];
        header('Content-Type: application/json');
        echo json_encode($json_data);
        exit;
    }
}

/**
 * Hook: search_results_thread
 *
 * @return void
 */
function search_results_thread(): void
{
    global $mybb, $lang, $cache, $templates, $thread_cache, $parser, $parser_options, $forumcache, $highlight;

    if ($mybb->get_input('ext') === Core::get_plugin_info('prefix'))
    {
        $lang->load(Core::get_plugin_info('prefix'));
        $lang->load("search");

        $forumcache = $cache->read("forums");

        $template = [];
        $sliced_threads = 0;
        $total_threads = count((array) $thread_cache);

        if (!empty($mybb->settings['rt_livesearch_total_results']))
        {
            $thread_cache = array_slice($thread_cache, 0, (int) $mybb->settings[Core::get_plugin_info('prefix') . '_total_results'], true);
            $sliced_threads = count($thread_cache);
        }

        $view_all = '';
        if ($total_threads > $sliced_threads)
        {
            $view_all = $lang->sprintf($lang->{Core::get_plugin_info('prefix') . '_view_all'}, $total_threads);
        }

        foreach ($thread_cache as $thread)
        {
            $bgcolor = alt_trow();
            // Unapproved colour
            if($thread['visible'] == 0)
            {
                $bgcolor = 'trow_shaded';
            }
            elseif($thread['visible'] == -1)
            {
                $bgcolor = 'trow_shaded trow_deleted';
            }
            if($thread['userusername'])
            {
                $thread['username'] = $thread['userusername'];
            }
            $thread['username'] = htmlspecialchars_uni($thread['username']);
            $thread['profilelink'] = build_profile_link($thread['username'], $thread['uid']);

            $thread['subject'] = $parser->parse_badwords($thread['subject']);
            $thread['subject'] = htmlspecialchars_uni($thread['subject']);
            $lastpostdate = my_date('relative', $thread['lastpost']);
            $thread['lastpostlink'] = get_thread_link($thread['tid'], 0, "lastpost");

            $thread['forumlink'] = '';
            if($forumcache[$thread['fid']])
            {
                $thread['forumlink_link'] = get_forum_link($thread['fid']);
                $thread['forumlink_name'] = $forumcache[$thread['fid']]['name'];
                eval("\$thread['forumlink'] = \"".$templates->get("search_results_threads_forumlink")."\";");
            }

            $lastposteruid = $thread['lastposteruid'];
            if(!$lastposteruid && !$thread['lastposter'])
            {
                $lastposter = htmlspecialchars_uni($lang->guest);
            }
            else
            {
                $lastposter = htmlspecialchars_uni($thread['lastposter']);
            }

            $thread_link = get_thread_link($thread['tid']);

            // Don't link to guest's profiles (they have no profile).
            if($lastposteruid == 0)
            {
                $lastposterlink = $lastposter;
            }
            else
            {
                $lastposterlink = build_profile_link($lastposter, $lastposteruid);
            }

            $thread['replies'] = my_number_format($thread['replies']);
            $thread['views'] = my_number_format($thread['views']);

            eval('$results = "' . \rt\LiveSearch\template('search_results_threads_thread', true) . '";');

            $template[] = $results;
        }
        header('Content-type: application/json');
        $json = (object) [
            'status' => true,
            'template' => $template,
            'view_all' => $view_all
        ];
        echo json_encode($json);
        exit;
    }
}