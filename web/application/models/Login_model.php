<?php

namespace Model;

use App;
use Exception;
use System\Core\CI_Model;

class Login_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();

    }

    public static function logout()
    {
        App::get_ci()->session->unset_userdata('id');
    }

    /**
     * @return User_model
     * @throws Exception
     */
    public static function login(): User_model
    {
        $user = User_model::find_user_by_email(App::get_ci()->input->post('login'));
        if ($user->is_loaded() && App::get_ci()->input->post('password') === $user->get_password()) {
            self::start_session($user->get_id());
        } else {
            throw new Exception('These credentials do not match our records.');
        }
        return $user;
    }

    public static function start_session(int $user_id)
    {
        // если перенедан пользователь
        if (empty($user_id))
        {
            throw new Exception('No id provided!');
        }

        App::get_ci()->session->set_userdata('id', $user_id);
    }
}
