-- Offline password reset via security questions (no email/SMS).
ALTER TABLE users
    ADD COLUMN security_question1 VARCHAR(20) NULL,
    ADD COLUMN security_answer1_hash VARCHAR(255) NULL,
    ADD COLUMN security_question2 VARCHAR(20) NULL,
    ADD COLUMN security_answer2_hash VARCHAR(255) NULL;
