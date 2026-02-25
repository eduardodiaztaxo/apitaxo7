CREATE TABLE user_interactions (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NULL,
  `interaction_type` VARCHAR(255) NOT NULL,
  `etiqueta` VARCHAR(255) NULL,
  `id_activo` BIGINT UNSIGNED NULL,
  `id_ciclo` BIGINT UNSIGNED NULL,
  `client_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_user_interaction` (`user_id`, `interaction_type`)
)
