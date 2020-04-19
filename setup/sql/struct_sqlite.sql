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
  `oid` TEXT NOT NULL,
  `name` TEXT NOT NULL,
  `standardized` INTEGER NOT NULL DEFAULT 0,
  `well_known` INTEGER NOT NULL DEFAULT 0,
  UNIQUE (`oid`,`name`)
);

DROP TABLE IF EXISTS `iri`;
CREATE TABLE `iri` (
  `lfd` INTEGER PRIMARY KEY AUTOINCREMENT,
  `oid` TEXT NOT NULL,
  `name` TEXT NOT NULL,
  `longarc` INTEGER NOT NULL DEFAULT 0,
  `well_known` INTEGER NOT NULL DEFAULT 0,
  UNIQUE (`oid`,`name`)
);

DROP TABLE IF EXISTS `objects`;
CREATE TABLE `objects` (
  `id` TEXT NOT NULL,
  `parent` TEXT DEFAULT NULL,
  `title` TEXT NOT NULL,
  `description` TEXT NOT NULL,
  `ra_email` TEXT NULL,
  `confidential` boolean NOT NULL,
  `created` TEXT, -- TODO: Datetime
  `updated` TEXT, -- TODO: Datetime
  `comment` TEXT NULL,
  PRIMARY KEY (`id`)
);

DROP TABLE IF EXISTS `ra`;
CREATE TABLE `ra` (
  `ra_id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `email` TEXT NOT NULL UNIQUE,
  `ra_name` TEXT NOT NULL,
  `personal_name` TEXT NOT NULL,
  `organization` TEXT NOT NULL,
  `office` TEXT NOT NULL,
  `street` TEXT NOT NULL,
  `zip_town` TEXT NOT NULL,
  `country` TEXT NOT NULL,
  `phone` TEXT NOT NULL,
  `mobile` TEXT NOT NULL,
  `fax` TEXT NOT NULL,
  `privacy` INTEGER NOT NULL DEFAULT 0,
  `salt` TEXT NOT NULL,
  `authkey` TEXT NOT NULL,
  `registered` TEXT, -- TODO: Datetime
  `updated` TEXT, -- TODO: Datetime
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
  `log_id` INTEGER NOT NULL,
  `username` TEXT NOT NULL
);

DROP TABLE IF EXISTS `log_object`;
CREATE TABLE `log_object` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `log_id` INTEGER NOT NULL,
  `object` TEXT NOT NULL
);

INSERT INTO `config` (name, description, value, protected, visible) VALUES ('database_version', 'Version of the database tables', '203', '1', '0');
