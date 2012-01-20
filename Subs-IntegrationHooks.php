<?php
/**
 * Integration Hooks Report (IHR)
 *
 * @package IHR
 * @author [SiNaN]
 * @2nd-author emanuele
 * @copyright 2011 [SiNaN], Simple Machines
 * @license http://www.simplemachines.org/about/smf/license.php BSD
 *
 * @version 1.3
 */

if (!defined('SMF'))
	die('Hacking attempt...');

function hooks_admin_areas($areas)
{
	global $context, $txt;

	loadLanguage('IntegrationHooks');

	$areas['config']['areas']['modsettings']['subsections']['hooks'] = array($txt['hooks_title_list']);
}

function hooks_modify_modifications($sub_actions)
{
	global $context;

	$sub_actions['hooks'] = 'list_integration_hooks';
	$context[$context['admin_menu_name']]['tab_data']['tabs']['hooks'] = array();
}

function list_integration_hooks()
{
	global $sourcedir, $scripturl, $context, $txt, $modSettings, $settings;

	if (!empty($_POST['remove_hooks']) && !empty($_POST['remove']) && is_array($_POST['remove']))
	{
		checkSession();

		foreach ($_POST['remove'] as $hook => $functions)
		{
			if (!is_array($functions))
				continue;

			foreach ($functions as $function)
				remove_integration_function($hook, $function);
		}
	}

	if (!empty($_POST['disable']))
	{
		checkSession();

		foreach ($_POST['disable'] as $hook => $functions)
		{
			if (!is_array($functions))
				continue;

			$active_hooks = explode(',', $modSettings[$hook]);
			foreach ($active_hooks as &$active_hook)
				$active_hook = trim($active_hook);

			foreach ($functions as $function => $state)
			{
				$function = trim($function);
				if ($state == 'disable' && in_array($function, $active_hooks))
				{
					remove_integration_function($hook, $function);
					// It's a hack I know...but I'm way too lazy!!!
					add_integration_function($hook, ']' . $function);
				}
				elseif ($state == 'enable' && in_array(']' . $function, $active_hooks))
				{
					remove_integration_function($hook, ']' . $function);
					// It's a hack I know...but I'm way too lazy!!!
					add_integration_function($hook, $function);
				}
			}
		}
	}

	$presentHooks = get_integration_hooks();

	$context['filter'] = (isset($_GET['filter']) && in_array($_GET['filter'], array_keys($presentHooks))) ? $_GET['filter'] : false;
	$sort = (isset($_GET['sort']) && in_array($_GET['sort'], array('hook_name', 'function_name', 'file_name', 'status'))) ? $_GET['sort'] : false;
	$sort_order = isset($_GET['desc']) ? ';desc' : '';

	$list_options = array(
		'id' => 'list_integration_hooks',
		'title' => $txt['hooks_title_list'],
		'items_per_page' => 20,
		'base_href' => $scripturl . '?action=admin;area=modsettings;sa=hooks;' . (!empty($context['filter']) ? ('filter=' . $context['filter'] . ';') : '') . (!empty($sort) ? ('sort=' . $sort . $sort_order .';') : '') . $context['session_var'] . '=' . $context['session_id'],
		'default_sort_col' => 'hook_name',
		'get_items' => array(
			'function' => 'get_integration_hooks_data',
		),
		'get_count' => array(
			'function' => 'get_integration_hooks_count',
		),
		'no_items_label' => $txt['hooks_no_hooks'],
		'columns' => array(
			'hook_name' => array(
				'header' => array(
					'value' => $txt['hooks_field_hook_name'],
				),
				'data' => array(
					'db' => 'hook_name',
				),
				'sort' =>  array(
					'default' => 'hook_name',
					'reverse' => 'hook_name DESC',
				),
			),
			'function_name' => array(
				'header' => array(
					'value' => $txt['hooks_field_function_name'],
				),
				'data' => array(
					'db' => 'function_name',
				),
				'sort' =>  array(
					'default' => 'function_name',
					'reverse' => 'function_name DESC',
				),
			),
			'file_name' => array(
				'header' => array(
					'value' => $txt['hooks_field_file_name'],
				),
				'data' => array(
					'db' => 'file_name',
				),
				'sort' =>  array(
					'default' => 'file_name',
					'reverse' => 'file_name DESC',
				),
			),
			'status' => array(
				'header' => array(
					'value' => $txt['hooks_field_hook_exists'],
					'style' => 'width:3%',
				),
				'data' => array(
					'function' => create_function('$data', '
						global $settings;

						$change_status = array(\'before\' => \'\', \'after\' => \'\');
						if ($data[\'can_be_disabled\'] && $data[\'status\'] != \'deny\')
						{
							$change_status[\'before\'] = \'<a href="" onclick="integrationHooks_switchstatus(this.id); return false;" id="\' . $data[\'id\'] . \'">\';
							$change_status[\'after\'] = \'</a><input id="input_\' . $data[\'id\'] . \'" type="hidden" name="disable[\' . $data[\'hook_name\'] . \'][\' . $data[\'function_name\'] . \']" value="\' . ($data[\'enabled\'] ? \'enable\' : \'disable\') . \'" />\';
						}
						return $change_status[\'before\'] . \'<img src="\' . $settings[\'images_url\'] . \'/admin/post_moderation_\' . $data[\'status\'] . \'.gif" alt="\' . $data[\'img_text\'] . \'" title="\' . $data[\'img_text\'] . \'" />\' . $change_status[\'after\'];
					'),
					'class' => 'centertext',
				),
				'sort' =>  array(
					'default' => 'status',
					'reverse' => 'status DESC',
				),
			),
			'check' => array(
				'header' => array(
					'value' => $txt['hooks_button_remove'],
					'style' => 'width:3%',
				),
				'data' => array(
					'function' => create_function('$data', '
						global $settings;

						if (!$data[\'hook_exists\'])
							return \'
							<a href="" onclick="integrationHooks_remove(this.id); return false;" id="remove_\' . $data[\'id\'] . \'">
								<img src="\' . $settings[\'images_url\'] . \'/icons/quick_remove.gif" alt="" />
							</a>
							<input id="input_remove_\' . $data[\'id\'] . \'" type="hidden" name="remove[\' . $data[\'hook_name\'] . \'][\' . $data[\'function_name\'] . \']" value="\' . ($data[\'enabled\'] ? \'enable\' : \'disable\') . \'" />\';
					'),
					'class' => 'centertext',
				),
			),
		),
		'form' => array(
			'href' => $scripturl . '?action=admin;area=modsettings;sa=hooks;' . (!empty($context['filter']) ? ('filter=' . $context['filter'] . ';') : '') . (!empty($sort) ? ('sort=' . $sort . $sort_order .';') : '') . $context['session_var'] . '=' . $context['session_id'],
			'name' => 'list_integration_hooks',
		),
		'additional_rows' => array(
			array(
				'position' => 'after_title',
				'value' => $txt['hooks_disable_instructions'] . '<br />
					' . $txt['hooks_disable_legend'] . ':
									<list>
					<li><img src="' . $settings['images_url'] . '/admin/post_moderation_allow.gif" alt="' . $txt['hooks_active'] . '" title="' . $txt['hooks_active'] . '" /> ' . $txt['hooks_disable_legend_exists'] . '</li>
					<li><img src="' . $settings['images_url'] . '/admin/post_moderation_moderate.gif" alt="' . $txt['hooks_disabled'] . '" title="' . $txt['hooks_disabled'] . '" /> ' . $txt['hooks_disable_legend_disabled'] . '</li>
					<li><img src="' . $settings['images_url'] . '/admin/post_moderation_deny.gif" alt="' . $txt['hooks_missing'] . '" title="' . $txt['hooks_missing'] . '" /> ' . $txt['hooks_disable_legend_missing'] . '</li>
				</list>'
			),
		),
	);

	require_once($sourcedir . '/Subs-List.php');

	createList($list_options);

	loadTemplate('IntegrationHooks');
	$context['page_title'] = $txt['hooks_title_list'];
	$context['sub_template'] = 'show_list';
	$context['template_layers'][] = 'integrationHooks';
	$context['default_list'] = 'list_integration_hooks';
}

function get_files_recursive($dir_path)
{
	$files = array();

	if ($dh = opendir($dir_path))
	{
		while (($file = readdir($dh)) !== false)
			if ($file != '.' && $file != '..')
			{
				if (is_dir($dir_path . '/' . $file))
					$files = array_merge($files, get_files_recursive($dir_path . '/' . $file));
				else
					$files[] = array('dir' => $dir_path, 'name' => $file);
			}
	}
	closedir($dh);

	return $files;
}

function get_integration_hooks_data($start, $per_page, $sort)
{
	global $boarddir, $sourcedir, $settings, $txt, $context, $scripturl;

	$hooks = $temp_hooks = get_integration_hooks();
	$hooks_data = $temp_data = $hook_status = array();

	$files = get_files_recursive($sourcedir);
	if (!empty($files))
		foreach ($files as $file)
		{
			if (is_file($file['dir'] . '/' . $file['name']) && substr($file['name'], -4) === '.php')
			{
				$fp = fopen($file['dir'] . '/' . $file['name'], 'rb');
				$fc = fread($fp, filesize($file['dir'] . '/' . $file['name']));
				fclose($fp);

				foreach ($temp_hooks as $hook => $functions)
				{
					foreach ($functions as $function_o)
					{
						$function = str_replace(']', '', $function_o);
						if (substr($hook, -8) === '_include')
						{
							$hook_status[$hook][$function]['exists'] = file_exists(strtr(trim($function), array('$boarddir' => $boarddir, '$sourcedir' => $sourcedir, '$themedir' => $settings['theme_dir'])));
							// I need to know if there is at least one function called in this file.
							$temp_data['include'][basename($function)] = array('hook' => $hook, 'function' => $function);
							unset($temp_hooks[$hook][$function_o]);
						}
						elseif (strpos($fc, 'function ' . trim($function) . '(') !== false || strpos($fc, 'function ' . trim($function) . ' (') !== false)
						{
							$hook_status[$hook][$function]['exists'] = true;
							$hook_status[$hook][$function]['in_file'] = $file['name'];
							// I want to remember all the functions called within this file (to check later if they are enabled or disabled and decide if the integrare_*_include of that file can be disabled too)
							$temp_data['function'][$file['name']][] = $function_o;
							unset($temp_hooks[$hook][$function_o]);
						}
					}
				}
			}
		}

	$sort_types = array(
		'hook_name' => array('hook', SORT_ASC),
		'hook_name DESC' => array('hook', SORT_DESC),
		'function_name' => array('function', SORT_ASC),
		'function_name DESC' => array('function', SORT_DESC),
		'file_name' => array('file_name', SORT_ASC),
		'file_name DESC' => array('file_name', SORT_DESC),
		'status' => array('status', SORT_ASC),
		'status DESC' => array('status', SORT_DESC),
	);

	$sort_options = $sort_types[$sort];
	$sort = array();
	$context['hooks_filters'] = '';
	$hooks_filters = array();

	foreach ($hooks as $hook => $functions)
	{
		$hooks_filters[] = '<option onclick="window.location = \'' . $scripturl . '?action=admin;area=modsettings;sa=hooks;filter=' . $hook . '\';"' . ($hook == $context['filter'] ? ' selected="selected"' : '') . '>' . $hook . '</option>';
		foreach ($functions as $function)
		{
			$function = str_replace(']', '', $function);
			// This is a not an include and the function is included in a certain file (if not it doesn't exists so don't care)
			if (substr($hook, -8) !== '_include' && isset($hook_status[$hook][$function]['in_file']))
			{
				$current_hook = $temp_data['include'][$hook_status[$hook][$function]['in_file']];
				$count = 0;

				// Checking all the functions within this particular file: Are they enabled?
				foreach ($temp_data['function'][$hook_status[$hook][$function]['in_file']] as $func)
					if (strstr($func, ']') !== false)
						$count++;

				// If any of them is enable then the file *must* be included and the integrate_*_include hook cannot be disabled
				if ($count != count($temp_data['function'][$hook_status[$hook][$function]['in_file']]))
					$hook_status[$current_hook['hook']][$current_hook['function']]['enabled'] = true;
			}
		}
	}

	if (!empty($hooks_filters))
		$context['hooks_filters'] = '<select style="margin-left:15px;">' . '<option>---</option><option onclick="window.location = \'' . $scripturl . '?action=admin;area=modsettings;sa=hooks\';">' . $txt['hooks_reset_filter'] . '</option>' . implode('', $hooks_filters) . '</select>';

	$temp_data = array();
	$id = 0;

	foreach ($hooks as $hook => $functions)
	{
		if (empty($context['filter']) || (!empty($context['filter']) && $context['filter'] == $hook))
			foreach ($functions as $function)
			{
				$enabled = strstr($function, ']') === false;
				$function = str_replace(']', '', $function);
				// Let's avoid the hooks of *this* mod are disabled by mistake. :P
				$thisMod = in_array($function, array('hooks_admin_areas', 'hooks_modify_modifications', '$sourcedir/Subs-IntegrationHooks.php'));
				$hook_exists = !empty($hook_status[$hook][$function]['exists']);
				$file_name = isset($hook_status[$hook][$function]['in_file']) ? $hook_status[$hook][$function]['in_file'] : ((substr($hook, -8) === '_include') ? 'zzzzzzzzz' : 'zzzzzzzza');
				$status = $hook_exists ? ($enabled ? 'a' : 'b') : 'c';
				$sort[] = $$sort_options[0];
				$temp_data[] = array(
					'id' => 'hookid_' . $id++,
					'hook_name' => $hook,
					'function_name' => $function,
					'file_name' => (isset($hook_status[$hook][$function]['in_file']) ? $hook_status[$hook][$function]['in_file'] : ''),
					'hook_exists' => $hook_exists,
					'status' => $hook_exists ? ($enabled ? 'allow' : 'moderate') : 'deny',
					'img_text' => $txt['hooks_' . ($hook_exists ? ($enabled ? 'active' : 'disabled') : 'missing')],
					'enabled' => $enabled,
					'can_be_disabled' => (isset($hook_status[$hook][$function]['enabled']) || $thisMod ? false : true),
				);
			}
	}

	array_multisort($sort, $sort_options[1], $temp_data);

	$counter = 0;
	$start++;

	foreach ($temp_data as $data)
	{
		if (++$counter < $start)
			continue;
		elseif ($counter == $start + $per_page)
			break;

		$hooks_data[] = $data;
	}

	return $hooks_data;
}

function get_integration_hooks_count()
{
	global $context;

	$hooks = get_integration_hooks();
	$hooks_count = 0;

	$context['filter'] = false;
	if (isset($_GET['filter']))
		$context['filter'] = $_GET['filter'];

	foreach ($hooks as $hook => $functions)
		if (empty($context['filter']) || (!empty($context['filter']) && $context['filter'] == $hook))
			$hooks_count += count($functions);

	return $hooks_count;
}

function get_integration_hooks()
{
	global $modSettings;
	static $integration_hooks;

	if (!isset($integration_hooks))
	{
		$integration_hooks = array();
		foreach ($modSettings as $key => $value)
		{
			if (!empty($value) && substr($key, 0, 10) === 'integrate_')
				$integration_hooks[$key] = explode(',', $value);
		}
	}

	return $integration_hooks;
}

?>