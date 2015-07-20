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

	public function save($email,$username,$password) {
		$query = $this->db->prepareQuery( 'INSERT INTO `*PREFIX*users_queue`'
			.' ( `email`, `username`, `password`, `activated`, `banned`,`token`,  `requested` ) VALUES( ?, ?,?, FALSE,FALSE, ?, NOW() )' );
		$token = $this->random->generate(30);
		$query->execute(array( $email, $username,$password,$token ));
		return $token;
	}
	public function find($email) {
		$query = $this->db->prepareQuery('SELECT `email`,`username`,`password`,`activated` FROM `*PREFIX*users_queue` WHERE `email` = ? ');
		return $query->execute(array($email))->fetchAll();
	}

	public function delete($email) {
		$query = $this->db->prepareQuery('DELETE FROM `*PREFIX*users_queue` WHERE `email` = ? ');
		return $query->execute(array($email));
	}

	public function activate($email) {
		$query = $this->db->prepareQuery('UPDATE `*PREFIX*users_queue` SET `activated`=TRUE where `email` = ? ');
		return $query->execute(array($email));
	}

	public function deactivate($email) {
		$query = $this->db->prepareQuery('UPDATE `*PREFIX*users_queue` SET `activated`=FALSE,`banned`=TRUE where `email` = ? ');
		return $query->execute(array($email));
	}


	public function findEmailByToken($token) {
		$query = $this->db->prepareQuery('SELECT `email`,`username`, `password`, `activated` FROM `*PREFIX*users_queue` WHERE `token` = ? ');
		return $query->execute(array($token))->fetchOne();
	}

}
