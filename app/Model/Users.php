<?php

namespace NTSchool\Phpblog\Model;

use NTSchool\Phpblog\Core\Http\Cookie;
use NTSchool\Phpblog\Core\Http\Request;
use NTSchool\Phpblog\Core\Exceptions\ValidateException;
use NTSchool\Phpblog\Core\Http\Session;
use NTSchool\Phpblog\Core\User;

class Users extends BaseModel
{
    /**
     * Users constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->table = 'users';
        $this->pk = 'id_user';
    }

    /**
     * @return array
     */
    public function validationMap()
    {
        return [
            'fields' => ['login', 'pass', 'name'],
            'not_empty' => ['name', 'login', 'pass', 'pass_confirm', 'answer'],
            'min_length' => ['login' => 5, 'pass' => 6]
        ];
    }

    /**
     * @return mixed
     */
    public function getAllUsers()
    {
        $sql = "SELECT
	              `users`.`name` AS 'username',
                  `users`.`login` AS 'login',
                  `roles`.`description` AS 'role',
                  COUNT(`news`.`id_news`) AS 'post_number'
                  FROM `users`
                  LEFT JOIN `news` 
                    ON `users`.`id_user`=`news`.`id_user`
                  LEFT JOIN `roles` 
                    ON `users`.`id_role`=`roles`.`id_role`
                    GROUP BY `users`.`login`";

        return $this->db->select($sql);
    }

    /**
     * @param array $fields
     *
     * @throws \NTSchool\Phpblog\Core\Exceptions\ValidateException
     */
    public function signUp(array $fields)
    {
        $session = Session::instance();
        $session->save();
        $checkSess = $session->collection()->get('randStr') ?? null;

        $fields['randStr'] = $checkSess;

        $this->validation->execute($fields);
        if(!$this->validation->success()) {
            throw new ValidateException($this->validation->errors());
        }

        if($this->isUserExists($fields)) {
            throw new ValidateException(['login' => 'Пользователь с таким именем уже существует!']);
        };

        $this->db->insert('users', ['name' => $fields['name'], 'login' => $fields['login'], 'pass' => $this->getHash($fields['pass']), 'id_role' => 3]);

        unset($fields['name']);
        unset($fields['pass_confirm']);
        $this->login($fields);
    }

    public function isUserExists(array $fields)
    {
        if($this->getByLogin($fields['login'])) {
            return true;
        }

        return false;
    }

    /**
     * @param array $fields
     *
     * @return bool
     * @throws \NTSchool\Phpblog\Core\Exceptions\ValidateException
     */
    public function login(array $fields)
    {
        $this->validation->execute($fields);
        if(!$this->validation->success()) {
            throw new ValidateException($this->validation->errors());
        }

        $user = $this->getByLogin($fields['login']);

        if(!$user) {
            throw new ValidateException(['login' => 'Такого пользователя не существует!']);
        }

        if($this->getHash($fields['pass']) !== $user['pass']) {
            throw new ValidateException(['pass' => 'Введен неверный пароль!']);
        }

        if(isset($fields['remember'])) {
            Cookie::set('login', $fields['login'], 3600 * 24 * 7);
            Cookie::set('pass', $this->getHash($fields['pass']), 3600 * 24 * 7);
        }

        $token = $this->generateSid();
        $mSession = new Sessions();
        $mSession->openSession($user['id_user'], $token);

        if(isset($_SESSION['returnUrl'])) {
            header('Location:' . $_SESSION['returnUrl']);
            unset($_SESSION['returnUrl']);
        }else {
            header('Location: ' . ROOT);
        }

        return true;
    }

    /**
     * @param \NTSchool\Phpblog\Core\Http\Request $request
     * @param \NTSchool\Phpblog\Model\Sessions $sessions
     * @param \NTSchool\Phpblog\Core\Http\Session $session
     * @param \NTSchool\Phpblog\Core\User $user
     *
     * @return bool
     */
    public function isAuth(Request $request, Sessions $sessions, Session $session, User &$user)
    {
        if($user->getCurrent()) {
            return true;
        }

        $sid = $session->collection()->get('sid');
        $login = $request->cookie()->get('login');

        if(!$sid && !$login) {
            return false;
        }

        if($sid) {
            $user->setCurrent($this->getBySid($sid));

            if($user->getCurrent()) {
                $sessions->edit($user->getCurrent()['id_session'], ['time_last' => date("Y-m-d H:i:s")]);
                return true;
            }
        }else {
            if($login) {
                $cUser = $this->getByLogin($login);
                if($cUser) {
                    $token = $this->generateSid();
                    $sessions->openSession($cUser['id_user'], $token);
                    return true;
                }
            }else {
                return false;
            }

            return false;
        }

        return false;
    }

    /**
     * @param $login
     *
     * @return null
     */
    public function getByLogin($login)
    {
        $query = $this->db->select("SELECT * FROM {$this->table} WHERE login= :login", ['login' => $login]);
        return $query[0] ?? null;
    }

    /**
     * @param $sid
     *
     * @return null
     */
    public function getBySid($sid)
    {
        $query = $this->db->select("SELECT * FROM {$this->table} JOIN sessions USING($this->pk) WHERE sid=:sid", ['sid' => $sid]);
        return $query[0] ?? null;
    }

    /**
     * @param $pass
     *
     * @return string
     */
    public function getHash($pass)
    {
        return hash('sha256', $pass . getenv('SALT'));
    }

    /**
     * @param int $number
     *
     * @return string
     */
    private function generateSid($number = 10)
    {
        $pattern = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        for($i = 0; $i < $number; $i++) {
            $code .= $pattern[mt_rand(0, strlen($pattern) - 1)];
        }
        return $code;
    }

    /**
     *
     */
    public function logout()
    {
        $session = Session::instance();
        $session->remove('sid');
        Cookie::del('login');
        Cookie::del('pass');
    }
}