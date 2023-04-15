
Install OIDplus to Microsoft IIS
================================

(1) Install IIS using "Programs and Features" in the control panel
( Source: https://docs.microsoft.com/en-us/iis/application-frameworks/scenario-build-a-php-website-on-iis/configuring-step-1-install-iis-and-php )

Install on a Windows Client (Windows 7/8/10 etc.):
- At the command promt, run appwiz.cpl
- Click Turn Windows features on or off.
- In the Windows Features dialog box, click Internet Information Services, note the preselected features that are installed by default, and then select CGI. This selection also installs FastCGI, which is recommended for PHP applications.
- Click OK.
- To verify that IIS installed successfully, type the following into a web browser: http://localhost
- You see the default IIS Welcome page.
		
Install on a Windows Server:
- Open the Window Server Manager
- In Server Manager, select Dashboard, and click Add roles and features.
- In the Add Roles and Features Wizard, on the Before You Begin page, click Next.
- On the Select Installation Type page, select Role-based or Feature-based Installation and click Next
- On the Select Destination Server page, select Select a server from the server pool, select your server, and click Next.
- On the Select Server Roles page, select Web Server (IIS), and then click Next.
- On the Select Features page, note the preselected features that are installed by default, and then select CGI. This selection also installs FastCGI, which is recommended for PHP applications.
- Click Next.
- On the Web Server Role (IIS) page, click Next.
- On the Select Role Services page, note the preselected role services that are installed by default, and then click Next.
- Note: You only have to install the IIS 8 default role services for a static-content web server.
- On the Confirm Installation Selections page, confirm your selections, and then click Install.
- On the Installation Progress page, confirm that your installation of the Web Server (IIS) role and required role services completed successfully, and then click Close.
- To verify that IIS installed successfully, type the following into a web browser: http://localhost
- You should see the default IIS Welcome page.

(2) Install PHP using the Web Platform Installer
Go to https://www.microsoft.com/web/downloads/platform.aspx
Click "Install this extension" to download the setup
Run WebPlatformInstaller_x64_en-US.msi
Accept the license aggreement
In the searchbox at the top right, enter "PHP" and press return
Select PHP 7.4 or similar

Note: The Web Platform Installer sometimes exits with errors (signature verification failed etc),
but you can ignore it, as PHP is actually successfully installed!

(3) Place OIDplus here:

	C:\inetpub\wwwroot\oidplus\

(4) Open CMD as administrator and run following command to unlock various things in the web.config file:

	%windir%\system32\inetsrv\appcmd.exe unlock config -section:system.webServer/security/authentication/anonymousAuthentication

(5) Edit "C:\Program Files (x86)\PHP\v7.4\php.ini" (you need to run Notepad as administrator)

Make sure that following extensions are enabled by uncommenting the lines:

	extension=curl
	extension=openssl
	extension=mbstring

And depending on your database connection, you need one of the following extensions:

	extension=mysqli      (if you want to use MySQL/MariaDB)
	extension=pgsql       (if you want to use PostgreSQL)
	extension=sqlite3     (if you want to use SQLite)
	extension=pdo_mysql   (if you want to use MySQL/MariaDB via PDO)
	extension=pdo_pgsql   (if you want to use PostgreSQL via PDO)
	extension=pdo_sqlite  (if you want to use SQLite via PDO)
	extension=odbc        (if you want to use ODBC)
	
Verify that the "date.timezone" setting has the correct value.

(6) Open http://localhost/oidplus and follow the setup instructions


How to install PHP 8.1 (not supported by Microsoft)?
----------------------------------------------------

- Download PHP for Windows: https://windows.php.net/download#php-8.1

- Unpack it to C:\Program Files (x86)\PHP\v8.1\

- Run  %windir%\system32\inetsrv\InetMgr.exe
    - Open Handler Mappings. Change PHP_via_FastCGI to C:\Program Files (x86)\PHP\v8.1\php-cgi.exe
    - Open FastCGI settings and add PHP 8.1 , make sure to copy the settings like they are done in 7.4

	