<?php
// 定数ファイルの読み込み
require_once '../conf/const.php';
// 汎用関数ファイルの読み込み
require_once '../model/functions.php';
// userデータに関する関数ファイルの読み込み
require_once '../model/user.php';
// itemデータに関する関数ファイルの読み込み
require_once '../model/item.php';

// ログインチェックを行うために、セッションを開始する
session_start();

// ログインチェック用関数を利用
if(is_logined() === false){
  // ログインしてない場合はログインページにリダイレクト
  redirect_to(LOGIN_URL);
}

// PDOを取得
$db = get_db_connect();

// PDOを利用してログインユーザーのデータを取得
$user = get_login_user($db);

// PDOを利用して商品一覧用のデータを取得
$items = get_open_items($db);

$token = get_csrf_token();
// ビューの読み込み
include_once VIEW_PATH . 'index_view.php';