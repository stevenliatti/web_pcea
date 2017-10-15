<?php

namespace Pcea\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Pcea\Entity\Event;
use Pcea\Entity\Spent;

class EventController {
	public function eventAction($eventId, Application $app) {
		if ($app['security.authorization_checker']->isGranted('IS_AUTHENTICATED_FULLY')) {
			$currentUser = $app['user'];
			if ($app['dao.event']->isAccessibleBy($eventId, $currentUser->getId())) {
				$event = $app['dao.event']->read($eventId);
				$parts = array();
				foreach ($event->getUsers() as $user) {
					$parts[$user->getId()] = 0;
				}
				$spents = $app['dao.spent']->readByEvent($eventId);
				$total = 0;
				
				foreach ($spents as $spent) {
					foreach ($spent->getUsers() as $user) {
						$parts[$user->getId()] += $user->getPart();
					}
					$total += floatval($spent->getAmount());
				}

				return $app['twig']->render('event.html.twig', array(
					'spents' => $spents,
					'event' => $event,
					'parts' => $parts,
					'total' => $total
				));
			}
			else {
				return $app->redirect('/pcea/web');
			}
		}
		else {
			return $app->redirect('/pcea/web');
		}
	}

	public function newEventAction(Request $request, Application $app) {
		if ($app['security.authorization_checker']->isGranted('IS_AUTHENTICATED_FULLY')) {
			$event = new Event();
			$users = $app['dao.user']->readAll();

			$eventForm = $app['form.factory']->createBuilder(FormType::class, $event)
				->add('name', TextType::class)
				->add('currency', CurrencyType::class, array(
					'preferred_choices' => array('CHF', 'EUR', 'USD')

				))
				->add('users', ChoiceType::class, array(
					'choices'  => array_column($users, 'id', 'username'),
					'multiple' => true
				))
				->getForm();

			$eventForm->handleRequest($request);
			if ($eventForm->isSubmitted() && $eventForm->isValid()) {
				$weight = $_POST['weight'];
				$app['dao.event']->create($event, $weight);
				return $app->redirect('/pcea/web/event/' . $event->getId());
			}
			return $app['twig']->render('new_event.html.twig', array(
				'title' => 'New event',
				'eventForm' => $eventForm->createView()));
		}
		else {
			return $app->redirect('/pcea/web');
		}
	}

	public function newSpentAction($eventId, Request $request, Application $app) {
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
	}
}
