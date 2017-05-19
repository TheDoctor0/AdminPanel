<?php

error_reporting(E_ALL ^ E_NOTICE);

date_default_timezone_set('Europe/Warsaw');

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request as Request;

class App extends Silex\Application {
    use Silex\Application\UrlGeneratorTrait;
    use Silex\Application\TwigTrait;
}

$app = new App;

$app['debug'] = true;

$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

$app->register(new Silex\Provider\SessionServiceProvider());

$app->register(new Silex\Provider\TwigServiceProvider(), [
    'twig.path' => __DIR__.'/../views',
]);
$app['twig']->addGlobal('session', $app['session']);

$app['db_accounts'] = $app->share(function() {
    return new PDO('mysql:host=localhost;dbname=accounts;charset=utf8', 'user', 'password');
});

$app['db_admins'] = $app->share(function() {
    return new PDO('mysql:host=localhost;dbname=sklepsms;charset=utf8', 'user', 'password');
});

$app['db_chat'] = $app->share(function() {
    return new PDO('mysql:host=localhost;dbname=chat;charset=utf8', 'user', 'password');
});

$app['accountsRepository'] = $app->share(function() use ($app) {
    return new App\Entity\AccountsRepository($app['db_accounts']);
});

$app['adminsRepository'] = $app->share(function() use ($app) {
    return new App\Entity\AdminsRepository($app['db_admins']);
});

$app['chatRepository'] = $app->share(function() use ($app) {
    return new App\Entity\ChatRepository($app['db_chat']);
});

$app['serversRepository'] = $app->share(function() use ($app) {
    return new App\Entity\ServersRepository($app['db_admins']);
});

$app['motdRepository'] = $app->share(function() use ($app) {
    return new App\Entity\MotdRepository($app['db_accounts']);
});

$app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
    $twig->addFunction(new \Twig_SimpleFunction('asset', function ($asset) use ($app) {
        return sprintf('%s/web/%s',
            $app['request']->getBasePath(),
            ltrim($asset, '/')
        );
    }));

    return $twig;
}));

$app->error(function (\Exception $error, $code) use ($app) {
    if ($code == 404) {
        return $app['twig']->render('404.html.twig');
    }
    return $app['twig']->render('error.html.twig', ['error' => $error]);
});

$app->get('/', function() use ($app) {
    return $app['twig']->render('index_dynamic.html.twig', ['servers' => $app['adminsRepository']->getCSServers(), 'servers2' => $app['serversRepository']->getServers()]);
})->bind('index');

$app->get('/login', function() use ($app) {
	if($app['session']->get('user') || $app['session']->get('admin')) {
        return $app->redirect('/');
    }
    return $app['twig']->render('login.html.twig');
})->bind('login');

$app->post('/logged', function(Request $request) use ($app) {
    $data = $request->request->all();
    
    $result = $app['accountsRepository']->checkUser($data['login']);
	
	if(!password_verify($data['password'], $result['password'])) {
		return $app['twig']->render('error.html.twig', ['error' => "Wpisane dane są nieprawidłowe!"]);
	}
	
	$app['session']->set('username', $result['login']); 
	$app['session']->set('id', $result['id']);
    
    switch($result['permissions']) {
        case 0: $app['session']->set('user', true); return $app->redirect('/user');
        case 1: $app['session']->set('admin', true); return $app->redirect('/admin');
    }
})->bind('logged');

$app->get('/register', function() use ($app) {
	if($app['session']->get('user') || $app['session']->get('admin')) {
        return $app->redirect('/');
    }
    return $app['twig']->render('register.html.twig');
})->bind('register');

