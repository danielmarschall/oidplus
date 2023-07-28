# OIDplus UUID format

## General OIDplus UUID format

OIDplus generates UUIDs for various object types using UUIDs in the "Custom / Version 8" format as defined in the new version of RFC 4122.

The general structure of these UUIDs is:

|Block| Byte  | Length  | Description          |
|-----|-------|---------|----------------------|
|  1  | 0-3   | 1 bit   | Reserved, must be 0.
|     |       | 31 bits | OIDplus SystemID (lower 31 bits of the SHA1 hash of the Public Key in raw binary representation)
|  2  | 4-5   | 16 bits | Creation timestamp: Days since 01.01.1970 00:00 GMT; 0 if unknown or not applicable. Max possible: 0xFFFF = 06 June 2149
|  3  | 6-7   | 4 bits  | UUID Version, must be 0x8 (Custom UUID)
|     |       | 12 bits | Reserved, must be 0x0000
|  4  | 8-9   | 2 bits  | UUID Variant, must be 0b10 (RFC 4122)
|     |       | 14 bits | Namespace
|  5  | 10-15 | 48 bits | Data as defined by the namespace


## OIDplus System UUID (Block 4 = 0x8000)

Every OIDplus system can be identified by a UUID.

The 14-bit namespace has a fixed value of 0, i.e. block 4 has the value of `0x8000`.

The 48-bit data has a fixed value of `0x1890afd80709` which is the lower 48 bits of SHA1 of an empty string.

The creation timestamp is currently 0 because OIDplus does not track the creation time of a freshly installed system.

**Example**: System #1855139287 has the following UUID:

    6e932dd7-0000-8000-8000-1890afd80709

It contains the information as follows:

|Block| Description       | Value            | Interpretation |
|-----|-------------------|------------------|----------------|
|  1  | System ID         | 0x6E932DD7       | SHA1(PubKey) & 0x7FFF.FFFF = 1855139287
|  2  | Timestamp         | 0x0000           | Unknown
|  3  | Reserved+Version  | 0x8000           | 0x0000 \| 0x8000
|  4  | Namespace+Variant | 0x8000           | 0x0000 \| 0x8000
|  5  | Data              | 0x1890AFD80709   | SHA1('') & 0xFFFF.FFFF.FFFF


## OIDplus User/RA UUID (Block 4 = 0x8001)

Every user (RA or the Admin) can be identified by a UUID. (Defined hereby, but currently not used in OIDplus)

The 14-bit namespace has a fixed value of 1, i.e. block 4 has the value of `0x8001`.

For the Admin account, the 48-bit data has a fixed value of `0x000000000000`.
The creation timestamp for the admin is the creation of the system,
which is currently 0 because OIDplus does not track the creation time of a freshly installed system.

For an RA account, the 48-bit data are the lower 48 bits of the SHA1 hash of the email address of the RA.
The creation timestamp is the user registration time, which is currently 0
because OIDplus does not track the creation time of a user registration.

Please note: Since there may be collisions in the 48-bit hash,
this UUID should only be used for security-relevant applications
if the system ensures that there is no collision, e.g. by storing
all UUIDs and rejecting new user accounts if their UUID collides
with another user.

**Example**: The user "joe@example.com" on system #1855139287 has the following UUID:

    6e932dd7-0000-8000-8001-2938f50e857e

It contains the information as follows:

|Block| Description       | Value          | Interpretation                             |
|-----|-------------------|----------------|--------------------------------------------|
|  1  | System ID         | 0x6E932DD7     | SHA1(PubKey) & 0x7FFF.FFFF = 1855139287    
|  2  | Timestamp         | 0x0000         | Unknown                                    
|  3  | Reserved+Version  | 0x8000         | 0x0000 \| 0x8000                           
|  4  | Namespace+Variant | 0x8001         | 0x0001 \| 0x8000                           
|  5  | Data              | 0x2938F50E857E | SHA1('joe@example.com') & 0xFFFF.FFFF.FFFF 


## OIDplus Log entry UUID (Block 4 = 0x8002)

