<?php

use Model\Analytics_model;
use Model\Boosterpack_model;
use Model\Post_model;
use Model\Transaction_info;
use Model\Transaction_type;
use Model\User_model;

/**
 * Created by PhpStorm.
 * User: mr.incognito
 * Date: 10.11.2018
 * Time: 21:36
 */
class Main_page extends MY_Controller
{

    public function __construct()
    {

        parent::__construct();

        if (is_prod())
        {
            die('In production it will be hard to debug! Run as development environment!');
        }
    }

    public function index()
    {
        $user = User_model::get_user();

        App::get_ci()->load->view('main_page', ['user' => User_model::preparation($user, 'default')]);
    }

    public function get_all_posts()
    {
        $posts =  Post_model::preparation_many(Post_model::get_all(), 'default');
        return $this->response_success(['posts' => $posts]);
    }

    public function get_boosterpacks()
    {
        $posts =  Boosterpack_model::preparation_many(Boosterpack_model::get_all(), 'default');
        return $this->response_success(['boosterpacks' => $posts]);
    }

    public function login()
    {
        $this->load->helper(array('form', 'url'));
        $this->load->library('form_validation');
        $this->form_validation->set_rules('login', 'E-mail', 'required');
        $this->form_validation->set_rules('password', 'Password', 'required',
            array('required' => 'You must provide a %s.')
        );
        if ($this->form_validation->run() == FALSE)
        {
            return $this->response_error($this->form_validation->error_array()[0] ?? 'Validation Error',
                ['errors' => $this->form_validation->error_array()], 422);
        }
        else
        {
            try {
                $user = \Model\Login_model::login();
            } catch (Exception $exception) {
                return $this->response_error($exception->getMessage(),
                    [], 422);
            }
        }
        return $this->response_success(['user' => User_model::preparation($user, 'default')]);
    }

    public function logout()
    {
        if ( ! User_model::is_logged())
        {
            return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_NEED_AUTH);
        }
        \Model\Login_model::logout();
        return redirect('/');
    }

    public function comment()
    {
        if ( ! User_model::is_logged())
        {
            return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_NEED_AUTH);
        }
        $this->load->helper(array('form', 'url'));
        $this->load->library('form_validation');
        $this->form_validation->set_rules('postId', 'Post Id', 'required');
        $this->form_validation->set_rules('commentText', 'Comment Text', 'required');
        if ($this->form_validation->run() == FALSE)
        {
            return $this->response_error($this->form_validation->error_array()[0] ?? 'Validation Error',
                ['errors' => $this->form_validation->error_array()], 422);
        }
        else
        {
            $data = [
                'user_id' => User_model::get_user()->get_id(),
                'assign_id' => $this->input->post('postId'),
                'text' => $this->input->post('commentText'),
                'likes' => 0,
            ];
            if ($this->input->post('replyId')) {
                $data['reply_id'] = $this->input->post('replyId');
            }
            $comment = \Model\Comment_model::create($data);
            return $this->response_success(['comment' => \Model\Comment_model::preparation($comment)]);
        }
    }

    public function like_comment(int $comment_id)
    {
        if ( ! User_model::is_logged())
        {
            return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_NEED_AUTH);
        }
        if (User_model::get_user()->get_likes_balance() < 1) {
            return $this->response_error('Insufficient funds');
        }
        $comment = \Model\Comment_model::find_by_id($comment_id);
        if (!$comment->is_loaded()) {
            show_404();
        } else {
            $comment->increment_likes(User_model::get_user());
        }
        return $this->response_success(['likes' => $comment->reload()->get_likes()]);
    }

    public function like_post(int $post_id)
    {
        if ( ! User_model::is_logged())
        {
            return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_NEED_AUTH);
        }
        if (User_model::get_user()->get_likes_balance() < 1) {
            return $this->response_error('Insufficient funds');
        }
        $post = Post_model::find_by_id($post_id);
        if (!$post->is_loaded()) {
            show_404();
        } else {
            $post->increment_likes(User_model::get_user());
        }
        return $this->response_success(['likes' => $post->reload()->get_likes()]);
    }

    public function add_money()
    {
        if ( ! User_model::is_logged())
        {
            return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_NEED_AUTH);
        }
        $sum = (float)App::get_ci()->input->post('sum');
        if ($sum > 0) {
            User_model::get_user()->add_money($sum);

            Analytics_model::create([
                'user_id' => User_model::get_user()->get_id(),
                'object' => Transaction_info::WALLET,
                'action' => Transaction_type::REFILL,
                'amount' => $sum
            ]);

            return $this->response_success();
        } else {
            return $this->response_error('not valid sum');
        }
    }

    public function get_post(int $post_id) {
        $post = Post_model::find_by_id($post_id);
        if (!$post->is_loaded()) {
            show_404();
        } else {
            return $this->response_success(['post' => Post_model::preparation($post, 'full_info')]);
        }
    }

    public function buy_boosterpack()
    {
        // Check user is authorize
        if ( ! User_model::is_logged())
        {
            return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_NEED_AUTH);
        }

        $id = $this->input->post('id');
        $booster_pack = Boosterpack_model::find_by_id($id);
        try {
            $amount = $booster_pack->open();
            return $this->response_success(compact('amount'));
        } catch (Exception $exception) {
            return $this->response_error($exception->getMessage(), [],422);
        }
    }

    public function get_history()
    {
        if ( ! User_model::is_logged())
        {
            return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_NEED_AUTH);
        }
        return $this->response_success(['history' => Analytics_model::preparation_many((new Analytics_model())->get_analytics_for_user(User_model::get_user()->get_id()))]);
    }

    public function get_boosterpack_history()
    {
        if ( ! User_model::is_logged())
        {
            return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_NEED_AUTH);
        }
        return $this->response_success(['booster_history' => (new Analytics_model())->get_boosterpack_history_for_user(User_model::get_user()->get_id()), 'boosterpack_history']);
    }

    public function get_user_totals()
    {
        if ( ! User_model::is_logged())
        {
            return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_NEED_AUTH);
        }
        return $this->response_success(['totals' => User_model::preparation(User_model::get_user(), 'totals')]);
    }

    /**
     * @return object|string|void
     */
    public function get_boosterpack_info(int $bootserpack_info)
    {
        // Check user is authorize
        if ( ! User_model::is_logged())
        {
            return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_NEED_AUTH);
        }


        //TODO получить содержимое бустерпака
    }
}
