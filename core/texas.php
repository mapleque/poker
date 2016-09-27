<?php
class Texas
{
	public static function create($user_id)
	{
		$sql = 'SELECT id FROM user_log WHERE user_id = ? && status != ? LIMIT 1';
		if (count(DB::select($sql, [ $user_id, SLS_FINISH ]) > 0) {
			return -1;
		}
		DB::begin();
		$sql = 'INSERT INTO set_info (time) VALUSE (NOW())';
		$set_id = DB::insert($sql);
		if ($set_id <= 0) {
			DB::commit(false);
			return -2;
		}
		$sql = 'INSERT INOT user_log (user_id, set_id, status, time) VALUSE (?,?,?,NOW())';
		$log_id = DB::insert($sql, [ $user_id, $set_id, SLS_CREATE ]);
		if ($log_id <= 0) {
			DB::commit(false);
			return -2;
		}
		DB::commit();
		return $set_id;
	}
	public static function join($user_id, $set_id)
	{
		$sql = 'SELECT id FROM user_log
				WHERE user_id = ? && status != ? LIMIT 1';
		if (count(DB::select($sql, [ $user_id, SLS_FINISH ]) > 0) {
			return false;
		}
		$sql = 'INSERT INOT user_log (user_id, set_id, status, time) VALUSE (?,?,?,NOW())';
		$log_id = DB::insert($sql, [ $user_id, $set_id, SLS_CREATE ]);
		if ($log_id <= 0) {
			return false;
		}
		return true;
	}
	public static function start($user_id, $set_id)
	{
		DB::begin();
		$sql = 'UPDATE user_log SET status = ?, score = 10
				WHERE user_id = ? && set_id = ? && status = ? LIMIT 1';
		if (DB::update($sql, [ SLS_READY, $user_id, $set_id, SLS_CREATE ]) !== 1) {
			DB::commit(false);
			return false;
		}
		$sql = 'SELECT id FROM user_log WHERE set_id = ? && status = ? LIMIT 1';
		if (count(DB::select($sql, [ $set_id, SLS_READY ]) === 0) {
			if (!self::init($set_id)) {
				DB::commit(false);
				return false;
			}
		}
		DB::commit();
		return true;
	}
	public static function add($user_id, $set_id, $amount)
	{
		DB::begin();
		$sql = 'UPDATE user_log SET score = score + ? WHERE user_id = ? && set_id = ? LIMIT 1';
		if (DB::update($sql, [ $amount, $user_id, $set_id ]) !== 1) {
			DB::commit(false);
			return false;
		}
		if (!self::checkAction($set_id, $user_id)) {
			DB::commit(false);
			return false;
		}
		DB::commit();
		return true;
	}
	public static function info($user_id, $set_id)
	{
		$sql = 'SELECT user_id, score, username, hand, pool FROM user_log
				INNER JOIN user ON user.id = user_id WHERE set_id = ?';
		$players = DB::select($sql, [ $set_id ]);
		$total_score = 0;
		foreach ($players as &$player) {
			$total_score += $player['score'];
			$player['hand'] = json_decode($player['hand'], true);
			$player['pool'] = json_decode($player['pool'], true);
		}
		unset($player);
		return [
			'players' => $players,
			'total_score' => $total_score,
		];
	}
	private static function init($set_id)
	{
		$sql = 'SELECT user_id FROM user_log WHERE set_id = ?';
		$players = DB::select($sql, [ $set_id ]);
		$action_stack = [];
		foreach (range(0,2) as $round) {
			foreach ($players as $player) {
				$action_stack[] = [
					'type' => SA_ADD,
					'user_id' => $player['user_id'],
				];
			}
			$action_stack[] = [
				'type' => SA_GET,
			];
		}
		$action_stack[] = [
			'type' => SA_END,
		];

		$cards_stack = Poker::init(range(0, 52));
		Poker::shuffle($cards_stack);
		$pool = Poker::get(2);
		foreach ($players as $player) {
			$hand = Poker::get(2, $card_stack);
			$sql = 'UPDATE user_log SET hand = ?
					WHERE user_id = ? && set_id = ? LIMIT 1';
			if (DB::update($sql,
				[ json_encode($hand), json_encode($pool), $player['user_id'], $set_id ]) !== 1) {
				return false;
			}
		}
		$sql = 'UPDATE set_info SET action_stack = ? && card_stack = ?, pool = ?
				WHERE id = ? LIMIT 1';
		$bind = [ json_encode($action_stack), json_encode($card_stack),
			json_encode($pool), $set_id ];
		if (DB::update($sql, $bind) !== 1) {
			return false;
		}
		return true;
	}
	private static function checkAction($set_id, $user_id)
	{
		$sql = 'SELECT card_stack, action_stack, pool FROM set_info WHERE id = ? LIMIT 1';
		$set_info = DB::select($sql, [ $set_id ])[0];
		$card_stack = $set_info['card_stack'] ? json_decode($set_info['card_stack'], true) : [];
		$action_stack = $set_info['action_stack'] ? json_decode($set_info['action_stack'], true) : [];
		$pool = $set_info['pool'] ? json_decode($set_info['pool'], true) : [];
		$action = array_pop($action_stack);
		if ($action['type'] !== SA_GET || $action['user_id'] !== $user_id) {
			return false;
		}
		if ($action_stack[-1]['type'] === SA_GET) {
			if (!self::get($set_id, $action_stack, $card_stack, $pool)) {
				return false;
			}
		}
		if ($action_stack[-1]['type'] === SA_END) {
			if (!self::end($set_id, $pool)) {
				return false;
			}
		}
		return true;
	}
	private static function get($set_id, $action_stack, $card_stack, $pool)
	{
		array_pop($action_stack);
		$pool[] = Poker::get(1, $card_stack);
		$sql = 'UPDATE set_info set card_stack = ?, action_stack = ?, pool = ?
				WHERE id = ? LIMIT 1';
		$bind = [ json_encode($card_stack), json_encode($action_stack),
			json_encode($pool), $set_id ];
		if (DB::update($sql, $bind) !== 1) {
			return false;
		}
		return true;
	}
	private static function end($set_id, $pool)
	{
		$sql = 'SELECT user_id, hand, score FROM user_log WHERE set_id = ?';
		$players = DB::select($sql, [ $set_id ]);
		$win_info = [];
		$total_score = 0;
		foreach ($players as $player) {
			$win_info[$player['user_id'] = self::calc(json_decode($player['hand'], true), $pool);
			$total_score += $player['score'];
		}
		$win_info['total_socre'] = $total_score;
		$winner = null;
		$max_score = 0;
		foreach ($win_info as $user_id => $info) {
			if ( $info['score'] > $max_score) {
				$max_score = $info['score'];
				$winner = $user_id;
			}
		}
		$win_info['winner'] = $winner;
		$sql = 'UPDATE set_info SET win_info = ? WHERE id = ? LIMIT 1';
		if (DB::update($sql, [ json_encode($win_info), $set_id) !== 1) {
			return false;
		}
		$sql = 'UPDATE user_log SET status = ? WHERE set_id = ?';
		if (DB::update($sql, [ SLS_FINISH, $set_id ]) <= 0) {
			return false;
		}
		return true;
	}
	private static function calc($hand, $pool)
	{
		// TODO
		// 每种组合都有一个自己的得分，不可能存在相同的
		// 这里需要挑出一个最大得分的组合返回
		return [
			'cards' => [],
			'score' => 0,
		];
	}
}
