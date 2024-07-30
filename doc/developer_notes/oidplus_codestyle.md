# PHP and JavaScript codestyle guide for OIDplus

by Daniel Marschall

## Rule: Do not put opening curly brackets on a new line.

Explanation: This makes the code more compact.

Good:

```php
if (true) {
	// Do something
}
```

Bad:

```php
if (true)
{
	// Do something
}
```

## Rule: Indent control structures with tabs and not with whitespaces. (This collides with PSR-12, clause 2.4!)

Explanation:

There are many reasons why I dislike indentation by whitespaces.

1. The file size increases
2. You are forcing users to look at a 4-whitespace-indentation.
   However, some users want bigger indentation, and some users (e.g. with smaller screens)
   want smaller indentation. If you use tabs, then the users can choose their favorite tab width.
3. If you use a normal text editor, you need to press 4 times the spacebar in order to indent.
4. If you use a normal text editor, you can accidentally indent with 2 or 3 whitespaces.
   This leads to inconsistent indentation. With tabs, you cannot do that, you cannot ident
   with a "half tab".

The following mistake of indentation can only happen if you indent with whitespaces. It is impossible to happen if you indent with tabs:

```php
if (true) {
    cmd(1);
    cmd(2);
    cmd(3);
   cmd(4);
    cmd(5);
}
```

## Rule: If necessary, use whitespaces (in addition to tabs) to indent multi-line commands.

Example:

```php
if (true) {
	$test = execute_sql("select * ".
			    "from test ".
			    "where id=1");
}
```

To show the indentations of the above example, I mark tabs with "T" and whitespaces with "W".

```php
if (true) {
T	$test = execute_sql("select * ".
T	WWWWWWWWWWWWWWWWWWWW"from test ".
T	WWWWWWWWWWWWWWWWWWWW"where id=1");
}
```

This indentation with whitespaces forces the strings to exactly match the strings at the line above,
while the tab indentation indents the control structures (if/while/...) in the favorite tab width
of the developer.

## Rule: Operators that concatenate strings should not be separated by whitespaces.

Explanation:
Consider the following string:

```php
'The green house has 2 parking lots.'
```

We can clearly see that there are 7 words, since they are separated with 6 whitespaces.

If words are replaced with variables or formulas, then there shall be no
whitespaces between the operators.

Good:

```php
'The '.$color.' house has '.($count+1).' parking lots.'
```

Here you can still see that there are 7 words and 6 whitespaces.

Bad:

```php
'The ' . $color . ' house has ' . ($count + 1) . ' parking lots.'
```

Here you cannot clearly see the original message anymore.
There are 6 whitespaces (inside strings) which separate the words,
and 10 whitespaces (outside strings) which seaprate operators.
Unfortunately, many people consider this as the only correct way.

Note: Of course, `sprintf()` is even better and should be preferred
over concatenating strings.

## Rule: Use whitespaces between operators where it makes the most sense (i.e. to hint operator precedence)

Explanation: Programmers might forget about the operator precendence, so by using
whitespaces you can make it more clear. Use whitespaces the way it makes the code easy to read and
easy to understand.

Good:

```php
(4*5 + 6*7 == 3*7)
```

Bad:

```php
(4*5+6*7==3*7)

( 4 * 5 + 6 * 7 == 3 * 7 )
```

Very bad (misleading):

```php
(4 * 5+6 * 7==3 * 7)
```

However, this rule might conflict with the rule above that asks the programmer to
ommit whitespaces if words are replaced by a formula.
In this case it is okay to write a formula compact.

Good:

```php
'The house has '.(3*7+3).' parking lots.'
```

Bad:

```php
'The house has '.(3*7 + 3).' parking lots.'
```

## Rule: Mark test/debug code with the word SPONGE.

If you include code for temporary tests/debugging, which must
not be commited to GIT/SVN, then mark it with the word "SPONGE".

If possible, implement things like SVN hooks on the server side to reject
incoming commits that contain this word.

Explanation: This method was invented by Terry A. Davis.
It illustrated an operation where the doctor counts the sponges before and
after the operation. If a sponge is missing, it might be still in the patient.

While the doctor must take care not to leave a sponge in the patient,
we as developers must not forget to leave a sponge in our code.

## Rule: If you have an empty body, add an explanation comment why it is empty

Good:

```php
try {
	optionalCleaningWork();
} catch (Exception $e) {
	// Ignore errors, because this step is not critical
}
```

Bad:

```php
try {
	optionalCleaningWork();
} catch (Exception $e) {}
```

## Rule: Do not use whitespaces between round brackets at method calls or array accesses

Explanation: If method calls and array accesses are written in a distinct style,
then you can use a simple searching using grep, or with the search of a simple text editor.
For example `execSQL(` will find all method calls for the method `execSQL`.
If someone would call `execSQL` as `execSQL (`, then the search would not work.

Good:

```php
execSQL($conn, "select * from test");
$ary[3] = "test";
```

Bad:

```php
execSQL ( $conn, "select * from test" );
$ary [3] = "test";
```

## Rule: Try to use whitespaces where it makes sense to make the code easier to read

Not so good:

```php
if ($aaa == 1 && $bbb == 1) cmd(1);
if ($aaa == 2 && $bbb == 2) cmd(2);
if ($aaa == 3 && $bbb == 3) cmd(3);
if ($aaa == 4 && $bbb == 4) cmd(4);
if ($aaa == 5 && $bbb == 5) cmd(5);
if ($aaa == 6 && $bbb == 6) cmd(6);
if ($aaa == 7 && $bbb == 7) cmd(7);
if ($aaa == 8 && $bbb == 8) cmd(8);
if ($aaa == 9 && $bbb == 9) cmd(9);
if ($aaa == 10 && $bbb == 10) cmd(10);
if ($aaa == 11 && $bbb == 11) cmd(11);
```

Better:

```php
if ($aaa ==  1 && $bbb ==  1) cmd( 1);
if ($aaa ==  2 && $bbb ==  2) cmd( 2);
if ($aaa ==  3 && $bbb ==  3) cmd( 3);
if ($aaa ==  4 && $bbb ==  4) cmd( 4);
if ($aaa ==  5 && $bbb ==  5) cmd( 5);
if ($aaa ==  6 && $bbb ==  6) cmd( 6);
if ($aaa ==  7 && $bbb ==  7) cmd( 7);
if ($aaa ==  8 && $bbb ==  8) cmd( 8);
if ($aaa ==  9 && $bbb ==  9) cmd( 9);
if ($aaa == 10 && $bbb == 10) cmd(10);
if ($aaa == 11 && $bbb == 11) cmd(11);
```
