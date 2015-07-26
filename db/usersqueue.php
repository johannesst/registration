<?php
namespace OCA\Registration\Db;

use \OCP\IDb;
use \OCP\Util;
use \OCP\Config;
use \OCP\Security\ISecureRandom;

class UsersQueue {

	private $db;

	/** @var \OCP\Security\ISecureRandom */
	protected $random;

	public function __construct(IDb $db, ISecureRandom $random) {
		$this->db = $db;
		$this->random = $random;
	}

	public function save($token,$email,$username,$password) {
		$query = $this->db->prepareQuery( 'INSERT INTO `*PREFIX*usersqueue`'
			.' ( `email`, `username`, `password`, `state`, `token`,  `requested` ) VALUES( ?, ?,?, ?, ?, NOW() )' );
		//$token = $this->random->generate(30);
		$query->execute(array( $email, $username,$password,'registered',$token ));
		return $token;
	}

	public function find($email) {
		$query = $this->db->prepareQuery('SELECT `email`,`username`,`password`,`state` FROM `*PREFIX*usersqueue` WHERE `email` = ? ');
		return $query->execute(array($email))->fetchAll();
	}

	public function findState($email,$state) {
		$query = $this->db->prepareQuery('SELECT `email`,`username`,`password`,`state` FROM `*PREFIX*usersqueue` WHERE `email` = ? AND `state` = ? ');
		return $query->execute(array($email,$state))->fetchAll();
	}

	public function delete($email) {
		$query = $this->db->prepareQuery('DELETE FROM `*PREFIX*usersqueue` WHERE `email` = ? ');
		return $query->execute(array($email));
	}

	public function setState($email,$state) {
		if (in_array($state, array("registered","banned","activated"))){
			$query = $this->db->prepareQuery('UPDATE `*PREFIX*usersqueue` SET `state`= ? where `email` = ? ');
			return $query->execute(array($state,$email));
		}else{
			return new TemplateResponse('', 'error', array(
				'errors' => array(array(
				'error' => $this->l10n->t('Failed to change state in users queue'),
				'hint' => ''
				))
			), 'error');
		}
	}



	public function findEmailByToken($token) {
		$query = $this->db->prepareQuery('SELECT `email`,`username`, `password`, `activated` FROM `*PREFIX*usersqueue` WHERE `token` = ? ');
		return $query->execute(array($token))->fetchOne();
	}

}
