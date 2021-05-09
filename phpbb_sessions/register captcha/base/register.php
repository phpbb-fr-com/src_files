<?php
/**
 * @var phpbb\auth\auth                                          $auth
 * @var phpbb\db\driver\driver_interface                         $db
 * @var phpbb\config\config                                      $config
 * @var phpbb\language\language                                  $language
 * @var phpbb\request\request                                    $request
 * @var phpbb\user                                               $user
 * @var Symfony\Component\DependencyInjection\ContainerInterface $phpbb_container
 */

define('IN_PHPBB', true);
$phpbb_root_path = './phpBB3/';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);
require($phpbb_root_path . 'includes/functions_user.' . $phpEx);
$user->session_begin();
$auth->acl($user->data);
$user->setup('ucp');
// Captcha début ajout -->
if ($config['enable_confirm'])
{
	$captcha = $phpbb_container->get('captcha.factory')->get_instance($config['captcha_plugin']);
	$captcha->init(CONFIRM_REG);
}
// <-- Captcha fin ajout

$error = array();

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

// Captcha début ajout -->
	if ($config['enable_confirm'])
	{
		$vc_response = $captcha->validate($data);
		if ($vc_response !== false)
		{
			$error[] = $vc_response;
		}

		if ($config['max_reg_attempts'] && $captcha->get_attempt_count() > $config['max_reg_attempts'])
		{
			$error[] = $language->lang('TOO_MANY_REGISTERS');
		}
	}
// <-- Captcha fin ajout

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

// Captcha début ajout -->
		if ($config['enable_confirm'] && isset($captcha))
		{
			$captcha->reset();
		}
// <-- Captcha fin ajout

		$url = append_sid('./index.php');
		die('<html>
			<head>
				<META http-equiv="Refresh"
				content="10; URL=' . $url . '">
			</head>
			<body>
			Votre compte a été enregistré avec succès<br>
			Vous allez être maintenant redirigé vers <a href="' . $url . '">la page d\'index</a>
			</body>
		</html>');
	}
}
echo '<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<title>Vous enregistrer</title>
</head>
<body>
	<form method="post">
	<h1>Vous enregistrer</h1>';

// Captcha début ajout -->
	if ($config['enable_confirm'])
	{
		$confirm_id = $captcha->confirm_id;
		$confirm_code = true;
		$confirm_image = '<img src="' . append_sid("{$phpbb_root_path}ucp.$phpEx", 'mode=confirm&amp;confirm_id=' . $confirm_id . '&amp;type=' . CONFIRM_REG) . '" alt="" title="" />';
	}
// <-- Captcha fin ajout

	if (count($error))
	{
		echo '<span style="color: red"><b>' . implode('<br>', $error) . '</b></span>';;
	}
	?>
	<table>
		<tr>
			<td style="text-align: right"><label for="username">Pseudo&nbsp;:</label></td>
			<td><input id="username" type="text" tabindex="1" name="username" size="25" value="<?php echo $data['username']; ?>"></td>
		</tr>
		<tr>
			<td style="text-align: right"><label for="new_password">Mot de passe&nbsp;:</label></td>
			<td><input id="new_password" type="password" tabindex="2" name="new_password" size="25" value="<?php echo $data['new_password']; ?>" autocomplete="off"></td>
		</tr>
		<tr>
			<td style="text-align: right"><label for="password_confirm">Confirmez votre mot de passe&nbsp;:</label></td>
			<td><input id="password_confirm" type="password" tabindex="3" name="password_confirm" size="25" value="<?php echo $data['password_confirm']; ?>" autocomplete="off"></td>
		</tr>
		<tr>
			<td style="text-align: right"><label for="email">Courriel&nbsp;:</label></td>
			<td><input id="email" type="text" tabindex="4" name="email" size="25" maxlength="100" value="<?php echo $data['email']; ?>" autocomplete="off"></td>
		</tr>
		<?php
// Captcha début ajout -->
		if ($confirm_code)
		{
			?>
			<tr>
				<td><label for="confirm_id"><?php echo $language->lang('CONFIRM_CODE') . '<br>' . $language->lang('CONFIRM_CODE_EXPLAIN'); ?></label></td>
				<td><input type="hidden" name="confirm_id" value="<?php echo $confirm_id; ?>"><?php echo $confirm_image; ?></td>
			</tr>
			<tr>
				<td><label for="confirm_code">&nbsp;</label></td>
				<td><input type="text" name="confirm_code" id="confirm_code" size="8" maxlength="8"></td>
			</tr>
			<?php
		}
// <-- Captcha fin ajout
		?>
		<tr>
			<td style="text-align: center" colspan="2">
				<input type="reset" value="Remettre &agrave; z&eacute;ro" name="reset">&nbsp;
				<input type="submit" name="submit" id="submit" value="S'enregistrer">
			</td>
		</tr>
	</table>
	</form>
</body>
</html>
