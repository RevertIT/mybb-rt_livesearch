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
 * Hook: admin_load
 *
 * @return void
 */
function admin_load(): void
{
    global $db, $mybb, $lang, $run_module, $action_file, $page, $sub_tabs;

    if ($run_module === 'tools' && $action_file === Core::get_plugin_info('prefix'))
    {
        $rt_livesearch_prefix = Core::get_plugin_info('prefix');
        $lang->load($rt_livesearch_prefix);

        $page->add_breadcrumb_item($lang->{$rt_livesearch_prefix . '_menu'}, "index.php?module=tools-{$rt_livesearch_prefix}");

        $page_url = "index.php?module={$run_module}-{$action_file}";

        $sub_tabs = [];

        $allowed_actions = [
            'statistics',
        ];

        $tabs = [
            'statistics',
        ];

        foreach ($tabs as $row)
        {
            $sub_tabs[$row] = [
                'link' => $page_url . '&amp;action=' . $row,
                'title' => $lang->{$rt_livesearch_prefix .'_tab_' . $row},
                'description' => $lang->{$rt_livesearch_prefix . '_tab_' . $row . '_desc'},
            ];
        }

        if (!$mybb->input['action'] || $mybb->input['action'] === 'statistics')
        {
            $page->output_header($lang->{$rt_livesearch_prefix . '_menu'} . ' - ' . $lang->{$rt_livesearch_prefix .'_tab_' . 'statistics'});
            $page->output_nav_tabs($sub_tabs, 'statistics');

            // Query the data
            $sql_table = TABLE_PREFIX.'searchlog';

            $graph_all = $db->write_query(<<<SQL
			SELECT
				COUNT(*) AS count,
				DATE_FORMAT(FROM_UNIXTIME(dateline),
				'%M %d, %h %p') AS hour
			FROM
				{$sql_table}
			GROUP BY
				HOUR
			ORDER BY
				dateline ASC;
			SQL);

            $graph_ajax = $db->write_query(<<<SQL
			SELECT
				COUNT(*) AS count,
				DATE_FORMAT(FROM_UNIXTIME(dateline),
				'%M %d, %h %p') AS hour
			FROM
				{$sql_table}
			WHERE
				rt_ajax = 1
			GROUP BY
				HOUR
			ORDER BY
				dateline ASC;
			SQL);

            $graph_nonajax = $db->write_query(<<<SQL
			SELECT
				COUNT(*) AS count,
				DATE_FORMAT(FROM_UNIXTIME(dateline),
				'%M %d, %h %p') AS hour
			FROM
				{$sql_table}
			WHERE
				rt_ajax != 1
			GROUP BY
				HOUR
			ORDER BY
				dateline ASC;
			SQL);

            $pie_chart = $db->write_query(<<<SQL
			SELECT
				COUNT(resulttype) AS count,
				resulttype
			FROM
				mybb_searchlog
			GROUP BY
				resulttype
			ORDER BY
				count ASC;
			SQL);

            $pie_chart2 = $db->write_query(<<<SQL
			SELECT
				COUNT(resulttype) AS count,
				resulttype
			FROM
				mybb_searchlog
			WHERE
			    rt_ajax = 1
			GROUP BY
				resulttype
			ORDER BY
				count ASC;
			SQL);

            $pie_chart3 = $db->write_query(<<<SQL
			SELECT
				COUNT(resulttype) AS count,
				resulttype
			FROM
				mybb_searchlog
			WHERE
			    rt_ajax != 1
			GROUP BY
				resulttype
			ORDER BY
				count ASC;
			SQL);

            // Process the data into arrays for line charts
            $values_ajax = [];
            foreach ($graph_ajax as $row)
            {
                $values_ajax[] = $row['count'];
            }
            $values_ajax = json_encode($values_ajax);

            $values_nonajax = [];
            foreach ($graph_nonajax as $row)
            {
                $values_nonajax[] = $row['count'];
            }
            $values_nonajax = json_encode($values_nonajax);

            // Process the data into arrays for Chart.js
            $labels_both = [];
            $values_both = [];
            foreach ($graph_all as $row)
            {
                $labels_both[] = $row['hour'];
                $values_both[] = $row['count'];
            }
            $labels_both = json_encode($labels_both);
            $values_both = json_encode($values_both);

            // Process the data into arrays for pie charts
            $labels_piechart = [];
            $values_piechart = [];
            foreach ($pie_chart as $row)
            {
                $labels_piechart[] = ucfirst($row['resulttype']);
                $values_piechart[] = $row['count'];
            }
            $labels_piechart = json_encode($labels_piechart);
            $values_piechart = json_encode($values_piechart);

            $piechart_colors = json_encode([
                'rgba(54, 162, 235, 0.2)',
                'rgba(54, 162, 235, 0.3)',
            ]);

            $labels_piechart2 = [];
            $values_piechart2 = [];
            foreach ($pie_chart2 as $row)
            {
                $labels_piechart2[] = ucfirst($row['resulttype']);
                $values_piechart2[] = $row['count'];
            }
            $labels_piechart2 = json_encode($labels_piechart2);
            $values_piechart2 = json_encode($values_piechart2);

            $piechart_colors2 = json_encode([
                'rgba(153, 102, 255, 0.2)',
                'rgba(153, 102, 255, 0.3)'
            ]);

            $labels_piechart3 = [];
            $values_piechart3 = [];
            foreach ($pie_chart3 as $row)
            {
                $labels_piechart3[] = ucfirst($row['resulttype']);
                $values_piechart3[] = $row['count'];
            }
            $labels_piechart3 = json_encode($labels_piechart3);
            $values_piechart3 = json_encode($values_piechart3);

            $piechart_colors3 = json_encode([
                'rgba(255, 159, 64, 0.2)',
                'rgba(255, 159, 64, 0.3)',
            ]);

            $graph_html = <<<GRAPH
			<!-- Create a canvas element to hold the chart -->
			<canvas id="search_logs" style="width: 100%; height: 300px;"></canvas>
			
			<!-- Generate the chart using PHP data -->
			<script>
			const search_log = document.getElementById('search_logs').getContext('2d');
			new Chart(search_log, {
			  type: 'line',
			  data: {
				labels: {$labels_both},
				datasets: [
				  {
					label: '{$lang->rt_livesearch_graph_both_title}',
					data: {$values_both},
					backgroundColor: 'rgba(54, 162, 235, 0.2)',
					borderColor: 'rgba(54, 162, 235, 1)',
					borderWidth: 1,
				  },
				  {
					label: '{$lang->rt_livesearch_graph_ajax_title}',
					data: {$values_ajax},
					backgroundColor: 'rgba(153, 102, 255, 0.2)',
					borderColor: 'rgb(153, 102, 255)',
					borderWidth: 1,
				  },
				  {
					label: '{$lang->rt_livesearch_graph_nonajax_title}',
					data: {$values_nonajax},
					backgroundColor: 'rgba(255, 159, 64, 0.2)',
					borderColor: 'rgba(255, 159, 64, 1)',
					borderWidth: 1,
				  },
				]
			  },
			  options: {
				responsive: false,
				maintainAspectRatio: false,
				scales: {
				  xAxes: [{
					type: 'category',
					scaleLabel: {
					  display: true,
					  labelString: 'Hour'
					},
					ticks: {
					  beginAtZero: true
					}
				  }],
				  yAxes: [{
					scaleLabel: {
					  display: true,
					  labelString: 'Count'
					},
					ticks: {
					  beginAtZero: true
					}
				  }]
				}
			  }
			});
			</script>
			GRAPH;

            $piechart_html = <<<CHART
			<canvas id="search_type" width="300" height="300"></canvas>
			<script>
			const search_type = document.getElementById('search_type').getContext('2d');
			new Chart(search_type, {
				type: 'pie',
				data: {
					labels: {$labels_piechart},
					datasets: [{
						data: {$values_piechart},
						backgroundColor: {$piechart_colors},
						borderColor: {$piechart_colors},
						borderWidth: 1
					}]
				},
				options: {
					responsive: false,
					maintainAspectRatio: false,
				}
			});
			</script>
			CHART;

            $piechart_html2 = <<<CHART
			<canvas id="search_type2" width="300" height="300"></canvas>
			<script>
			const search_type2 = document.getElementById('search_type2').getContext('2d');
			new Chart(search_type2, {
				type: 'pie',
				data: {
					labels: {$labels_piechart2},
					datasets: [{
						data: {$values_piechart2},
						backgroundColor: {$piechart_colors2},
						borderColor: {$piechart_colors2},
						borderWidth: 1
					}]
				},
				options: {
					responsive: false,
					maintainAspectRatio: false,
				}
			});
			</script>
			CHART;

            $piechart_html3 = <<<CHART
			<canvas id="search_type3" width="300" height="300"></canvas>
			<script>
			const search_type3 = document.getElementById('search_type3').getContext('2d');
			new Chart(search_type3, {
				type: 'pie',
				data: {
					labels: {$labels_piechart3},
					datasets: [{
						data: {$values_piechart3},
						backgroundColor: {$piechart_colors3},
						borderColor: {$piechart_colors3},
						borderWidth: 1
					}]
				},
				options: {
					responsive: false,
					maintainAspectRatio: false,
				}
			});
			</script>
			CHART;

            echo '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
            $table = new \Table;
            $table->construct_header($lang->{$rt_livesearch_prefix . '_graph_desc'});
            $table->construct_cell($graph_html);
            $table->construct_row();
            $table->output($lang->{$rt_livesearch_prefix . '_graph_title'});


            $table->construct_header($lang->{$rt_livesearch_prefix . '_chart1_desc'});
            $table->construct_header($lang->{$rt_livesearch_prefix . '_chart2_desc'});
            $table->construct_header($lang->{$rt_livesearch_prefix . '_chart3_desc'});

            $table->construct_cell($piechart_html, [
                'class' =>  'align_center'
            ]);
            $table->construct_cell($piechart_html2, [
                'class' =>  'align_center'
            ]);
            $table->construct_cell($piechart_html3, [
                'class' =>  'align_center'
            ]);
            $table->construct_row();
            $table->output($lang->{$rt_livesearch_prefix . '_chart_title'});

            $page->output_footer();
        }

        try
        {
            if (!in_array($mybb->get_input('action'), $allowed_actions))
            {
                throw new \Exception('Not allowed!');
            }
        }
        catch (\Exception $e)
        {
            flash_message($e->getMessage(), 'error');
            admin_redirect("index.php?module=tools-{$rt_livesearch_prefix}");
        }
    }
}