$app->post('/registered', function(Request $request) use ($app) {
	if($app['session']->get('user') || $app['session']->get('admin')) {
        return $app->redirect('/');
    }
	
	$data = $request->request->all();
    
    $user = $app['accountsRepository']->checkUserExists($data['login'], $data['email']);

	if($user['id'] != 0 && $user['password'] != NULL) {
		return $app['twig']->render('error.html.twig', ['error' => "Konto o podanych danych już istnieje!"]);
	}
	else if($user['id'] != 0 && $user['password'] == NULL){
		$app['accountsRepository']->registeredUser($data['login'], password_hash($data['password'], PASSWORD_DEFAULT), $data['email']);
	}
	else {
		$id = $app['accountsRepository']->registerUser($data['login'], password_hash($data['password'], PASSWORD_DEFAULT), $data['email']);
	}
	
    return $app['twig']->render('success.html.twig', ['success' => "Rejestracja przebiegła pomyślnie!"]);
})->bind('registered');

$app->get('/admin', function() use ($app) {
    if(!$app['session']->get('admin')) {
        return $app->redirect('/login');
    }
    return $app['twig']->render('admin.html.twig');
})->bind('admin');

$app->get('/user', function() use ($app) {
    if(!$app['session']->get('user') && !$app['session']->get('admin')) {
        return $app->redirect('/login');
    }
    return $app['twig']->render('user.html.twig', ['user' => $user = $app['accountsRepository']->getUser($app['session']->get('id')), 'groups' => $app['accountsRepository']->getGroups(), 'responsibilities' => $app['accountsRepository']->getUserResponsibilities($app['session']->get('id'))]);
})->bind('user');

$app->post('/change', function(Request $request) use ($app) {
    if(!$app['session']->get('user') && !$app['session']->get('admin')) {
        return $app->redirect('/login');
    }
	
	$data = $request->request->all();
	
	$result = $app['accountsRepository']->checkUser($data['login']);
	
	if(!password_verify($data['oldpassword'], $result['password'])) {
		return $app['twig']->render('error.html.twig', ['error' => "Podane hasło jest nieprawidłowe!"]);
	}
	
	$app['accountsRepository']->updatePassword($app['session']->get('id'), password_hash($data['newpassword'], PASSWORD_DEFAULT));
	
	return $app['twig']->render('success.html.twig', ['success' => "Hasło zostało pomyślnie zmienione!"]);
})->bind('change');

$app->post('/save', function(Request $request) use ($app) {
    if(!$app['session']->get('user') && !$app['session']->get('admin')) {
        return $app->redirect('/login');
    }
	
	$data = $request->request->all();
	
	$result = $app['accountsRepository']->checkUser($data['login']);
	
	if(!password_verify($data['oldpassword'], $result['password'])) {
		return $app['twig']->render('error.html.twig', ['error' => "Podane hasło jest nieprawidłowe!"]);
	}
	
	if($data['permissions'] < $result['permissions'])
	{
		$app['session']->set('admin', false);
		$app['session']->set('user', false);
		$app['session']->set('username', '');
		$app['session']->set('id', '');
	}
	
	$app['accountsRepository']->updateUser($app['session']->get('id'), $data['login'], 0, $data['email'], $data['avatar'], $data['group'], $data['permissions'], $data['responsibilities']);
	
	return $app['twig']->render('success.html.twig', ['success' => "Profil został pomyślnie zapisany!"]);
})->bind('save');

$app->get('/logout', function() use ($app) {
    $app['session']->set('admin', false);
    $app['session']->set('user', false);
    $app['session']->set('username', '');
    $app['session']->set('id', '');

    return $app->redirect('/');
})->bind('logout');

$app->get('/chat/{id}', function($id) use ($app) { 
	return $app['twig']->render('chat.html.twig', ['chat' => $app['chatRepository']->getMessages($id), 'server' => $id]);
})->bind('chat');

$app->get('/responsibilities', function() use ($app) { 
    return $app['twig']->render('responsibilities.html.twig', ['users' => $app['accountsRepository']->getUsersResponsibilities()]);
})->bind('responsibilities');

$app->get('/responsibilities/{id}', function($id) use ($app) { 
    return $app['twig']->render('userresponsibilities.html.twig', ['user' => $app['accountsRepository']->getResponsibilities($id)]);
})->bind('userresponsibilities');

