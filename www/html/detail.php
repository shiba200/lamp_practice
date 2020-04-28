<?php
require_once '../conf/const.php';
require_once MODEL_PATH . 'functions.php';
require_once MODEL_PATH . 'user.php';
require_once MODEL_PATH . 'item.php';
require_once MODEL_PATH . 'cart.php';

session_start();

if(is_logined() === false){
  redirect_to(LOGIN_URL);
}

$db = get_db_connect();
$user = get_login_user($db);
$history_id = get_get("id");
$total = get_get("total");
$date = get_get("date");

if($user['type'] === USER_TYPE_ADMIN){
  $details = get_detail($db, $history_id);
} else {
  $details  = get_detail($db, $history_id, $user['user_id']);
}

include_once VIEW_PATH . 'detail_view.php';