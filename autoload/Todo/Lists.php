<?php

namespace Todo;

class Lists extends SessionHelper {
	public function home($f3) {
		$f3->set('content', ($this->user === NULL) ? 'login.html' : 'noListSelected.html');
	}
	
	private function populateListData($f3, $l) {
		$f3->set('listid', $l->id);
		$f3->set('listname', $l->name);
		$f3->set('listdata', $l->itemsToArray());
	}
	
	private function getList($f3, $id) {
		$l = new \Todo\Model\TodoList($f3->get('DB'));
		if((!$l->byID($id)) || $l->user !== $this->user->id) {
			$f3->set('content', 'blank.html');
			$f3->set('error', 'List not found');
			$f3->error(404); # TODO: Meh, this stops F3 from rendering the template....
			return NULL;
		}
		return $l;
	}
	
	public function deleteList($f3, $args) {
		if($this->needLogin($f3)) {
			return;
		}
		
		if(!($l = $this->getList($f3, $args['list']))) {
			return;
		}
		
		if(($f3->get('VERB') === 'POST') && ($f3->get('POST.confirm') === 'OK')) {
			$l->deleteList();
			
			$f3->set('success', 'List deleted');
			$f3->set('content', 'blank.html');
		} else {
			$this->populateListData($f3, $l);
			$f3->set('content', 'deleteList.html');
		}
	}
	
	public function newList($f3) {
		if($this->needLogin($f3)) {
			return;
		}
		
		if($f3->get('VERB') !== 'POST') {
			return;
		}
		
		if($f3->get('POST.listname') === '') {
			$f3->set('error', 'List name must not be empty');
			$f3->set('content', 'blank.html');
			return;
		}
		
		$l = new \Todo\Model\TodoList($f3->get('DB'));
		$l->user = $this->user->id;
		$l->name = $f3->get('POST.listname');
		$l->save();
		
		$f3->set('success', 'List created!');
		
		$f3->set('content', 'list.html');
		$this->populateListData($f3, $l);
	}
	
	public function showList($f3, $args) {
		if($this->needLogin($f3)) {
			return;
		}
		
		$f3->set('content', 'list.html');
		
		if(!($l = $this->getList($f3, $args['list']))) {
			return;
		}
		
		if($f3->get('VERB') === 'POST') {
			$ok = true;
			switch($f3->get('POST.action')) {
			case 'setname':
				if($f3->get('POST.name') === '') {
					$f3->set('error', 'List name must not be empty');
					$ok = false;
				} else {
					$l->name = $f3->get('POST.name');
					$l->save();
				}
				break;
			case 'additem':
				if($f3->get('POST.itemtext') === '') {
					$f3->set('error', 'Can not add empty list item');
					$ok = false;
				} else {
					$l->addItem($f3->get('POST.itemtext'));
				}
				break;
			case 'delitem':
				$l->delItem($f3->get('POST.id'));
				break;
			case 'setchecked':
				$it = $l->getItem($f3->get('POST.id'));
				if($it === NULL) {
					$f3->set('error', 'Invalid item ID');
					$ok = false;
				} else {
					$it->checked = ($f3->get('POST.checked') === 'y');
					$it->save();
				}
				break;
			case 'moveup':
				$l->moveItem($f3->get('POST.id'), -1);
				break;
			case 'movedown':
				$l->moveItem($f3->get('POST.id'), 1);
				break;
			case 'movex':
				$l->moveItem($f3->get('POST.id'), $f3->get('POST.move'));
				break;
			case 'delchecked':
				$l->deleteChecked();
				break;
			default:
				$ok = false;
				$f3->set('error', 'Unknown list action.');
			}
			
			if($ok) {
				$f3->set('success', 'List updated');
			}
		}
		
		$this->populateListData($f3, $l);
	}
}