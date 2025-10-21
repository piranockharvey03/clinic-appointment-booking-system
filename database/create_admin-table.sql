-- Create admin table (for administrators)
CREATE TABLE IF NOT EXISTS `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- add admin account in the admin table
INSERT INTO `admin` (`full_name`, `email`, `password`)
VALUES ('Admin', 'admin@.com', '$2y$12$NZMY5ff1cOYntTre7ReZie.FBpj6QGhlsgx6ds0rg9MfaQo/YlWai');