$app->get('/groups', function() use ($app) {
	if(!$app['session']->get('admin')) {
        return $app->redirect('/');
    }
	
	return $app['twig']->render('groups.html.twig', ['groups' => $app['accountsRepository']->getGroups()]);
})->bind('groups');

$app->get('/groups/{id}', function($id) use ($app) {
	if(!$app['session']->get('admin')) {
        return $app->redirect('/');
    }

	return $app['twig']->render('groupsedit.html.twig', ['group' => $app['accountsRepository']->getGroup($id)]);
})->bind('groupsedit');

$app->post('/groups', function(Request $request) use ($app) {
    if(!$app['session']->get('admin')) {
        return $app->redirect('/');
    }
	
	$data = $request->request->all();
	
	$app['accountsRepository']->updateGroup($data['id'], $data['name'], $data['image'], $data['prefix'], $data['suffix']);
    
    return $app['twig']->render('success.html.twig', ['success' => "Zmiany grupy zostały pomyślnie zapisane."]);
})->bind('groupssave');

$app->get('/groupadd', function() use ($app) {
	if(!$app['session']->get('admin')) {
        return $app->redirect('/');
    }
	
	return $app['twig']->render('groupadd.html.twig');
})->bind('groupadd');

$app->post('/groupadded', function(Request $request) use ($app) {
    if(!$app['session']->get('admin')) {
        return $app->redirect('/');
    }
	
	$data = $request->request->all();
	
	$app['accountsRepository']->addGroup($data['name'], $data['image'], $data['prefix'], $data['suffix']);
    
    return $app['twig']->render('success.html.twig', ['success' => "Grupa została pomyślnie dodana."]);
})->bind('groupadded');

$app->post('/groupdelete', function(Request $request) use ($app) {
    if(!$app['session']->get('admin')) {
        return $app->redirect('/');
    }
	
	$data = $request->request->all();
	
	$app['accountsRepository']->deleteGroup($data['id']);
    
    return $app->path('groups');
})->bind('groupdelete');

$app->get('/users', function() use ($app) {
	if(!$app['session']->get('admin')) {
        return $app->redirect('/');
    }
	
	return $app['twig']->render('users.html.twig', ['users' => $app['accountsRepository']->getUsers()]);
})->bind('users');

$app->get('/users/{id}', function($id) use ($app) {
	if(!$app['session']->get('admin')) {
        return $app->redirect('/');
    }

	return $app['twig']->render('usersedit.html.twig', ['user' => $app['accountsRepository']->getUser($id), 'groups' => $app['accountsRepository']->getGroups(), 'responsibilities' => $app['accountsRepository']->getUserResponsibilities($id)]);
})->bind('usersedit');

$app->post('/users', function(Request $request) use ($app) {
    if(!$app['session']->get('admin')) {
        return $app->redirect('/');
    }
	
	$data = $request->request->all();
	
	$password = $data['password'];

	if(strlen($password) > 0)
	{
		if(strlen($password) < 8) {
			return $app['twig']->render('error.html.twig', ['error' => "Hasło musi mieć co najmniej 8 znaków!"]);
		}
		
		if(strlen($password) < 60) {
			$password = password_hash($password, PASSWORD_DEFAULT);
		}
	}

	$app['accountsRepository']->updateUser($data['id'], $data['login'], $password, $data['email'], $data['avatar'], $data['group'], $data['permissions'], $data['responsibilities']);
    
    return $app['twig']->render('success.html.twig', ['success' => "Zmiany użytkownika zostały pomyślnie zapisane."]);
})->bind('userssave');

$app->get('/useradd', function() use ($app) {
	if(!$app['session']->get('admin')) {
        return $app->redirect('/');
    }
	
	return $app['twig']->render('useradd.html.twig', ['groups' => $app['accountsRepository']->getGroups()]);
})->bind('useradd');

