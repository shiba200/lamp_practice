CREATE TABLE 'history'{
    'history_id' int(11) NOT NULL,
    'cart_id' int(11) NOT NULL,
    'user_id' int(11) NOT NULL,
    `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
};

ALTER TABLE 'history'
    ADD PRIMARY KEY ('history_id');

ALTER TABLE 'history'
    MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

CREATE TABLE 'detail'{
    'history_id' int(11) NOT NULL,
    'item_id' int(11) NOT NULL,
    'amount' int(11) NOT NULL,
};