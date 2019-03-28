<?php

/*
 * OIDplus 2.0
 * Copyright 2019 Daniel Marschall, ViaThinkSoft
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

OIDplus::db()->query("insert into ".OIDPLUS_TABLENAME_PREFIX."config (name, description, value) values ('max_ra_email_change_time', 'Max RA email change time in seconds (0 = infinite)', '0')");
OIDplus::db()->query("insert into ".OIDPLUS_TABLENAME_PREFIX."config (name, description, value) values ('allow_ra_email_change', 'Allow that RAs change their email address (0/1)', '1')");
