CREATE TABLE IF NOT EXISTS `user` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL CHECK(id >= 0),
    `email` TEXT(191) DEFAULT NULL,
    `password` TEXT(191) DEFAULT NULL,
    `is_active` INTEGER DEFAULT 0 CHECK(is_active >= 0),
    `is_admin` INTEGER DEFAULT 0 CHECK(is_admin >= 0)
)
--; DEFAULT CHARSET=utf8;