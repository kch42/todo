<?php

$f3 = require('lib/base.php');

$f3->set('AUTOLOAD','autoload/');
$f3->set('UI', 'ui/');

$f3->config('config.ini');

# Init DB
$db = new DB\SQL($f3->get('sql_dsn'), $f3->get('sql_user'), $f3->get('sql_pass'));
$f3->set("DB", $db);

# Init Sessions
new Db\SQL\Session($db);

# Homepage
$f3->route('GET      @root:       /',                     'Todo\Lists->home');

# User management stuff
$f3->route('GET|POST @login:      /login',                'Todo\UserManager->login');
$f3->route('GET      @logout:     /logout',               'Todo\UserManager->logout');
$f3->route('GET|POST @register:   /register',             'Todo\UserManager->register');
$f3->route('GET|POST @delete_acc: /delete_acc',           'Todo\UserManager->delete');
$f3->route('GET                   /activate/@user/@code', 'Todo\UserManager->activate');
$f3->route('GET|POST @resetpw:    /resetpw',              'Todo\UserManager->initResetpw');
$f3->route('GET|POST              /resetpw/@user/@code',  'Todo\UserManager->resetpw');
$f3->route('GET|POST @settings:   /settings',             'Todo\UserManager->settings');

# List stuff
$f3->route('POST     @newlist:    /newlist',              'Todo\Lists->newList');
$f3->route('GET|POST              /list/@list',           'Todo\Lists->showList');
$f3->route('GET|POST              /list/@list/delete',    'Todo\Lists->deleteList');

$f3->route('GET /foo', function($f3) {
	$f3->set('content', 'blank.html');
	$f3->set('info', 'http://'.$f3->get('HOST').$f3->get('BASE'));
});

$f3->run();
echo Template::instance()->render('master.html');
