<?php
class Poker
{
	public static function init($index_array)
	{
		$ret = [];
		foreach ($index_array as $index) {
			$ret[] = [
				'index' => $index,
				'type' => floor($index / 13) % 4,
				'value' => $index % 13,
			];
		}
		return $ret;
	}
	public static function shuffle(&$cards_array)
	{
		shuffle($cards_array);
		return $cards_array;
	}
	public static function get($num, &$cards_array, $reverse = false)
	{
		if ($reverse) {
			$count = count($cards_array);
			$cards = array_splice($count - $num, $num, $cards_array);
		} else {
			$cards = array_splice(0, $num, $cards_array);
		}
		return $cards;
	}
	public static function push($index_array, &$cards_array)
	{
		arsort($index_array);
		$ret = [];
		foreach ($index_array as $index) {
			array_merge($ret, array_splice($index, 1, $cards_array);
		}
		return $ret;
	}
}
