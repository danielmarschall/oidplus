<?php

/*
 * OIDplus 2.0
 * Copyright 2020 Daniel Marschall, ViaThinkSoft
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

// DATABASE UPDATE 202 -> 203
// This script will be included by OIDplusDatabasePlugin.class.php inside function afterConnect().
// Parameters: $this is the OIDplusDatabasePlugin class
//             $version is the current version (this script MUST increase the number by 1 when it is done)

if (!isset($version)) throw new Exception("Argument 'version' is missing; was the file included in a wrong way?");
if (!isset($this))    throw new Exception("Argument 'this' is missing; was the file included in a wrong way?");

$this->transaction_begin();

if ($this->slang() == 'mssql') {
	$sql = "CREATE FUNCTION [dbo].[getOidArc] (@strList varchar(512), @maxArcLen int, @occurence int)
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
	END";
	$this->query($sql);
}

$version = 203;
$this->query("UPDATE ".OIDPLUS_TABLENAME_PREFIX."config SET value = ? WHERE name = 'database_version'", array($version));

$this->transaction_commit();