Every log entry can be identified by a UUID. (Defined hereby, but currently not used in OIDplus)

The 14-bit namespace has a fixed value of 2, i.e. block 4 has the value of `0x8002`.

The 48-bit data is a sequential number. It can be either sequential and unique
for the current day of the given system, or be sequential and unique for the system
independent of the day. An example of the latter usage would be using the
autoincrement field of the database table.
In case the autoincrement field of the database gets lost (e.g. data loss in the
log table or fresh install of the system), then the sequence could be incremented
by a reasonable value at least for the rest of the current day.

**Example**: The log entry 1234 on system #1855139287 logged on 30 September 2018 has the following UUID:

    6e932dd7-458c-8000-8002-0000000004d2

It contains the information as follows:

|Block| Description       | Value          | Interpretation                               |
|-----|-------------------|----------------|----------------------------------------------|
|  1  | System ID         | 0x6E932DD7     | SHA1(PubKey) & 0x7FFF.FFFF = 1855139287      
|  2  | Timestamp         | 0x458C         | 30 September 2018 (17804 days since 1 January 1970)                                      
|  3  | Reserved+Version  | 0x8000         | 0x0000 \| 0x8000                             
|  4  | Namespace+Variant | 0x8002         | 0x0002 \| 0x8000                             
|  5  | Data              | 0x0000000004D2 | Sequence 1234 


## OIDplus Configuration entry UUID (Block 4 = 0x8003)

Every system configuration entry (the values on the config database table;
not the base configuration settings in the userdata folder) can be
identified by a UUID. (Defined hereby, but currently not used in OIDplus)

The 14-bit namespace has a fixed value of 3, i.e. block 4 has the value of `0x8003`.

The 48-bit data are the 48 bits of the SHA1 hash of the
configuration name.

The creation timestamp is currently 0 because OIDplus does not track the creation time of configuration entries.

**Example**: The configuration value "max_ra_invite_time" on system #1855139287 has the following UUID:

    6e932dd7-0000-8000-8003-f14dda42862a

It contains the information as follows:

|Block| Description       | Value          | Interpretation                             |
|-----|-------------------|----------------|--------------------------------------------|
|  1  | System ID         | 0x6E932DD7     | SHA1(PubKey) & 0x7FFF.FFFF = 1855139287    
|  2  | Timestamp         | 0x0000         | Unknown                                    
|  3  | Reserved+Version  | 0x8000         | 0x0000 \| 0x8000                           
|  4  | Namespace+Variant | 0x8003         | 0x0003 \| 0x8000                           
|  5  | Data              | 0xF14DDA42862A | SHA1('max_ra_invite_time') & 0xFFFF.FFFF.FFFF 


## OIDplus ASN.1 Alphanumeric ID UUID (Block 4 = 0x8004)

Each allocation of an alphanumeric (ASN.1) identifier of an OID
can be identified by a UUID. (Defined hereby, but currently not used in OIDplus)

The 14-bit namespace has a fixed value of 4, i.e. block 4 has the value of `0x8004`.

The 48-bit data is defined as follows:
- The upper 24 bits are the lower 24 bits of the SHA1 hash of the OID.
- The lower 24 bits are the lower 24 bits of the SHA1 hash of the alphanumeric ASN.1 identifier.

**Example**: The alphanumeric identifier "example" of OID "2.999" on system #1855139287 has the following UUID:

    6e932dd7-0000-8000-8004-208ded8a3f8f

It contains the information as follows:

| Block | Description       | Value      | Interpretation                          |
|-------|-------------------|------------|-----------------------------------------|
| 1     | System ID         | 0x6E932DD7 | SHA1(PubKey) & 0x7FFF.FFFF = 1855139287 
| 2     | Timestamp         | 0x0000     | Unknown                                 
| 3     | Reserved+Version  | 0x8000     | 0x0000 \| 0x8000                        
| 4     | Namespace+Variant | 0x8004     | 0x0004 \| 0x8000                        
| 5     | Data (High)       | 0x208DED   | SHA1('2.999') & 0xFF.FFFF        
|       | Data (Low)        | 0x8A3F8F   | SHA1('example') & 0xFF.FFFF      


