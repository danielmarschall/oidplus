# Recieve

Description: Returns data...

GET https://www.example.com/oidplus/rest/v1/objects/[ns]:[id]

Input Parameters: None

Output Parameters:

| Element  | Description  | Type  | Notes  |
|---|---|---|---|
| status  |the status of the returned data  |integer   |<0 is error, >=0 is success   |
| status_bits |   |   |   |
| error  | returns if an error occurs  | integer  | <0 is error  |
| ra_email  |   |   |   |
| comment  |   |   |   |
| iris  |   |   |  for OID only |
| asn1ids  |   |   | for OID only  |
| confidential  |   |   |   |
| title  |   |   |   |
| description  |   |   |   |
| children |   |   |   |
| created  |   |   |   |
| updated  |   |   |   |


# Re-Create
Description: Recreates/Edits the objects

PUT https://www.example.com/oidplus/rest/v1/objects/[ns]:[id]

Input Parameters:

|Parameter   |Description   | Type  |Required   |Notes   |
|---|---|---|---|---|
|ra_email |   | string  |Optional   |   |
|comment   |   |string   |Optional   |   |
|iris   |   |string   |Optional   |   |
|asn1ids   |   |string   |Optional   |   |
|confidential   |   | string  |Optional   |   |
|title   |   |string   |Optional   |   |
|description   |   |string   |Optional   |   |

Output Parameters:

|Element   |Description   |Type   |Notes   |
|---|---|---|---|
|status   |the status of the returned data   |integer   |<0 is error, >=0 is success   |
|status_bits   |   |   |   |
|error   |returns if an eror occurs   |integer   |<0 is error, >=0 is success   |
|inserted_id   |recreated/edited ID   |   |will return if ID was created   |

# Create
Description: Creates a new object

POST https://www.example.com/oidplus/rest/v1/objects/[ns]:[id]

Input Parameters:

|Parameter   |Description   | Type  |Required   |Notes   |
|---|---|---|---|---|
|ra_email |   | string  |Optional   |   |
|comment   |   |string   |Optional   |   |
|iris   |   |string   |Optional   |   |
|asn1ids   |   |string   |Optional   |   |
|confidential   |   | string  |Optional   |   |
|title   |   |string   |Optional   |   |
|description   |   |string   |Optional   |   |

Output Parameters:

|Element   |Description   |Type   |Notes   |
|---|---|---|---|
|status   |the status of the returned data   |integer   |<0 is error, >=0 is success   |
|status_bits   |   |   |   |
|error   |returns if an eror occurs   |integer   |<0 is error, >=0 is success   |
|inserted_id   |newly created ID   |   |will return if ID was created   |

# Update
Description: Updates the API 

PATCH  https://www.example.com/oidplus/rest/v1/objects/[ns]:[id]

Input Parameters:

|Parameter   |Description   | Type  |Required   |Notes   |
|---|---|---|---|---|
|ra_email |   | string  |Optional   |   |
|comment   |   |string   |Optional   |   |
|iris   |   |string   |Optional   |   |
|asn1ids   |   |string   |Optional   |   |
|confidential   |   | string  |Optional   |   |
|title   |   |string   |Optional   |   |
|description   |   |string   |Optional   |   |


Output Parameters:

|Element   |Description   |Type   |Notes   |
|---|---|---|---|
|status   |the status of the returned data   |integer   |<0 is error, >=0 is success   |
|status_bits   |   |   |   |
|error   |returns if an eror occurs   |integer   |<0 is error, >=0 is success   |

# Remove
Description: Deletes an object

DELETE https://www.example.com/oidplus/rest/v1/objects/[ns]:[id]

Input Parameters: None

Output Parameters:

|Element   |Description   |Type   |Notes   |
|---|---|---|---|
|status   |the status of the returned data   |integer   |<0 is error, >=0 is success   |
|status_bits   |   |   |   |
|error   |returns if an eror occurs   |integer   |<0 is error, >=0 is success   |