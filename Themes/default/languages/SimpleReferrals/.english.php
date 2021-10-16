<?php

/**
 * @package Simple Referrals
 * @version 1.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2021, SMF Tricks
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html
 */

$txt['SimpleReferrals_referred'] = 'Referred by';
$txt['SimpleReferrals_referrer_desc'] = 'The member that referred you to the forum.';
$txt['SimpleReferrals_count_total'] = 'Total Referrals';
$txt['SimpleReferrals_link'] = 'Referral Link';

$txt['maintain_referrals'] = 'Referrals';
$txt['maintain_recountreferrals'] = 'Recount User Referrals';
$txt['maintain_recountreferrals_info'] = 'Run this maintenance task to update your users referral count. It will recount all (countable) referrals they have and then update their profile referral totals';
$txt['SimpleReferrals_settings'] = 'Simple Referrals Settings';
$txt['SimpleReferrals_allow_select'] = 'Only allow direct links';
$txt['SimpleReferrals_allow_select_desc'] = 'If enabled, guests will not be able to search/set forum users in the signup form, can only use direct referral links.';
$txt['SimpleReferrals_enable_posts'] = 'Enable referrals count in posts';
$txt['SimpleReferrals_enable_profile'] = 'Enable referrals count in profile';
$txt['SimpleReferrals_display_link'] = 'Display the referral link in profile';

// Stats
$txt['SimpleReferrals_enable_stats'] = 'Display top referrers in the forum stats';
$txt['top_most_referrals'] = 'Most referrals';

// Alerts
$txt['SimpleReferrals_send_alerts'] = 'Send alert to referrals upon new registrations';
$txt['alert_group_referrals'] = $txt['maintain_referrals'];							// Don't translate
$txt['alert_new_referred'] = 'When someone signs up using my referral link';
$txt['alert_referral_new_referred'] = '{member_link} just used your referral link';