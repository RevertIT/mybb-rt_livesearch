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
            $table = new \Table();

            // Query the data
            $table_prefix = TABLE_PREFIX;
            $sql_table = TABLE_PREFIX.'searchlog';

            $graph_all = $db->write_query(<<<SQL
			SELECT
				COUNT(*) AS `count`,
				DATE_FORMAT(FROM_UNIXTIME(dateline),
				'%M %d, %h %p') AS `hour`
			FROM
				{$sql_table}
			GROUP BY
				`hour`
			ORDER BY
				dateline ASC;
			SQL);

            $graph_ajax = $db->write_query(<<<SQL
			SELECT
				COUNT(*) AS `count`,
				DATE_FORMAT(FROM_UNIXTIME(dateline),
				'%M %d, %h %p') AS `hour`
			FROM
				{$sql_table}
			WHERE
				rt_ajax = 1
			GROUP BY
				`hour`
			ORDER BY
				dateline ASC;
			SQL);

            $graph_nonajax = $db->write_query(<<<SQL
			SELECT
				COUNT(*) AS `count`,
				DATE_FORMAT(FROM_UNIXTIME(dateline),
				'%M %d, %h %p') AS `hour`
			FROM
				{$sql_table}
			WHERE
				rt_ajax != 1
			GROUP BY
				`hour`
			ORDER BY
				dateline ASC;
			SQL);

            $pie_chart = $db->write_query(<<<SQL
			SELECT
				COUNT(resulttype) AS `count`,
				resulttype
			FROM
				{$sql_table}
			GROUP BY
				resulttype
			ORDER BY
				`count` ASC;
			SQL);

            $pie_chart2 = $db->write_query(<<<SQL
			SELECT
				COUNT(resulttype) AS `count`,
				resulttype
			FROM
				{$sql_table}
			WHERE
			    rt_ajax = 1
			GROUP BY
				resulttype
			ORDER BY
				`count` ASC;
			SQL);

            $pie_chart3 = $db->write_query(<<<SQL
			SELECT
				COUNT(resulttype) AS `count`,
				resulttype
			FROM
				{$sql_table}
			WHERE
			    rt_ajax != 1
			GROUP BY
				resulttype
			ORDER BY
				`count` ASC;
			SQL);

            $bar_chart = $db->write_query(<<<SQL
			SELECT
				COUNT(*) AS `count`,
				u.username
			FROM
				{$sql_table} s
			LEFT JOIN
				{$table_prefix}users u ON u.uid = s.uid
			GROUP BY
				u.username
			ORDER BY
				`count` DESC;
			SQL);

            $bar_chart2 = $db->write_query(<<<SQL
			SELECT
				COUNT(*) AS `count`,
				u.username
			FROM
				{$sql_table} s
			LEFT JOIN
				{$table_prefix}users u ON u.uid = s.uid
			WHERE
			    s.rt_ajax = 1
			GROUP BY
				u.username
			ORDER BY
				`count` DESC;
			SQL);

            $bar_chart3 = $db->write_query(<<<SQL
			SELECT
				COUNT(*) AS `count`,
				u.username
			FROM
				{$sql_table} s
			LEFT JOIN
				{$table_prefix}users u ON u.uid = s.uid
			WHERE
			    s.rt_ajax != 1
			GROUP BY
				u.username
			ORDER BY
				`count` DESC;
			SQL);

            $doughnut_chart = $db->write_query(<<<SQL
			SELECT
				COUNT(keywords) AS `count`,
				keywords
			FROM
				{$sql_table}
			GROUP BY
				keywords
			ORDER BY
				`count` DESC;
			SQL);

            $doughnut_chart2 = $db->write_query(<<<SQL
			SELECT
				COUNT(keywords) AS `count`,
				keywords
			FROM
				{$sql_table}
			WHERE
			    rt_ajax = 1
			GROUP BY
				keywords
			ORDER BY
				`count` DESC;
			SQL);

            $doughnut_chart3 = $db->write_query(<<<SQL
			SELECT
				COUNT(keywords) AS `count`,
				keywords
			FROM
				{$sql_table}
			WHERE
			    rt_ajax != 1
			GROUP BY
				keywords
			ORDER BY
				`count` DESC;
			SQL);

            // Process the data into arrays for line charts
            $labels_both = [];
            $values_both = [];
            foreach ($graph_all as $row)
            {
                $labels_both[] = $row['hour'];
                $values_both[] = $row['count'];
            }
            $labels_both = json_encode($labels_both);
            $values_both = json_encode($values_both);

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

            // Process the data into arrays for bar charts
            $labels_barchart = [];
            $values_barchart = [];
            $barchart_num = 0;
            foreach ($bar_chart as $row)
            {
                $barchart_num++;
                if ($barchart_num > 20)
                {
                    break;
                }
                if ($row['username'] === null)
                {
                    $row['username'] = $lang->guest;
                }
                $labels_barchart[] = ucfirst($row['username']);
                $values_barchart[] = $row['count'];
            }
            $labels_barchart = json_encode($labels_barchart);
            $values_barchart = json_encode($values_barchart);

            $labels_barchart2 = [];
            $values_barchart2 = [];
            $barchart2_num = 0;
            foreach ($bar_chart2 as $row)
            {
                $barchart2_num++;
                if ($barchart2_num > 20)
                {
                    break;
                }
                if ($row['username'] === null)
                {
                    $row['username'] = $lang->guest;
                }
                $labels_barchart2[] = ucfirst($row['username']);
                $values_barchart2[] = $row['count'];
            }
            $labels_barchart2 = json_encode($labels_barchart2);
            $values_barchart2 = json_encode($values_barchart2);

            $labels_barchart3 = [];
            $values_barchart3 = [];
            $barchart3_num = 0;
            foreach ($bar_chart3 as $row)
            {
                $barchart3_num++;
                if ($barchart3_num > 20)
                {
                    break;
                }
                if ($row['username'] === null)
                {
                    $row['username'] = $lang->guest;
                }
                $labels_barchart3[] = ucfirst($row['username']);
                $values_barchart3[] = $row['count'];
            }
            $labels_barchart3 = json_encode($labels_barchart3);
            $values_barchart3 = json_encode($values_barchart3);

            $labels_doughnut = [];
            $values_doughnut = [];
            $doughnut_num = 0;
            foreach ($doughnut_chart as $row)
            {
                $doughnut_num++;
                if ($doughnut_num > 20)
                {
                    break;
                }
                $labels_doughnut[] = ucfirst($row['keywords']);
                $values_doughnut[] = $row['count'];
            }
            $labels_doughnut = json_encode($labels_doughnut);
            $values_doughnut = json_encode($values_doughnut);

            $labels_doughnut2 = [];
            $values_doughnut2 = [];
            $doughnut2_num = 0;
            foreach ($doughnut_chart2 as $row)
            {
                $doughnut2_num++;
                if ($doughnut2_num > 20)
                {
                    break;
                }
                $labels_doughnut2[] = ucfirst($row['keywords']);
                $values_doughnut2[] = $row['count'];
            }
            $labels_doughnut2 = json_encode($labels_doughnut2);
            $values_doughnut2 = json_encode($values_doughnut2);

            $labels_doughnut3 = [];
            $values_doughnut3 = [];
            $doughnut3_num = 0;
            foreach ($doughnut_chart3 as $row)
            {
                $doughnut3_num++;
                if ($doughnut3_num > 20)
                {
                    break;
                }
                $labels_doughnut3[] = ucfirst($row['keywords']);
                $values_doughnut3[] = $row['count'];
            }
            $labels_doughnut3 = json_encode($labels_doughnut3);
            $values_doughnut3 = json_encode($values_doughnut3);

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

            $barchart_label = $lang->{$rt_livesearch_prefix . '_chart4_desc'};
            $barchart_html = <<<CHART
			<canvas id="user_log"></canvas>
			<script>
			const user_log = document.getElementById('user_log').getContext('2d');
			new Chart(user_log, {
				type: 'bar',
				data: {
					labels: {$labels_barchart},
					datasets: [{
						label: '{$barchart_label}',
						data: {$values_barchart},
						borderWidth: 1
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: true,
				}
			});
			</script>
			CHART;

            $barchart2_label = $lang->{$rt_livesearch_prefix . '_chart5_desc'};
            $barchart_html2 = <<<CHART
			<canvas id="user_log2"></canvas>
			<script>
			const user_log2 = document.getElementById('user_log2').getContext('2d');
			new Chart(user_log2, {
				type: 'bar',
				data: {
					labels: {$labels_barchart2},
					datasets: [{
						label: '{$barchart2_label}',
						data: {$values_barchart2},
						borderWidth: 1
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: true,
				}
			});
			</script>
			CHART;

            $barchart3_label = $lang->{$rt_livesearch_prefix . '_chart6_desc'};
            $barchart_html3 = <<<CHART
			<canvas id="user_log3"></canvas>
			<script>
			const user_log3 = document.getElementById('user_log3').getContext('2d');
			new Chart(user_log3, {
				type: 'bar',
				data: {
					labels: {$labels_barchart3},
					datasets: [{
						label: '{$barchart3_label}',
						data: {$values_barchart3},
						borderWidth: 1
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: true,
				}
			});
			</script>
			CHART;

            $doughnutchart_html = <<<CHART
			<canvas id="keyword_log" width="400" height="300"></canvas>
			<script>
			const keyword_log = document.getElementById('keyword_log').getContext('2d');
			new Chart(keyword_log, {
				type: 'doughnut',
				data: {
					labels: {$labels_doughnut},
					datasets: [{
						data: {$values_doughnut},
						borderWidth: 1
					}]
				},
				options: {
					responsive: false,
					maintainAspectRatio: false,
					plugins: {
						legend: {
							position: 'bottom',
						}
					}
				},
			});
			</script>
			CHART;

            $doughnutchart_html2 = <<<CHART
			<canvas id="keyword_log2" width="400" height="300"></canvas>
			<script>
			const keyword_log2 = document.getElementById('keyword_log2').getContext('2d');
			new Chart(keyword_log2, {
				type: 'doughnut',
				data: {
					labels: {$labels_doughnut2},
					datasets: [{
						data: {$values_doughnut2},
						borderWidth: 1
					}]
				},
				options: {
					responsive: false,
					maintainAspectRatio: false,
					plugins: {
						legend: {
							position: 'bottom',
						}
					}
				},
			});
			</script>
			CHART;

            $doughnutchart_html3 = <<<CHART
			<canvas id="keyword_log3" width="400" height="300"></canvas>
			<script>
			const keyword_log3 = document.getElementById('keyword_log3').getContext('2d');
			new Chart(keyword_log3, {
				type: 'doughnut',
				data: {
					labels: {$labels_doughnut3},
					datasets: [{
						data: {$values_doughnut3},
						borderWidth: 1
					}]
				},
				options: {
					responsive: false,
					maintainAspectRatio: false,
					plugins: {
						legend: {
							position: 'bottom',
						}
					}
				},
			});
			</script>
			CHART;

            echo '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
            // Line graph
            $table->construct_header($lang->{$rt_livesearch_prefix . '_graph_search_desc'});
            $table->construct_cell($graph_html);
            $table->construct_row();
            $table->output($lang->{$rt_livesearch_prefix . '_graph_search_title'});

            // Pie charts 1-3
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
            $table->output($lang->{$rt_livesearch_prefix . '_chart_type_title'});

            // Bar charts
            $table->construct_header($lang->{$rt_livesearch_prefix . '_chart4_desc'});
            $table->construct_header($lang->{$rt_livesearch_prefix . '_chart5_desc'});
            $table->construct_header($lang->{$rt_livesearch_prefix . '_chart6_desc'});

            $table->construct_cell($barchart_html, [
                'class' =>  'align_center',
                'width' => '33%'
            ]);
            $table->construct_cell($barchart_html2, [
                'class' =>  'align_center',
                'width' => '33%'
            ]);
            $table->construct_cell($barchart_html3, [
                'class' =>  'align_center',
                'width' => '33%'
            ]);
            $table->construct_row();
            $table->output($lang->{$rt_livesearch_prefix . '_chart_user_title'});

            // Doughnut
            $table->construct_header($lang->{$rt_livesearch_prefix . '_chart7_desc'});
            $table->construct_header($lang->{$rt_livesearch_prefix . '_chart8_desc'});
            $table->construct_header($lang->{$rt_livesearch_prefix . '_chart9_desc'});

            $table->construct_cell($doughnutchart_html, [
                'class' =>  'align_center',
                'width' => '33%'
            ]);
            $table->construct_cell($doughnutchart_html2, [
                'class' =>  'align_center',
                'width' => '33%'
            ]);
            $table->construct_cell($doughnutchart_html3, [
                'class' =>  'align_center',
                'width' => '33%'
            ]);
            $table->construct_row();
            $table->output($lang->{$rt_livesearch_prefix . '_chart_keywords_title'});

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
 * Hook: admin_tools_action_handler
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
 * Hook: admin_tools_menu
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

    if (isset($mybb->input['upsetting']['rt_livesearch_enabled']))
    {
        // Revert quick change to no when plugin is disabled
        if ((int) $mybb->input['upsetting']['rt_livesearch_enabled'] === 0 && (int) $mybb->input['upsetting']['rt_livesearch_quick_search_change'] === 1)
        {
            $mybb->input['upsetting']['rt_livesearch_quick_search_change'] = 0;
        }
    }
    if (isset($mybb->input['upsetting']['rt_livesearch_quick_search_change']))
    {
        if ((int) $mybb->input['upsetting']['rt_livesearch_quick_search_change'] === 1)
        {
            \rt\LiveSearch\Core::edit_installed_templates();
        }
        else
        {
            \rt\LiveSearch\Core::revert_installed_templates_changes();
        }
    }
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