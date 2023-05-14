
OIDplus Logger Mask Codes
=========================

## What is a mask code?

A "mask code" gives information about the log event.
It contains:
1. The severity (success, info, warning, error, critical)
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

## Syntax rules

In the code, mask codes would look like this:

	OIDplus::logger()->log("V2:[INFO]OID(%1)", "RA of object '%1' changed from '%2' to '%3'", $oid, $old_ra, $new_ra);

As you can see, the mask code and message can be parameterized like `sprintf()` does,
but with the difference that `%1`, `%2`, `%3`, ..., is used instead of `%s`.

Please note that the event message is not enclosed in `_L(...)`,
because log messages are always written in English,
and not in the front-end language of the user.

### Version

A mask code begins with `V2:`

### Components

A mask code can have multiple components which are split into single codes using `+`, e.g. `OID(x)+OIDRA(x)` would
be split to `OID(x)` and `OIDRA(x)` which would result in the message being placed in the logbook of OID x,
and the logbook of the RA owning OID x.

### Severity

At the beginning of each mask code, you must define a severity, which is written in square brackets.

Valid severities:
- `[OK]`: Rule of thumb: You have done something and it was successful.
- `[INFO]`: Rule of thumb: Someone else has done something (that affects you) and it was successful.
- `[WARN]`: Rule of thumb: Something happened (probably someone did something) and it affects you.
- `[ERR]`: Rule of thumb: Something failed (probably someone did something) and it affects you.
- `[CRIT]`: Rule of thumb: Something happened (probably someone did something) which is not an error, but some critical situation (e.g. hardware failure), and it affects you.

If you have a mask code with multiple components, you don't have to place the severity for each component.
You can just leave it at the beginning. For example, `[WARN]OID(x)+OIDRA(x)` is equal to `[WARN]OID(x)+[WARN]OIDRA(x)`.
You can also put different severities for the components, e.g. `[INFO]OID(x)+[WARN]OIDRA(x)`
would be an informative message (`INFO`) for the OID, but a warning (`WARN`) for the RA.

### Online/Offline dependency

If you want to make the logging event dependent on whether 
the target (`A`, `RA`, `OIDRA`, `SUPOIDRA`) matches the currently
logged-in user or not, use the severity `[S1/S2]` where `S1` is the severity
when the logged-in user is the target
and `S2` is the severity when the user is not logged in or
logged in as a user not matching the target.

With this technique, you can achieve that the RA gets warned if an admin or superior RA
changed some of their OIDs without their knowledge,
but receives a success message if they did the change themselves.

Example: `[OK/WARN]RA(x)+[OK/INFO]A` means that there are two log messages generated:
- Message 1 (`[OK/WARN]RA(x)`): If the currently logged-in user (performing the action)
is RA "x", then it is a success message (`OK`) for them,
otherwise it is a warning (`WARN`) for them,
i.e. they get warned that someone else (admin or superior RA)
has changed something without their knowledge.
- Message 2 (`[OK/INFO]A`): If the currently logged-in user (performing the action)
is the administrator of the system, then it is a success message (`OK`)
for them, otherwise it is an informative message (`INFO`) for them,
i.e. the admin gets informed that a RA has done something.

You can use the special severity `NONE` to achieve that an event is
not logged, so `[NONE/...]` means that the event is not logged
if the currently logged-in user matches the target,
and `[.../NONE]` means that the event is not logged if the user
is not logged in or logged in as a user not matching the target.

Example: `[OK/NONE]RA(x)+[OK/NONE]A` could be used
to give the RA or the admin a success message (`OK`)
for their action, but the admin won't be notified if the
RA has changed it, and the RA won't be notified if the
admin changed it. An Exception is if the user is logged in
with both accounts (RA and admin) at the same time (which is
possible with OIDplus), then two log messages would be generated.

The severities `[NONE]` and `[NONE/NONE]` are invalid,
because they are meaningless (resulting in nothing being logged at all).

The online/offline dependency is only possible for the types `OIDRA`, `SUPOIDRA`, `RA`, and `A`,
but not for `OID` or `SUPOID`.

### Valid types

Besides the severity, the component has a payload in the form `Type(Value)`. 

`OID(x)` means: Save the log entry into the logbook of object "x".

`SUPOID(x)` means: Save the log entry into the logbook of the parent of object "x".

`OIDRA(x)` means: Save the log entry into the logbook of the RA of object "x".

`SUPOIDRA(x)` means: Save the log entry into the logbook of the RA that owns the superior object of "x".

`RA(x)` means: Save the log entry into the logbook of the RA "x".

`A` means: Save the log entry into the logbook of the administrator of the system.

### Escaping

Inside a severity block, you can escape []/\ with \

Inside the value, you can escape ()+\ with \

## Implementation

You can find the implementation in **includes/classes/OIDplusLogger.class.php**.

## Tests

To check if your mask codes have the correct syntax, run the tool **dev/logger/verify_maskcodes.phps**.
