-- Payment-revision audit log for cancelled orders.
-- Each row captures one admin revision: the frozen pre-revision totals,
-- the new revised totals, the admin who did it, and the reason.
CREATE TABLE IF NOT EXISTS disputes (
    id INT(11) NOT NULL AUTO_INCREMENT,
    order_id INT(11) NOT NULL,
    original_total_amount DECIMAL(10, 2) NOT NULL,
    original_shipping_cost DECIMAL(10, 2) NOT NULL,
    original_order_total DECIMAL(10, 2) NOT NULL,
    revised_total_amount DECIMAL(10, 2) NOT NULL,
    revised_shipping_cost DECIMAL(10, 2) NOT NULL,
    revised_order_total DECIMAL(10, 2) NOT NULL,
    reason TEXT NOT NULL,
    admin_id INT(11) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_disputes_order_id (order_id),
    KEY idx_disputes_admin_id (admin_id),
    CONSTRAINT fk_disputes_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_disputes_admin FOREIGN KEY (admin_id) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
