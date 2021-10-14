<?php

/**
 * @package Simple Referrals
 * @version 1.4
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2021, SMF Tricks
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html
 */

function template_maintain_referrals()
{
	global $context, $txt, $scripturl;

	echo '
	<div id="manage_maintenance">';

	// If maintenance has finished, tell the user.
	if (!empty($context['maintenance_finished']))
		echo '
		<div class="infobox">
			', sprintf($txt['maintain_done'], $context['maintenance_finished']), '
		</div>';

	echo '
		<div class="cat_bar">
			<h3 class="catbg">', $txt['maintain_recountreferrals'], '</h3>
		</div>
		<div class="windowbg">
			<form action="', $scripturl, '?action=admin;area=maintain;sa=referrals;activity=recountreferrals" method="post" accept-charset="', $context['character_set'], '" id="referralsRecountForm">
				<p>', $txt['maintain_recountreferrals_info'], '</p>
				<input type="submit" value="', $txt['maintain_run_now'], '" class="button">
				<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
				<input type="hidden" name="', $context['admin-maint_token_var'], '" value="', $context['admin-maint_token'], '">
			</form>
		</div>
	</div><!-- #manage_maintenance -->';
}