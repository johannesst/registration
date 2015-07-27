<?php
/**
 * ownCloud - registration
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Pellaeon Lin <pellaeon@hs.ntnu.edu.tw>
 * @copyright Pellaeon Lin 2014
 */

namespace OCA\Registration\App;
use \OCP\IUserSession;

$app = new Registration();
$c = $app->getContainer();


// add an navigation entry
$user = $c->getServer()->getUserSession()->getUser();
$group = 'cleveradmin';
if (\OC_Group::inGroup($user->getUID(), $group) || $c->isAdminUser() ){
	$navigationEntry = function () use ($c) {
		return [
			'id' => $c->getAppName(),
			'order' => 1,
			'name' => $c->query('L10N')->t('Pending Requests'),
			'href' => $c->query('URLGenerator')->linkToRoute('registration.register.pendingReg'),
			'icon' => $c->query('URLGenerator')->imagePath('settings', 'users.svg'),
		];
	};
	$c->getServer()->getNavigationManager()->add($navigationEntry);
}

\OC_App::registerLogIn(array('name' => $c->query('L10N')->t('Register'), 'href' => $c->query('URLGenerator')->linkToRoute('registration.register.askEmail')));

\OCP\App::registerAdmin($c->getAppName(), 'admin');
