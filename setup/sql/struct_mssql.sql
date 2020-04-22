SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO

SET ANSI_PADDING ON
GO

/**********************************************/

DROP FUNCTION IF EXISTS [dbo].[getOidArc];
GO
CREATE FUNCTION [dbo].[getOidArc] (@strList varchar(512), @maxArcLen int, @occurence int)
RETURNS varchar(512) AS
BEGIN 
	DECLARE @intPos int

	DECLARE @cnt int
	SET @cnt = 0

	if SUBSTRING(@strList, 1, 4) <> 'oid:'
	begin
		RETURN ''
	end

	SET @strList = RIGHT(@strList, LEN(@strList)-4)

	WHILE CHARINDEX('.',@strList) > 0
	BEGIN
		SET @intPos=CHARINDEX('.',@strList) 
		SET @cnt = @cnt + 1
		IF @cnt = @occurence
		BEGIN
			SET @strList = LEFT(@strList,@intPos-1)
			RETURN REPLICATE('0', @maxArcLen-len(@strList)) + @strList
		END
		SET @strList = RIGHT(@strList, LEN(@strList)-@intPos)
	END
	IF LEN(@strList) > 0
	BEGIN
		SET @cnt = @cnt + 1
		IF @cnt = @occurence
		BEGIN
			RETURN REPLICATE('0', @maxArcLen-len(@strList)) + @strList
		END
	END

	RETURN REPLICATE('0', @maxArcLen)
END
GO

/**********************************************/

DROP TABLE IF EXISTS [dbo].[config];
CREATE TABLE [dbo].[config](
	[name] [varchar](50) NOT NULL,
	[value] [text] NOT NULL,
	[description] [varchar](255) NULL,
	[protected] [bit] NOT NULL DEFAULT ('0'),
	[visible] [bit] NOT NULL DEFAULT ('0'),
	CONSTRAINT [PK_config] PRIMARY KEY CLUSTERED 
	(
		[name] ASC
	)
)
GO

/**********************************************/

DROP TABLE IF EXISTS [dbo].[asn1id];
CREATE TABLE [dbo].[asn1id](
	[lfd] [int] IDENTITY(1,1) NOT NULL,
	[oid] [varchar](255) NOT NULL,
	[name] [varchar](255) NOT NULL,
	[standardized] [bit] NOT NULL CONSTRAINT [DF__asn1id__standard__21B6055D]  DEFAULT ('0'),
	[well_known] [bit] NOT NULL CONSTRAINT [DF__asn1id__well_kno__22AA2996]  DEFAULT ('0'),
 	CONSTRAINT [PK_asn1id] PRIMARY KEY CLUSTERED 
	(
		[lfd] ASC
	),
	INDEX [IX_asn1id_oid_name] NONCLUSTERED
	(
		[oid] ASC,
		[name] ASC
	)
)
GO

/**********************************************/

DROP TABLE IF EXISTS [dbo].[iri];
CREATE TABLE [dbo].[iri](
	[lfd] [int] IDENTITY(1,1) NOT NULL,
	[oid] [varchar](255) NOT NULL,
	[name] [varchar](255) NOT NULL,
	[longarc] [bit] NOT NULL CONSTRAINT [DF__iri__longarc__24927208]  DEFAULT ('0'),
	[well_known] [bit] NOT NULL CONSTRAINT [DF__iri__well_known__25869641]  DEFAULT ('0'),
	CONSTRAINT [PK_iri] PRIMARY KEY CLUSTERED 
	(
		[lfd] ASC
	),
	INDEX [IX_iri_oid_name] NONCLUSTERED
	(
		[oid] ASC,
		[name] ASC
	)
)
GO

/**********************************************/

