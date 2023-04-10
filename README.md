## RT LiveSearch
Is a plugin which utilizes native MyBB search functionality and provides result via ajax.
Very light and highly customizable plugin for your search queries.

### Table of contents

1. [❗ Dependencies](#-dependencies)
2. [📃 Features](#-features)
3. [➕ Installation](#-installation)
4. [🔼 Update](#-update)
5. [➖ Removal](#-removal)
6. [❔ How-to: Add custom search box](#-how-to-add-custom-search-box)
7. [💡 Feature request](#-feature-request)
8. [🙏 Questions](#-questions)
9. [🐞 Bug reports](#-bug-reports)
8. [📷 Preview](#-preview)

### ❗ Dependencies
- MyBB 1.8.x
- https://github.com/frostschutz/MyBB-PluginLibrary (>= 13)
- PHP >= 8.0

### 📃 Features
- Ajax search with native MyBB search function.
- **ZERO** additional database queries!
- **KeyPress search**: Bind key (default "S") on your keyboard to open search popup modal at any time while not typing.
- **CustomAjaxSearch**: Attach ajax search on any HTML form you wish! Ref: [How-to: Add custom search box](#how-to-add-custom-search-box) 
- **Detailed search statistics**:
  - Provides a graph with detailed data for search queries on the forum
    - Total ajax/normal search queries
    - Total ajax search queries
    - Total normal search queries
  - Search type graphs (Threads / Posts)
    - Total ajax/normal search types (24 hrs)
    - Total ajax search types (24 hrs)
    - Total normal search types (24 hrs)
  - Search stats by users (See which users use search function the most and how many queries)
    - Total ajax/normal search queries by users (24 hrs)
    - Total ajax search queries by users (24 hrs)
    - Total normal search queries by users (24 hrs)
  - Most searched keywords
    - Most searched keywords via ajax/normal search queries (24 hrs)
    - Most searched keywords via ajax search queries (24 hrs)
    - Most searched keywords via normal search queries (24 hrs)
  - Replace/Revert MyBB quick search with ajax search via settings.
  - **Organized templates**
  - Easy to use configuration and settings.

### ➕ Installation
1. Copy the directories from the plugin inside your root MyBB installation.
2. Settings for the plugin are located in the "Plugin Settings" tab. (`/admin/index.php?module=config-settings`)

### 🔼 Update
1. Deactivate the plugin.
2. Replace the plugin files with the new files.
3. Activate the plugin again.

### ➖ Removal
1. Uninstall the plugin from your plugin manager.
2. _Optional:_ Delete all the RT LiveSearch plugin files from your MyBB folder.

### ❔ How-to: Add custom search box
This is a minimal configuration needed for form to fire up ajax
You can replace `custom_ajax*` with any other class
```smarty
<div class="custom_ajax">
  <form action="search.php" class="custom_ajax_form">
    <input name="keywords" type="text" class="textbox custom_ajax_keywords" />
    
    <!-- START hidden input form fields -->
    <input name="action" type="hidden" value="do_search" />
    <input name="ext" type="hidden" value="rt_livesearch" />
    <input name="ajax" type="hidden" value="1" />
    <input name="my_post_key" type="hidden" value="{$mybb->post_code}" />
    <input name="showresults" type="hidden" value="threads" />
    <input type="hidden" name="postthread" value="1" />
    <input type="text" style="display: none;" />
    <!-- END Hidden input form fields -->
    
    <!-- START Show ajax results/errors container -->
    <div class="custom_ajax_container" style="display: none; position: absolute"></div>
    <!-- END Show ajax results/errors container -->
    
  </form>
</div>
<script>LiveSearch.searchInput('.custom_ajax', {$mybb->settings['rt_livesearch_keypress_timeout']});</script>
```

### 💡 Feature request
Open a new idea by [clicking here](https://github.com/RevertIT/mybb-rt_livesearch/discussions/new?category=ideas)

### 🙏 Questions
Open a new question by [clicking here](https://github.com/RevertIT/mybb-rt_livesearch/discussions/new?category=q-a)

### 🐞 Bug reports
Open a new bug report by [clicking here](https://github.com/RevertIT/mybb-rt_livesearch/issues/new)

### 📷 Preview
<img src="https://i.postimg.cc/J0JcgcV7/ss1.png" alt="ss1"/>
<img src="https://i.postimg.cc/05JfbxMg/ss2.png" alt="ss2"/>
<img src="https://i.postimg.cc/tCQz6fWs/ss3.png" alt="ss3"/>
<img src="https://i.postimg.cc/6QM09qd3/ss6.png" alt="ss6"/>
<img src="https://i.postimg.cc/qMsxxLxj/ss4.png" alt="ss4"/>
<img src="https://i.postimg.cc/mgd2MSw4/ss5.png" alt="ss5"/>
<img src="https://i.postimg.cc/Y9jxj73x/ss7.png" alt="ss7"/>
