CREATE TABLE `config` (
  `name` varchar(50) NOT NULL,
  `value` varchar(255) NOT NULL,
  `description` varchar(255),
  `protected` bit(1) NOT NULL DEFAULT b'0',
  `visible` bit(1) NOT NULL DEFAULT b'0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `asn1id` (
  `lfd` int(11) NOT NULL,
  `oid` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `standardized` bit(1) NOT NULL DEFAULT b'0',
  `well_known` bit default 0 NOT NULL DEFAULT b'0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `iri` (
  `lfd` int(11) NOT NULL,
  `oid` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `longarc` bit default 0 NOT NULL DEFAULT b'0',
  `well_known` bit default 0 NOT NULL DEFAULT b'0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `objects` (
  `id` varchar(255) NOT NULL,
  `parent` varchar(255) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `ra_email` varchar(100) NOT NULL,
  `confidential` bit default 0 NOT NULL,
  `created` datetime,
  `updated` datetime
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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

ALTER TABLE `config`
  ADD PRIMARY KEY (`name`);

ALTER TABLE `asn1id`
  ADD PRIMARY KEY (`lfd`),
  ADD UNIQUE KEY `oid` (`oid`,`name`);

ALTER TABLE `iri`
  ADD PRIMARY KEY (`lfd`),
  ADD UNIQUE KEY `oid` (`oid`,`name`);

ALTER TABLE `objects`
  ADD PRIMARY KEY (`id`) USING BTREE;

ALTER TABLE `ra`
  ADD PRIMARY KEY (`ra_id`),
  ADD UNIQUE KEY `email` (`email`);

ALTER TABLE `asn1id`
  MODIFY `lfd` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `iri`
  MODIFY `lfd` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `ra`
  MODIFY `ra_id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

