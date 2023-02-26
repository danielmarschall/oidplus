DROP TABLE IF EXISTS `config`;
CREATE TABLE `config` (
  `name` TEXT NOT NULL,
  `value` TEXT NOT NULL,
  `description` TEXT,
  `protected` INTEGER NOT NULL DEFAULT 0,
  `visible` INTEGER NOT NULL DEFAULT 0,
  PRIMARY KEY (`name`)
);

DROP TABLE IF EXISTS `asn1id`;
CREATE TABLE `asn1id` (
  `lfd` INTEGER PRIMARY KEY AUTOINCREMENT,
  `oid` TEXT NOT NULL REFERENCES `objects`(`id`),
  `name` TEXT NOT NULL,
  `standardized` INTEGER NOT NULL DEFAULT 0,
  `well_known` INTEGER NOT NULL DEFAULT 0,
  UNIQUE (`oid`,`name`)
);

DROP TABLE IF EXISTS `iri`;
CREATE TABLE `iri` (
  `lfd` INTEGER PRIMARY KEY AUTOINCREMENT,
  `oid` TEXT NOT NULL REFERENCES `objects`(`id`),
  `name` TEXT NOT NULL,
  `longarc` INTEGER NOT NULL DEFAULT 0,
  `well_known` INTEGER NOT NULL DEFAULT 0,
  UNIQUE (`oid`,`name`)
);

DROP TABLE IF EXISTS `objects`;
CREATE TABLE `objects` (
  `id` TEXT NOT NULL,
  `parent` TEXT DEFAULT NULL REFERENCES `objects`(`id`),
  `title` TEXT NULL,
  `description` TEXT NULL,
  `ra_email` TEXT NULL REFERENCES `ra`(`email`),
  `confidential` boolean NOT NULL,
  `created` TEXT, -- DateTime
  `updated` TEXT, -- DateTime
  `comment` TEXT NULL,
  PRIMARY KEY (`id`)
);

DROP TABLE IF EXISTS `ra`;
CREATE TABLE `ra` (
  `ra_id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `email` TEXT NOT NULL UNIQUE,
  `ra_name` TEXT NULL,
  `personal_name` TEXT NULL,
  `organization` TEXT NULL,
  `office` TEXT NULL,
  `street` TEXT NULL,
  `zip_town` TEXT NULL,
  `country` TEXT NULL,
  `phone` TEXT NULL,
  `mobile` TEXT NULL,
  `fax` TEXT NULL,
  `privacy` INTEGER NOT NULL DEFAULT 0,
  `authkey` TEXT NULL,
  `registered` TEXT, -- DateTime
  `updated` TEXT, -- DateTime
  `last_login` datetime
);

DROP TABLE IF EXISTS `log`;
CREATE TABLE `log` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `unix_ts` INTEGER NOT NULL,
  `addr` TEXT NOT NULL,
  `event` TEXT NOT NULL
);

DROP TABLE IF EXISTS `log_user`;
CREATE TABLE `log_user` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `log_id` INTEGER NOT NULL REFERENCES `log`(`id`),
  `username` TEXT NOT NULL,
  `severity` INTEGER NOT NULL,
  UNIQUE (`log_id`,`username`)
);

DROP TABLE IF EXISTS `log_object`;
CREATE TABLE `log_object` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `log_id` INTEGER NOT NULL REFERENCES `log`(`id`),
  `object` TEXT NOT NULL,
  `severity` INTEGER NOT NULL,
  UNIQUE (`log_id`,`object`)
);

INSERT INTO `config` (name, description, value, protected, visible) VALUES ('database_version', 'Version of the database tables', '1002', '1', '0');