$app->post('/useradded', function(Request $request) use ($app) {
    if(!$app['session']->get('admin')) {
        return $app->redirect('/');
    }
	
	$data = $request->request->all();
	
	$user = $app['accountsRepository']->checkUserExists($data['login'], $data['email']);
	if($user['id'] != 0) {
		return $app['twig']->render('error.html.twig', ['error' => "Użytkownik o podanych danych już istnieje!"]);
	}
	
	$password = $data['password'];

	if(strlen($password) > 0)
	{
		if(strlen($password) < 8) {
			return $app['twig']->render('error.html.twig', ['error' => "Hasło musi mieć co najmniej 8 znaków!"]);
		}
		
		$password = password_hash($password, PASSWORD_DEFAULT);
	}
	
	$app['accountsRepository']->addUser($data['login'], $password, $data['email'], $data['avatar'], $data['group'], $data['permissions'], $data['responsibilities']);
    
    return $app['twig']->render('success.html.twig', ['success' => "Użytkownik został pomyślnie dodany."]);
})->bind('useradded');

$app->get('/servers/{name}/{ip}', function($name, $ip) use ($app) {
	require_once('./GameQ/Autoloader.php');
	
	if(strpos($name, 'CS:GO') !== false)
	{
		$servers = [['type' => 'cs16', 'host' => $ip]];
	}
	else
	{
		$servers = [['type' => 'csgo', 'host' => $ip]];
	}
	
	$GameQ = new \GameQ\GameQ(); 
	$GameQ->addServers($servers);
	$GameQ->setOption('timeout', 1);
	$GameQ->addFilter('normalize');
	
	$results = $GameQ->process();
	
	foreach($results as $server) 
	{
		if($server['gq_hostname'] == "") 
		{
			$info['status'] = 'Offline';
		}
		else
		{
			$info['status'] = 'Online';
			$info['map'] = $server['gq_mapname'];
			$info['players'] = $server['gq_numplayers'];
			$info['maxplayers'] = $server['gq_maxplayers'];
			$info['percent'] = round($info['players']/$info['maxplayers']*100);
		
			usort($server['players'], function($a, $b) 
			{
				return $b['score'] - $a['score'];
			});

			$rank = 1; $value = 0; $index = 0;

			foreach($server['players'] as $key => $player)
			{
				if($player['score'] != $value) 
				{
					$value = $player['score'];
					$ranks[$key] = $rank++;	
				}
				else
				{
					$ranks[$key] = $rank;	
				}
			
				$time['seconds'][$key] = $player['time'];
			
				while($time['seconds'][$index] >= 60)
				{
					$time['seconds'][$index] -= 60;
					$time['minutes'][$index]++;
			
					if ($time['minutes'][$index] >= 60)
					{
						$time['minutes'][$index] -= 60;
						$time['hours'][$index]++;
					}
				}
			
				$time['seconds'][$index] = round($time['seconds'][$index]);
			
				$index++;
			}
		}
	}
	
	return $app['twig']->render('score.html.twig', ['players' => $server['players'], 'time' => $time, 'ranks' => $ranks, 'server' => $name, 'ip' => $ip, 'info' => $info]);
})->bind('servers');

$app->get('/admins', function() use ($app) {
	if(!$app['session']->get('admin')) {
        return $app['twig']->render('adminsall.html.twig', ['admins' => $app['adminsRepository']->getAdminsAll()]);
    }
	
	return $app['twig']->render('admins.html.twig', ['admins' => $app['adminsRepository']->getAdmins()]);
})->bind('admins');

$app->get('/adminsall', function() use ($app) {
	if($app['session']->get('admin')) {
        return $app['twig']->render('admins.html.twig', ['admins' => $app['adminsRepository']->getAdmins()]);
    }
	
	return $app['twig']->render('adminsall.html.twig', ['admins' => $app['adminsRepository']->getAdminsAll()]);
})->bind('adminsall');

