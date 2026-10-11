-- Phase 1 cash-on-claim payment tracking.
ALTER TABLE requests
    ADD COLUMN amount_due DECIMAL(10,2) NOT NULL DEFAULT 0;

-- Backfill from current document fees (all seed fees are 0, so this is a no-op today).
UPDATE requests q
    JOIN document_types d ON d.document_id = q.document_id
    SET q.amount_due = d.fee;

CREATE TABLE IF NOT EXISTS payments (
    payment_id  INT AUTO_INCREMENT PRIMARY KEY,
    request_id  INT NOT NULL UNIQUE,
    amount      DECIMAL(10,2) NOT NULL,
    method      ENUM('cash') NOT NULL DEFAULT 'cash',
    or_number   VARCHAR(50) NOT NULL,
    received_by INT NULL,
    paid_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_payments_request
        FOREIGN KEY (request_id) REFERENCES requests(request_id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT fk_payments_user
        FOREIGN KEY (received_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
);

CREATE INDEX idx_payments_paid_at ON payments(paid_at);
