<?php
define('IN_PHPBB', true);
$phpbb_root_path = './phpBB3/';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);
require($phpbb_root_path . 'includes/functions_user.' . $phpEx);
$user->session_begin();
$auth->acl($user->data);
$user->setup('ucp');

$error = array();
$success = false;
$message = $message_error = '';
$url_redirect = '';

$data = array(
	'username'         => $request->variable('username', '', true),
	'new_password'     => $request->variable('new_password', '', true),
	'password_confirm' => $request->variable('password_confirm', '', true),
	'email'            => strtolower($request->variable('email', '')),
);

if ($request->is_set_post('submit'))
{
	$error = validate_data($data, array(
		'username'         => array(
			array('string', false, $config['min_name_chars'], $config['max_name_chars']),
			array('username', '')),
		'new_password'     => array(
			array('string', false, $config['min_pass_chars'], 0),
			array('password')),
		'password_confirm' => array('string', false, $config['min_pass_chars'], 0),
		'email'            => array(
			array('string', false, 6, 60),
			array('user_email')),
	));

	// Replace "error" strings with their real, localised form
	$error = array_map(array($language, 'lang'), $error);

	if (!count($error))
	{
		if ($data['new_password'] != $data['password_confirm'])
		{
			$error[] = $language->lang('NEW_PASSWORD_ERROR');
		}
	}

	if (!count($error))
	{
		$group_name = 'REGISTERED';

		$sql = 'SELECT group_id
				FROM ' . GROUPS_TABLE . "
				WHERE group_name = '" . $db->sql_escape($group_name) . "'
					AND group_type = " . GROUP_SPECIAL;
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (!$row)
		{
			trigger_error('NO_GROUP');
		}

		$group_id = $row['group_id'];

		// Instantiate passwords manager
		/* @var $passwords_manager \phpbb\passwords\manager */
		$passwords_manager = $phpbb_container->get('passwords.manager');

		$user_row = array(
			'username'             => $data['username'],
			'user_password'        => $passwords_manager->hash($data['new_password']),
			'user_email'           => $data['email'],
			'group_id'             => (int) $group_id,
			'user_timezone'        => $config['board_timezone'],
			'user_lang'            => $user->lang_name,
			'user_type'            => USER_NORMAL,
			'user_actkey'          => '',
			'user_ip'              => $user->ip,
			'user_regdate'         => time(),
			'user_inactive_reason' => 0,
			'user_inactive_time'   => 0,
		);

		$user_id = user_add($user_row);

		if ((bool) $user_id === false)
		{
			trigger_error('NO_USER', E_USER_ERROR);
		}

		$success = true;
		$url_redirect = append_sid("{$this->root_path}index.{$this->php_ext}");
		$message = $language->lang('ACCOUNT_ADDED') . '<br><br>' . $language->lang('RETURN_INDEX', '<a href="' . $url_redirect . '">', '</a>');
	}
	else
	{
		$message_error = implode('<br>', $error);
	}
}

$template->set_filenames(array('body' => 'register_body.html'));

$template->assign_vars(array(
	'S_SUCCESS'        => $success,
	'MESSAGE'          => $message,
	'MESSAGE_ERROR'    => $message_error,
	'U_REDIRECT'       => $url_redirect,
	'USERNAME'         => $data['username'],
	'PASSWORD'         => $data['password'],
	'PASSWORD_CONFIRM' => $data['password_confirm'],
	'EMAIL'            => $data['email'],
));

$template->display('body');
