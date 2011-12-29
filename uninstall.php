<?php

if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
    require_once(dirname(__FILE__) . '/SSI.php');
elseif (!defined('SMF'))
    exit('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php.');

$hooks = array(
    'integrate_admin_include' => '$sourcedir/Subs-IntegrationHooks.php',
    'integrate_admin_areas' => 'hooks_admin_areas',
    'integrate_modify_modifications' => 'hooks_modify_modifications',
);

foreach ($hooks as $hook => $function)
    remove_integration_function($hook, $function);

if (SMF == 'SSI')
    echo 'Database adaptation successful!';

?>