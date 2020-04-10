IF OBJECT_ID('dbo.asn1id', 'U') IS NOT NULL 
	DROP TABLE [asn1id]
IF OBJECT_ID('dbo.config', 'U') IS NOT NULL 
	DROP TABLE [config]
IF OBJECT_ID('dbo.iri', 'U') IS NOT NULL 
	DROP TABLE [iri]
IF OBJECT_ID('dbo.log', 'U') IS NOT NULL 
	DROP TABLE [log]
IF OBJECT_ID('dbo.log_object', 'U') IS NOT NULL 
	DROP TABLE [log_object]
IF OBJECT_ID('dbo.log_user', 'U') IS NOT NULL 
	DROP TABLE [log_user]
IF OBJECT_ID('dbo.objects', 'U') IS NOT NULL 
	DROP TABLE [objects]
IF OBJECT_ID('dbo.ra', 'U') IS NOT NULL 
	DROP TABLE [ra]


/****** Object:  Table [dbo].[asn1id]    Script Date: 08.04.2020 22:51:47 ******/
SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO

SET ANSI_PADDING ON
GO

CREATE TABLE [dbo].[asn1id](
	[lfd] [int] IDENTITY(1,1) NOT NULL,
	[oid] [varchar](255) NOT NULL,
	[name] [varchar](255) NOT NULL,
	[standardized] [bit] NOT NULL,
	[well_known] [bit] NOT NULL,
 CONSTRAINT [PK_asn1id] PRIMARY KEY CLUSTERED 
(
	[lfd] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]

GO

SET ANSI_PADDING OFF
GO

ALTER TABLE [dbo].[asn1id] ADD  CONSTRAINT [DF__asn1id__standard__21B6055D]  DEFAULT ('0') FOR [standardized]
GO

ALTER TABLE [dbo].[asn1id] ADD  CONSTRAINT [DF__asn1id__well_kno__22AA2996]  DEFAULT ('0') FOR [well_known]
GO


/****** Object:  Table [dbo].[config]    Script Date: 08.04.2020 22:52:22 ******/
SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO

SET ANSI_PADDING ON
GO

CREATE TABLE [dbo].[config](
	[name] [varchar](50) NOT NULL,
	[value] [text] NOT NULL,
	[description] [varchar](255) NULL,
	[protected] [bit] NOT NULL DEFAULT ('0'),
	[visible] [bit] NOT NULL DEFAULT ('0'),
 CONSTRAINT [PK_config] PRIMARY KEY CLUSTERED 
(
	[name] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]

GO

SET ANSI_PADDING OFF
GO


/****** Object:  Table [dbo].[iri]    Script Date: 08.04.2020 22:52:32 ******/
SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO

SET ANSI_PADDING ON
GO

CREATE TABLE [dbo].[iri](
	[lfd] [int] IDENTITY(1,1) NOT NULL,
	[oid] [varchar](255) NOT NULL,
	[name] [varchar](255) NOT NULL,
	[longarc] [bit] NOT NULL,
	[well_known] [bit] NOT NULL,
 CONSTRAINT [PK_iri] PRIMARY KEY CLUSTERED 
(
	[lfd] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]

GO

SET ANSI_PADDING OFF
GO

ALTER TABLE [dbo].[iri] ADD  CONSTRAINT [DF__iri__longarc__24927208]  DEFAULT ('0') FOR [longarc]
GO

ALTER TABLE [dbo].[iri] ADD  CONSTRAINT [DF__iri__well_known__25869641]  DEFAULT ('0') FOR [well_known]
GO


/****** Object:  Table [dbo].[log]    Script Date: 08.04.2020 22:52:41 ******/
SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO

SET ANSI_PADDING ON
GO

CREATE TABLE [dbo].[log](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[unix_ts] [bigint] NOT NULL,
	[addr] [varchar](45) NOT NULL,
	[event] [text] NOT NULL,
 CONSTRAINT [PK_log] PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]

GO

SET ANSI_PADDING OFF
GO


/****** Object:  Table [dbo].[log_object]    Script Date: 08.04.2020 22:52:50 ******/
SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO

SET ANSI_PADDING ON
GO

CREATE TABLE [dbo].[log_object](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[log_id] [int] NOT NULL,
	[object] [varchar](255) NOT NULL,
 CONSTRAINT [PK_log_object] PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]

GO

SET ANSI_PADDING OFF
GO


/****** Object:  Table [dbo].[log_user]    Script Date: 08.04.2020 22:52:58 ******/
SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO

SET ANSI_PADDING ON
GO

CREATE TABLE [dbo].[log_user](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[log_id] [int] NOT NULL,
	[username] [varchar](255) NOT NULL,
 CONSTRAINT [PK_log_user] PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]

GO

SET ANSI_PADDING OFF
GO


/****** Object:  Table [dbo].[objects]    Script Date: 08.04.2020 22:53:07 ******/
SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO

SET ANSI_PADDING ON
GO

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
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]

GO

SET ANSI_PADDING OFF
GO

ALTER TABLE [dbo].[objects] ADD  DEFAULT (NULL) FOR [parent]
GO


/****** Object:  Table [dbo].[ra]    Script Date: 08.04.2020 22:53:16 ******/
SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO

SET ANSI_PADDING ON
GO

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
	[privacy] [bit] NOT NULL,
	[salt] [varchar](100) NOT NULL,
	[authkey] [varchar](100) NOT NULL,
	[registered] [datetime] NULL,
	[updated] [datetime] NULL,
	[last_login] [datetime] NULL,
 CONSTRAINT [PK_ra] PRIMARY KEY CLUSTERED 
(
	[ra_id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]

GO

SET ANSI_PADDING OFF
GO

ALTER TABLE [dbo].[ra] ADD  CONSTRAINT [DF__ra__privacy__29572725]  DEFAULT ('0') FOR [privacy]
GO


/****** Object:  UserDefinedFunction [dbo].[getOidArc]    Script Date: 11.04.2020 00:03:10 ******/
SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO

CREATE FUNCTION [dbo].[getOidArc] (@strList varchar(8000), @separator varchar(1), @occurence tinyint)
RETURNS bigint AS
BEGIN 
	DECLARE @intPos tinyint

	DECLARE @cnt tinyint
	SET @cnt = 0

	if substring(@strList, 1, 4) <> 'oid:'
	begin
		return 0
	end

	SET @strList = RIGHT(@strList, LEN(@strList)-4);

	WHILE CHARINDEX(@separator,@strList) > 0
	BEGIN
		SET @intPos = CHARINDEX(@separator,@strList) 
		SET @cnt = @cnt + 1
		IF @cnt = @occurence
		BEGIN
			RETURN CONVERT(bigint, LEFT(@strList,@intPos-1));
		END
		SET @strList = RIGHT(@strList, LEN(@strList)-@intPos)
	END
	IF LEN(@strList) > 0
	BEGIN
		SET @cnt = @cnt + 1
		IF @cnt = @occurence
		BEGIN
			RETURN CONVERT(bigint, @strList);
		END
	END

	RETURN -1
END
GO



INSERT INTO [config] (name, description, value, protected, visible) VALUES ('database_version', 'Version of the database tables', '203', '1', '0');
