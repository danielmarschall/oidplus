
# FAQ

## What is OIDplus?

OIDplus is an OpenSource software solution by ViaThinkSoft that can be used by Registration Authorities to manage and publish information about Object Identifiers (OIDs), Globally Unique Identifiers (GUIDs), and much more.

To our knowledge, OIDplus is the only OpenSource software that implements a general (not application-specific) registry for Object Identifiers.

## Where does the name OIDplus come from?

OID stands for Object Identifier, the main purpose of OIDplus. The "plus" stands for all the other object types that can be managed (GUID, IP addresses, AIDs, MAC addresses, etc.)

## Where can I find information about Object Identifiers?

You can find a lot of information about OIDs here: www.oid-info.com

## What are Information Objects?

The term "Information Objects" has gained various meanings over time, but the overall idea behind Information Objects is that additional data (Information) is added to an Object, therefore the name "Information Objects". There are currently three definitions for Information Objects:

1.  Identifiers (such as OIDs) describe not only the file types/classes but also files/instances. That is usual for GUIDs, but rather unusual for OIDs. However, if this technique is applied, then the OIDs should most likely not be published at OIDplus, to avoid bloating the database with information that might not be useful, and to avoid that data being maintained at several places. However, in case all Information Objects are inherited from a single base OID, then the base OID under which the Information Objects lie should be defined and documented using OIDplus, describing the (possibly automatic) allocation schema.
2.  Objects registered in OIDplus (OID, GUID, etc.) can contain some kind of payload in the form of file attachments, machine-readable, metadata, etc. so that the distribution through OIDplus also enables the extraction and processing of the attached data of these OIDs. For example, MIBs could be attached to OIDs managed in OIDplus, to allow automatic processing in SNMP applications. Another example, PHP classes could be identified by OIDs, and the PHP classes are attachments to OIDs; an autoloading mechanism could then fetch the PHP classes from the OIDplus system.
3.  To achieve Information Objects, it is often important to get identifiers of a specific type. For example, you have a Java package name (java:com.example.xyz), but you want an Information Object based on an OID. Then you can use the various conversion algorithms and schemas which have been developed to automatically assign an identifier based on another identifier (that might already identify an object/file/...). These derivated identifiers are also called Alternative Identifiers in OIDplus. Note that not all Alternative Identifiers are developed for OIDplus; there are Alternative Identifiers that were already available, for example, a UUID can be natively mapped to OID through the arc 2.25.

The following schemas were developed for OIDplus:
1.  "Information Object AID" is an AID derivated from an OIDplus Object Hash. The base AID is `D276000186F` and the schemas are described [here](https://hosted.oidplus.com/viathinksoft/?goto=aid:D276000186F).
2.  "Information Object AAI" is a MAC address type (not globally unique!) derivated from the lower 48 bits of a SHA1 hash.
3.  "Information Object GUID" is a UUID derivated from an OIDplus Object Hash using an UUIDv8 Custom Schema which is described [here](https://github.com/danielmarschall/oidplus/blob/master/doc/oidplus_custom_guid.md).
4.  "Information Object OID" is an OID derivated from an OIDplus Object Hash in `1.3.6.1.4.1.37476.30.9`. The schema is described [here](https://hosted.oidplus.com/viathinksoft/?goto=oid:1.3.6.1.4.1.37476.30.9).
5.  "Information Object X.500 RDN" can be used to create an X.500 DN identifying a system and/or its objects (identified by the hash of the object name).

