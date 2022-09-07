# OID/DER converter for C and PHP

Current version: [1.3](https://github.com/m9aertner/oidConverter)+viathinksoft12

## Functionalities

- Encode  **absolute**  OID in dot-notation (`"2.999.1234"`) into Hex-String (`"06 04 88 37 89 52"`)  
- Encode  **absolute**  OID in dot-notation (`"2.999.1234"`) into C-Hex-String (`"\x06\x04\x88\x37\x89\x52"`)  
- Encode  **absolute**  OID in dot-notation (`"2.999.1234"`) into C-Array (`{ 0x06, 0x04, 0x88, 0x37, 0x89, 0x52 }`)  
- Encode  **relative**  OID in dot-notation (`"1234"`) into Hex-String (`"0D 02 89 52"`)  
- Encode  **relative**  OID in dot-notation (`"1234"`) into C-Hex-String (`"\x0D\x02\x89\x52"`)  
- Encode  **relative**  OID in dot-notation (`"1234"`) into C-Array (`{ 0x0D, 0x02, 0x89, 0x52 }`)  
- Decode Hex-Notation (`"06 04 88 37 89 52"`  or  `"\x06\x04\x88\x37\x89\x52"`  or  `{ 0x06, 0x04, 0x88, 0x37, 0x89, 0x52 }`) into dot-notation (`"2.999.1234"`)  

## Acknowledgements

Object ID converter by  [Matthias Gärtner](http://www.rtner.de/software/oid.html), 06/1999. Converted to plain 'C' 07/2001.

Heavily improved version by Daniel Marschall, ViaThinkSoft June-July 2011.

Translated from C to PHP by Daniel Marschall, ViaThinkSoft.

September 2022: Synchronized to upstream version 1.3 (added `-c` argument).

## License

Work of original author: "Freeware - do with it whatever you want. Use at your own risk. No warranty of any kind."

Work of Daniel Marschall (PHP): Licensed under the Apache 2.0 license
