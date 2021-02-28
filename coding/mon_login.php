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
if ($user->data['user_id'] != ANONYMOUS)
{
	echo 'Bienvenue ' . $user->data['username'] . '<br>';
	echo '<a href="' . append_sid('mon_login.php?logout=true') . '">Déconnexion</a>';
}
else
{
	if(!empty($login_error))
	{
		echo '<span style="color: red"><b>$login_error</b></span>';
	}
	?>
	<form method="post">
		<table>
			<tr>
				<td style="text-align: right"><label for="username">Pseudo&nbsp;:</label></td>
				<td><input id="username" type="text" tabindex="1" name="username" size="25"></td>
			</tr>
			<tr>
				<td style="text-align: right"><label for="password">Mot de passe&nbsp;:</label></td>
				<td><input id="password" type="password" tabindex="2" name="password" size="25"></td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td><a href="<?php echo append_sid("{$phpbb_root_path}ucp.$phpEx?mode=sendpassword"); ?>">J’ai oublié mon mot de passe</a></td>
			</tr>
			<tr>
				<td><label for="autologin">&nbsp;</label></td>
				<td><input id="autologin" type="checkbox" name="autologin" tabindex="3"> Me connecter automatiquement à chaque visite</td>
			</tr>
			<tr>
				<td><label for="viewonline">&nbsp;</label></td>
				<td><input id="viewonline" type="checkbox" name="viewonline" tabindex="4"> Cacher mon statut en ligne pour cette session</td>
			</tr>
			<tr>
				<td style="text-align: center" colspan="2"><input type="submit" name="login" tabindex="5" value="Connexion"></td>
			</tr>
		</table>
	</form>
	<?php
}
?>
