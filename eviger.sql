SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `eviger`
--
CREATE DATABASE IF NOT EXISTS `eviger` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `eviger`;

-- --------------------------------------------------------

--
-- Структура таблицы `eviger_attempts_auth`
--

DROP TABLE IF EXISTS `eviger_attempts_auth`;
CREATE TABLE `eviger_attempts_auth` (
  `id` int(11) NOT NULL,
  `login` varchar(20) NOT NULL,
  `time` bigint(32) NOT NULL,
  `auth_ip` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Структура таблицы `eviger_bans`
--

DROP TABLE IF EXISTS `eviger_bans`;
CREATE TABLE `eviger_bans` (
  `id` int(11) NOT NULL,
  `eid` int(255) NOT NULL,
  `time_ban` int(20) NOT NULL,
  `time_unban` int(20) NOT NULL,
  `type` int(1) NOT NULL,
  `reason` varchar(2048) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Структура таблицы `eviger_codes_email`
--

DROP TABLE IF EXISTS `eviger_codes_email`;
CREATE TABLE `eviger_codes_email` (
  `id` int(11) NOT NULL,
  `code` varchar(16) NOT NULL,
  `email` varchar(2048) NOT NULL,
  `date_request` bigint(32) NOT NULL,
  `ip_request` varchar(32) NOT NULL,
  `hash` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Структура таблицы `eviger_deactivated_accounts`
--

DROP TABLE IF EXISTS `eviger_deactivated_accounts`;
CREATE TABLE `eviger_deactivated_accounts` (
  `id` int(11) NOT NULL,
  `eid` int(14) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Структура таблицы `eviger_dialogs`
--

DROP TABLE IF EXISTS `eviger_dialogs`;
CREATE TABLE `eviger_dialogs` (
  `id` int(11) NOT NULL,
  `peers` varchar(4096) NOT NULL,
  `last_message_sender` int(11) NOT NULL,
  `last_message_id` int(128) NOT NULL,
  `last_message_date` int(128) NOT NULL,
  `last_message` varchar(4096) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Структура таблицы `eviger_messages`
--

DROP TABLE IF EXISTS `eviger_messages`;
CREATE TABLE `eviger_messages` (
  `id` int(11) NOT NULL,
  `local_id_message` int(128) NOT NULL,
  `peers` varchar(4096) NOT NULL,
  `out_id` int(11) NOT NULL,
  `peer_id` int(11) NOT NULL,
  `message` varchar(8096) NOT NULL,
  `date` int(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Структура таблицы `eviger_sessions`
--

DROP TABLE IF EXISTS `eviger_sessions`;
CREATE TABLE `eviger_sessions` (
  `id` int(11) NOT NULL,
  `login` varchar(20) NOT NULL,
  `date_auth` int(11) NOT NULL,
  `session_type_device` int(2) NOT NULL,
  `ip_device` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Структура таблицы `eviger_tokens`
--

DROP TABLE IF EXISTS `eviger_tokens`;
CREATE TABLE `eviger_tokens` (
  `id` int(11) NOT NULL,
  `eid` int(11) NOT NULL,
  `token` varchar(77) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Структура таблицы `eviger_updates`
--

DROP TABLE IF EXISTS `eviger_updates`;
CREATE TABLE `eviger_updates` (
  `id` int(11) NOT NULL,
  `version` varchar(32) NOT NULL,
  `dl` varchar(512) NOT NULL,
  `changelog` varchar(4096) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Структура таблицы `eviger_users`
--

DROP TABLE IF EXISTS `eviger_users`;
CREATE TABLE `eviger_users` (
  `id` int(11) NOT NULL,
  `login` varchar(20) NOT NULL,
  `password_hash` varchar(48) NOT NULL,
  `password_salt` varchar(16) NOT NULL,
  `username` varchar(128) NOT NULL,
  `email` varchar(2048) NOT NULL,
  `lastSeen` int(1) NOT NULL DEFAULT 1,
  `lastSendedOnline` varchar(64) NOT NULL DEFAULT unix_timestamp(),
  `isAdmin` int(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `eviger_attempts_auth`
--
ALTER TABLE `eviger_attempts_auth`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `eviger_bans`
--
ALTER TABLE `eviger_bans`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `eviger_codes_email`
--
ALTER TABLE `eviger_codes_email`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `eviger_deactivated_accounts`
--
ALTER TABLE `eviger_deactivated_accounts`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `eviger_dialogs`
--
ALTER TABLE `eviger_dialogs`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `eviger_messages`
--
ALTER TABLE `eviger_messages`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `eviger_sessions`
--
ALTER TABLE `eviger_sessions`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `eviger_tokens`
--
ALTER TABLE `eviger_tokens`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `eviger_updates`
--
ALTER TABLE `eviger_updates`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `eviger_users`
--
ALTER TABLE `eviger_users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `eviger_attempts_auth`
--
ALTER TABLE `eviger_attempts_auth`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `eviger_bans`
--
ALTER TABLE `eviger_bans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `eviger_codes_email`
--
ALTER TABLE `eviger_codes_email`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `eviger_deactivated_accounts`
--
ALTER TABLE `eviger_deactivated_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `eviger_dialogs`
--
ALTER TABLE `eviger_dialogs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `eviger_messages`
--
ALTER TABLE `eviger_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `eviger_sessions`
--
ALTER TABLE `eviger_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `eviger_tokens`
--
ALTER TABLE `eviger_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `eviger_updates`
--
ALTER TABLE `eviger_updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `eviger_users`
--
ALTER TABLE `eviger_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

DELIMITER $$
--
-- События
--
DROP EVENT IF EXISTS `clearAttemptsToAuth`$$
CREATE DEFINER=`root`@`localhost` EVENT `clearAttemptsToAuth` ON SCHEDULE EVERY 5 SECOND STARTS '2021-01-01 00:00:00' ON COMPLETION NOT PRESERVE ENABLE DO DELETE FROM eviger_attempts_auth WHERE UNIX_TIMESTAMP() > eviger_attempts_auth.time + 300$$

DROP EVENT IF EXISTS `clearEmailCodes`$$
CREATE DEFINER=`root`@`localhost` EVENT `clearEmailCodes` ON SCHEDULE EVERY 5 SECOND STARTS '2021-01-01 00:00:00' ON COMPLETION NOT PRESERVE ENABLE DO DELETE FROM eviger_codes_email WHERE UNIX_TIMESTAMP() > eviger_codes_email.date_request + 3600$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
