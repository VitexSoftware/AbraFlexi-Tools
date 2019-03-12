# FlexiBee-TestingTools

Set of commandline tools related for testing FlexiBee functionality

![Project Logo](https://raw.githubusercontent.com/VitexSoftware/FlexiBee-TestingTools/master/project-logo.png)

FlexiBee Get
------------

Obtain record data from FlexiBee


Usage:

    fbget -eevidence-name -iRowID [-u] [-cpath] [column names to show] 

**-p** path to custom config file
**-u** show record URL 

Example:

```shell
~$ fbget -v -u -c /etc/flexibee/localhost-client.json -e adresar -i 666 kod nazev
https://localhost:5434/c/spoje_net_s_r_o_/adresar/666&detail=custom:kod,nazev
```

```json
{
    "external-ids": [
        "ext:subreg:36699",
        "ext:ipex:58487"
    ],
    "id": "666",
    "kod": "VITEX",
    "nazev": "V\u00edt\u011bzslav Dvo\u0159\u00e1k",
    "kontakty": [
        {
            "id": "2371"
        }
    ]
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

fbput.php --evidence adresar -i 333 -u --nazev=Zmeneno

```
https://demo.flexibee.eu:5434/c/demo/adresar/333
{"winstrom":{"@version":"1.0","success":"true","stats":{"created":"0","updated":"1","deleted":"0","skipped":"0","failed":"0"},"results":[{"id":"333","request-id":"333","ref":"
```

FlexiBee company Copy
---------------------

Copy one FlexiBee company to another FlexiBee

Usage:

    fbcp https://user:password@flexibee.source.cz:5434/c/firma_a_s_  https://user:password@flexibee.destination.cz:5434/c/firma_a_s_  [production] 

Use **production** parameter to keep EET,  Auto Sending Mails and WebHooks enabled in restored company.

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


Create New Company in FlexiBee
------------------------------

    fbnc  new_company_name
    fbnc  https://user:password@flexibee.source.cz:5434/c/nova_firma_a_s_
  

Delete Company in FlexiBee
--------------------------

    fbdc company_to_delete
    fbdc https://user:password@flexibee.source.cz:5434/c/smazat_firma_a_s_

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
Default config file location is /etc/flexibee/client.json ( also provided by [php-flexibee-config](https://github.com/VitexSoftware/php-flexibee-config) debian package )

WebHooks Wipe
-------------

Drop all webHooks

    fbwhwipe [custom config file] 


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

We use:

  * [PHP Language](https://secure.php.net/)
  * [FlexiPeeHP](https://github.com/Spoje-NET/FlexiPeeHP) - Library for Interaction with [FlexiBee](https://flexibee.eu/)
  * [EaseFramework](https://github.com/VitexSoftware/EaseFramework) - Glue & Tool Set 


Thanks to:
----------

 * [PureHTML](https://purehtml.cz/) & [Spoje.Net]( https://spoje.net/ )  for support
 * [Abra](https://abra.eu) for [FlexiBee](https://flexibee.eu/)
