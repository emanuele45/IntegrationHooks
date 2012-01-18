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
 * @version 1.2
 */

if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
	require_once(dirname(__FILE__) . '/SSI.php');
elseif (!defined('SMF'))
	exit('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php.');

$hooks = array(
	'integrate_admin_include' => '$sourcedir/Subs-IntegrationHooks.php',
	'integrate_admin_areas' => 'hooks_admin_areas',
	'integrate_modify_modifications' => 'hooks_modify_modifications',
);

$integration_function = empty($context['uninstalling']) ? 'add_integration_function' : 'remove_integration_function';
foreach ($hooks as $hook => $function)
	$integration_function($hook, $function);

if (SMF == 'SSI')
	echo 'Database adaptation successful!';

?>