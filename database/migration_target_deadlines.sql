-- Create target_deadlines table for MOV deadlines
CREATE TABLE IF NOT EXISTS `target_deadlines` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `target_id` INT NOT NULL,
  `deadline` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`target_id`) REFERENCES `task_list`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
