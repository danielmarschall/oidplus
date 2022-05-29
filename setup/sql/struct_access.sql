
CREATE TABLE [config](
	[name] varchar(50) NOT NULL,
	[value] text NOT NULL,
	[description] varchar(255) null,
	[protected] bit NOT NULL,
	[visible] bit NOT NULL,
	primary key
	(
		[name]
	)
);

CREATE TABLE [asn1id](
	[lfd] AUTOINCREMENT,
	[oid] varchar(255) NOT NULL,
	[name] varchar(255) NOT NULL,
	[standardized] bit NOT NULL,
	[well_known] bit NOT NULL,
 	primary key
	(
		[lfd]
	)
);

CREATE TABLE [iri](
	[lfd] AUTOINCREMENT,
	[oid] varchar(255) NOT NULL,
	[name] varchar(255) NOT NULL,
	[longarc] bit NOT NULL,
	[well_known] bit NOT NULL,
	primary key
	(
		[lfd]
	)
);

CREATE TABLE [objects](
	[id] varchar(255) NOT NULL,
	[parent] varchar(255) NULL,
	[title] varchar(255) NULL,
	[description] text NULL,
	[ra_email] varchar(100) NULL,
	[confidential] bit NOT NULL,
	[created] datetime null,
	[updated] datetime null,
	[comment] varchar(255) null,
	primary key
	(
		[id]
	)
);

CREATE TABLE [ra](
	[ra_id] AUTOINCREMENT,
	[email] varchar(100) NOT NULL,
	[ra_name] varchar(100) NULL,
	[personal_name] varchar(100) NULL,
	[organization] varchar(100) NULL,
	[office] varchar(100) NULL,
	[street] varchar(100) NULL,
	[zip_town] varchar(100) NULL,
	[country] varchar(100) NULL,
	[phone] varchar(100) NULL,
	[mobile] varchar(100) NULL,
	[fax] varchar(100) NULL,
	[privacy] bit NOT NULL,
	[salt] varchar(100) NULL,
	[authkey] varchar(100) NULL,
	[registered] datetime NULL,
	[updated] datetime NULL,
	[last_login] datetime NULL,
	primary key
	(
		[ra_id]
	),
	CONSTRAINT [IX_ra_email] UNIQUE (
		[email]
	)
);

CREATE TABLE [log](
	[id] AUTOINCREMENT,
	[unix_ts] long NOT NULL,
	[addr] varchar(45) NOT NULL,
	[event] text NOT NULL,
	primary key
	(
		[id]
	)
);

CREATE TABLE [log_user](
	[id] AUTOINCREMENT,
	[log_id] integer NOT NULL,
	[username] varchar(255) NOT NULL,
	[severity] integer NOT NULL,
	primary key
	(
		[id]
	),
	CONSTRAINT [IX_log_user_log_id_username] UNIQUE
	(
		[log_id],
		[username]
	)
);

CREATE TABLE [log_object](
	[id] AUTOINCREMENT,
	[log_id] integer NOT NULL,
	[object] varchar(255) NOT NULL,
	[severity] integer NOT NULL,
	primary key
	(
		[id]
	),
	CONSTRAINT [IX_log_object_log_id_object] UNIQUE
	(
		[log_id],
		[object]
	)
);

INSERT INTO [config] ([name], [description], [value], [protected], [visible]) VALUES ('database_version', 'Version of the database tables', '1001', '1', '0');
