<?php

/**
 * @package Simple Referrals
 * @version 1.3
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2021, SMF Tricks
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html
 */

if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
	require_once(dirname(__FILE__) . '/SSI.php');

elseif (!defined('SMF'))
	exit('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php.');

	global $smcFunc, $context;

	db_extend('packages');

	if (empty($context['uninstalling']))
	{
		// Add a column for the referral
		$smcFunc['db_add_column'](
			'{db_prefix}members', 
			[
				'name' => 'referral',
				'type' => 'mediumint',
				'size' => 8,
				'default' => 0,
				'not_null' => true,
				'unsigned' => true,
			]
		);
		// Add a column for the referral count
		$smcFunc['db_add_column'](
			'{db_prefix}members', 
			[
				'name' => 'ref_count',
				'type' => 'int',
				'size' => 10,
				'default' => 0,
				'not_null' => true,
				'unsigned' => true,
			]
		);
	}