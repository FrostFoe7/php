-- Seed data for initial setup

-- Insert User
SET @user_id = UUID();

INSERT INTO `users` (`id`, `email`, `password`, `name`, `created_at`)
VALUES (@user_id, 'frostfoe@gmail.com', '12345678', 'FrostFoe', NOW());

-- Insert API Token
INSERT INTO `api_tokens` (`id`, `user_id`, `token`, `name`, `created_at`, `is_active`)
VALUES (UUID(), @user_id, 'ff1337', 'Seed Token', NOW(), 1);
