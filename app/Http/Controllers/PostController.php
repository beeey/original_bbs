<?php

namespace App\Http\Controllers;

use App\Http\Session;
use App\Models\Post;

class PostController
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * インスタンス作成
     *
     * @param Session $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
        //var_dump($_SESSION['token']);
        //exit;
    }

    /**
     * 一覧表示
     *
     * @param array $request $_REQUEST を渡す（値渡し）
     */
    public function index(array $request)
    {
        [$name, $comment] = $this->get_name_comment();
        [$success, $error] = $this->get_results();
        $token = $this->session->token();
        $posts = $this->get_posts();
        include __DIR__ . '/../../../resources/views/index.php';
    }

    /**
     * 新規作成
     *
     * @param array $request $_REQUEST を渡す（値渡し）
     */
    public function store(array $request)
    {
        try {
            $this->memoize_inputs($request);

            //投稿不備検証
            $this->validate_name($request);

            $this->validate_comment($request);

            $this->session->validate_token($request);

            (new Post())->insert_post();

            $this->session->set('success', 'Upload done!');

            $this->clear_inputs();
        } catch (\Exception $e) {
            $this->session->set('error', $e->getMessage());
        }

        //redirect
        header('Location: /');
        exit;
    }

    private function memoize_inputs($request)
    {
        $this->session->set('name', $request['name'] ?? null);
        $this->session->set('comment', $request['comment'] ?? null);
    }

    private function clear_inputs()
    {
        $this->session->unset('name');
        $this->session->unset('comment');
    }

    public function get_results()
    {
        return [
            $this->session->flash('success'),
            $this->session->flash('error'),
        ];
    }

    public function get_name_comment()
    {
        return [
            $this->session->get('name'),
            $this->session->get('comment'),
        ];
    }

    private function validate_name($request)
    {
        if ((!isset($request['name']) || $request['name'] === '') && (!isset($request['comment']) || $request['comment'] === '')) {
            throw new \Exception('何も入力されていません。');
        }

        if (!isset($request['name']) || $request['name'] === '') {
            throw new \Exception('名前が入力されていません。');
        }

        if (mb_strlen($request['name']) > 10) {
            throw new \Exception('名前は10文字以下にしてください。');
        }
    }

    private function validate_comment($request)
    {
        if (!isset($request['comment']) || $request['comment'] === '') {
            throw new \Exception('本文が入力されていません。');
        }

        if (mb_strlen($request['comment']) > 20) {
            throw new \Exception('投稿は20文字以下にしてください。');
        }
    }

    public function get_posts()
    {

        //データベースから取得
        $pdo = new \PDO(
            'mysql:dbname=testdb;host=localhost;charset=utf8mb4',
            'root',
            '',
            [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]
        );

        $stmt = $pdo->prepare('select name, comment, created from posts order by id DESC');
        $stmt->execute();
        $comments = $stmt->fetchAll();

        return $comments;
    }
}
