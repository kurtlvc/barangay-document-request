-- Non-destructive migration for databases created from the earlier schema.sql.
-- Existing users, document types, and requests are preserved.

ALTER TABLE users
    ADD COLUMN contact_number VARCHAR(20) NULL,
    ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1;

ALTER TABLE document_types
    ADD COLUMN fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    ADD COLUMN requirements TEXT NULL,
    ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1;

ALTER TABLE requests
    ADD COLUMN purpose TEXT NULL;
