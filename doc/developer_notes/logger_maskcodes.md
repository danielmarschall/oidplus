
OIDplus Logger Maskcodes
========================

What is a mask code?
--------------------

A "mask code" gives information about the log event.
It contains:
1. The severity (info, warning, error, critical)
2. In which logbook(s) the event shall be placed

Example:
Let's imagine the following event:

    "Person 'X' moves from house 'A' to house 'B'"

This event would affect:
- Person 'X'
- House 'A'
- House 'B'

Instead of logging into 3 logbooks separately, you would create a mask code that tells the system to put the message
into the logbooks of person X, house A, and house B.

Syntax rules
------------

In the code, mask codes would look like this:

	OIDplus::logger()->log("[INFO]OID(%1)", "RA of object '%1' changed from '%2' to '%3'", $oid, $old_ra, $new_ra);

As you can see, the maskcode and message can be parameterized like `sprintf()` does,
but with the difference that `%1`, `%2`, `%3`, ..., is used instead of `%s`.

Please note that the event message is not enclosed in `_L(...)`, because log-messages are always written in English,
and not in the front-end language of the user.

At the beginning of each mask code, you must define a severity, which is written in square brackets.
Valid severities:
- `[OK]`: Rule of thumb: YOU have done something and it was successful.
- `[INFO]`: Rule of thumb: Someone else has done something (that affects you) and it was successful.
- `[WARN]`: Rule of thumb: Something happened (probably someone did something) and it affects you.
- `[ERR]`: Rule of thumb: Something failed (probably someone did something) and it affects you.
- `[CRIT]`: Rule of thumb: Something happened (probably someone did something) which is not an error, but some critical situation (e.g. hardware failure), and it affects you.

A mask code can have multiple components which are split into single codes using `+` or `/`, e.g. `OID(x)+RA(x)` would
be split to `OID(x)` and `RA(x)` which would result in the message being placed in the logbook of OID x,
and the logbook of the RA owning OID x.

If you have a mask code with multiple components,  you don't have to place the severity for each component.
You can just leave it at the beginning. For example, `[WARN]OID(x)+RA(x)` is equal to `[WARN]OID(x)+[WARN]RA(x)`.
You can also put different severities for the components, e.g. `[INFO]OID(x)+[WARN]RA(x)` would be a info for the OID,
but a warning for the RA.

If you want to make the severity dependent on wheather the user is logged in or not,
prepend `?` or `!` and use `/` as delimiter
Example: `[?WARN/!OK]RA(x)` means: If RA "x" is not logged in, it is a warning; if it is logged in, it is an success.
With this technique you can achive that the RA gets warned if an admin changed some of their OIDs,
but receives an OK-Event if they did the change.

`OID(x)` means: Save the log entry into the logbook of: Object "x".

`SUPOID(x)` means: Save the log entry into the logbook of: Parent of object "x".

`OIDRA(x)!` means: Save the log entry into the logbook of: RA of object "x".

`OIDRA(x)?` means: Save the log entry into the logbook of: Logged in RA of object "x". If it is not logged in, nothing will be logged.

`SUPOIDRA(x)!` means: Save the log entry into the logbook of: RA that owns the superior object of "x".

`SUPOIDRA(x)?` means: Save the log entry into the logbook of: Logged in RA that owns the superior object of "x". If it is not logged in, nothing will be logged.

`RA(x)!` means: Save the log entry into the logbook of: RA "x".

`RA(x)?` means: Save the log entry into the logbook of: Logged in RA "x". If it is not logged in, nothing will be logged.

`A!` means: Save the log entry into the logbook of: The admin.

`A?` means: Save the log entry into the logbook of: The logged in admin. If it is not logged in, nothing will be logged.

Implementation
==============

You can find the implementation in **includes/classes/OIDplusLogger.class.php**.
