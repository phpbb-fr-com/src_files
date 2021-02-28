<?php
define('IN_PHPBB', true);
$phpbb_root_path = './phpBB3/';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);
$user->session_begin();
$auth->acl($user->data);
$user->setup();

if ($request->is_set('logout', \phpbb\request\request_interface::GET))
{
	$user->session_kill();
	$user->session_begin();
}

if ($request->is_set_post('login'))
{
	$username = $request->variable('username', '', true);
	$password = $request->variable('password', '', true);
	$autologin = $request->variable('autologin', false) ? true : false;
	$viewonline = $request->variable('viewonline', 1) ? 0 : 1;
	$admin = 0;

	$result = $auth->login($username, $password, $autologin, $viewonline, $admin);

	if ($result['status'] != LOGIN_SUCCESS)
	{
		$login_error = $language->lang($result['error_msg']);
		if ($result['error_msg'] == 'LOGIN_ERROR_USERNAME' || $result['error_msg'] == 'LOGIN_ERROR_PASSWORD')
		{
			$login_error = (!$config['board_contact']) ? $language->lang($result['error_msg'], '', '') : $language->lang($result['error_msg'], '<a href="mailto:' . htmlspecialchars($config['board_contact']) . '">', '</a>');
		}
	}
	else
	{
		$auth->acl($user->data);
	}
}

$template->set_filenames(array('body' => 'mon_login_body.html'));
$template->assign_vars(array(
	'TITLE'           => ($user->data['user_id'] != ANONYMOUS) ? $language->lang('WELCOME') : $language->lang('LOGIN'),
	'S_REGISTERED'    => $user->data['user_id'] != ANONYMOUS,
	'S_ERROR'         => $login_error,
	'USERNAME'        => $user->data['username'],
	'U_LOGOUT'        => append_sid('mon_login.php?logout=true'),
	'U_SEND_PASSWORD' => append_sid("{$phpbb_root_path}ucp.$phpEx?mode=sendpassword"),
));
$template->display('body');
