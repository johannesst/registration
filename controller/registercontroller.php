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

namespace OCA\Registration\Controller;


use \OCP\IRequest;
use \OCP\AppFramework\Http\TemplateResponse;
use \OCP\AppFramework\Controller;
use \OCP\Util;
use \OCA\Registration\Wrapper;
use \OCP\IUserManager;
use \OCP\User;
use \OCP\IGroupManager;
use \OCP\IL10N;
use \OCP\IConfig;

class RegisterController extends Controller {

	private $mail;
	private $l10n;
	private $urlgenerator;
	private $pendingreg;
	private $usersqueue;
	private $usermanager;
	private $config;
	private $groupmanager;
	protected $appName;

	public function __construct($appName, IRequest $request, Wrapper\Mail $mail, IL10N $l10n, $urlgenerator,
			$pendingreg, $usersqueue, IUserManager $usermanager, IConfig $config, IGroupManager $groupmanager){
		$this->mail = $mail;
		$this->l10n = $l10n;
		$this->urlgenerator = $urlgenerator;
		$this->pendingreg = $pendingreg;
		$this->usersqueue = $usersqueue;
		$this->usermanager = $usermanager;
		$this->config = $config;
		$this->groupmanager = $groupmanager;
		$this->appName = $appName;
		parent::__construct($appName, $request);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 */
	public function askEmail($errormsg, $entered) {
		$params = array(
				'link'  => $this->urlgenerator->getAbsoluteURL($this->urlgenerator->linkToRoute('registration.register.validateEmail')), 
				'errormsg' => $errormsg ? $errormsg : $this->request->getParam('errormsg'),
				'entered' => $entered ? $entered : $this->request->getParam('entered')
			       );
		return new TemplateResponse('registration', 'register', $params, 'guest');
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 */
	public function validateEmail() {
		$email = $this->request->getParam('email');
		if ( !filter_var($email, FILTER_VALIDATE_EMAIL) ) {
			return new TemplateResponse('', 'error', array(
						'errors' => array(array(
								'error' => $this->l10n->t('Email address you entered is not valid'),
								'hint' => ''
								))
						), 'error');
		}

		if ( $this->pendingreg->find($email) ) {
			return new TemplateResponse('', 'error', array(
						'errors' => array(array(
								'error' => $this->l10n->t('There is already a pending registration with this email'),
								'hint' => ''
								))
						), 'error');
		}

		if ($this->usersqueue->find($email) ) {
			return new TemplateResponse('', 'error', array(
						'errors' => array(array(
								'error' => $this->l10n->t('There is already a pending registration with this email'),
								'hint' => ''
								))
						), 'error');

		}

		if ( $this->config->getUsersForUserValue('settings', 'email', $email) ) {
			return new TemplateResponse('', 'error', array(
						'errors' => array(array(
								'error' => $this->l10n->t('There is an existing user with this email'),
								'hint' => ''
								))
						), 'error');
		}


		// FEATURE: allow only from specific email domain

		$allowed_domains= $this->config->getAppValue($this->appName, 'allowed_domains','');
		if ( ($allowed_domains === null) || ($allowed_domains === '') || ( strlen($allowed_domains)===0)){
		}else{
			$allowed_domains= explode (";",$allowed_domains);
			$allowed=false;
			$domains=array();
			foreach ($allowed_domains as $domain ) {
				$domains[]=$domain;//=$domain.print_unescaped("<br>").$domains;
				$maildomain=explode("@",$email)[1];
				// valid domain, everythings fine
				if ($maildomain === $domain) {
					$allowed=true;
					break;
				}

			}
			// $allowed still false->return error message
			if ( $allowed === false ) {
				return new TemplateResponse('registration', 'domains', ['domains' =>
						$domains
						], 'guest');
			}
		}//else var_dump($allowed_domains);

		$token = $this->pendingreg->save($email);
		//TODO: check for error
		$link = $this->urlgenerator->linkToRoute('registration.register.verifyToken', array('token' => $token));
		$link = $this->urlgenerator->getAbsoluteURL($link);
		$from = Util::getDefaultEmailAddress('register');
		$res = new TemplateResponse('registration', 'email', array('link' => $link), 'blank');
		$msg = $res->render();
		try {
			$this->mail->sendMail($email, 'ownCloud User', $this->l10n->t('Verify your ownCloud registration request'), $msg, $from, 'ownCloud');
		} catch (\Exception $e) {
			\OC_Template::printErrorPage( 'A problem occurs during sending the e-mail please contact your administrator.');
			return;
		}
		return new TemplateResponse('registration', 'message', array('msg' =>
					$this->l10n->t('Verification email successfully sent.')
					), 'guest');
	}


	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @NoPublicPage
	 */
	public function pendingReg(){
		//	OCP\User::checkAdminUser();
		$user=  \OC_User::getUser();
		$group = $this->config->getAppValue($this->appName,'registrators_group','');
		if (\OC_Group::inGroup($user, $group) || \OC_User::isAdminUser($user)){

			$accounts=$this->usersqueue->getQueue();
			$link = $this->urlgenerator->linkToRoute('registration.register.changeQueue');
			$link = $this->urlgenerator->getAbsoluteURL($link);
			return new TemplateResponse('registration', 'queue', [
					'accounts' => $accounts,
					'link' => $link
					]);

		}else{
			\OCP\User::checkAdminUser();
		}
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 */
	public function changeQueue(){
		$ban = $this->request->getParam('ban');
		$enable = $this->request->getParam('enable');
		$accounts = $this->usersqueue->getQueue();
		$email = null;
		$state = null;
		if ($ban === null && $enable !== null ){
			$email = $enable;
			$state = 'activated';
		}else if ($enable === null && $ban !==null) {
			$email = $ban;
			$state= 'banned';
		}else{
			return new TemplateResponse('', 'error', array(
						'errors' => array(array(
								'error' => $this->l10n->t('Changing queue entry failed'),
								'hint' => ''
								))
						), 'error');
		}
		//->$usersqueue->setState($email,$state);	
		//$msg = $res->render();
		$entry=$this->usersqueue->find($email);
		if (!empty(array_filter($entry))) {
			$username = $entry[0]['username'];
			$password = $entry[0]['password'];
			if ($state === 'activated'){
				$this->createAccountPriv($email,$username,$password);
				$from = Util::getDefaultEmailAddress('register');

				$link = $this->urlgenerator->getAbsoluteURL('/');
				$msg=str_replace('{link}', $link, $this->l10n->t('Your account has been enabled, you can <a href="{link}">log in now</a>.'));

				$this->usersqueue->delete($email);	
				try {
					$this->mail->sendMail($email, 'ownCloud User', $this->l10n->t('ownCloud account enabled'), $msg, $from, 'ownCloud');
				} catch (Exception $e) {
					\OC_Template::printErrorPage( 'A problem occurs during sending the e-mail please contact your administrator.');
					return;
				}
				return new TemplateResponse('registration', 'message', array('msg' =>
							$this->l10n->t('Enable email successfully sent.')
							), 'guest');

			}else if($state === 'banned'){
				$this->usersqueue->setState($email,$state);
				$from = Util::getDefaultEmailAddress('register');

				$msg=$this->l10n->t('Your Emailadress has been banned. Please contact the administrator for more information.');
				try {
					$this->mail->sendMail($email, 'ownCloud User', $this->l10n->t('ownCloud registration banned'), $msg, $from, 'ownCloud');
				} catch (Exception $e) {
					\OC_Template::printErrorPage( 'A problem occurs during sending the e-mail please contact your administrator.');
					return;
				}
				return new TemplateResponse('registration', 'message', array('msg' =>
							$this->l10n->t('Ban email successfully sent.')
							), 'guest');

			}else{
				return new TemplateResponse('', 'error', array(
							'errors' => array(array(
									'error' => $this->l10n->t('Changing queue entry failed'),
									'hint' => ''
									))
							), 'error');
			}
		}
		//	$this->createAccountPriv($email,$username,$password);

		$accounts=$this->usersqueue->getQueue();
		$link = $this->urlgenerator->linkToRoute('registration.register.changeQueue');
		$link = $this->urlgenerator->getAbsoluteURL($link);
		return new TemplateResponse('registration', 'queue', [
				'accounts' => $accounts,
				'link' => $link
				]);


	}
	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 */
	public function verifyToken($token) {
		$email = $this->pendingreg->findEmailByToken($token);
		if ( \OCP\DB::isError($email) ) {
			return new TemplateResponse('', 'error', array(
						'errors' => array(array(
								'error' => $this->l10n->t('Invalid verification URL. No registration request with this verification URL is found.'),
								'hint' => ''
								))
						), 'error');
		} elseif ( $email ) {
			return new TemplateResponse('registration', 'form', array('link'  => $this->urlgenerator->getAbsoluteURL($this->urlgenerator->linkToRoute('registration.register.createAccount',array('token' => $token))), 'email' => $email, 'token' => $token), 'guest');
		}
	}
	private function deletePendingreg($email){
		// Delete pending reg request
		$res = $this->pendingreg->delete($email);
		if ( \OCP\DB::isError($res) ) {
			return new TemplateResponse('', 'error', array(
						'errors' => array(array(
								'error' => $this->l10n->t('Failed to delete pending registration request'),
								'hint' => ''
								))
						), 'error');
		}
	}

	private function createQueueEntry($token,$email,$username,$password){
		$this->usersqueue->save($token,$email,$username,$password);
		$this->deletePendingreg($email);
	}

	/**
	 *

	 */
	private function createAccountPriv($email,$username,$password) {
		try {
			$user = $this->usermanager->createUser($username, $password);
		} catch (Exception $e) {
			return new TemplateResponse('registration', 'form',
					array(  'email' => $email, 
						'link'  => $this->urlgenerator->getAbsoluteURL($this->urlgenerator->linkToRoute('registration.register.createAccount',array('token' => $token))), 
						'entered_data' => array('username' => $username),
						'errormsgs' => array($e->message, $username, $password)), 'guest');
		}

		if ( $user === false ) {
			return new TemplateResponse('', 'error', array(
						'errors' => array(array(
								'error' => $this->l10n->t('Unable to create user, there are problems with user backend.'),
								'hint' => ''
								))
						), 'error');
		} else {
			// Set user email
			try {
				$this->config->setUserValue($user->getUID(), 'settings', 'email', $email);
			} catch (Exception $e) {
				return new TemplateResponse('registration', 'form',
						array('email' => $email,
							'link'  => $this->urlgenerator->getAbsoluteURL($this->urlgenerator->linkToRoute('registration.register.createAccount',array('token' => $token))), 
							'entered_data' => array('username' => $username),
							'errormsgs' => array($e->message, $username, $password)), 'guest');
			}

			// Add user to group
			$registered_user_group = $this->config->getAppValue($this->appName, 'registered_user_group', 'none');
			if ( $registered_user_group !== 'none' ) {
				try {
					$group = $this->groupmanager->get($registered_user_group);
					$group->addUser($user);
				} catch (Exception $e) {
					return new TemplateResponse('', 'error', array(
								'errors' => array(array(
										'error' => $e->message,
										))
								), 'error');
				}
			}
		}

	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 */
	public function createAccount($token) {
		$email = $this->pendingreg->findEmailByToken($token);
		if ( \OCP\DB::isError($email) ) {
			return new TemplateResponse('', 'error', array(
						'errors' => array(array(
								'error' => $this->l10n->t('Invalid verification URL. No registration request with this verification URL is found.'),
								'hint' => ''
								))
						), 'error');
		} elseif ( $email ) {
			$username = $this->request->getParam('username');
			$password = $this->request->getParam('password');
			if ($this->usermanager->userExists($username)){
				return new TemplateResponse('registration', 'message', array('msg' =>
							$this->l10n->t('There is an existing user with this username')
							), 'guest');

			}
			// only alphanumeric usernames are allowed
			if (preg_match('/[^a-zA-Z0-9 _\.@\-\']/', $username)) {
				return new TemplateResponse('registration', 'message', array('msg' =>
							$this->l10n->t('Only the following characters are allowed in a username:'
				. ' "a-z", "A-Z", "0-9", and "_.@-\'"')
							), 'guest');
			}
			//länge überprüfen ->Wenn <8 ->exception
			if (strlen($username) <1){
				return new TemplateResponse('registration', 'message', array('msg' =>
							$this->l10n->t('No username given. Please enter a username!')
							), 'guest');
			}
			if(strlen($password)<8){
				return new TemplateResponse('registration', 'message', array('msg' =>
							$this->l10n->t('Password too short. Please enter a password of eight or more characters!')
							), 'guest');
			}
			$needs_activation =  $this->config->getAppValue($this->appName, 'needs_activation','');
			//Do we need an activation by an Administrator?
			if ($needs_activation === 'checked'){

				// 
				if(empty($this->usersqueue->find($email))){
					$this->createQueueEntry($token,$email,$username,$password);	
					return new TemplateResponse('registration', 'message', array('msg' =>
								$this->l10n->t('Your account needs to be enabled by an administrator.')
								), 'guest');


				}else{
					$this->createAccountPriv($email,$username,$password);
					$this->deletePendingreg($email);



					return new TemplateResponse('registration', 'message', array('msg' =>
								str_replace('{link}',
									$this->urlgenerator->getAbsoluteURL('/'),
									$this->l10n->t('Your account has been successfully created, you can <a href="{link}">log in now</a>.'))
								)	, 'guest');
				}
			}
		}

	}
}