DROP TABLE IF EXISTS [dbo].[objects];
CREATE TABLE [dbo].[objects](
	[id] [varchar](255) NOT NULL,
	[parent] [varchar](255) NULL,
	[title] [varchar](255) NOT NULL,
	[description] [text] NOT NULL,
	[ra_email] [varchar](100) NULL,
	[confidential] [bit] NOT NULL,
	[created] [datetime] NULL,
	[updated] [datetime] NULL,
	[comment] [varchar](255) NULL,
	CONSTRAINT [PK_objects] PRIMARY KEY CLUSTERED 
	(
		[id] ASC
	),
	INDEX [IX_objects_parent] NONCLUSTERED 
	(
		[parent] ASC
	),
	INDEX [IX_objects_ra_email] NONCLUSTERED  /* TODO: add to other DBMS structs */
	(
		[ra_email] ASC
	)
)
GO

/**********************************************/

DROP TABLE IF EXISTS [dbo].[ra];
CREATE TABLE [dbo].[ra](
	[ra_id] [int] IDENTITY(1,1) NOT NULL,
	[email] [varchar](100) NOT NULL,
	[ra_name] [varchar](100) NOT NULL,
	[personal_name] [varchar](100) NOT NULL,
	[organization] [varchar](100) NOT NULL,
	[office] [varchar](100) NOT NULL,
	[street] [varchar](100) NOT NULL,
	[zip_town] [varchar](100) NOT NULL,
	[country] [varchar](100) NOT NULL,
	[phone] [varchar](100) NOT NULL,
	[mobile] [varchar](100) NOT NULL,
	[fax] [varchar](100) NOT NULL,
	[privacy] [bit] NOT NULL CONSTRAINT [DF__ra__privacy__29572725]  DEFAULT ('0'),
	[salt] [varchar](100) NOT NULL,
	[authkey] [varchar](100) NOT NULL,
	[registered] [datetime] NULL,
	[updated] [datetime] NULL,
	[last_login] [datetime] NULL,
	CONSTRAINT [PK_ra] PRIMARY KEY CLUSTERED 
	(
		[ra_id] ASC
	),
	CONSTRAINT [IX_ra_email] UNIQUE (
		[email] ASC
	)
)
GO

/**********************************************/

DROP TABLE IF EXISTS [dbo].[log];
CREATE TABLE [dbo].[log](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[unix_ts] [bigint] NOT NULL,
	[addr] [varchar](45) NOT NULL,
	[event] [text] NOT NULL,
	CONSTRAINT [PK_log] PRIMARY KEY CLUSTERED 
	(
		[id] ASC
	)
)
GO

/**********************************************/

DROP TABLE IF EXISTS [dbo].[log_user];
CREATE TABLE [dbo].[log_user](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[log_id] [int] NOT NULL,
	[username] [varchar](255) NOT NULL,
	CONSTRAINT [PK_log_user] PRIMARY KEY CLUSTERED 
	(
		[id] ASC
	),
	INDEX [IX_log_user_log_id] NONCLUSTERED  /* TODO: add to other DBMS structs */
	(
		[log_id] ASC
	),
	INDEX [IX_log_user_username] NONCLUSTERED  /* TODO: add to other DBMS structs */
	(
		[username] ASC
	),
	CONSTRAINT [IX_log_object_log_id_username] UNIQUE  /* TODO: add to other DBMS structs */
	(
		[log_id],
		[username]
	)
)
GO

/**********************************************/

DROP TABLE IF EXISTS [dbo].[log_object];
CREATE TABLE [dbo].[log_object](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[log_id] [int] NOT NULL,
	[object] [varchar](255) NOT NULL,
	CONSTRAINT [PK_log_object] PRIMARY KEY CLUSTERED 
	(
		[id] ASC
	),
	INDEX [IX_log_object_log_id] NONCLUSTERED  /* TODO: add to other DBMS structs */
	(
		[log_id] ASC
	),
	INDEX [IX_log_object_object] NONCLUSTERED  /* TODO: add to other DBMS structs */
	(
		[object] ASC
	),
	CONSTRAINT [IX_log_object_log_id_object] UNIQUE  /* TODO: add to other DBMS structs */
	(
		[log_id],
		[object]
	)
)
GO


/****** Set database version ******/

INSERT INTO [config] (name, description, value, protected, visible) VALUES ('database_version', 'Version of the database tables', '203', '1', '0');

SET ANSI_PADDING OFF
GO
