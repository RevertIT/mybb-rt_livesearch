<div class="modal" style="width: 490px">
    <div style="overflow-y: auto; max-height: 400px;" class="{$plugin_info['prefix']}_modal_keypress">
        <form method="get" class="{$plugin_info['prefix']}_modal_keypress_form">
            <input name="action" type="hidden" value="do_search" />
            <input name="my_post_key" type="hidden" value="{$mybb->post_code}" />
            <input name="ext" type="hidden" value="{$plugin_info['prefix']}" />
            <input name="ajax" type="hidden" value="1" />
            <input name="showresults" type="hidden" value="threads" />
            <input type="text" style="display: none;" />
            <table width="100%" cellspacing="{$theme['borderwidth']}" cellpadding="{$theme['tablespace']}" border="0" class="tborder">
                <tr>
                    <td class="thead" colspan="2"><strong>{$lang->rt_livesearch_keypress_title}</strong></td>
                </tr>
                <tr>
                    <td class="trow1" width="25%"><strong><label for="{$plugin_info['prefix']}_modal_keypress_keywords">{$lang->rt_livesearch_keypress_keywords}</label></strong></td>
                    <td class="trow1"><input name="keywords" id="{$plugin_info['prefix']}_modal_keypress_keywords" type="text" size="50" class="{$plugin_info['prefix']}_modal_keypress_keywords textbox initial_focus" />
                        <span class="{$plugin_info['prefix']}_modal_keypress_spinner"></span>
                        <div class="smalltext">
                            <input type="radio" class="radio" name="postthread" value="1" checked="checked" />{$lang->search_entire_post}<br />
                            <input type="radio" class="radio" name="postthread" value="2" />{$lang->search_titles_only}</div>
                    </td>
                </tr>
                <tr>
                    <td class="tcat" colspan="2">
                        <span class="smalltext">{$lang->rt_livesearch_keypress_results}</span>
                        <span style="float: right" class="smalltext {$plugin_info['prefix']}_modal_keypress_viewall"></span>
                    </td>
                </tr>
                <tr>
                    <td class="trow2 {$plugin_info['prefix']}_modal_keypress_container" colspan="2">{$lang->rt_livesearch_keypress_starttyping}</td>
                </tr>
            </table>
        </form>
        <script>
            LiveSearch.searchInput('.rt_livesearch_modal_keypress', {$mybb->settings['rt_livesearch_keypress_timeout']});
        </script>
    </div>
</div>
