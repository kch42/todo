<?php

namespace Todo\Model;

class TodoList extends \DB\SQL\Mapper {
	public function __construct($db) {
		parent::__construct($db, 'lists');
	}
	
	public function byID($id) {
		$this->load(array('id=?', $id));
		return !$this->dry();
	}
	
	public function itemsToArray() {
		$items = array();
		$it = new \DB\SQL\Mapper($this->db, 'items');
		for($it->load(array('list=?', $this->id), array('order' => 'ord ASC')); !$it->dry(); $it->next()) {
			$items[] = array(
				'id' => $it->id,
				'ord' => $it->ord,
				'text' => $it->text,
				'date' => $it->date,
				'checked' => $it->checked,
			);
		}
		
		return $items;
	}
	
	public function countItems() {
		$it = new \DB\SQL\Mapper($this->db, 'items');
		return $it->count(array('list=?', $this->id));
	}
	
	public function getItem($id) {
		$it = new \DB\SQL\Mapper($this->db, 'items');
		$it->load(array('list=? AND id=?', $this->id, $id));
		return $it->dry() ? NULL : $it;
	}
	
	public function moveItem($id, $movement) {
		$it = new \DB\SQL\Mapper($this->db, 'items');
		$it->load(array('list=? AND id=?', $this->id, $id));
		if($it->dry()) {
			return;
		}
		$ordOld = $it->ord;
		$it->reset();
		
		$ordNew = min(max(0, $ordOld + $movement), $this->countItems() - 1);
		if($ordNew === $ordOld) {
			return;
		}
		
		$moveSgn = ($movement > 0) ? 1 : -1;
		
		if($moveSgn === 1) {
			$it->load(array('list=? AND ord>? AND ord<=?', $this->id, $ordOld, $ordNew));
		} else {
			$it->load(array('list=? AND ord>=? AND ord<?', $this->id, $ordNew, $ordOld));
		}
		for(;!$it->dry(); $it->next()) {
			$it->ord -= $moveSgn;
			$it->save();
		}
		
		$it->reset();
		$it->load(array('list=? AND id=?', $this->id, $id));
		$it->ord = $ordNew;
		$it->save();
	}
	
	public function addItem($text) {
		$n = $this->countItems();
		
		$now = new \DateTime('now');
		
		$it = new \DB\SQL\Mapper($this->db, 'items');
		$it->text = $text;
		$it->ord = $n;
		$it->date = $now->format('Y-m-d H:i:s');
		$it->checked = false;
		$it->list = $this->id;
		$it->save();
	}
	
	public function delItem($id) {
		$this->db->exec('DELETE FROM `items` WHERE `list` = :l AND `id` = :i', array(':l' => $this->id, ':i' => $id));
	}
	
	public function deleteList() {
		$this->db->exec('DELETE FROM `items` WHERE `list` = :l', array(':l' => $this->id));
		$this->erase();
	}
	
	public function deleteChecked() {
		$this->db->exec('DELETE FROM `items` WHERE `list` = :l AND `checked` = :c', array(':l' => $this->id, ':c' => true));
	}
}