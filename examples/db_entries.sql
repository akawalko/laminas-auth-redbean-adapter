--; EMAIL                           PASSWORD            ADMIN ACCOUNT
--; kira-shanahan@yahoo.com         A,0p3QC!0_          NO
--; santino-kertzmann@hotmail.com   8AMQCcO?4~`1        NO
--; dorian-conn@yahoo.com           f;qc35lyp0wAd-      NO
--; dorian-conn@yahoo.com           277hpQ#5G"Md!       YES
--; chesley-o-conner@yahoo.com      l=*EH{h{+b          YES
--; rory-bergnaum@gmail.com         rLVJ/*9MShgWfV      NO


INSERT INTO user (email, password, is_active, is_admin) VALUES
    ('kira-shanahan@yahoo.com', '$2y$10$CwKgjqgHsM8DMQALfZCvIuo282a8VGoaA2h3V5FuVr89dhJPvsBlm', 1, 0),
    ('santino-kertzmann@hotmail.com', '$2y$10$XnrWhkFYk87VtRjGDsnjX.RqtXV6Fei5rx..Qnoj5YOBz5pLjEZM.', 0, 0),
    ('dorian-conn@yahoo.com', '$2y$10$I.Foc28FcPidi.taAVPyKuUztJHd/9RR9Wl564wUOWgH1gCzGMngK', 1, 0),
    ('dorian-conn@yahoo.com', '$2y$10$.u0dDLDcM9HK7hqEox8IAOzdz/kfzMxUO8Z5Q5OX/RYDPwTPA2G3W', 1, 1),
    ('chesley-o-conner@yahoo.com', '$2y$10$3IYdr0b9N5FfMZZi1hbzQ.HyXVIj2zkTD3TO9mLxO52OYraSqGDFe', 1, 1),
    ('rory-bergnaum@gmail.com', '$2y$10$BCw9L0sStNARsA0yaM/1GuJYJeVWfvVd9bnRtBxJaT4l2ZdmZzyY.', 1, 0);
