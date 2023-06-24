
Authentication methods
======================

    -----------------------------------------------------------------------------------------------------------------------------------
                                                 JWT        Bound to    JWT accepted     Outputs JWT        CSRF Disabled /
    Login method                    Handling     Generator  client IP?  Request types    Exception?         OriginHeaders
    -----------------------------------------------------------------------------------------------------------------------------------
    Browser login                   JWT          40         Optional    COOKIE           No (Silent)        No
    Automated AJAX call             JWT          10         No          GET/POST         Yes                Only ajax.php with GET/POST
    REST plugin                     JWT          10         No          HTTP Bearer      Yes                Yes
    Manually created JWT token      JWT          80         Optional    GET/POST/COOKIE  Only via GET/POST  Only ajax.php with GET/POST
    -----------------------------------------------------------------------------------------------------------------------------------
