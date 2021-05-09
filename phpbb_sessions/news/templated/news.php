<?php
/**
 * @var phpbb\auth\auth                                          $auth
 * @var phpbb\db\driver\driver_interface                         $db
 * @var phpbb\request\request                                    $request
 * @var phpbb\template\template                                  $template
 * @var phpbb\user                                               $user
 * @var Symfony\Component\DependencyInjection\ContainerInterface $phpbb_container
 */

define('IN_PHPBB', true);
$phpbb_root_path = './phpBB3/';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);
$user->session_begin();
$auth->acl($user->data);
$user->setup();

$user_loader = $phpbb_container->get('user_loader');

$forum_id = $request->variable('forum_id', 0);

$where = ($forum_id) ? ' WHERE forum_id= ' . (int) $forum_id : ' WHERE ' . $db->sql_in_set('forum_id', array_keys($auth->acl_getf('f_read', true)));
$sql = 'SELECT forum_id, topic_id, topic_time, topic_title, topic_views, topic_posts_approved, topic_poster, topic_last_post_id, topic_last_poster_id, topic_last_post_time
	FROM ' . TOPICS_TABLE .
	$where . '
		AND topic_status <> ' . ITEM_MOVED . ' 
		ORDER BY topic_time DESC';
$result = $db->sql_query_limit($sql, 10);

$template->set_filenames(array('body' => 'news_body.html'));

$template->assign_vars(array(
	'LAST_POST_IMG' => $user->img('icon_topic_latest', 'VIEW_LATEST_POST'),
));

while ($row = $db->sql_fetchrow($result))
{
	$topic_id = $row['topic_id'];
	$view_topic_url = append_sid("{$phpbb_root_path}viewtopic.$phpEx", 'f=' . ($row['forum_id'] ? $row['forum_id'] : (int) $forum_id) . '&amp;t=' . $topic_id);
	$template->assign_block_vars('topicrow', array(
		'FIRST_POST_TIME'  => $user->format_date($row['topic_time']),
		'LAST_POST_AUTHOR' => $user_loader->get_username($row['topic_last_poster_id'], 'full'),
		'LAST_POST_TIME'   => $user->format_date($row['topic_last_post_time']),
		'REPLIES'          => $row['topic_replies'],
		'TOPIC_AUTHOR'     => $user_loader->get_username($row['topic_poster'], 'full'),
		'TOPIC_TITLE'      => censor_text($row['topic_title']),
		'U_LAST_POST'      => $view_topic_url . '&amp;p=' . $row['topic_last_post_id'] . '#p' . $row['topic_last_post_id'],
		'U_VIEW_TOPIC'     => $view_topic_url,
		'VIEWS'            => $row['topic_views'],
	));
}

$db->sql_freeresult($result);

$template->display('body');
