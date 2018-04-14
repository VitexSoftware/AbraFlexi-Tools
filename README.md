# FlexiBee-TestingTools

Set of commandline tools related to testing FlexiBee functionality

![Project Logo](https://raw.githubusercontent.com/VitexSoftware/FlexiBee-TestingTools/master/projec-log.png)

FlexiBee Get
------------

Obtain record data from FlexiBee


Usage:

    fbget -eevidence-name -iRowID [-u] [-cpath] [column names to show] 

**-p** path to custom config file
**-u** show record URL 

Example:

```shell
flexibeeget -e adresar -i 333 kod
```

```json
{                                                                                                                                                                              
    "id": "333",                                                                                                                                                               
    "kod": "F\u00da - 288",                                                                                                                                                    
    "kontakty": []                                                                                                                                                             
}
```

FlexiBee PUT
------------

Insert or update record data in FlexiBee

Usage:

    fbput -eevidence -iRowID [-cpath] [-u] [--colum-name=value] [--colum-name2=value2] ...

**-p** path to custom config file
**-u** show record URL 

Example:

fbput.php --evidence adresar -i333 --nazev=Zmeneno

```
https://demo.flexibee.eu:5434/c/demo/adresar/333
{"winstrom":{"@version":"1.0","success":"true","stats":{"created":"0","updated":"1","deleted":"0","skipped":"0","failed":"0"},"results":[{"id":"333","request-id":"333","ref":"
```

FlexiBee company Copy
---------------------

Copy one FlexiBee company to another FlexiBee

Usage:

    fbcp https://user:password@flexibee.source.cz:5434/c/firma_a_s_  https://user:password@flexibee.source.cz:5434/c/firma_a_s_  

Example:

```
fbcp https://lgn:pwd@company.flexibee.eu:5434/c/company_name https://lgn2:pwd2@vitexsoftware.flexibee.eu:5434/c/company_name
04/14/18 13:57:18 `FlexiPeeHP\Company`  ⓘ saving backup
04/14/18 13:57:25 `FlexiPeeHP\Company`  ❁ backup saved
04/14/18 13:57:26 `FlexiPeeHP\Company`  ⓘ Remove company before restore
04/14/18 13:57:27 `FlexiPeeHP\Company`  ☠ JSON Decoder: Syntax error
04/14/18 13:57:27 `FlexiPeeHP\Company`  ⚙ ok
04/14/18 13:57:27 `FlexiPeeHP\Company`  ❁ restore begin
04/14/18 13:57:58 `FlexiPeeHP\Company`  ❁ backup restored
```

Configuration file example
--------------------------

```json
{
    "FLEXIBEE_URL": "https:\/\/demo.flexibee.eu:5434",
    "FLEXIBEE_LOGIN": "winstrom",
    "FLEXIBEE_PASSWORD": "winstrom",
    "FLEXIBEE_COMPANY": "demo"
}
```
Default config file location is /etc/flexibee/client.json

Installation
------------

To install tools into vendor/bin please use [composer](https://getcomposer.org/):

    composer require vitexsoftware/flexibee-testing-tools

For Debian or Ubuntu please use [repo](http://vitexsoftware.cz/repos.php):

    wget -O - http://v.s.cz/info@vitexsoftware.cz.gpg.key|sudo apt-key add -
    echo deb http://v.s.cz/ stable main > /etc/apt/sources.list.d/vitexsoftware.list
    aptitude update
    aptitude install flexibee-testing-tools

![Debian Installation](https://raw.githubusercontent.com/VitexSoftware/FlexiBee-TestingTools/master/debian-screenshot.png "Debian example")

Note: Debian package depends on [php-flexibee-config](https://github.com/VitexSoftware/php-flexibee-config) package

Thanks to:
----------

 * [PureHTML](https://purehtml.cz/) & [Spoje.Net]( https://spoje.net/ )  for support
 * [Abra](https://abra.eu) for [FlexiBee](https://flexibee.eu/)
