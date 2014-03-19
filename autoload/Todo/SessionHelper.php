<?php

namespace Todo;

abstract class SessionHelper {
	protected $user = NULL;
	
	private function populateLists($f3) {
		$lists = array();
		for($l = $this->user->lists(); !$l->dry(); $l->next()) {
			$lists[] = array(
				'id' => $l->id,
				'name' => $l->name,
				'active' => ($l->id === $f3->get('listid')),
			);
		}
		$f3->set('lists', $lists);
	}
	
	public function beforeRoute($f3) {
		$id = $f3->get('SESSION.userID');
		if(is_numeric($id) && ($id > 0)) {
			$this->user = new \Todo\Model\User($f3->get('DB'));
			if((!$this->user->byID($id)) || (!$this->user->active)) {
				$this->user = NULL;
			}
		}
		$f3->set('listid', -1);
	}
	
	public function afterRoute($f3) {
		$f3->set('SESSION.userID', ($this->user === NULL) ? -1 : $this->user->id);
		if($this->user !== NULL) {
			$f3->set('user', $this->user->name);
			$this->populateLists($f3);
		} else {
			$f3->set('user', '');
		}
	}
	
	protected function needLogin($f3) {
		if($this->user === NULL) {
			$f3->set('error', 'You must be logged in to do that');
			$f3->set('content', 'blank.html');
			return true;
		}
		return false;
	}
}