/**
 * Hook: admin_config_action_handler
 *
 * @param array $actions
 * @return void
 */
function admin_tools_action_handler(array &$actions): void
{
    $rt_livesearch_prefix = Core::get_plugin_info('prefix');

    $actions[$rt_livesearch_prefix] = [
        'active'=> $rt_livesearch_prefix,
        'file'   => $rt_livesearch_prefix,
    ];
}

/**
 * Hook: admin_config_menu
 *
 * @param array $sub_menu
 * @return void
 */
function admin_tools_menu(array &$sub_menu): void
{
    global $lang;

    $rt_livesearch_prefix = Core::get_plugin_info('prefix');
    $lang->load($rt_livesearch_prefix);

    $sub_menu[] = [
        'id' => $rt_livesearch_prefix,
        'title' => $lang->rt_livesearch_menu,
        'link' => 'index.php?module=tools-' . $rt_livesearch_prefix,
    ];
}

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
            admin_redirect("index.php?module=tools-settings&action=change&gid=".(int)$mybb->input['gid']);
        }
        if (!preg_match('/[a-zA-Z]/', $mybb->input['upsetting']['rt_livesearch_keypress_letter']))
        {
            flash_message($lang->rt_livesearch_keypress_letter_alphabet, 'error');
            admin_redirect("index.php?module=tools-settings&action=change&gid=".(int)$mybb->input['gid']);
        }
    }
}