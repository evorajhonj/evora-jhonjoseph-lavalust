<?php

class Create_refresh_tokens_table
{
    public function up()
    {
        lava_instance()->db->raw('CREATE TABLE IF NOT EXISTS refresh_tokens (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            token VARCHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            jti VARCHAR(64) NOT NULL,
            INDEX user_id_idx (user_id),
            INDEX jti_idx (jti)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }

    public function down() { lava_instance()->db->raw('DROP TABLE IF EXISTS refresh_tokens'); }
}
