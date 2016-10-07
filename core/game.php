<?php
class Game
{
	public static function getList()
	{
		$sql = 'SELECT id, rule FROM set_info WHERE status = ? ORDER BY id DESC';
		return DB::select($sql, [ SLS_CREATE ]);
	}

	public static function info($user_id)
	{
		$sql = 'SELECT set_id, rule FROM user_log
				INNER JOIN set_info ON set_info.id = set_id
				WHERE user_id = ? && user_log.status != ? LIMIT 1';
		return DB::select($sql, [ $user_id, SLS_FINISH ])[0];
	}
}
