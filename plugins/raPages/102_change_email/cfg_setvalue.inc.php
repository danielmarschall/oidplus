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

if ($name == 'allow_ra_email_change') {
        if (($value != '0') && ($value != '1')) {
                throw new Exception("Please enter either 0 or 1.");
        }
}
if (($name == 'max_ra_invite_time') || ($name == 'max_ra_pwd_reset_time') || ($name == 'max_ra_email_change_time')) {
	if (!is_numeric($value) || ($value < 0)) {
		throw new Exception("Please enter a valid value.");
	}
}
