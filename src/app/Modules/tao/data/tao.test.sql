CREATE TABLE IF NOT EXISTS `demo_article_migrate` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `title` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `demo_article_migrate` (`id`, `user_id`, `title`) VALUES
(1, 1, 'test for db.migrate 中文');

