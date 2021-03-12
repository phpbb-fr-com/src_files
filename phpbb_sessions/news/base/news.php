<?php
define('IN_PHPBB', true);
$phpbb_root_path = './phpBB3/';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);
$user->session_begin();
$auth->acl($user->data);
$user->setup();

$forum_id = $request->variable('forum_id', 0);
$where = ($forum_id) ? ' WHERE forum_id= ' . (int) $forum_id : ' WHERE' . $db->sql_in_set('forum_id', array_keys($auth->acl_getf('f_read', true)));

$sql = 'SELECT forum_id,topic_id, topic_time, topic_title, topic_views, topic_replies, topic_poster, topic_first_poster_name, topic_first_poster_colour, topic_last_post_id, topic_last_poster_id, topic_last_poster_name, topic_last_poster_colour, topic_last_post_time
	FROM ' . TOPICS_TABLE .
	$where . '
		AND topic_status <> ' . ITEM_MOVED . ' 
		ORDER BY topic_time DESC';
$result = $db->sql_query_limit($sql, 10);

?>
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<title>Les dernières news</title>
	<style type="text/css">
		th{
			background-color: #0066FF;
			color: #FFFF99;
		}
	</style>
</head>
<body>
<table width="100%">
	<tr>
		<th>Sujets</th>
		<th>Réponses</th>
		<th>Vus</th>
		<th>Dernier message</th>
	</tr>
	<?php
	$i = 0;
	while($row = $db->sql_fetchrow($result))
	{
		$topic_id = $row['topic_id'];
		$view_topic_url = append_sid("{$phpbb_root_path}viewtopic.$phpEx", 'f=' . ($row['forum_id'] ? $row['forum_id'] : (int) $forum_id) . '&amp;t=' . $topic_id);

		$row_colour = ($i % 2) ? '#FFFFCC' : '#99FFFF';
		echo '<tr bgcolor="' . $row_colour .'">';
		echo '<td>';
		echo '<a href="' . $view_topic_url . '">' . censor_text($row['topic_title']) . '</a><br>';
		echo $language->lang('POST_BY_AUTHOR') . '&nbsp;' . get_username_string('full', $row['topic_poster'], $row['topic_first_poster_name'], $row['topic_first_poster_colour']);
		echo '&nbsp;' . $language->lang('POSTED_ON_DATE') . '&nbsp;' . $user->format_date($row['topic_time']) . '</td>';
		echo '<td>' . $row['topic_replies'] . '</td>';
		echo '<td>' . $row['topic_views'] . '</td>';
		echo '<td>' . $language->lang('POST_BY_AUTHOR') . '&nbsp;' . get_username_string('full', $row['topic_last_poster_id'], $row['topic_last_poster_name'], $row['topic_last_poster_colour']);
		echo '<a href="' . $view_topic_url . '&amp;p=' . $row['topic_last_post_id'] . '#p' . $row['topic_last_post_id'] . '">' . '&nbsp;' . $user->img('icon_topic_latest', 'VIEW_LATEST_POST') . '</a><br>';
		echo $language->lang('POSTED_ON_DATE') . $user->format_date($row['topic_last_post_time']) . '</td>';
		echo '</tr>';
		$i++;
	}
	?>
</table>
</body>
</html>
