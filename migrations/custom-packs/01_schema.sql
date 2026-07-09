CREATE TABLE user_custom_pack (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(64) NOT NULL,
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY ucp_code_idx (code),
    KEY ucp_user_idx (user_id),
    CONSTRAINT fk_ucp_user FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE user_custom_pack_card (
    id INT AUTO_INCREMENT PRIMARY KEY,
    custom_pack_id INT NOT NULL,
    card_id INT NOT NULL,
    quantity TINYINT UNSIGNED NOT NULL DEFAULT 1,
    UNIQUE KEY ucpc_pack_card_idx (custom_pack_id, card_id),
    CONSTRAINT fk_ucpc_pack FOREIGN KEY (custom_pack_id) REFERENCES user_custom_pack(id) ON DELETE CASCADE,
    CONSTRAINT fk_ucpc_card FOREIGN KEY (card_id) REFERENCES card(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
