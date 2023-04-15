
Path functions in OIDplus
=========================

How to get relative/absolute paths, e.g. when you are in a plugin?

Here is an overview of the methods `OIDplus::localpath()` and `OIDplus::webpath()`:

                              +---------------------------------------------------------------------------------
                              |      Local path                               URL
    --------------------------+---------------------------------------------------------------------------------
                              |
       Get relative path      |      OIDplus::localpath(null, true)           OIDplus::webpath(null, OIDplus::PATH_RELATIVE)
       to base directory      |      Example: ../                             Example: ../
                              |                                               or
                              |                                               OIDplus::webpath(null, OIDplus::PATH_RELATIVE_TO_ROOT)
                              |                                               Example: /oidplus/
                              |
                              |
       Get relative path      |      OIDplus::localpath('file.jpg', true)     OIDplus::webpath('file.jpg', OIDplus::PATH_RELATIVE)
       to any file/dir        |      Example: xyz/file.jpg                    Example: xyz/file.jpg
                              |                                               or
                              |                                               OIDplus::webpath('file.jpg', OIDplus::PATH_RELATIVE_TO_ROOT)
                              |                                               Example: /oidplus/xyz/file.jpg
                              |
                              |
       Get absolute path      |      OIDplus::localpath(null, false)          OIDplus::webpath(null, OIDplus::PATH_ABSOLUTE)
       to base directory      |      Example: /var/www/oidplus/               Example: https://www.example.com/oidplus/
                              |
                              |
       Get absolute path      |      OIDplus::localpath('file.jpg', true)     OIDplus::webpath('file.jpg', OIDplus::PATH_ABSOLUTE)
       to any file/dir        |      Example: /var/www/oidplus/xyz/file.jpg   Example: https://www.example.com/oidplus/xyz/file.jpg
                              |
    --------------------------+---------------------------------------------------------------------------------
    

These function ensure that directories end with a trailing path delimiter.

If you want to prefer the canonical system url (that can be set with the base config setting `CANONICAL_SYSTEM_URL`),
then you can replace `OIDplus::PATH_ABSOLUTE` with `OIDplus::PATH_ABSOLUTE_CANONICAL`.

Usage examples
--------------

Here are some ways to test it:
    
    echo "Rel Webpath null: ";print_r(OIDplus::webpath(null,OIDplus::PATH_RELATIVE));echo "\n";
    echo "Rel Webpath non-existing: ";print_r(OIDplus::webpath('xxx',OIDplus::PATH_RELATIVE));echo "\n";
    echo "Rel Webpath existing: ";print_r(OIDplus::webpath('test',OIDplus::PATH_RELATIVE));echo "\n";
    echo "Rel Webpath self: ";print_r(OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE));echo "\n";
    
    echo "Abs Webpath null: ";print_r(OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE));echo "\n";
    echo "Abs Webpath non-existing: ";print_r(OIDplus::webpath('xxx',OIDplus::PATH_ABSOLUTE));echo "\n";
    echo "Abs Webpath existing: ";print_r(OIDplus::webpath('test',OIDplus::PATH_ABSOLUTE));echo "\n";
    echo "Abs Webpath self: ";print_r(OIDplus::webpath(__DIR__,OIDplus::PATH_ABSOLUTE));echo "\n";
    
    echo "Rel localpath null: ";print_r(OIDplus::localpath(null,true));echo "\n";
    echo "Rel localpath non-existing: ";print_r(OIDplus::localpath('xxx',true));echo "\n";
    echo "Rel localpath existing: ";print_r(OIDplus::localpath('test',true));echo "\n";
    echo "Rel localpath self: ";print_r(OIDplus::localpath(__DIR__,true));echo "\n";
    
    echo "Abs localpath null: ";print_r(OIDplus::localpath(null,false));echo "\n";
    echo "Abs localpath non-existing: ";print_r(OIDplus::localpath('xxx',false));echo "\n";
    echo "Abs localpath existing: ";print_r(OIDplus::localpath('test',false));echo "\n";
    echo "Abs localpath self: ";print_r(OIDplus::localpath(__DIR__,false));echo "\n";
