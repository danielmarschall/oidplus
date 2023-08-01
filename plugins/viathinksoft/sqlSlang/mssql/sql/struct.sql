SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO

SET ANSI_PADDING ON
GO

/**********************************************/

IF OBJECT_ID('dbo.config', 'U') IS NOT NULL /*Backwards compatibility*/
	DROP TABLE /*IF EXISTS*/ [dbo].[config];
CREATE TABLE [dbo].[config](
	[name] [nvarchar](50) NOT NULL,
	[value] [ntext] NOT NULL,
	[description] [nvarchar](255) NULL,
	[protected] [bit] NOT NULL DEFAULT ('0'),
	[visible] [bit] NOT NULL DEFAULT ('0'),
	CONSTRAINT [PK_config] PRIMARY KEY CLUSTERED 
	(
		[name] ASC
	)
)
GO

/**********************************************/

IF OBJECT_ID('dbo.asn1id', 'U') IS NOT NULL /*Backwards compatibility*/
	DROP TABLE /*IF EXISTS*/ [dbo].[asn1id];
CREATE TABLE [dbo].[asn1id](
	[lfd] [int] IDENTITY(1,1) NOT NULL,
	[oid] [nvarchar](255) COLLATE German_PhoneBook_CS_AS NOT NULL,
	[name] [nvarchar](255) COLLATE German_PhoneBook_CS_AS NOT NULL,
	[standardized] [bit] NOT NULL CONSTRAINT [DF__asn1id__standardized]  DEFAULT ('0'),
	[well_known] [bit] NOT NULL CONSTRAINT [DF__asn1id__well_known]  DEFAULT ('0'),
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

IF OBJECT_ID('dbo.iri', 'U') IS NOT NULL /*Backwards compatibility*/
	DROP TABLE /*IF EXISTS*/ [dbo].[iri];
CREATE TABLE [dbo].[iri](
	[lfd] [int] IDENTITY(1,1) NOT NULL,
	[oid] [nvarchar](255) COLLATE German_PhoneBook_CS_AS NOT NULL,
	[name] [nvarchar](255) COLLATE German_PhoneBook_CS_AS NOT NULL,
	[longarc] [bit] NOT NULL CONSTRAINT [DF__iri__longarc]  DEFAULT ('0'),
	[well_known] [bit] NOT NULL CONSTRAINT [DF__iri__well_known]  DEFAULT ('0'),
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

IF OBJECT_ID('dbo.objects', 'U') IS NOT NULL /*Backwards compatibility*/
	DROP TABLE /*IF EXISTS*/ [dbo].[objects];
CREATE TABLE [dbo].[objects](
	[id] [nvarchar](255) COLLATE German_PhoneBook_CS_AS NOT NULL,
	[parent] [nvarchar](255) COLLATE German_PhoneBook_CS_AS NULL,
	[title] [nvarchar](255) NULL,
	[description] [ntext] NULL,
	[ra_email] [nvarchar](100) NULL,
	[confidential] [bit] NOT NULL,
	[created] [datetime] NULL,
	[updated] [datetime] NULL,
	[comment] [nvarchar](255) NULL,
	CONSTRAINT [PK_objects] PRIMARY KEY CLUSTERED 
	(
		[id] ASC
	),
	INDEX [IX_objects_parent] NONCLUSTERED 
	(
		[parent] ASC
	),
	INDEX [IX_objects_ra_email] NONCLUSTERED
	(
		[ra_email] ASC
	)
)
GO

/**********************************************/

IF OBJECT_ID('dbo.ra', 'U') IS NOT NULL /*Backwards compatibility*/
	DROP TABLE /*IF EXISTS*/ [dbo].[ra];
CREATE TABLE [dbo].[ra](
	[ra_id] [int] IDENTITY(1,1) NOT NULL,
	[email] [nvarchar](100) NOT NULL,
	[ra_name] [nvarchar](100) NULL,
	[personal_name] [nvarchar](100) NULL,
	[organization] [nvarchar](100) NULL,
	[office] [nvarchar](100) NULL,
	[street] [nvarchar](100) NULL,
	[zip_town] [nvarchar](100) NULL,
	[country] [nvarchar](100) NULL,
	[phone] [nvarchar](100) NULL,
	[mobile] [nvarchar](100) NULL,
	[fax] [nvarchar](100) NULL,
	[privacy] [bit] NOT NULL CONSTRAINT [DF__ra__privacy]  DEFAULT ('0'),
	[authkey] [nvarchar](250) NULL,
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

IF OBJECT_ID('dbo.log', 'U') IS NOT NULL /*Backwards compatibility*/
	DROP TABLE /*IF EXISTS*/ [dbo].[log];
CREATE TABLE [dbo].[log](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[unix_ts] [bigint] NOT NULL,
	[addr] [nvarchar](45) NOT NULL,
	[event] [ntext] NOT NULL,
	CONSTRAINT [PK_log] PRIMARY KEY CLUSTERED 
	(
		[id] ASC
	)
)
GO

/**********************************************/

IF OBJECT_ID('dbo.log_user', 'U') IS NOT NULL /*Backwards compatibility*/
	DROP TABLE /*IF EXISTS*/ [dbo].[log_user];
CREATE TABLE [dbo].[log_user](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[log_id] [int] NOT NULL,
	[username] [nvarchar](255) NOT NULL,
	[severity] [int] NOT NULL,
	CONSTRAINT [PK_log_user] PRIMARY KEY CLUSTERED 
	(
		[id] ASC
	),
	INDEX [IX_log_user_log_id] NONCLUSTERED
	(
		[log_id] ASC
	),
	INDEX [IX_log_user_username] NONCLUSTERED
	(
		[username] ASC
	),
	CONSTRAINT [IX_log_user_log_id_username] UNIQUE
	(
		[log_id],
		[username]
	)
)
GO

/**********************************************/

IF OBJECT_ID('dbo.log_object', 'U') IS NOT NULL /*Backwards compatibility*/
	DROP TABLE /*IF EXISTS*/ [dbo].[log_object];
CREATE TABLE [dbo].[log_object](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[log_id] [int] NOT NULL,
	[object] [nvarchar](255) COLLATE German_PhoneBook_CS_AS NOT NULL,
	[severity] [int] NOT NULL,
	CONSTRAINT [PK_log_object] PRIMARY KEY CLUSTERED 
	(
		[id] ASC
	),
	INDEX [IX_log_object_log_id] NONCLUSTERED
	(
		[log_id] ASC
	),
	INDEX [IX_log_object_object] NONCLUSTERED
	(
		[object] ASC
	),
	CONSTRAINT [IX_log_object_log_id_object] UNIQUE
	(
		[log_id],
		[object]
	)
)
GO


/****** Set database version ******/

INSERT INTO [config] (name, description, value, protected, visible) VALUES ('database_version', 'Version of the database tables', '1002', '1', '0');

SET ANSI_PADDING OFF
GO
