
OIDPLUS FAIL2BAN INTEGRATION
============================

fail2ban can be used to watch your log files and
block network addresses that try to break into
your system using brute force.


Configuration
-------------

In the OIDplus admin control panel, you need to set the following settings to "1" (enabled)
- log_failed_admin_logins
- log_failed_ra_logins

Also, please make sure that in the menu tree under "Plugins --> Logger" the plugin
"Simple Log File" is working (not marked in gray color).
If it is gray, please click "Logger" and look at the error message.
You probably need to set rights to the file "userdata/logs/oidplus.log" using chmod.


/etc/fail2ban/jail.d/vts-oidplus.conf (please adjust it to your needs!)
-----------------------------------------------------------------------

    [vts-oidplus]
    enabled  = true
    port     = http,https
    filter   = vts-oidplus
    logpath  = /......../userdata/logs/oidplus.log
    # action   = iptables-allports[name=OIDPLUS, protocol=tcp]
    action   = iptables-multiport[name=OIDPLUS, port="80,443"]
    maxretry = 20


/etc/fail2ban/filter.d/vts-oidplus.conf
---------------------------------------

    # Fail2Ban configuration file for OIDplus
    #
    # Author: Daniel Marschall
    #
    # $Revision: 1
    #
    
    [Definition]
    
    # Option:  failregex
    # Notes.:  regex to match the password failure messages in the logfile. The
    #          host must be matched by a group named "host". The tag "<HOST>" can
    #          be used for standard IP/hostname matching and is only an alias for
    #          (?:::f{4,6}:)?(?P<host>[\w\-.^_]+)
    # Values:  TEXT
    #
    failregex = [[]<HOST>[]] Failed login to (admin|RA) account
    
    # Option:  ignoreregex
    # Notes.:  regex to ignore. If this regex matches, the line is ignored.
    # Values:  TEXT
    #
    ignoreregex =
    
    [Init]
    
    datepattern = ^%%Y-%%m-%%d %%H:%%M:%%S
