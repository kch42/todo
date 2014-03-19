<?php

namespace Todo\Model;

class User extends \DB\SQL\Mapper {
	public function __construct(\DB\SQL $db) {
		parent::__construct($db, 'users');
	}
	
	public function makeCode() {
		$alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$len = strlen($alphabet);
		$this->code = '';
		for($i = 0; $i < 16; $i++) {
			$this->code .= substr($alphabet, mt_rand(0, $len-1), 1);
		}
	}
	
	public function register($name, $email, $password) {
		$this->load(array('name=? OR email=?', $name, $email));
		if(!$this->dry()) {
			$this->reset();
			return false;
		}
		
		$this->name = $name;
		$this->email = $email;
		$this->pwhash = \Bcrypt::instance()->hash($password);
		$this->active = false;
		$this->makeCode();
		$this->save();
		return true;
	}
	
	public function byName($name) {
		$this->load(array('name=?', $name));
		return !$this->dry();
	}
	
	public function byID($id) {
		$this->load(array('id=?', $id));
		return !$this->dry();
	}
	
	public function byEmail($email) {
		$this->load(array('email=?', $email));
		return !$this->dry();
	}
	
	public function verifyPass($password) {
		return \Bcrypt::instance()->verify($password, $this->pwhash);
	}
	
	public function deleteUser() {
		$this->db->exec('DELETE FROM `items` WHERE `list` IN (SELECT `id` FROM `lists` WHERE `user` = :u)', array(':u' => $this->id));
		$this->db->exec('DELETE FROM `lists` WHERE `user` = :u', array(':u' => $this->id));
		$this->erase();
	}
	
	# Returns a TodoList object that can be iterated with ->next().
	public function lists() {
		$l = new \Todo\Model\TodoList($this->db);
		$l->load(array('user=?', $this->id), array('order' => 'name ASC'));
		return $l;
	}
}