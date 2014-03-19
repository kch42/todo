<?php

namespace Todo;

class UserManager extends \Todo\SessionHelper {
	public function login($f3, $args) {
		if($this->user !== NULL) {
			$f3->set('info', 'You are already logged in');
			$f3->set('content', 'blank.html');
			return;
		}
		$f3->set('content', 'login.html');
		
		if($f3->get('VERB') !== "POST") {
			return;
		}
		
		$this->user = new \Todo\Model\User($f3->get('DB'));
		if($this->user->byName($f3->get('POST.name')) && $this->user->verifyPass($f3->get('POST.password')) && $this->user->active) {
			$f3->set('success', 'Login Successful!');
			$f3->set('content', 'noListSelected.html');
		} else {
			$this->user = NULL;
			$f3->set('error', 'Username or password is wrong or account is not active.');
		}
	}

	public function logout($f3, $args) {
		$this->user = NULL;
		$f3->set('success', 'Logout Successful!');
		$f3->set('content', 'login.html');
	}

	public function register($f3, $args) {
		if($this->user !== NULL) {
			$f3->set('error', 'You already have an account!');
			$f3->set('content', 'blank.html');
			return;
		}
		$f3->set('content', 'register.html');
		
		if($f3->get('VERB') !== 'POST') {
			return;
		}
		
		if(($f3->get('POST.password') === '') || ($f3->get('POST.name') === '') || ($f3->get('POST.email') === '')) {
			$f3->set('error', 'All form inputs must be filled out');
			return;
		}
			
		if($f3->get('POST.password') !== $f3->get('POST.repeatPassword')) {
			$f3->set('error', 'Passwords do not match');
			return;
		}
		
		$u = new \Todo\Model\User($f3->get('DB'));
		if($u->register($f3->get('POST.name'), $f3->get('POST.email'), $f3->get('POST.password'))) {
			$f3->set('username', $u->name);
			$f3->set('activationurl', 'http://'.$f3->get('HOST').$f3->get('BASE').'/activate/'.$u->id.'/'.$u->code);
			mail($u->email, 'Activation code for ' . $f3->get('appname'), \Template::instance()->render('mails/activationcode', 'text/plain'), 'From:'.$f3->get('mail_from'));
				
			$f3->set('success', 'New account created. Check your mails for the activation link.');
			$f3->set('content', 'blank.html');
		} else {
			$f3->set('error', 'Name or E-Mail already in use.');
		}
	}

	public function delete($f3, $args) {
		if($this->needLogin($f3)) {
			return;
		}
		
		$f3->set('content', 'deleteAccount.html');
		
		if(($f3->get('VERB') == 'POST') && ($f3->get('POST.confirm') == 'OK')) {
			$this->user->deleteUser();
			$this->user = NULL;
			$f3->set('success', 'Account deleted!');
			$f3->set('content', 'blank.html');
		}
	}

	public function activate($f3, $args) {
		$f3->set('content', 'blank.html');
		
		$u = new \Todo\Model\User($f3->get('DB'));
		if(!$u->byID($args['user'])) {
			$f3->set('error', 'Unknown user.');
			return;
		}
		
		if($u->active) {
			$f3->set('info', 'Account already activated');
			return;
		}
		
		if($u->code !== $args['code'])  {
			$f3->set('error', 'Wrong activation code!');
			return;
		}
		
		$u->active = true;
		$u->makeCode(); # set a new random code to prevent double usage
		$u->save();
		
		$f3->set('success', 'Account activated!');
	}

	public function initResetpw($f3, $args) {
		$f3->set('content', 'pwresetRequest.html');
		
		if($f3->get('VERB') !== 'POST') {
			return;
		}
		
		$u = new \Todo\Model\User($f3->get('DB'));
		if(!$u->byEmail($f3->get('POST.email'))) {
			$f3->set('error', 'No account with this address registered.');
			return;
		}
		
		$u->makeCode();
		$u->save();
		
		$f3->set('username', $u->name);
		$f3->set('reseturl', 'http://'.$f3->get('HOST').$f3->get('BASE').'/pwreset/'.$u->id.'/'.$u->code);
		mail($u->email, 'Password reset for ' . $f3->get('appname'), \Template::instance()->render('mails/pwreset', 'text/plain'), 'From:'.$f3->get('mail_from'));
		
		$f3->set('success', 'Password reset link was sent to your E-Mail address.');
		$f3->set('content', 'blank.html');
	}
	
	public function resetpw($f3, $args) {
		$u = new \Todo\Model\User($f3->get('DB'));
		if((!$u->byID($args['user'])) || ($u->code !== $args['code'])) {
			$f3->set('error', 'Invalid password reset link.');
			$f3->set('content', 'blank.html');
			return;
		}
		
		$f3->set('content', 'pwreset.html');
		if($f3->get('VERB') !== 'POST') {
			return;
		}
		
		if($f3->get('POST.password') !== $f3->get('POST.repeatPassword')) {
			$f3->set('error', 'Passwords do not match');
			return;
		}
		
		$u->pwhash = \Bcrypt::instance()->hash($f3->get('POST.password'));
		$u->makeCode();
		$u->save();
		
		$f3->set('success', 'Password changed');
		$f3->set('content', 'blank.html');
	}
	
	public function settings($f3, $args) {
		if($this->needLogin($f3)) {
			return;
		}
		
		$f3->set('content', 'settings.html');
		$f3->set('email', $this->user->email);
		
		if($f3->get('VERB') !== 'POST') {
			return;
		}
		
		$ok = array();
		$error = array();
		
		if(($this->user->email !== $f3->get('POST.email')) && ($f3->get('POST.email') !== '')) {
			$this->user->email = $f3->get('POST.email');
			$f3->set('email', $this->user->email);
			$ok[] = 'E-Mail address changed.';
		}
		
		if($f3->get('POST.password') !== '') {
			if($f3->get('POST.password') === $f3->get('POST.repeatPassword')) {
				$this->user->pwhash = \Bcrypt::instance()->hash($f3->get('POST.password'));
				$ok[] = 'Password changed.';
			} else {
				$error[] = 'Passwords do not match.';
			}
		}
		
		if(!empty($ok)) {
			$this->user->save();
		}
		
		$text = array_reduce(array_merge($ok, $error), function($a, $b) { return $a . ' ' . $b; });
		if((!empty($ok)) && (!empty($error))) {
			$f3->set('info', $text);
		} else if(!empty($ok)) {
			$f3->set('success', $text);
		} else if(!empty($error)) {
			$f3->set('error', $text);
		}
	}
}
