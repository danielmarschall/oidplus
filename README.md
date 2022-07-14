# About OIDplus 2.0

### What is OIDplus?
OIDplus is an OpenSource software solution by ViaThinkSoft that can be used by
Registration Authorities to manage and publish information about
Object Identifiers (OIDs), Globally Unique Identifiers (GUIDs), and much more.

More information as well as a demo version of OIDplus can be found here:
https://www.oidplus.com/

### Download and install OIDplus

##### Method A - Download using SVN:
    sudo apt-get update
    sudo apt-get install svn
    svn co https://svn.viathinksoft.com/svn/oidplus/trunk/

##### Method B - Download using Git:
    sudo apt-get update
    sudo apt-get install git
    git clone https://github.com/danielmarschall/oidplus.git

##### Method C - Download SVN snapshot:
Download a TAR.GZ file here: https://www.viathinksoft.com/projects/oidplus

### System requirements
- PHP compatible web server (tested with Apache 2, nginx and Microsoft IIS)
- PHP 7.0 or higher (tested till PHP version 8.1)
        with extension MySQLi, PostgreSQL, SQLite3, PDO, OCI8, or ODBC, depending on your database
- Supported databases:
        MySQL/MariaDB,
        PostgreSQL,
        SQLite3,
	Oracle,
        Microsoft SQL Server
- Independent of operating systems (tested with Windows, Linux and macOS X)

### Reporting a bug
You can file a bug report here:
- https://github.com/danielmarschall/oidplus/issues (recommended)
- https://www.viathinksoft.com/contact/daniel-marschall (for confidential reports)

### Support
If you have any questions or need help, please contact us:
https://www.viathinksoft.com/contact/daniel-marschall
