<?php

/**
 * @package Simple Referrals
 * @version 1.3.1
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2021, SMF Tricks
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html
 */

if (!defined('SMF'))
	die('No direct access...');

class SimpleReferrals
{
	/**
	 * @var int Used to store the referral mostly.
	 */
	private static $_member_id = 0;

	/**
	 * @var array For saving the member data collected during certain queries.
	 */
	private static $_member_data = [];

	/**
	 * SimpleReferrals::custom_fields()
	 *
	 * Loads custom profile fields
	 *
	 * @param int $user The ID of a user previously loaded by {@link loadMemberData()}
	 * @param array $area An array containing the action areas
	 * @return void
	 */
	public static function custom_fields($user, $area)
	{
		// Language
		loadLanguage('SimpleReferrals/');

		// Signup
		if ($area == 'register')
			self::register();

		// Profile
		if ($area == 'summary')
			self::profile();
	}

	/**
	 * SimpleReferrals::save_referral()
	 *
	 * It includes the referral in the member newly created account,
	 * as well as attempting to find the referral found in the form.
	 *
	 * @param array $regOptions The register options
	 * @return void
	 */
	public static function save_referral(&$regOptions, &$theme_vars, &$knownInts)
	{
		global $smcFunc;

		// Supposedly we are in the signup page, right?
		if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'signup2')
		{
			// We should have our lovely member name, or maybe not
			if (!empty($_REQUEST['simple_referrer_name']) || !empty($_REQUEST['simple_referrer_id']))
			{
				self::$_member_id = (int) (isset($_REQUEST['simple_referrer_id']) && !empty($_REQUEST['simple_referrer_id']) ? $_REQUEST['simple_referrer_id'] : 0);

				// We only know the display name
				if (empty(self::$_member_id))
				{
					$request = $smcFunc['db_query']('', '
						SELECT id_member
						FROM {db_prefix}members
						WHERE real_name = {string:name}',
						array(
							'name' => $smcFunc['htmlspecialchars']($_REQUEST['simple_referrer_name'], ENT_QUOTES),
						)
					);
					list(self::$_member_id) = $smcFunc['db_fetch_row']($request);
					$smcFunc['db_free_result']($request);
				}

				// Update the session with the input user? I don't think it's relevant for the mod, but will leave here just in case
				// $_SESSION['signup_referral'] = (int) self::$_member_id;

				// Insert the value
				$regOptions['register_vars']['referral'] = (int) self::$_member_id;

				// Both are integers
				$knownInts[] = 'referral';
			}
		}
	}

	/**
	 * SimpleReferrals::update_count()
	 *
	 * Updates a member ref_count if set as the referral
	 *
	 * @param array $regOptions The register options
	 * @return void
	 */
	public static function update_count($regOptions)
	{
		global $smcFunc;

		// Do a checking just in case something is broken
		if (!empty($regOptions['register_vars']['referral']))
		{
			$smcFunc['db_query']('',  '
				UPDATE IGNORE {db_prefix}members
				SET ref_count = ref_count + 1
				WHERE id_member = {int:user}',
				[
					'user' => $regOptions['register_vars']['referral'],
				]
			);

			// Are we doing anything else with this?
			call_integration_hook('integrate_mod_simplereferrals', [$regOptions['register_vars']['referral']]);
		}

		// If we still have the referral in the session, remove it
		if (isset($_SESSION['signup_referral']))
			unset($_SESSION['signup_referral']);
	}

	/**
	 * SimpleReferrals::register()
	 *
	 * Inserts a fake custom field in the register/signup view. It also does all the 
	 * checkings in case of errors and other unexpected situations.
	 *
	 * @return void
	 */
	public static function register()
	{
		global $context, $txt, $modSettings;

		// Save the referral in the session
		$_SESSION['signup_referral'] = (int) (isset($_REQUEST['referral']) && !empty($_REQUEST['referral']) ? $_REQUEST['referral'] : (isset($_SESSION['signup_referral']) && !empty($_SESSION['signup_referral']) ? $_SESSION['signup_referral'] : 0));

		// Auto suggest
		loadJavaScriptFile('suggest.js', array('defer' => false, 'minimize' => true), 'smf_suggest');

		// Do we have a referrer in the session/url?
		if (!empty($_SESSION['signup_referral']) && isset($_SESSION['signup_referral']))
			self::$_member_id = (int) $_SESSION['signup_referral'];
		// Maybe we are returning to some errors?
		elseif (!empty($_REQUEST['simple_referrer_id']) && isset($_REQUEST['simple_referrer_id']))
			self::$_member_id = (int) $_REQUEST['simple_referrer_id'];

		// Load info from this member
		// Check if we have an id first
		loadMemberData(self::$_member_id, false, 'minimal');

		// We found a member?
		if (loadMemberData(self::$_member_id, false, 'minimal'))
			self::$_member_data = loadMemberContext(self::$_member_id);

		// If we are returning to the errors, check if the name is the same we had before?
		if (!empty($_REQUEST['simple_referrer_name']) && isset($_REQUEST['simple_referrer_name']) && !empty(self::$_member_data['name']) && self::$_member_data['name'] != $_REQUEST['simple_referrer_name'])
		{
			// Remove the info
			self::$_member_data = [];
			self::$_member_id = 0;
		}

		// Do we allow for selection? If not, don't display anything if there's no referral
		if (empty($modSettings['SimpleReferrals_allow_select']) || (!empty($modSettings['SimpleReferrals_allow_select']) && !empty(self::$_member_data)))
		{
			// Add fake custom field
			$context['custom_fields'][] = [
				'name' => $txt['SimpleReferrals_referred'],
				'desc' => $txt['SimpleReferrals_referrer_desc'],
				'input_html' => (empty($modSettings['SimpleReferrals_allow_select']) ? '
					<input type="text" name="simple_referrer_name" id="simple_referrer_name" value="' . (!empty(self::$_member_data) ? self::$_member_data['name'] : (!empty($_REQUEST['simple_referrer_name']) && isset($_REQUEST['simple_referrer_name']) ? $_REQUEST['simple_referrer_name'] : '')) . '">' : '') . (!empty(self::$_member_data) ? '
					<input type="number" name="simple_referrer_id" id="simple_referrer_id" value="' . self::$_member_id . '" readonly size="'. strlen(self::$_member_id) . '">' : '') . '
					<script>
						var oAddMemberSuggest = new smc_AutoSuggest({
							sSelf: \'oAddMemberSuggest\',
							sSessionId: \'' . $context['session_id'] . '\',
							sSessionVar: \'' . $context['session_var'] . '\',
							sSuggestId: \'to_suggest\',
							sControlId: \'simple_referrer_name\',
							sSearchType: \'member\',
							sPostName: \'simple_referrer_id\',
							sURLMask: \'action=profile;u=%item_id%\',
							sTextDeleteItem: \'' . $txt['autosuggest_delete_item'] . '\',
						});
					</script>',
				'show_reg' => 1,
			];
		}
	}

	/**
	 * SimpleReferrals::profile()
	 *
	 * Inserts the custom field in the profile view
	 *
	 * @return void
	 */
	public static function profile()
	{
		global $context, $txt, $modSettings, $scripturl;

		// Is it enabled for display in the profile
		if (!empty($modSettings['SimpleReferrals_enable_profile']))
			$context['custom_fields']['ref_count'] = [
				'name' => $txt['SimpleReferrals_count_total'],
				'colname' => 'ref_count',
				'output_html' => $context['member']['ref_count'],
				'placement' => 0,
			];

		// Add their referral link?
		if (!empty($modSettings['SimpleReferrals_display_link']))
			$context['custom_fields'][] = [
				'name' => $txt['SimpleReferrals_link'],
				'colname' => 'ref_link',
				'output_html' => '<input type="text" readonly value="' . $scripturl . '?action=signup;referral=' . $context['member']['id'] . '" size="50">',
				'placement' => 0,
			];
	}

	/**
	 * SimpleReferrals::member_data()
	 *
	 * Include referral count in loadMemberData
	 *
	 * @param string $columns The member columns
	 * @param string $tablws Any additional tables
	 * @param string $set What kind of data to load (normal, profile, minimal)
	 * @return void
	 */
	public static function member_data(&$columns, &$tables, &$set)
	{
		switch ($set)
		{
			case 'normal':
				$columns .= ', mem.ref_count';
				break;
			case 'profile':
				$columns .= ', mem.ref_count';
				break;
			case 'minimal':
				$columns .= ', mem.ref_count';
				break;
			default:
				trigger_error('loadMemberData(): Invalid member data set \'' . $set . '\'', E_USER_WARNING);
		}
	}

	/**
	 * SimpleReferrals::member_context()
	 *
	 * Referrals count
	 *
	 * @param array $data The monstrous array of user information
	 * @param int $user The ID of a user previously loaded by {@link loadMemberData()}
	 * @return void
	 */
	public static function member_context(&$data, $user)
	{
		global $user_profile, $modSettings, $txt, $topic;

		// Set the data
		$data['ref_count'] = $user_profile[$user]['ref_count'];

		// Is it enabled for display in the posts
		if (!empty($modSettings['SimpleReferrals_enable_posts']) && !empty($topic))
		{
			// Load language again
			loadLanguage('SimpleReferrals/');

			// Add custom field
			$data['custom_fields']['ref_count'] = [
				'title' => $txt['SimpleReferrals_count_total'],
				'col_name' => 'ref_count',
				'value' => $user_profile[$user]['ref_count'],
				'placement' => 0,
			];
		}
	}

	/**
	 * SimpleReferrals::admin()
	 *
	 * Adds the maintainance tab to the menu and sections
	 *
	 * @param array $areas The monstrous admin array
	 * @return void
	 */
	public static function admin(&$areas)
	{
		global $txt;

		// load language here too
		loadLanguage('SimpleReferrals/');

		$areas['maintenance']['areas']['maintain']['subsections']['referrals'] = [$txt['maintain_referrals'], 'admin_forum'];
	}

	/**
	 * SimpleReferrals::subaction()
	 *
	 * Append the subaction
	 *
	 * @param array $subActions mod settings subactions
	 * @return void
	 */
	public static function mod_settings(&$subActions)
	{
		$subActions['referrals'] = 'SimpleReferrals::settings';
	}

	/**
	 * SimpleReferrals::admin_area()
	 *
	 * Insert the button in the menu
	 *
	 * @param array $admin_areas The admin menu
	 * @return void
	 */
	public static function admin_area(&$admin_areas)
	{
		global $txt;

		// Load the language file
		loadLanguage('SimpleReferrals/');

		// Add the new setting area
		$admin_areas['config']['areas']['modsettings']['subsections']['referrals'] = [$txt['SimpleReferrals_settings']];
	}

	/**
	 * SimpleReferrals::settings()
	 *
	 * Adds the settings to the mods settings page
	 *
	 * @param array $config_vars The mod settings array
	 * @return void
	 */
	public static function settings($return_config = false)
	{
		global $context, $txt, $scripturl;

		$context['post_url'] = $scripturl . '?action=admin;area=modsettings;sa=referrals;save';
		$context['sub_template'] = 'show_settings';
		$context['settings_title'] = $txt['SimpleReferrals_settings'];
		$context['page_title'] .= ' - ' . $txt['SimpleReferrals_settings'];

		// $config_vars []= ['title', 'SimpleReferrals_settings'];
		$config_vars = [
			['check', 'SimpleReferrals_allow_select', 'subtext' => $txt['SimpleReferrals_allow_select_desc']],
			['check', 'SimpleReferrals_enable_profile'],
			['check', 'SimpleReferrals_enable_posts'],
			['check', 'SimpleReferrals_display_link'],
			['check', 'SimpleReferrals_enable_stats'],
		];

		// Return config vars
		if ($return_config)
			return $config_vars;

		// Saving?
		if (isset($_GET['save'])) {
			checkSession();
			saveDBSettings($config_vars);
			clean_cache();
			redirectexit('action=admin;area=modsettings;sa=referrals');
		}
		prepareDBSettingContext($config_vars);
	}

	/**
	 * SimpleReferrals::maint_recount()
	 *
	 * Adds the maintainance area to the sections
	 *
	 * @param array $areas The maint areas array
	 * @return void
	 */
	public static function maint_recount(&$areas)
	{
		// Load the template
		loadTemplate('SimpleReferrals');

		// Add the new activity
		$areas['referrals'] = [
			'function' => 'SimpleReferrals::maintain_recount',
			'template' => 'maintain_referrals',
			'activities' => [
				'recountreferrals' => 'SimpleReferrals::do_recount',
			]
		];
	}

	/**
	 * SimpleReferrals::maintain_recount()
	 *
	 * Returns a finished message after running the task
	 *
	 * @return void
	 */
	public static function maintain_recount()
	{
		global $context, $txt;

		if (isset($_GET['done']) && $_GET['done'] == 'recountreferrals')
			$context['maintenance_finished'] = $txt['maintain_recountreferrals'];
	}

	/**
	 * SimpleReferrals::do_recount()
	 *
	 * Mimics the posts recount of smf, but it recounts the referrals for each user,
	 * and cleans those that didn't actually have referrals
	 *
	 * @return void
	 */
	public static function do_recount()
	{
		global $txt, $context, $smcFunc;

		// You have to be allowed in here
		isAllowedTo('admin_forum');
		checkSession('request');

		// Set up to the context.
		$context['page_title'] = $txt['not_done_title'];
		$context['continue_countdown'] = 3;
		$context['continue_get_data'] = '';
		$context['sub_template'] = 'not_done';

		// init
		$increment = 200;
		$_REQUEST['start'] = !isset($_REQUEST['start']) ? 0 : (int) $_REQUEST['start'];

		// Ask for some extra time, on big boards this may take a bit
		@set_time_limit(600);

		// Only run this query if we don't have the total number of members that have referred someone to the forum
		if (!isset($_SESSION['total_referrals']))
		{
			validateToken('admin-maint');
	
			$request = $smcFunc['db_query']('', '
				SELECT COUNT(DISTINCT mem.referral)
				FROM {db_prefix}members AS mem
				WHERE mem.referral != 0',
				[]
			);

			// save it so we don't do this again for this task
			list ($_SESSION['total_referrals']) = $smcFunc['db_fetch_row']($request);
			$smcFunc['db_free_result']($request);
		}
		else
		validateToken('admin-recountreferrals');

		// Lets get a group of members and determine their referral count
		$request = $smcFunc['db_query']('', '
			SELECT COUNT(*) as ref_count, mem.referral
			FROM {db_prefix}members AS mem
			WHERE mem.referral != {int:zero}
			GROUP BY mem.referral
			LIMIT {int:start}, {int:number}',
			[
				'start' => $_REQUEST['start'],
				'number' => $increment,
				'zero' => 0,
			]
		);
		$total_rows = $smcFunc['db_num_rows']($request);

		// Update the referrals count for this group
		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}members
				SET ref_count = {int:ref_count}
				WHERE id_member = {int:row}',
				[
					'row' => $row['referral'],
					'ref_count' => $row['ref_count'],
				]
			);
		}
		$smcFunc['db_free_result']($request);

		// Continue?
		if ($total_rows == $increment)
		{
			$_REQUEST['start'] += $increment;
			$context['continue_get_data'] = '?action=admin;area=maintain;sa=referrals;activity=recountreferrals;start=' . $_REQUEST['start'] . ';' . $context['session_var'] . '=' . $context['session_id'];
			$context['continue_percent'] = round(100 * $_REQUEST['start'] / $_SESSION['total_referrals']);
	
			createToken('admin-recountreferrals');
			$context['continue_post_data'] = '<input type="hidden" name="' . $context['admin-recountreferrals_token_var'] . '" value="' . $context['admin-recountreferrals_token'] . '">';
	
			if (function_exists('apache_reset_timeout'))
				apache_reset_timeout();
			return;
		}

		// place all members who have referrals in a temp table
		$createTemporary = $smcFunc['db_query']('', '
			CREATE TEMPORARY TABLE {db_prefix}tmp_maint_recountreferrals (
				referral mediumint(8) unsigned NOT NULL default {string:string_zero},
				PRIMARY KEY (referral)
			)
			SELECT DISTINCT mem.referral
			FROM {db_prefix}members AS mem',
			[
				'zero' => 0,
				'string_zero' => '0',
				'db_error_skip' => true,
			]
		) !== false;

		if ($createTemporary)
		{
			// outer join the members table on the temporary table finding the members that have a referral count but no referrals in the members table
			$request = $smcFunc['db_query']('', '
				SELECT mem.id_member, mem.ref_count
				FROM {db_prefix}members AS mem
					LEFT OUTER JOIN {db_prefix}tmp_maint_recountreferrals AS res
					ON res.referral = mem.id_member
				WHERE res.referral IS null
					AND mem.ref_count != {int:zero}',
				array(
					'zero' => 0,
				)
			);

			// set the referral count to zero for any delinquents we may have found
			while ($row = $smcFunc['db_fetch_assoc']($request))
			{
				$smcFunc['db_query']('', '
					UPDATE {db_prefix}members
					SET ref_count = {int:zero}
					WHERE id_member = {int:row}',
					array(
						'row' => $row['id_member'],
						'zero' => 0,
					)
				);
			}
			$smcFunc['db_free_result']($request);
		}

		// all done
		unset($_SESSION['total_referrals']);
		$context['maintenance_finished'] = $txt['maintain_recountreferrals'];
		redirectexit('action=admin;area=maintain;sa=referrals;done=recountreferrals');
	}

	/**
	 * SimpleReferrals::forum_stats()
	 *
	 * Adds the top referrers in the forum 
	 *
	 * @return void
	 */
	public function forum_stats()
	{
		global $smcFunc, $context, $scripturl, $settings, $modSettings;

		// Referrals Top.
		if (!empty($modSettings['SimpleReferrals_enable_stats']))
		{
			// Ref language
			loadLanguage('SimpleReferrals/');

			// Template
			loadTemplate('SimpleReferrals');

			// Add the icon without adding a css file...
			addInlineCss('
				.main_icons.most_referrals::before {
					background: url('. $settings['default_images_url'] . '/icons/most_referrals.png);
				}
			');

			// Top 10 referrals
			$context['stats_blocks']['most_referrals'] = [];
			$max_referrals = 1;
			$request = $smcFunc['db_query']('', '
				SELECT mem.ref_count, mem.id_member, mem.real_name
				FROM {db_prefix}members AS mem
				WHERE mem.ref_count > 0
				ORDER BY mem.ref_count DESC
				LIMIT 10',
				[]
			);
			while ($ref_row = $smcFunc['db_fetch_assoc']($request))
			{
				$context['stats_blocks']['most_referrals'][] = [
					'id' => $ref_row['id_member'],
					'num' => $ref_row['ref_count'],
					'link' => '<a href="' . $scripturl . '?action=profile;u=' . $ref_row['id_member'] . '">' . $ref_row['real_name'] . '</a>',
				];

				if ($max_referrals < $ref_row['ref_count'])
					$max_referrals = $ref_row['ref_count'];
			}
			$smcFunc['db_free_result']($request);

			// Percentage
			foreach ($context['stats_blocks']['most_referrals'] as $i => $referral_count)
				$context['stats_blocks']['most_referrals'][$i]['percent'] = round(($referral_count['num'] * 100) / $max_referrals);
		}
	}
}