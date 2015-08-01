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
//$user = $c->getServer()->getUserSession()->getUser()->getUID();
$user = \OC_User::getUser();
$group = $c->query('Config')->getAppValue($c->getAppName(),'registrators_group','');
if (\OC_Group::inGroup($user, $group) || $c->isAdminUser() ){
	$navigationEntry = function () use ($c) {
		return [
			'id' => $c->getAppName(),
			'order' => 1,
			'name' => $c->query('L10N')->t('Pending registration requests'),
			'href' => $c->query('URLGenerator')->linkToRoute('registration.register.pendingReg'),
			'icon' => $c->query('URLGenerator')->imagePath('settings', 'users.svg'),
		];
	};
	$c->getServer()->getNavigationManager()->add($navigationEntry);
}

\OC_App::registerLogIn(array('name' => $c->query('L10N')->t('Register'), 'href' => $c->query('URLGenerator')->linkToRoute('registration.register.askEmail')));

\OCP\App::registerAdmin($c->getAppName(), 'admin');
