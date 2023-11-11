
TODO
----

- allow changing automatic help page to describe which individual ranges stand for (is it already possible, by getting the argument object and then changing its help text?)
- everywhere getter and setter instead of accessing class member variables
- ipfm monitor: dygraph has MIT license
- *.conf files: /daten sollte nicht in den example conf's stehen. irgendwie anders machen (Aber achtung: wir symlinken die config files in unserem /etc )
- idea: a script that converts the output of an EXISTING nagios plugin into VNag Weboutput. So an arbitary Nagios script can be forwarded to other systems over HTTP
- make all plugins "web enabled"
- In the framework create an easy function, which generates a simple default HTML header and footer
- should error details, e.g. defective hard disks at the mdstat monitor be Verbosity=Summary, or Verbosity=AdditionalInformation ?
- idea for a new plugin: sudo /daten/scripts/tools/check_etc_perms | grep -v "world readable" | grep -v "world executable"
- should putputID, passwordOut and privateKey be a default argument? Then you can use encryption/signing for all plugins by default
- In re syntax checking/getopt:
  * Evaluate if PHP 7.1 (with getopt()'s $optind) is able to detect unexpected CLI parameters for us (so we can output a syntax warning)
  * Limit warning range numbers (avoid user adds too many, e.g. "-w A,B,C" although only 2 are allowed)

Future
------

- For arguments (warning/critical), also accept mixed UOMs, e.g. MB and %
- In re usage page:
  * Automatic creation of usage page. Which arguments are necessary?
  * Automatically generate syntax?
- Allow individual design via CSS
- Check if regex of validateLongOpt() is accurate

Unsure
------

- Automatically encrypt/sign via a global config setting?
- Should we also allow other UOMs?
