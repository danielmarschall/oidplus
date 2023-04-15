
Authentication methods
======================

    -----------------------------------------------------------------------------------------------------------------------------------
                                                 JWT        Bound to    JWT accepted     Outputs JWT        CSRF Disabled /
    Login method                    Handling     Generator  client IP?  Request types    Exception?         OriginHeaders
    -----------------------------------------------------------------------------------------------------------------------------------
    Browser login (regular)         PHP Session  n/a        Yes         n/a              n/a                No
    Browser login ("remember me")   JWT          1          No          COOKIE           No (Silent)        No
    Automated AJAX call             JWT          0          No          GET/POST         Yes                Only ajax.php with GET/POST
    Manually created JWT token      JWT          2          No          GET/POST/COOKIE  Only via GET/POST  Only ajax.php with GET/POST
    -----------------------------------------------------------------------------------------------------------------------------------