## OIDplus OID-IRI Unicode Label UUID (Block 4 = 0x8005)

Each allocation of a Unicode Label (for OID-IRI) of an OID
can be identified by a UUID. (Defined hereby, but currently not used in OIDplus)

The 14-bit namespace has a fixed value of 5, i.e. block 4 has the value of `0x8005`.

The 48-bit data is defined as follows:
- The upper 24 bits are the lower 24 bits of the SHA1 hash of the OID.
- The lower 24 bits are the lower 24 bits of the SHA1 hash of the Unicode Label in UTF-8 encoding.

**Example**: The Unicode Label "Example" of OID "2.999" on system #1855139287 has the following UUID:

    6e932dd7-0000-8000-8005-208dedaf9a96

It contains the information as follows:

| Block | Description       | Value      | Interpretation                                  |
|-------|-------------------|------------|-------------------------------------------------|
| 1     | System ID         | 0x6E932DD7 | SHA1(PubKey) & 0x7FFF.FFFF = 1855139287         
| 2     | Timestamp         | 0x0000     | Unknown                                         
| 3     | Reserved+Version  | 0x8000     | 0x0000 \| 0x8000                                
| 4     | Namespace+Variant | 0x8005     | 0x0005 \| 0x8000                                
| 5     | Data (High)       | 0x208DED   | SHA1('2.999') & 0xFF.FFFF                
|       | Data (Low)        | 0xAF9A96   | SHA1(utf8_encode('Example')) & 0xFF.FFFF 


## System reserved UUIDs (Block 4 = 0x8006 till 0x800F)

The 14-bit namespace values 6 (block 4 = `0x8006`) through 15 (block 4 = `0x800F`) are reserved
for future use.


## OIDplus Information Object UUID (Block 4 = 0x8010 till 0xFFFF)

OIDplus automatically assigns a UUID to every object it manages.

The 14-bit namespace is defined as the lower 14 bits of the SHA1 hash of the OIDplus Object Type Plugin OID.
However, the values `0x0` - `0xF` are reserved for the other UUID types and must not be used. (In this case,
the object type plugin author shall pick a different OID for their plugin). So block 4 has a range
between `0x8010` till `0xFFFF`.

The 48-bit data is defined as the lower 48 bits of the SHA1 hash of the
object name without the object type prefix.

**Example**: The object **java:com.example**, created on 30 September 2018
on system #1855139287 has the following UUID:

    6e932dd7-458c-8000-b9e9-c1e3894d1105

It contains the information as follows:

|Block| Description       | Value          | Interpretation |
|-----|-------------------|----------------|----------------|
|  1  | System ID         | 0x6E932DD7     | SHA1(PubKey) & 0x7FFF.FFFF = 1855139287
|  2  | Timestamp         | 0x458C         | 30 September 2018 (17804 days since 1 January 1970)
|  3  | Reserved+Version  | 0x8000         | 0x0000 \| 0x8000
|  4  | Namespace+Variant | 0xB9E9         | SHA1('1.3.6.1.4.1.37476.2.5.2.4.8.6') & 0x3FFF \| 0x8000
|  5  | Data              | 0xC1E3894D1105 | SHA1('com.example') & 0xFFFF.FFFF.FFFF


## Known namespaces

