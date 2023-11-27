CREATE TABLE IF NOT EXISTS `user` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `email` varchar(191) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
    `password` varchar(191) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
    `is_active` tinyint(3) unsigned DEFAULT NULL,
    `is_admin` tinyint(3) unsigned DEFAULT NULL,
    `_seeded` tinyint(3) unsigned DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;