$app->get('/admins/{id}', function($id) use ($app) {
	if(!$app['session']->get('admin')) {
        return $app->redirect('/');
    }
	
	return $app['twig']->render('adminedit.html.twig', ['admin' => $app['adminsRepository']->getAdmin($id), 'servers' => $app['serversRepository']->getServers()]);
})->bind('adminsedit');

$app->get('/adminsadd', function() use ($app) {
	if(!$app['session']->get('admin')) {
        return $app->redirect('/');
    }
	
	return $app['twig']->render('adminadd.html.twig', ['servers' => $app['serversRepository']->getServers()]);
})->bind('adminsadd');

$app->post('/adminadd', function(Request $request) use ($app) {
    if(!$app['session']->get('admin')) {
        return $app->redirect('/');
    }
	
	$data = $request->request->all();

	if($data['steamid'] == '')
	{
		$data['steamid'] = "Non-Steam";
	}

	if($data['contact'] == '')
	{
		$data['contact'] = "Brak";
	}
	
	$app['adminsRepository']->addAdmin($data['name'], $data['steamid'], $data['password'], $data['contact'], $data['server'], $data['type'], $data['service']);
    
    return $app['twig']->render('success.html.twig', ['success' => "Admin został pomyślnie dodany."]);
})->bind('adminsadded');

$app->post('/admins', function(Request $request) use ($app) {
    if(!$app['session']->get('admin')) {
        return $app->redirect('/');
    }
	
	$data = $request->request->all();
	
	$app['adminsRepository']->updateAdmin($data['id'], $data['name'], $data['steamid'], $data['password'], $data['contact'], $data['server'], $data['service'], $data['type'], $data['old_name'], $data['old_steamid'], $data['old_server']);
    
    return $app['twig']->render('success.html.twig', ['success' => "Zmiany admina zostały pomyślnie zapisane."]);
})->bind('adminssave');

$app->post('/adminsdelete', function(Request $request) use ($app) {
    if(!$app['session']->get('admin')) {
        return $app->redirect('/');
    }
	
	$data = $request->request->all();
	
	$app['adminsRepository']->deleteAdmin($data['id'], $data['service'], $data['service_type'], $data['server'], $data['name'], $data['steamid']);
    
    return $app->path('admins');
})->bind('adminsdelete');

$app->post('/adminsextend', function(Request $request) use ($app) {
    if(!$app['session']->get('admin')) {
        return $app->redirect('/');
    }
	
	$data = $request->request->all();
	
	$app['adminsRepository']->extendAdmin($data['id'], $data['service'], $data['service_type'], $data['server'], $data['name'], $data['steamid'], $data['password']);
    
    return $app['twig']->render('success.html.twig', ['success' => "Usługa admina została przedłużona o 30 dni."]);
})->bind('adminsextend');

$app->get('/motd', function(Request $request) use ($app) {
	return $app['twig']->render('motd.html.twig', ['motd' => $app['motdRepository']->getMOTD()]);
})->bind('motd');

$app->get('/motdedit', function(Request $request) use ($app) {
    if(!$app['session']->get('admin')) {
        return $app->redirect('/');
    }
	
	return $app['twig']->render('motdedit.html.twig', ['motd' => $app['motdRepository']->getMOTD()]);
})->bind('motdedit');

$app->post('/motdsave', function(Request $request) use ($app) {
    if(!$app['session']->get('admin')) {
        return $app->redirect('/');
    }
	
	$data = $request->request->all();
	
	$app['accountsRepository']->saveMOTD($data['motd']);
    
    return $app['twig']->render('success.html.twig', ['success' => "MOTD zostało pomyślnie zapisane."]);
})->bind('motdsave');

$app->get('/logged', function() use ($app) {
    return $app->redirect('/');
});

$app->get('/registered', function() use ($app) {
    return $app->redirect('/');
});

$app->get('/save', function() use ($app) {
    return $app->redirect('/');
});

$app->get('/change', function() use ($app) {
    return $app->redirect('/');
});

$app->get('/update', function() use ($app) {
    return $app->redirect('/');
});

$app->run();