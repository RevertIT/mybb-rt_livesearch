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

class Core
{
	/**
	 * @var array|string[]
	 */
	private static array $plugin_info;
	private static array|bool $cache_info;

	private static function plugin_info(): void
	{
		global $cache;

		static::$plugin_info = [
			'name' => 'RT Live Search',
			'description' => 'Is a plugin which utilizes native MyBB search functionality and provides result via ajax. Very light and highly customizable plugin for your search queries.',
			'website' => 'https://github.com/RevertIT/mybb-rt_livesearch',
			'author' => 'RevertIT',
			'authorsite' => 'https://github.com/RevertIT/',
			'version' => '1.0',
			'compatibility' => '18*',
			'codename' => 'rt_livesearch',
			'prefix' => 'rt_livesearch'
		];
		static::$cache_info = $cache->read(self::$plugin_info['prefix']);
	}

	public static function is_installed(): bool
	{
		global $mybb;

		if (isset($mybb->settings['rt_livesearch_enabled']))
		{
			return true;
		}

		return false;
	}

	public static function is_enabled(): bool
	{
		global $mybb;

		if (isset($mybb->settings['rt_livesearch_enabled']) && $mybb->settings['rt_livesearch_enabled'] !== '1')
		{
			return false;
		}

		return true;
	}
	public static function is_healthy(): bool|string
	{
		self::plugin_info();

		if (self::is_installed() && empty(self::$cache_info))
		{
			return <<<ERROR
			<br><span style="color: red; font-weight: 700">Error: There is an error with the plugin, please deactivate and activate again.</span>
			ERROR;
		}

		return true;
	}

	public static function is_current(): bool|string
	{
		self::plugin_info();

		$current = self::$cache_info;
		$version = self::$plugin_info['version'];

		if (!empty($current) && self::is_installed() && (version_compare(self::$plugin_info['version'], $current['version'], '>') || version_compare(self::$plugin_info['version'], $current['version'], '<')))
		{
			return <<<UPDATE
			<br><span style="color: darkorange; font-weight: 700">Important: Your current installed plugin version is {$current['version']}, but the current files are for version the {$version}. Please deactivate and activate again.</span>
			UPDATE;
		}

		return true;
	}

	public static function get_plugin_info(string $name = ''): array|string
	{
		self::plugin_info();

		return match(empty($name))
		{
			true => self::$plugin_info,
			default => self::$plugin_info[$name] ?? ''
		};
	}

	public static function set_cache(): void
	{
		global $cache;

		if (!empty(self::$plugin_info))
		{
			$cache->update(self::$plugin_info['prefix'], self::$plugin_info);
		}
	}

	public static function remove_cache(): void
	{
		global $cache;

		if (!empty($cache->read(self::$plugin_info['prefix'])))
		{
			$cache->delete(self::$plugin_info['prefix']);
		}
	}

	public static function add_settings(): void
	{
		global $PL;

		$PL->settings(self::$plugin_info['prefix'],
			'RT LiveSearch Settings',
			'General settings for the RT LiveSearch',
			[
				"enabled" => [
					'title' => 'Enable plugin?',
					'description' => 'Useful way to disable plugin without deleting templates/settings.',
					'optioncode' => 'yesno',
					'value' => 1
				],
				"keypress_enabled" => [
					'title' => 'Enable KeyPress search',
					'description' => 'Open quick search modal by pressing binded key.',
					'optioncode' => 'yesno',
					'value' => 1,
				],
				"keypress_letter" => [
					'title' => 'KeyPress letter',
					'description' => 'By default, it is bind to "s" letter, if you want to change, enter <u>exactly 1 letter</u>.',
					'optionscode' => 'text',
					'value' => 's'
				],
				"keypress_timeout" => [
					'title' => 'Search timeout (in ms)',
					'description' => 'Time between user input inside search area to fire up ajax, by default it is set to 1000ms = 1s.<br/><b>Notice:</b> Setting it to lower values will flood ajax requests.',
					'optionscode' => 'numeric',
					'value' => 1000
				],
				"keypress_usergroups" => [
					'title' => 'KeyPress Permissions',
					'description' => 'Which usergroups can use keypress?',
					'optionscode' => 'groupselect',
					'value' => '-1',
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

	public static function remove_settings(): void
	{
		global $PL;

		$PL->settings_delete(self::$plugin_info['prefix'], true);
	}

	public static function add_templates(): void
	{
		global $PL;

		$PL->templates(
			// Prevent underscore on template prefix
			str_replace('_', '', self::$plugin_info['prefix']),
			self::$plugin_info['name'],
			load_template_files('inc/plugins/'.self::$plugin_info['prefix'].'/templates/')
		);
	}

	public static function remove_templates(): void
	{
		global $PL;

		$PL->templates_delete(str_replace('_', '', self::$plugin_info['prefix']), true);
	}

	/**
	 * Frontend head html injection
	 *
	 * @return string|null
	 */
	public static function head_html_front(): string|null
	{
		global $mybb;

		self::plugin_info();

		$html = null;

		$html .= '<script src="'.$mybb->asset_url.'/jscripts/'.self::$plugin_info['prefix'].'.js?ver='.self::$plugin_info['version'].'"></script>' . PHP_EOL;

		$html .= '</head>';

		return $html;
	}

	/**
	 * Frontend body html injection
	 *
	 * @return string|null
	 * @throws \Exception
	 */
	public static function body_html_front(): string|null
	{
		global $mybb;

		$html = null;

		if (self::function_enabled('keypress'))
		{
			$load = 'modal';
			$keypress_url = '/misc.php?action='.self::$plugin_info['prefix'].'&load='.$load;
			$html .= '<script>LiveSearch.keypress("'.$keypress_url.'", "'.$mybb->settings['rt_livesearch_keypress_letter'].'")</script>' . PHP_EOL;
		}

		$html .= '</body>';

		return $html;
	}

	public static function edit_installed_templates(): void
	{
		// header
		$replace = '{$'.self::$plugin_info['prefix'].'}';
		$replace .= PHP_EOL;
		$replace .= '                        {$quicksearch}';
		edit_template("header", '{$quicksearch}', $replace);
	}

	public static function revert_installed_templates_changes(): void
	{
		// header
		$find = '                        {$'.self::$plugin_info['prefix'].'}';
		$find .= PHP_EOL;
		edit_template("header", $find, '');
	}

	/**
	 * @throws \Exception
	 */
	public static function function_enabled(string $function): bool
	{
		global $mybb;

		return match ($function)
		{
			'keypress' => isset($mybb->settings['rt_livesearch_keypress_usergroups'], $mybb->settings['rt_livesearch_keypress_enabled']) && (str_contains($mybb->settings['rt_livesearch_keypress_usergroups'], (string) $mybb->user['usergroup']) || $mybb->settings['rt_livesearch_keypress_usergroups'] === '-1') && $mybb->settings['rt_livesearch_keypress_enabled'] === '1',
			'customajax' => isset($mybb->settings['rt_livesearch_customajax_usergroups'], $mybb->settings['rt_livesearch_customajax_enabled']) && (str_contains($mybb->settings['rt_livesearch_customajax_usergroups'], (string) $mybb->user['usergroup']) || $mybb->settings['rt_livesearch_customajax_usergroups'] === '-1') && $mybb->settings['rt_livesearch_keypress_enabled'] === '1',
			default => throw new \Exception('Function not found'),
		};
	}

}
