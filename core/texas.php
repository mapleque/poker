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
		$total = count($pool);
		$r_score = 0;
		$r_cards = null;
		foreach (range(0,$total - 2) as $i) {
			foreach (range($i + 1, $total - 1) as $j) {
				$pick = array_diff_key($pool, array_flip([$i,$j]));
				$cards = array_merge($hand, $pick);
				$score = self::socre($cards);
				if ($score > $r_score) {
					$r_score = $score;
					$r_cards = $cards;
				}
			}
		}
		return [
			'cards' => $r_cards,
			'score' => $r_score,
		];
	}
	private static function score($cards)
	{
		usort($cards, function($a, $b){
			return $b['value'] - $a['value']; // 12 - 0
		});
		$flush = true;
		$type = -1;
		foreach ($cards as $card) {
			if ($type < 0) {
				$type = $card['type'];
			} elseif ($type != $card['type']) {
				$flush = false;
			}
		}
		$straight = true;
		$value = -1;
		$royal = false;
		$ace = false;
		foreach ($cards as $card) {
			if ($value < 0) {
				if ($card['value'] === 12) {
					// A,x,x,x,x
					$ace = true;
				} else {
					$value = $card['value'];
				}
			} elseif ($value - $card['value'] !== 1) {
				$straight = false;
			} else {
				$value = $card['value'];
			}
		}
		if ($straight && $ace) {
			if ($cards[1] === 3) { // A,5,4,3,2
			} elseif ($cards[1] === 11) { // A,K,Q,J,10
				$royal = true;
			}
		}
		$value = -1;
		$same_one = [];
		$same_two = [];
		$last_card = null;
		foreach ($cards as $card) {
			if ($value === $card['value']) {
				if (empty($same_one) || $same_one[0]['value'] === $card['value']) {
					$same_one = array_merge($same_one, $last_card, $card);
				} else {
					$same_two = array_merge($same_two, $last_card, $card);
				}
				$last_card = $card;
			} else {
				$value = $card['value'];
				$last_card = $card;
			}
		}
		// royal flush 200000
		if ($royal && $flush && $straight) {
			return 200000;
		}
		// straight flush [4,13] + 174000 < 175000
		if ($flush && $straight) {
			$tmp = $cards[0]['value'];
			if ($ace) {
				$tmp = $cards[1]['value'];
			}
			return $tmp + 174000
		}
		$score = 0;
		foreach ($cards as $card) {
			$score += $card['value'] + 1;
			$score += 13;
		}
		// four of a kind [1,13] * 13 + [1,13] + 173000 < 174000
		if (count($same_one) === 4) {
			return $same_one[0]['value'] * 13 + $score;
		}
		// fullhouse [1,13] * 13 + [1,13] + 172600 < 173000
		if (count($same_one) === 3 && count($same_two) === 2) {
			return $same_one[0]['value'] * 13 + $same_two[0]['value'] + 172600;
		} else if (count($same_one) === 2 && count($same_two === 3) {
			return $same_one[0]['value'] + $same_two[0]['value'] * 13 + 172600;
		}
		// flush [1,13*6] + 172500 < 172600
		if ($flush) {
			return $score + 172500;
		}
		// straight [4,13] + 172458 < 172500
		if ($straight) {
			$tmp = $cards[0]['value'];
			if ($ace) {
				$tmp = $cards[1]['value'];
			}
			return $tmp + 172458
		}
		// three of a kind [1,13] * 13 * 6 + [1,13*6] + 14274 < 172458
		if (count($same_one) === 3) {
			return $same_one[0]['value'] * 13 * 6 + $score + 14274;
		}
		// tow pairs [1,13] * 13 * 13 * 6  + [1,13] * 13 * 6 + [1,13*6] < (13 * 13 + 14) * 13 * 6 = 14274
		if (count($same_one) === 2 && count($same_two) === 2) {
			return $same_one[0]['value'] * 13 * 13 * 6 + $same_two[0]['value'] * 13 * 6 + $score;
		}
		// one pair [1,13] * 13 * 6 + [1,13*6] < 14 * 13 * 6 = 1092
		if (count($same_one) === 2) {
			return $same_one[0]['value'] * 13 * 6 + $score;
		}
		// hight card [1,13*6] < 78
		return $score;
	}
}