| Vendor       | Namespace | Plugin OID                                        | SHA1 hash | Block 4 | Notes        |
|--------------|-----------|---------------------------------------------------|-----------|---------|--------------|
| n/a          | n/a       | n/a                                               | n/a       | 0x8000  | System
| n/a          | n/a       | n/a                                               | n/a       | 0x8001  | User/RA
| n/a          | n/a       | n/a                                               | n/a       | 0x8002  | Logging entry
| n/a          | n/a       | n/a                                               | n/a       | 0x8003  | Configuration entry
| n/a          | n/a       | n/a                                               | n/a       | 0x8004  | ASN.1 ID
| n/a          | n/a       | n/a                                               | n/a       | 0x8005  | Unicode Label (IRI)
| n/a          | n/a       | n/a                                               | n/a       | 0x8006  | Reserved for system
| n/a          | n/a       | n/a                                               | n/a       | 0x8007  | Reserved for system
| n/a          | n/a       | n/a                                               | n/a       | 0x8008  | Reserved for system
| n/a          | n/a       | n/a                                               | n/a       | 0x8009  | Reserved for system
| n/a          | n/a       | n/a                                               | n/a       | 0x800A  | Reserved for system
| n/a          | n/a       | n/a                                               | n/a       | 0x800B  | Reserved for system
| n/a          | n/a       | n/a                                               | n/a       | 0x800C  | Reserved for system
| n/a          | n/a       | n/a                                               | n/a       | 0x800D  | Reserved for system
| n/a          | n/a       | n/a                                               | n/a       | 0x800E  | Reserved for system
| n/a          | n/a       | n/a                                               | n/a       | 0x800F  | Reserved for system
| ViaThinkSoft | doi       | 1.3.6.1.4.1.37476.2.5.2.4.8.1                     | 0x...2259 | 0xA259  |
| ViaThinkSoft | gs1       | 1.3.6.1.4.1.37476.2.5.2.4.8.2                     | 0x...021E | 0x821E  |
| ViaThinkSoft | guid      | 1.3.6.1.4.1.37476.2.5.2.4.8.3                     | 0x...B924 | 0xB924  | In OIDplus, only the UUID itself will be shown
| ViaThinkSoft | ipv4      | 1.3.6.1.4.1.37476.2.5.2.4.8.4                     | 0x...5AF9 | 0x9AF9  |
| ViaThinkSoft | ipv6      | 1.3.6.1.4.1.37476.2.5.2.4.8.5                     | 0x...55DB | 0x95DB  |
| ViaThinkSoft | java      | 1.3.6.1.4.1.37476.2.5.2.4.8.6                     | 0x...79E9 | 0xB9E9  |
| ViaThinkSoft | oid       | 1.3.6.1.4.1.37476.2.5.2.4.8.7                     | 0x...66D3 | 0xA6D3  |
| ViaThinkSoft | other     | 1.3.6.1.4.1.37476.2.5.2.4.8.8                     | 0x...D068 | 0x9068  |
| ViaThinkSoft | domain    | 1.3.6.1.4.1.37476.2.5.2.4.8.9                     | 0x...D982 | 0x9982  |
| ViaThinkSoft | fourcc    | 1.3.6.1.4.1.37476.2.5.2.4.8.10                    | 0x...B648 | 0xB648  |
| ViaThinkSoft | aid       | 1.3.6.1.4.1.37476.2.5.2.4.8.11                    | 0x...2571 | 0xA571  |
| ViaThinkSoft | php       | 1.3.6.1.4.1.37476.2.5.2.4.8.12                    | 0x...A6F0 | 0xA6F0  |
| ViaThinkSoft | mac       | 1.3.6.1.4.1.37476.2.5.2.4.8.13                    | 0x...91CD | 0x91CD  |
| Frdlweb      | circuit   | 1.3.6.1.4.1.37553.8.1.8.8.53354196964.27255728261 | 0x...EBD5 | 0xABD5  |
| Frdlweb      | ns        | 1.3.6.1.4.1.37476.9000.108.19361.856              | 0x...AF2D | 0xAF2D  |
| Frdlweb      | pen       | 1.3.6.1.4.1.37553.8.1.8.8.53354196964.32927       | 0x...D31E | 0x931E  |
| Frdlweb      | uri       | 1.3.6.1.4.1.37553.8.1.8.8.53354196964.39870       | 0x...AA05 | 0xAA05  |
| Frdlweb      | web+fan   | 1.3.6.1.4.1.37553.8.1.8.8.53354196964.1958965295  | 0x...F077 | 0xB077  |
<!--
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.14                    | 0x...AB3E | 0xAB3E  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.15                    | 0x...4779 | 0x8779  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.16                    | 0x...2318 | 0xA318  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.17                    | 0x...1412 | 0x9412  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.18                    | 0x...76C5 | 0xB6C5  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.19                    | 0x...D43A | 0x943A  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.20                    | 0x...1DE3 | 0x9DE3  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.21                    | 0x...9FF7 | 0x9FF7  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.22                    | 0x...12F6 | 0x92F6  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.23                    | 0x...3B1D | 0xBB1D  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.24                    | 0x...0617 | 0x8617  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.25                    | 0x...A952 | 0xA952  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.26                    | 0x...C0DA | 0x80DA  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.27                    | 0x...FAC8 | 0xBAC8  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.28                    | 0x...E993 | 0xA993  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.29                    | 0x...44CA | 0x84CA  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.30                    | 0x...70DA | 0xB0DA  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.31                    | 0x...BCB0 | 0xBCB0  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.32                    | 0x...23AB | 0xA3AB  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.33                    | 0x...FD1D | 0xBD1D  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.34                    | 0x...812A | 0x812A  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.35                    | 0x...CF70 | 0x8F70  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.36                    | 0x...C724 | 0x8724  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.37                    | 0x...D6E3 | 0x96E3  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.38                    | 0x...DFAF | 0x9FAF  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.39                    | 0x...C521 | 0x8521  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.40                    | 0x...E8B8 | 0xA8B8  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.41                    | 0x...D0C0 | 0x90C0  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.42                    | 0x...ECF3 | 0xACF3  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.43                    | 0x...63D2 | 0xA3D2  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.44                    | 0x...BA72 | 0xBA72  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.45                    | 0x...348B | 0xB48B  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.46                    | 0x...C66A | 0x866A  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.47                    | 0x...0788 | 0x8788  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.48                    | 0x...C48F | 0x848F  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.49                    | 0x...41ED | 0x81ED  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.50                    | 0x...F7C8 | 0xB7C8  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.51                    | 0x...B898 | 0xB898  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.52                    | 0x...134D | 0x934D  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.53                    | 0x...0DBF | 0x8DBF  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.54                    | 0x...84FF | 0x84FF  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.55                    | 0x...3CDF | 0xBCDF  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.56                    | 0x...FDCF | 0xBDCF  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.57                    | 0x...6988 | 0xA988  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.58                    | 0x...EA2B | 0xAA2B  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.59                    | 0x...763A | 0xB63A  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.60                    | 0x...81B7 | 0x81B7  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.61                    | 0x...A5E8 | 0xA5E8  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.62                    | 0x...9D2A | 0x9D2A  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.63                    | 0x...10B6 | 0x90B6  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.64                    | 0x...554F | 0x954F  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.65                    | 0x...9DA2 | 0x9DA2  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.66                    | 0x...E810 | 0xA810  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.67                    | 0x...0CCA | 0x8CCA  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.68                    | 0x...3D0E | 0xBD0E  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.69                    | 0x...4599 | 0x8599  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.70                    | 0x...7152 | 0xB152  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.71                    | 0x...E6E0 | 0xA6E0  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.72                    | 0x...25FE | 0xA5FE  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.73                    | 0x...D608 | 0x9608  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.74                    | 0x...AECC | 0xAECC  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.75                    | 0x...3D11 | 0xBD11  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.76                    | 0x...B4B4 | 0xB4B4  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.77                    | 0x...5967 | 0x9967  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.78                    | 0x...772E | 0xB72E  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.79                    | 0x...B8CE | 0xB8CE  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.80                    | 0x...797A | 0xB97A  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.81                    | 0x...D21C | 0x921C  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.82                    | 0x...DA41 | 0x9A41  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.83                    | 0x...C9F6 | 0x89F6  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.84                    | 0x...FDF1 | 0xBDF1  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.85                    | 0x...CEE4 | 0x8EE4  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.86                    | 0x...8A32 | 0x8A32  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.87                    | 0x...4D26 | 0x8D26  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.88                    | 0x...EBB2 | 0xABB2  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.89                    | 0x...E8D4 | 0xA8D4  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.90                    | 0x...6500 | 0xA500  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.91                    | 0x...7A8F | 0xBA8F  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.92                    | 0x...F322 | 0xB322  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.93                    | 0x...B75E | 0xB75E  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.94                    | 0x...3F4D | 0xBF4D  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.95                    | 0x...D7FB | 0x97FB  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.96                    | 0x...A1FA | 0xA1FA  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.97                    | 0x...0490 | 0x8490  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.98                    | 0x...6C91 | 0xAC91  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.99                    | 0x...4410 | 0x8410  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.100                   | 0x...B089 | 0xB089  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.101                   | 0x...38BA | 0xB8BA  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.102                   | 0x...22BC | 0xA2BC  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.103                   | 0x...CDD8 | 0x8DD8  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.104                   | 0x...971F | 0x971F  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.105                   | 0x...1C69 | 0x9C69  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.106                   | 0x...C456 | 0x8456  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.107                   | 0x...F064 | 0xB064  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.108                   | 0x...9490 | 0x9490  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.109                   | 0x...7186 | 0xB186  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.110                   | 0x...A9BD | 0xA9BD  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.111                   | 0x...1338 | 0x9338  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.112                   | 0x...BE9E | 0xBE9E  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.113                   | 0x...3B4F | 0xBB4F  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.114                   | 0x...CFFE | 0x8FFE  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.115                   | 0x...D37F | 0x937F  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.116                   | 0x...125F | 0x925F  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.117                   | 0x...B781 | 0xB781  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.118                   | 0x...9F3E | 0x9F3E  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.119                   | 0x...A69B | 0xA69B  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.120                   | 0x...144B | 0x944B  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.121                   | 0x...B548 | 0xB548  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.122                   | 0x...3AB4 | 0xBAB4  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.123                   | 0x...4181 | 0x8181  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.124                   | 0x...5CB8 | 0x9CB8  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.125                   | 0x...765A | 0xB65A  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.126                   | 0x...814C | 0x814C  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.127                   | 0x...142E | 0x942E  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.128                   | 0x...BD4A | 0xBD4A  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.129                   | 0x...173E | 0x973E  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.130                   | 0x...21C0 | 0xA1C0  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.131                   | 0x...95B8 | 0x95B8  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.132                   | 0x...87D3 | 0x87D3  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.133                   | 0x...271D | 0xA71D  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.134                   | 0x...8763 | 0x8763  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.135                   | 0x...F354 | 0xB354  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.136                   | 0x...68A5 | 0xA8A5  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.137                   | 0x...337F | 0xB37F  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.138                   | 0x...E001 | 0xA001  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.139                   | 0x...1F59 | 0x9F59  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.140                   | 0x...00D0 | 0x80D0  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.141                   | 0x...A375 | 0xA375  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.142                   | 0x...9794 | 0x9794  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.143                   | 0x...0A70 | 0x8A70  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.144                   | 0x...010D | 0x810D  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.145                   | 0x...633D | 0xA33D  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.146                   | 0x...2CD9 | 0xACD9  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.147                   | 0x...410C | 0x810C  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.148                   | 0x...2900 | 0xA900  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.149                   | 0x...4141 | 0x8141  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.150                   | 0x...A350 | 0xA350  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.151                   | 0x...E8E0 | 0xA8E0  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.152                   | 0x...4BE1 | 0x8BE1  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.153                   | 0x...0B25 | 0x8B25  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.154                   | 0x...D933 | 0x9933  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.155                   | 0x...7917 | 0xB917  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.156                   | 0x...1C1A | 0x9C1A  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.157                   | 0x...9C25 | 0x9C25  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.158                   | 0x...1D24 | 0x9D24  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.159                   | 0x...B922 | 0xB922  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.160                   | 0x...69CC | 0xA9CC  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.161                   | 0x...9A8A | 0x9A8A  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.162                   | 0x...D284 | 0x9284  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.163                   | 0x...1944 | 0x9944  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.164                   | 0x...A074 | 0xA074  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.165                   | 0x...51D1 | 0x91D1  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.166                   | 0x...C3F6 | 0x83F6  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.167                   | 0x...731E | 0xB31E  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.168                   | 0x...B4EF | 0xB4EF  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.169                   | 0x...EF93 | 0xAF93  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.170                   | 0x...A3E9 | 0xA3E9  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.171                   | 0x...32DC | 0xB2DC  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.172                   | 0x...3930 | 0xB930  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.173                   | 0x...0FC1 | 0x8FC1  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.174                   | 0x...5131 | 0x9131  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.175                   | 0x...F5F4 | 0xB5F4  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.176                   | 0x...4B63 | 0x8B63  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.177                   | 0x...F1CD | 0xB1CD  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.178                   | 0x...9364 | 0x9364  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.179                   | 0x...40AB | 0x80AB  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.180                   | 0x...A182 | 0xA182  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.181                   | 0x...FC4A | 0xBC4A  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.182                   | 0x...DF14 | 0x9F14  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.183                   | 0x...DA04 | 0x9A04  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.184                   | 0x...7B3E | 0xBB3E  |
| ViaThinkSoft | (Unused)  | 1.3.6.1.4.1.37476.2.5.2.4.8.185                   | 0x...4854 | 0x8854  |
-->


