<?php 
require_once MODEL_PATH . 'functions.php';
require_once MODEL_PATH . 'db.php';

function get_user_carts($db, $user_id){
  $sql = "
    SELECT
      items.item_id,
      items.name,
      items.price,
      items.stock,
      items.status,
      items.image,
      carts.cart_id,
      carts.user_id,
      carts.amount
    FROM
      carts
    JOIN
      items
    ON
      carts.item_id = items.item_id
    WHERE
      carts.user_id = ?
  ";
  return fetch_all_query($db, $sql, [$user_id]);
}

function get_user_cart($db, $user_id, $item_id){
  $sql = "
    SELECT
      items.item_id,
      items.name,
      items.price,
      items.stock,
      items.status,
      items.image,
      carts.cart_id,
      carts.user_id,
      carts.amount
    FROM
      carts
    JOIN
      items
    ON
      carts.item_id = items.item_id
    WHERE
      carts.user_id = ?
    AND
      items.item_id = ?
  ";

  return fetch_query($db, $sql, [$user_id, $item_id]);

}

function add_cart($db, $user_id, $item_id ) {
  $cart = get_user_cart($db, $user_id, $item_id);
  if($cart === false){
    return insert_cart($db, $user_id, $item_id);
  }
  return update_cart_amount($db, $cart['cart_id'], $cart['amount'] + 1);
}

function insert_cart($db, $user_id, $item_id, $amount = 1){
  $sql = "
    INSERT INTO
      carts(
        item_id,
        user_id,
        amount
      )
    VALUES(?, ?, ?)
  ";

  return execute_query($db, $sql, [$item_id, $user_id, $amount]);
}

function update_cart_amount($db, $cart_id, $amount){
  $sql = "
    UPDATE
      carts
    SET
      amount = ?
    WHERE
      cart_id = ?
    LIMIT 1
  ";
  return execute_query($db, $sql, [$amount, $cart_id]);
}

function delete_cart($db, $cart_id){
  $sql = "
    DELETE FROM
      carts
    WHERE
      cart_id = ?
    LIMIT 1
  ";

  return execute_query($db, $sql, [$cart_id]);
}

function purchase_carts($db, $carts){
  if(validate_cart_purchase($carts) === false){
    return false;
  }
  $db->beginTransaction();
  foreach($carts as $cart){
    if(update_item_stock(
        $db, 
        $cart['item_id'], 
        $cart['stock'] - $cart['amount']
      ) === false){
      set_error($cart['name'] . 'の購入に失敗しました。');
      }
  }
  
  delete_user_carts($db, $carts[0]['user_id']);
  createHistory($db, $carts);

  if (has_error()){
    $db->rollback();
  } else {
    $db->commit();
  }
}

function delete_user_carts($db, $user_id){
  $sql = "
    DELETE FROM
      carts
    WHERE
      user_id = ?
  ";

  execute_query($db, $sql, [$user_id]);
}


function sum_carts($carts){
  $total_price = 0;
  foreach($carts as $cart){
    $total_price += $cart['price'] * $cart['amount'];
  }
  return $total_price;
}

function validate_cart_purchase($carts){
  if(count($carts) === 0){
    set_error('カートに商品が入っていません。');
    return false;
  }
  foreach($carts as $cart){
    if(is_open($cart) === false){
      set_error($cart['name'] . 'は現在購入できません。');
    }
    if($cart['stock'] - $cart['amount'] < 0){
      set_error($cart['name'] . 'は在庫が足りません。購入可能数:' . $cart['stock']);
    }
  }
  if(has_error() === true){
    return false;
  }
  return true;
}

function insert_history($db, $user_id, $total){
  $sql = "
    INSERT INTO
      history(
        user_id,
        total
      )
    VALUES(?, ?)
  ";

  return execute_query($db, $sql, [$user_id, $total]);
}

function insert_detail($db, $history_id, $item_id, $price, $amount){
  $sql = "
    INSERT INTO
      detail(
        history_id,
        item_id,
        price,
        amount
      )
    VALUES(?, ?, ?, ?)
  ";

  return execute_query($db, $sql, [$history_id, $item_id, $price, $amount]);
}

function createHistory($db, $carts){
  $total = sum_carts($carts);
  if (insert_history($db, $carts[0]['user_id'], $total) === true){
    $id = $db->lastInsertId();
    foreach($carts as $cart){
      if (insert_detail($db, $id, $cart['item_id'], $cart['price'], $cart['amount'])===false){
        set_error($cart['name'] . 'の購入明細の作成に失敗しました');
      }
    }
  } else {
    set_error('購入履歴の作成に失敗しました');
  }
  if (has_error()){
    return false;
  } else {
    return true;
  }
}

function get_history($db, $user_id = ''){
  $param=[];
  $sql = "
    SELECT
      *
    FROM
      history
  ";
  if($user_id !== ''){
    $sql .= "where user_id=?";
    $param[]=$user_id;
  }
  $sql .= " order by created desc";
  return fetch_all_query($db, $sql, $param);
}

function get_detail($db, $history_id, $user_id=""){
  $param = [$history_id];
  $sql = "
    SELECT
      items.item_id,
      items.name,
      detail.price,
      detail.amount
    FROM
      items
    JOIN
      detail
    ON
      items.item_id = detail.item_id
    JOIN
      history
    ON
     detail.history_id = history.history_id
    where
      detail.history_id = ?
  ";
  if($user_id !== ''){
    $sql .= " and user_id=?";
    $param[]=$user_id;
  }
  return fetch_all_query($db, $sql, $param);
}