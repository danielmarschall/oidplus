# OIDplus Information Object GUID format

OIDplus automatically assigns a UUID to every object it manages. It is based on the custom UUID type (UUIDv8) which was defined in the new version of RFC 4122.

| Block | Byte | Length | Description |
|--|--|--|--|
| 1 | 0-3 |  1 bit  | Reserved, must be 0. |
|   |     | 31 bits | OIDplus SystemID (lower 31 bits of SHA1 of Public Key); 0 if not available  |
| 2 | 4-5 | 16 bits | Creation timestamp: Days since 01.01.1970 00:00 GMT; 0 if unknown. Max possible: 0xFFFF = 06 June 2149 |
| 3 | 6-7 |  4 bits | UUID Version, must be 0x8 [Custom] |
|   |     | 12 bits | Reserved, must be 0x0000 |
| 4 | 8-9 |  2 bits | UUID Variant, must be 0b10 (RFC 4122) |
|   |     | 14 bits | Namespace (lower 14 bits of SHA1 of Namespace OID) |
| 5 |10-15| 48 bits | Object name (lower 48 bits of SHA1 of canonical object name) |


## Example

The object **java:com.example**, created 30 September 2018 on system #1855139287 has the following UUID:

    6e932dd7-458c-8000-b9e9-c1e3894d1105

It contains the information as follows:

|Block| Description     | Value            | Interpretation |
|--|--|--|--|
| 1 | System ID         | [0x6E932DD7]     | SHA1(PubKey) & 0x7FFF.FFFF = 1855139287 |
| 2 | Timestamp         | [0x458C]         | 30 September 2018 (17804 days since 1 January 1970) |
| 3 | Reserved+Version  | [0x0000]         | 0 \| 0x8000 |
| 4 | Namespace+Variant | [0xB9E9]         | SHA1('1.3.6.1.4.1.37476.2.5.2.4.8.6') & 0x3FFF \| 0x8000 |
| 5 | Object Name       | [0xC1E3894D1105] | SHA1('com.example') & 0xFFFF.FFFF.FFFF |

## Known namespaces

|Vendor  | Namespace | OID | SHA1 hash | UUID block 4 | Notes |
|--|--|--|--|--|--|
| ViaThinkSoft | doi    | 1.3.6.1.4.1.37476.2.5.2.4.8.1  | 0x...2259 | 0xA259 |
| ViaThinkSoft | gs1    | 1.3.6.1.4.1.37476.2.5.2.4.8.2  | 0x...021E | 0x821E |
| ViaThinkSoft | guid   | 1.3.6.1.4.1.37476.2.5.2.4.8.3  | 0x...B924 | 0xB924 | In OIDplus, only the UUID itself will be shown
| ViaThinkSoft | ipv4   | 1.3.6.1.4.1.37476.2.5.2.4.8.4  | 0x...5AF9 | 0x9AF9 |
| ViaThinkSoft | ipv6   | 1.3.6.1.4.1.37476.2.5.2.4.8.5  | 0x...55DB | 0x95DB |
| ViaThinkSoft | java   | 1.3.6.1.4.1.37476.2.5.2.4.8.6  | 0x...79E9 | 0xB9E9 |
| ViaThinkSoft | oid    | 1.3.6.1.4.1.37476.2.5.2.4.8.7  | 0x...66D3 | 0xA6D3 | In OIDplus only UUIDv3/UUIDv5 with the official OID namespace will be shown
| ViaThinkSoft | other  | 1.3.6.1.4.1.37476.2.5.2.4.8.8  | 0x...D068 | 0x9068 |
| ViaThinkSoft | domain | 1.3.6.1.4.1.37476.2.5.2.4.8.9  | 0x...D982 | 0x9982 |
| ViaThinkSoft | fourcc | 1.3.6.1.4.1.37476.2.5.2.4.8.10 | 0x...B648 | 0xB648 |
| ViaThinkSoft | aid    | 1.3.6.1.4.1.37476.2.5.2.4.8.11 | 0x...2571 | 0xA571 |
| ViaThinkSoft | php    | 1.3.6.1.4.1.37476.2.5.2.4.8.12 | 0x...A6F0 | 0xA6F0 |
| ViaThinkSoft | mac    | 1.3.6.1.4.1.37476.2.5.2.4.8.13 | 0x...91CD | 0x91CD |
<!--
| ViaThinkSoft | (Unused) | 1.3.6.1.4.1.37476.2.5.2.4.8.14 | 0x...AB3E | 0xAB3E |
| ViaThinkSoft | (Unused) | 1.3.6.1.4.1.37476.2.5.2.4.8.15 | 0x...4779 | 0x8779 |
| ViaThinkSoft | (Unused) | 1.3.6.1.4.1.37476.2.5.2.4.8.16 | 0x...2318 | 0xA318 |
| ViaThinkSoft | (Unused) | 1.3.6.1.4.1.37476.2.5.2.4.8.17 | 0x...1412 | 0x9412 |
| ViaThinkSoft | (Unused) | 1.3.6.1.4.1.37476.2.5.2.4.8.18 | 0x...76C5 | 0xB6C5 |
| ViaThinkSoft | (Unused) | 1.3.6.1.4.1.37476.2.5.2.4.8.19 | 0x...D43A | 0x943A |
| ViaThinkSoft | (Unused) | 1.3.6.1.4.1.37476.2.5.2.4.8.20 | 0x...1DE3 | 0x9DE3 |
-->

Note: When a new object type plugin is developed, the plugin author should check if their plugin OID does conflict with plugin OIDs of other vendors, and consider using a different Plugin OID in that case.
Between ViaThinkSoft OIDs, the lowest collision is `1.3.6.1.4.1.37476.2.5.2.4.8.186` which collides with `1.3.6.1.4.1.37476.2.5.2.4.8.48`.
