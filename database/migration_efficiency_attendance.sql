-- Create efficiency_attendance table for tracking activity attendance
CREATE TABLE IF NOT EXISTS `efficiency_attendance` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `faculty_id` INT NOT NULL,
  `target_id` INT NOT NULL,
  `rating_period` VARCHAR(50) NOT NULL,
  `activity_title` VARCHAR(255) NOT NULL,
  `date_conducted` DATE NOT NULL,
  `percentage` DECIMAL(5,2) NOT NULL,
  `rating` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`faculty_id`) REFERENCES `employee_list`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`target_id`) REFERENCES `task_list`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
