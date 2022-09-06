# OID/DER converter for C and PHP

Version: 1.11

## Functionalities

- Encode  **absolute**  OID in dot-notation (`"2.999.1234"`) into Hex-String (`"06 04 88 37 89 52"`)  
- Encode  **relative**  OID in dot-notation (`"1234"`) into Hex-String (`"0D 02 89 52"`)  
- Encode  **absolute**  OID in dot-notation (`"2.999.1234"`) into C-Hex-String (`"\x06\x04\x88\x37\x89\x52"`)  
- Encode  **relative**  OID in dot-notation (`"1234"`) into C-Hex-String (`"\x0D\x02\x89\x52"`)  
- Decode Hex-Notation (`"06 04 88 37 89 52"`  or  `"\x06\x04\x88\x37\x89\x52"`) into dot-notation (`"2.999.1234"`)  

## Acknowledgements

Object ID converter by  [Matthias Gärtner](http://www.rtner.de/software/oid.html), 06/1999. Converted to plain 'C' 07/2001.  
Heavily improved version by Daniel Marschall, ViaThinkSoft June-July 2011.

Translated from C to PHP by Daniel Marschall, ViaThinkSoft.

Freeware - do with it whatever you want. Use at your own risk. No warranty of any kind.