## Notes for new object-type plugins

(1) When new object types are developed, the plugin author should check
if their "Block 4" hash based on their plugin OID conflicts with the
"Block 4" hashes of plugins from other vendors, and consider
using a different plugin OID in that case.
Between ViaThinkSoft OIDs, the lowest collision is `1.3.6.1.4.1.37476.2.5.2.4.8.186`
which collides with `1.3.6.1.4.1.37476.2.5.2.4.8.48`.

Add this line to your baseconfig-file (userdata/baseconfig/config.inc.php) to
automatically let OIDplus check all third-party object type plugins for
hash conflicts:

    OIDplus::baseConfig()->setValue('DEBUG', true);

As an alternative, the following script can help you check
(and generate a new line for this table):

    <?php

	$plugin_oid = '2.999'; // your plugin-oid here

	if (!str_starts_with($plugin_oid, '1.3.6.1.4.1.37476.2.5.2.4.8.')) {
		$coll = [];
		for ($i = 1; $i <= 185; $i++) {
            // No conflict between ViaThinkSoft OIDs .1 till .185
			$block4 = dechex(hexdec(substr(sha1('1.3.6.1.4.1.37476.2.5.2.4.8.'.$i), -4)) & 0x3FFF | 0x8000);
			$coll[] = $block4;
		}
		for ($i=0; $i<=0xF; $i++) {
            // 0x8000 - 0x800F are used by the system
            $coll[] = dechex(0x8000+$i); 
        }
		$block4 = dechex(hexdec(substr(sha1($plugin_oid), -4)) & 0x3FFF | 0x8000);
		if (in_array($block4, $coll)) {
			echo "HASH CONFLICT\n";
		} else {
			echo "| (Author) | (NSName) | $plugin_oid | 0x...".strtoupper(substr(sha1($plugin_oid), -4))." | 0x".strtoupper($block4)." |\n";
		}
	} else {
		$block4 = dechex(hexdec(substr(sha1($plugin_oid), -4)) & 0x3FFF | 0x8000);
		echo "| ViaThinkSoft | (NSName) | $plugin_oid | 0x...".strtoupper(substr(sha1($plugin_oid), -4))." | 0x".strtoupper($block4)." |\n";
	}

    ?>

(2) After the release of the object type plugin, please extend this table.

(3) Please also change the array with known namespaces at "UUID Utils":
https://github.com/danielmarschall/uuid_mac_utils/blob/master/includes/uuid_utils.inc.php

This allows the interpretation of OIDplus Information Object UUIDs using this tool:
https://misc.daniel-marschall.de/tools/uuid_mac_decoder/interprete_uuid.php?uuid=6e932dd7-458c-8000-b9e9-c1e3894d1105
