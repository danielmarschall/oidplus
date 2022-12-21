<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2021 Daniel Marschall, ViaThinkSoft
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

use ViaThinkSoft\OIDplus\OIDplusDatabaseConnection;

/**
 * This function will be called by OIDplusDatabaseConnection.class.php at method afterConnect().
 * @param OIDplusDatabaseConnection $db is the OIDplusDatabaseConnection class
 * @return int new version set
 * @throws \ViaThinkSoft\OIDplus\OIDplusException
 */
function oidplus_dbupdate_202(OIDplusDatabaseConnection $db) {
	if ($db->transaction_supported()) $db->transaction_begin();

	if ($db->getSlang()->id() == 'mssql') {
		$db->query("CREATE FUNCTION [dbo].[getOidArc] (@strList varchar(512), @maxArcLen int, @occurence int)
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
		END");
	}

	$version = 203;
	$db->query("UPDATE ###config SET value = ? WHERE name = 'database_version'", array($version));

	if ($db->transaction_supported()) $db->transaction_commit();

	return $version;
}
