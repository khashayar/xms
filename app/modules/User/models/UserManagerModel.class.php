<?php

class User_UserManagerModel extends XRXUserBaseModel
{
	private $tblUsers = "users";

	public function getById($id)
	{
		$sql = "SELECT u.*
				FROM %s AS u
				WHERE u.id = :id";

		$sql	= sprintf($sql, $this->tblUsers);
		$stmt	= $this->getContext()->getDatabaseConnection()->prepare($sql);
		$stmt->bindValue(':id', $id, PDO::PARAM_INT);
		$stmt->execute();
		
		$result = $stmt->fetchObject();
		FirePHP::getInstance(true)->log($result);
	}


	public function getByUsername($username)
	{
		$sql = "SELECT u.*
				FROM %s AS u
				WHERE username = :username";

		$sql	= sprintf($sql, $this->tblUsers);
		$stmt	= $this->getContext()->getDatabaseConnection()->prepare($sql);
		$stmt->bindValue(':username', $username, PDO::PARAM_STR);
		$stmt->execute();

		$result = $stmt->fetch(PDO::FETCH_ASSOC);

		if ($result != false) {
			return $this->getContext()->getModel('User', 'User', array($result));
		}

		return null;
	}


	public function getByUsernameOrEmail($username, $email)
	{
		$sql = "SELECT u.*
				FROM %s AS u
				WHERE username = :username OR email = :email";

		$sql	= sprintf($sql, $this->tblUsers);
		$stmt	= $this->getContext()->getDatabaseConnection()->prepare($sql);
		$stmt->bindValue(':username', $username, PDO::PARAM_STR);
		$stmt->bindValue(':email', $email, PDO::PARAM_STR);
		$stmt->execute();
		
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if ($result != false) {
			return $this->getContext()->getModel('User', 'User', array($result));
		}

		return null;
	}


	public function add(array $data)
	{
		$user = $this->getContext()->getModel('User', 'User', array($data));
		
		$sql = "INSERT INTO %s (username, password, email)
				VALUES(:username, :password, :email)";

		$sql	= sprintf($sql, $this->tblUsers);
		$stmt	= $this->getContext()->getDatabaseConnection()->prepare($sql);
		$stmt->bindValue(':username', $user->getUsername(), PDO::PARAM_STR);
		$stmt->bindValue(':password', $user->getPassword(), PDO::PARAM_STR);
		$stmt->bindValue(':email', $user->getEmail(), PDO::PARAM_STR);
		$stmt->execute();

		return true;
	}
}

?>