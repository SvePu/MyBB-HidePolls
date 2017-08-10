<?php

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook("showthread_end", "hidepolls");

function hidepolls_info()
{
	return array(
		"name"			=> "Hide Polls",
		"description"	=> "MyBB 1.8 plugin to hide polls at defined user groups.",
		"website"		=> "https://github.com/SvePu/MyBB-HidePolls",
		"author"		=> "SvePu",
		"authorsite"	=> "https://github.com/SvePu",
		"version"		=> "1.0",
		"codename"		=> "hidepolls",
		"compatibility" => "18*"
	);
}

function hidepolls_activate()
{
	global $db;

	$query = $db->simple_select("settinggroups", "COUNT(*) as rows");
	$rows = $db->fetch_field($query, "rows");

	$setting_group = array(
		'name' => 'hidepollssettings',
		'title' => 'Hide Polls',
		'description' => 'Settings of Hide Polls Plugin',
		'disporder' => $rows+1,
		'isdefault' => 0
	);

	$gid = $db->insert_query("settinggroups", $setting_group);

	$setting_array = array(
		'hidepolls_enable' => array(
			'title' => 'Enable Hide Polls Plugin',
			'description' => 'Choose YES to enable.',
			'optionscode' => 'yesno',
			'value' => '1',
			'disporder' => 1
		),
		'hidepolls_forums' => array(
			'title' => 'Forum Select',
			'description' => 'Choose forums where the plugin should work.',
			'optionscode' => "forumselect",
			'value' => '-1',
			'disporder' => 2
		),
		'hidepolls_groups' => array(
			'title' => 'Group Select',
			'description' => 'Choose groups where the polls will be hidden.',
			'optionscode' => "groupselect",
			'value' => '-1',
			'disporder' => 3
		),
		'hidepolls_infobox' => array(
			'title' => 'Info Box for Hidden Polls',
			'description' => 'Enter an info text for the hidden poll - this will be shown instead of the poll .... leave it blank to disable the info box.',
			'optionscode' => "textarea",
			'value' => '',
			'disporder' => 3
		)
	);

	foreach($setting_array as $name => $setting)
	{
		$setting['name'] = $name;
		$setting['gid'] = $gid;

		$db->insert_query('settings', $setting);
	}

	rebuild_settings();

	$templates['showthread_hidepolls_box'] = '<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder tfixed">
<tr>
<td class="trow1" style="text-align: center;">{$mybb->settings[\'hidepolls_infobox\']}</td>
</tr>
</table>
<br />';

	foreach($templates as $title => $template)
	{
		$new_template = array(
			'title' => $db->escape_string($title),
			'template' => $db->escape_string($template),
			'sid' => '-2',
			'version' => '1800',
			'dateline' => TIME_NOW
		);
		$db->insert_query('templates', $new_template); 
	}
}

function hidepolls_deactivate()
{
	global $db;

	$query = $db->simple_select("settinggroups", "gid", "name='hidepollssettings'");
	$gid = $db->fetch_field($query, "gid");
	if(!$gid)
	{
		return;
	}
	$db->delete_query("settinggroups", "name='hidepollssettings'");
	$db->delete_query("settings", "gid=$gid");
	rebuild_settings();

	$db->delete_query("templates", "title LIKE 'showthread_hidepolls_%'");
}

function hidepolls()
{
	global $thread, $templates, $mybb, $theme, $pollbox;

	if($mybb->settings['hidepolls_enable'] != 1)
	{
		return;
	}

	if((is_member($mybb->settings['hidepolls_groups']) ||  $mybb->settings['hidepolls_groups'] == '-1') && (my_strpos($mybb->settings['hidepolls_forums'], $thread['fid']) !== false || $mybb->settings['hidepolls_forums'] == '-1'))
	{
		if(!empty($mybb->settings['hidepolls_infobox']))
		{
			eval("\$pollbox = \"".$templates->get("showthread_hidepolls_box")."\";");
		}
		else
		{
			$pollbox = "";
		}
	}
}
