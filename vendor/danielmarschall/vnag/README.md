
[![VNag](https://raw.githubusercontent.com/danielmarschall/vnag/master/logos/vnag_logo_400.png "VNag")](https://www.viathinksoft.com/projects/vnag "VNag")

**VNag** (**V**iaThinkSoft **Nag**ios) is a framework for PHP which allows developing plugins for Nagios-compatible systems (e.g. Icinga2), following the [development guidelines](https://nagios-plugins.org/doc/guidelines.html "development guidelines").

The download package contains documentation, examples and many new plugins, e.g. to check WordPress installations.

Beside developing normal Nagios/Icinga plugins (PHP will be called via CLI), you can develop plugins which are additionally served over HTTP.

- The plugins can be shown via a HTTP-Demon (e.g. Apache) in user's browsers. Beside the output for Nagios (Summary, Verbose information, Performance data), user-defined HTML output can be added, e.g. to complete your Nagios output with more diagrams, pictures, etc. Only one code base is required!

- The web-output contains a hidden machine readable part, which can be read out with the "WebReader" plugin of VNag. The WebReader plugins reads the machine readable part and outputs the data in the output format which can be read by Nagios. This way, you can monitor things like WordPress version at systems where you have no shell access and/or without Nagios installed.

- It is also possible to create websites which only have a machine readable part (i.e. you include your VNag output in your existing website). This machine readable part can be optionally signed and/or encrypted.

Included plugins
----------------

VNag comes with the following plugins pre-installed (in the bin directory):

- **4images_version**: Checks 4images installations for updates.
- **aastra_430_voicemail**: Checks Aastra 430 phone system for new voicemails.
- **disk_running**: Checks if harddisks which do not have SMART capability are online
- **file_timestamp**: Warns when files are not modified withhin a specific interval/age.
- **gitlab_version**: Checks GitLab install~ations for updates.
- **hp_smartarray**: Checks disk and controller status of HP SmartArray RAID controllers.
- **ipfm**: Checks the log files of the tool "ipfm" and warns when the measured traffic exceeds a given limit.
- **joomla_version**: checks Joomla installations for updates.
- **last**: Checks the output of the tool "last" and warns when logins from suspicious IP adresses are detected.
- **mdstat**: Parses the output of "/proc/mdstat" and warns when drives inside a RAID array have failed.
- **mediawiki_version**: Checks MediaWiki installations for updates.
- **megaraid**: Checks MegaRAID (MegaCLI64) RAID arrays for degraded arrays, SMART warnings, or failing batteries.
- **minecraft_java_version**: Checks the version of a local Minecraft Java server for updates.
- **net2ftp_version**: Checks [net2ftp](https://www.net2ftp.com/) installations for updates.
- **nextcloud_version**: Checks Nextcloud installations for updates.
- **nocc_version**: Checks NOCC webmail installations for updates.
- **openbugbounty**: Checks if your domains are listed at OpenBugBounty.org.
- **open_deleted_files**: Checks if there are deleted files which have open file handles (leaked disk space).
- **owncloud_version**: Checks ownCloud installations for updates.
- **phpbb_version**: Checks phpBB installations for updates.
- **phpmyadmin_version**: Checks phpMyAdmin installations for updates.
- **phppgadmin_version**: Checks phpPgAdmin installations (original or ReimuHakurei fork) for updates.
- **ping**: Pings a hostname or IP address.
- **pmwiki_version**: Checks PmWiki installations for updates.
- **roundcube_version**: Checks RoundCube installations for updates.
- **smart**: Checks the SMART attributes of harddrives and warns when bad attributes are detected.
- **viewvc_version**: Checks [ViewVC](https://github.com/viewvc/viewvc/) installations for updates.
- **virtual_mem**: Checks the amount of virtual memory (physical memory + swap).
- **webreader**: Reads the output of another VNag plugin transferred over HTTP.
- **websvn_version**: Checks [WebSVN](https://github.com/websvnphp/websvn/) installations for updates.
- **wordpress_version**: Checks WordPress installations for updates.
- **x509_expire**: Warns when X.509 (PEM) certificate files reach a specific age.

Use-case diagrams
-----------------

1. [Simple case](https://raw.githubusercontent.com/danielmarschall/vnag/master/doc/vnag_model_1.png "Simple case"): Nagios/CLI checks an object
2. [Extended case](https://raw.githubusercontent.com/danielmarschall/vnag/master/doc/vnag_model_2.png "Extended case"): Nagios/CLI checks an object, and a user can additionally view the status in a web-browser
3. [More extended case](https://raw.githubusercontent.com/danielmarschall/vnag/master/doc/vnag_model_3.png "More extended case"): Nagios/CLI checks an object, a user can additionally view the status in a web-browser, and another Nagios/CLI instance can remotely access the output of the primary Nagios/CLI

Create your own plugins
-----------------------

To create your own plugins, you can look at the source codes of the existing plugins
to get inspiration and use them as templates.

Also, a small documentation is found in
[doc/Plugin_Development.md](https://github.com/danielmarschall/vnag/blob/master/doc/Plugin_Development.md).

If you have created useful plugins, we would be happy if you could share them with us!
