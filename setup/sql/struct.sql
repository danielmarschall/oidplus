DROP TABLE IF EXISTS `config`;
CREATE TABLE `config` (
  `name` varchar(50) NOT NULL,
  `value` text NOT NULL,
  `description` varchar(255),
  `protected` bit(1) NOT NULL DEFAULT b'0',
  `visible` bit(1) NOT NULL DEFAULT b'0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `asn1id`;
CREATE TABLE `asn1id` (
  `lfd` int(11) NOT NULL,
  `oid` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `standardized` bit(1) NOT NULL DEFAULT b'0',
  `well_known` bit default 0 NOT NULL DEFAULT b'0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `iri`;
CREATE TABLE `iri` (
  `lfd` int(11) NOT NULL,
  `oid` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `longarc` bit default 0 NOT NULL DEFAULT b'0',
  `well_known` bit default 0 NOT NULL DEFAULT b'0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `objects`;
CREATE TABLE `objects` (
  `id` varchar(255) NOT NULL,
  `parent` varchar(255) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `ra_email` varchar(100) NULL,
  `confidential` bit default 0 NOT NULL,
  `created` datetime,
  `updated` datetime
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `ra`;
CREATE TABLE `ra` (
  `ra_id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `ra_name` varchar(100) NOT NULL,
  `personal_name` varchar(100) NOT NULL,
  `organization` varchar(100) NOT NULL,
  `office` varchar(100) NOT NULL,
  `street` varchar(100) NOT NULL,
  `zip_town` varchar(100) NOT NULL,
  `country` varchar(100) NOT NULL,
  `phone` varchar(100) NOT NULL,
  `mobile` varchar(100) NOT NULL,
  `fax` varchar(100) NOT NULL,
  `privacy` bit(1) NOT NULL DEFAULT b'0',
  `salt` varchar(100) NOT NULL,
  `authkey` varchar(100) NOT NULL,
  `registered` datetime,
  `updated` datetime,
  `last_login` datetime
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `log`;
CREATE TABLE `log` (
  `id` int(11) NOT NULL,
  `unix_ts` bigint NOT NULL,
  `addr` varchar(45) NOT NULL,
  `event` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `log_user`;
CREATE TABLE `log_user` (
  `id` int(11) NOT NULL,
  `log_id` int(11) NOT NULL,
  `user` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `log_object`;
CREATE TABLE `log_object` (
  `id` int(11) NOT NULL,
  `log_id` int(11) NOT NULL,
  `object` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `config`
  ADD PRIMARY KEY (`name`);

ALTER TABLE `asn1id`
  ADD PRIMARY KEY (`lfd`),
  ADD UNIQUE KEY `oid` (`oid`,`name`);
ALTER TABLE `asn1id`
  MODIFY `lfd` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `iri`
  ADD PRIMARY KEY (`lfd`),
  ADD UNIQUE KEY `oid` (`oid`,`name`);
ALTER TABLE `iri`
  MODIFY `lfd` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `objects`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD INDEX `parent` (`parent`);

ALTER TABLE `ra`
  ADD PRIMARY KEY (`ra_id`),
  ADD UNIQUE KEY `email` (`email`);
ALTER TABLE `ra`
  MODIFY `ra_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `log`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `log_user`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `log_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `log_object`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `log_object`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

INSERT INTO `config` (name, description, value, protected, visible) VALUES ('database_version', 'Version of the database tables', '200', 1, 0);
