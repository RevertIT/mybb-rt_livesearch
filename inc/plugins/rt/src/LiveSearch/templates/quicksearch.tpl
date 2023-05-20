<div class="rt_quicksearch" id="search">
    <form action="search.php" class="rt_quicksearch_form">
        <input name="keywords" type="text" class="textbox rt_quicksearch_keywords" />
        <span class="rt_quicksearch_spinner"></span>
        <!-- START hidden input form fields -->
        <input name="action" type="hidden" value="do_search" />
        <input name="ext" type="hidden" value="rt_livesearch" />
        <input name="ajax" type="hidden" value="1" />
        <input name="my_post_key" type="hidden" value="{$mybb->post_code}" />
        <input name="showresults" type="hidden" value="threads" />
        <input type="hidden" name="postthread" value="1" />
        <input type="text" style="display: none;" />
        <!-- END Hidden input form fields -->
        <div class="rt_quicksearch_container tcat" style="display: none; position: absolute"></div>
    </form>
</div>
<script>
    LiveSearch.searchInput('.rt_quicksearch', {$mybb->settings['rt_livesearch_keypress_timeout']});
</script>
<style>
    .rt_quicksearch .trow2 {
        background: #2c2c2c;
        border-color: #222;
        padding: 3px;
    }
    .rt_quicksearch .trow1 {
        background: #252525;
        border-color: #333;
        padding: 3px;
    }
    .rt_quicksearch .error_message {
        color: #fff;
    }
</style>
