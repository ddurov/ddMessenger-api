SET
SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET
time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

CREATE
DATABASE IF NOT EXISTS `eviger` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE
`eviger`;

DROP TABLE IF EXISTS `eviger_attempts_auth`;
CREATE TABLE `eviger_attempts_auth`
(
    `id`              int(11) NOT NULL,
    `login`           varchar(20) NOT NULL,
    `time`            bigint(32) NOT NULL,
    `authorizationIp` text        NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `eviger_bans`;
CREATE TABLE `eviger_bans`
(
    `id`        int(11) NOT NULL,
    `eid`       int(11) NOT NULL,
    `type`      int(1) NOT NULL,
    `banTime`   int(20) NOT NULL,
    `unbanTime` int(20) NOT NULL,
    `reason`    text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `eviger_codes_email`;
CREATE TABLE `eviger_codes_email`
(
    `id`          int(11) NOT NULL,
    `code`        text NOT NULL,
    `email`       text NOT NULL,
    `requestTime` int(11) NOT NULL,
    `requestIp`   text NOT NULL,
    `hash`        text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `eviger_deactivated_accounts`;
CREATE TABLE `eviger_deactivated_accounts`
(
    `id`  int(11) NOT NULL,
    `eid` int(14) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `eviger_dialogs`;
CREATE TABLE `eviger_dialogs`
(
    `id`                int(11) NOT NULL,
    `firstId`           int(11) NOT NULL,
    `secondId`          int(11) NOT NULL,
    `lastMessageId`     int(11) NOT NULL,
    `lastMessageSender` int(11) NOT NULL,
    `lastMessage`       text NOT NULL,
    `lastMessageDate`   int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `eviger_longpoll_data`;
CREATE TABLE `eviger_longpoll_data`
(
    `id`              int(11) NOT NULL,
    `personalIdEvent` int(11) NOT NULL,
    `type`            int(11) NOT NULL,
    `fromEid`         int(11) NOT NULL,
    `toEid`           int(11) NOT NULL,
    `dataSerialized`  text NOT NULL,
    `isChecked`       int(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `eviger_messages`;
CREATE TABLE `eviger_messages`
(
    `id`          int(11) NOT NULL,
    `fromId`      int(11) NOT NULL,
    `peerId`      int(11) NOT NULL,
    `messageId`   int(11) NOT NULL,
    `message`     text NOT NULL,
    `messageDate` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `eviger_sessions`;
CREATE TABLE `eviger_sessions`
(
    `id`             int(11) NOT NULL,
    `eid`            int(11) NOT NULL,
    `authTime`       int(11) NOT NULL,
    `authDeviceType` int(11) NOT NULL,
    `authIp`         text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `eviger_tokens`;
CREATE TABLE `eviger_tokens`
(
    `id`    int(11) NOT NULL,
    `eid`   int(11) NOT NULL,
    `token` varchar(77) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `eviger_updates`;
CREATE TABLE `eviger_updates`
(
    `id`        int(11) NOT NULL,
    `version`   varchar(32)   NOT NULL,
    `dl`        varchar(512)  NOT NULL,
    `changelog` varchar(4096) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `eviger_users`;
CREATE TABLE `eviger_users`
(
    `id`               int(11) NOT NULL,
    `login`            text        NOT NULL,
    `passwordHash`     text        NOT NULL,
    `passwordSalt`     text        NOT NULL,
    `username`         text        NOT NULL,
    `email`            text        NOT NULL,
    `lastSeen`         int(1) NOT NULL DEFAULT 1,
    `lastSendedOnline` varchar(64) NOT NULL DEFAULT unix_timestamp(),
    `isAdmin`          int(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


ALTER TABLE `eviger_attempts_auth`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `eviger_bans`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `eviger_codes_email`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `eviger_deactivated_accounts`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `eviger_dialogs`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `eviger_longpoll_data`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `eviger_messages`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `eviger_sessions`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `eviger_tokens`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `eviger_updates`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `eviger_users`
    ADD PRIMARY KEY (`id`);


ALTER TABLE `eviger_attempts_auth`
    MODIFY `id` int (11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `eviger_bans`
    MODIFY `id` int (11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `eviger_codes_email`
    MODIFY `id` int (11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `eviger_deactivated_accounts`
    MODIFY `id` int (11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `eviger_dialogs`
    MODIFY `id` int (11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `eviger_longpoll_data`
    MODIFY `id` int (11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `eviger_messages`
    MODIFY `id` int (11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `eviger_sessions`
    MODIFY `id` int (11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `eviger_tokens`
    MODIFY `id` int (11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `eviger_updates`
    MODIFY `id` int (11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `eviger_users`
    MODIFY `id` int (11) NOT NULL AUTO_INCREMENT;

DELIMITER
$$
DROP
EVENT IF EXISTS `clearAttemptsToAuth`$$
CREATE
DEFINER=`root`@`localhost` EVENT `clearAttemptsToAuth` ON SCHEDULE EVERY 5 SECOND STARTS '2021-01-01 00:00:00' ON COMPLETION NOT PRESERVE ENABLE DO
DELETE
FROM eviger_attempts_auth
WHERE UNIX_TIMESTAMP() > eviger_attempts_auth.time + 300$$

DROP
EVENT IF EXISTS `clearEmailCodes`$$
CREATE
DEFINER=`root`@`localhost` EVENT `clearEmailCodes` ON SCHEDULE EVERY 5 SECOND STARTS '2021-01-01 00:00:00' ON COMPLETION NOT PRESERVE ENABLE DO
DELETE
FROM eviger_codes_email
WHERE UNIX_TIMESTAMP() > eviger_codes_email.requestTime + 3600$$
    DELIMITER;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
