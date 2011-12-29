<?php

if (!defined('SMF'))
    die('Hacking attempt...');

function hooks_admin_areas($areas)
{
    global $context, $txt;

    loadLanguage('IntegrationHooks');

    $areas['config']['areas']['modsettings']['subsections']['hooks'] = array($txt['hooks_title_list']);

    if (!empty($_REQUEST['area']) && $_REQUEST['area'] == 'modsettings' && !empty($_REQUEST['sa']) && $_REQUEST['sa'] == 'hooks')
        $context['hooks_area'] = true;
}

function hooks_modify_modifications($sub_actions, $tabs)
{
    global $context;

    $sub_actions['hooks'] = 'list_integration_hooks';
    $tabs['hooks'] = array();

    if (!empty($context['hooks_area']))
        $context['sub_action'] = $_REQUEST['sa'] = 'hooks';
}

function list_integration_hooks()
{
    global $sourcedir, $scripturl, $context, $txt;

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

    $list_options = array(
        'id' => 'list_integration_hooks',
        'title' => $txt['hooks_title_list'],
        'items_per_page' => 20,
        'base_href' => $scripturl . '?action=admin;area=modsettings;sa=hooks;' . $context['session_var'] . '=' . $context['session_id'],
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
            'hook_exists' => array(
                'header' => array(
                    'value' => $txt['hooks_field_hook_exists'],
                ),
                'data' => array(
                    'db' => 'hook_exists_text',
                    'class' => 'centertext',
                ),
                'sort' =>  array(
                    'default' => 'hook_exists',
                    'reverse' => 'hook_exists DESC',
                ),
            ),
            'check' => array(
                'header' => array(
                    'value' => '<input type="checkbox" onclick="invertAll(this, this.form);" class="input_check" />',
                ),
                'data' => array(
                    'function' => create_function('$data', '
                        return \'<input type="checkbox" name="remove[\' . $data[\'hook_name\'] . \'][]" value="\' . $data[\'function_name\'] . \'"\' . ($data[\'hook_exists\'] ? \' disabled="disabled"\' : \'\') . \'  class="input_check" />\';
                    '),
                    'class' => 'centertext',
                ),
            ),
        ),
        'form' => array(
            'href' => $scripturl . '?action=admin;area=modsettings;sa=hooks;' . $context['session_var'] . '=' . $context['session_id'],
        ),
        'additional_rows' => array(
            array(
                'position' => 'below_table_data',
                'value' => '<input type="submit" name="remove_hooks" value="' . $txt['hooks_button_remove'] . '" class="button_submit" />',
                'class' => 'righttext',
            ),
        ),
    );

    require_once($sourcedir . '/Subs-List.php');

    createList($list_options);

    $context['page_title'] = $txt['hooks_title_list'];
    $context['sub_template'] = 'show_list';
    $context['default_list'] = 'list_integration_hooks';
}

function get_integration_hooks_data($start, $per_page, $sort)
{
    global $boarddir, $sourcedir, $settings, $txt;

    $hooks = $temp_hooks = get_integration_hooks();
    $hooks_data = $temp_data = $hook_status = array();

    if ($dh = opendir($sourcedir))
    {
        while (($file = readdir($dh)) !== false)
        {
            if (is_file($sourcedir . '/' . $file) && substr($file, -4) === '.php')
            {
                $fp = fopen($sourcedir . '/' . $file, 'rb');
                $fc = fread($fp, filesize($sourcedir . '/' . $file));
                fclose($fp);

                foreach ($temp_hooks as $hook => $functions)
                {
                    foreach ($functions as $function)
                    {
                        if (substr($hook, -8) === '_include')
                        {
                            $hook_status[$hook][$function] = file_exists(strtr(trim($function), array('$boarddir' => $boarddir, '$sourcedir' => $sourcedir, '$themedir' => $settings['theme_dir'])));
                            unset($temp_hooks[$hook][$function]);
                        }
                        elseif (strpos($fc, 'function ' . trim($function) . '(') !== false)
                        {
                            $hook_status[$hook][$function] = true;
                            unset($temp_hooks[$hook][$function]);
                        }
                    }
                }
            }
        }
        closedir($dh);
    }

    $sort_types = array(
        'hook_name' => array('hook', SORT_ASC),
        'hook_name DESC' => array('hook', SORT_DESC),
        'function_name' => array('function', SORT_ASC),
        'function_name DESC' => array('function', SORT_DESC),
        'hook_exists' => array('hook_exists', SORT_ASC),
        'hook_exists DESC' => array('hook_exists', SORT_DESC),
    );

    $sort_options = $sort_types[$sort];
    $sort = array();

    foreach ($hooks as $hook => $functions)
    {
        foreach ($functions as $function)
        {
            $hook_exists = !empty($hook_status[$hook][$function]);
            $sort[] = $$sort_options[0];
            $temp_data[] = array(
                'hook_name' => $hook,
                'function_name' => $function,
                'hook_exists' => $hook_exists,
                'hook_exists_text' => $hook_exists ? $txt['hooks_exists'] : $txt['hooks_missing'],
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
    $hooks = get_integration_hooks();
    $hooks_count = 0;

    foreach ($hooks as $hook => $functions)
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