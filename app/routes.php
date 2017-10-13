<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Pcea\Entity\User;
use Pcea\Entity\Event;
use Pcea\Entity\Spent;

const saltLength = 23;

// Index page
$app->get('/', function() use ($app) {
	if ($app['security.authorization_checker']->isGranted('IS_AUTHENTICATED_FULLY')) {
		$user = $app['user'];
		$events = $app['dao.event']->readByUser($user->getId());
		return $app['twig']->render('index.html.twig', array('events' => $events));
	}
	else {
		return $app['twig']->render('index.html.twig');
	}
})->bind('index');

// New event page
$app->match('/newevent', function(Request $request) use ($app) {
	if ($app['security.authorization_checker']->isGranted('IS_AUTHENTICATED_FULLY')) {
		$event = new Event();
		$users = $app['dao.user']->readAll();

		$eventForm = $app['form.factory']->createBuilder(FormType::class, $event)
			->add('name', TextType::class)
			->add('currency', CurrencyType::class)
			->add('users', ChoiceType::class, array(
				'choices'  => array_column($users, 'id', 'username'),
				'multiple' => true
			))
			->getForm();

		$eventForm->handleRequest($request);
		if ($eventForm->isSubmitted() && $eventForm->isValid()) {
			$app['dao.event']->create($event);
			return $app->redirect('/pcea/web/event/' . $event->getId());
		}
		return $app['twig']->render('new_event.html.twig', array(
			'title' => 'New event',
			'eventForm' => $eventForm->createView()));
	}
	else {
		return $app->redirect('/pcea/web');
	}
})->bind('new_event');

// Event page
$app->get('/event/{eventId}', function($eventId) use ($app) {
	if ($app['security.authorization_checker']->isGranted('IS_AUTHENTICATED_FULLY')) {
		$user = $app['user'];
		if ($app['dao.event']->isAccessibleBy($eventId, $user->getId())) {
			$event = $app['dao.event']->read($eventId);
			$spents = $app['dao.spent']->readByEvent($eventId);
			
			$total = 0;
			$grandTotal = 0;
			$weight = floatval($app['dao.event']->getWeight($eventId, $user->getId()));
			
			foreach ($spents as $spent) {
				$spent->setPart(0);

				if (in_array(array('username' => $user->getUsername()), $spent->getUsers())) {
					$amount = floatval($spent->getAmount());
					$nbConcerned = floatval($app['dao.spent']->nbConcerned($spent->getId(), $eventId));
					$spent->setPart(($amount / $nbConcerned) * $weight);
					$total += $spent->getPart();
				}

				$grandTotal += $amount;
			}

			return $app['twig']->render('event.html.twig', array(
				'spents' => $spents,
				'event' => $event,
				'total' => $total,
				'grandTotal' => $grandTotal
			));
		}
		else {
			return $app->redirect('/pcea/web');
		}
	}
	else {
		return $app->redirect('/pcea/web');
	}
})->bind('event');

// New spent page
$app->match('/event/{eventId}/newspent', function($eventId, Request $request) use ($app) {
	if ($app['security.authorization_checker']->isGranted('IS_AUTHENTICATED_FULLY')) {
		$user = $app['user'];
		if ($app['dao.event']->isAccessibleBy($eventId, $user->getId())) {
			$spent = new Spent();
			$users = $app['dao.user']->readAllFromEvent($eventId);

			$spentForm = $app['form.factory']->createBuilder(FormType::class, $spent)
				->add('name', TextType::class)
				->add('amount', NumberType::class)
				->add('buyDate', DateType::class, array(
					'input' => 'string'
				))
				->add('buyer', ChoiceType::class, array(
					'choices'  => array_column($users, 'id', 'username')
				))
				->add('users', ChoiceType::class, array(
					'choices'  => array_column($users, 'id', 'username'),
					'expanded' => true,
					'multiple' => true
				))
				->getForm();

				$spentForm->handleRequest($request);
				if ($spentForm->isSubmitted() && $spentForm->isValid()) {
					$spent->setEvent($eventId);
					$app['dao.spent']->create($spent);
					return $app->redirect('/pcea/web/event/' . $eventId);
				}
				return $app['twig']->render('new_spent.html.twig', array(
					'title' => 'New spent',
					'spentForm' => $spentForm->createView(),
					'eventId' => $eventId
				));
		}
		else {
			return $app->redirect('/pcea/web');
		}
	}
	else {
		return $app->redirect('/pcea/web');
	}
})->bind('new_spent');

// Login form
$app->get('/login', function(Request $request) use ($app) {
	$app['monolog']->debug(sprintf("'%s'", $request));
	return $app['twig']->render('login.html.twig', array(
		'error'         => $app['security.last_error']($request),
		'last_username' => $app['session']->get('_security.last_username'),
	));
})->bind('login');

// Register user
$app->match('/register', function(Request $request) use ($app) {
	if ($app['security.authorization_checker']->isGranted('IS_AUTHENTICATED_FULLY')) {
		return $app->redirect('/pcea/web');
	}
	else {
		$user = new User();
		
		$userForm = $app['form.factory']->createBuilder(FormType::class, $user)
				->add('username', TextType::class)
				->add('password', RepeatedType::class, array(
					'type'            => PasswordType::class,
					'invalid_message' => 'The password fields must match.',
					'options'         => array('required' => true),
					'first_options'   => array('label' => 'Password'),
					'second_options'  => array('label' => 'Repeat password'),
				))
				->getForm();

		$userForm->handleRequest($request);
		if ($userForm->isSubmitted() && $userForm->isValid()) {
			// generate a random salt value
			$salt = substr(md5(time()), 0, saltLength);
			$user->setSalt($salt);
			// compute the encoded password
			$password = $app['security.encoder.bcrypt']->encodePassword($user->getPassword(), $salt);
			$user->setPassword($password); 
			$app['dao.user']->create($user);
			return $app->redirect('/pcea/web');
		}
		return $app['twig']->render('register_form.html.twig', array(
			'title' => 'New user',
			'userForm' => $userForm->createView())
		);
	}
})->bind('register');
