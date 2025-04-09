# Tools for AbraFlexi

Set of commandline tools for interaction with AbraFlexi server

[![wakatime](https://wakatime.com/badge/user/5abba9ca-813e-43ac-9b5f-b1cfdf3dc1c7/project/de981fb2-c103-4203-a51a-104ed0489608.svg)](https://wakatime.com/badge/user/5abba9ca-813e-43ac-9b5f-b1cfdf3dc1c7/project/de981fb2-c103-4203-a51a-104ed0489608)

![Project Logo](abraflexitools.svg?raw=true)

AbraFlexi Get
-------------

Obtain record data from AbraFlexi


Usage:

    fbget -eevidence-name -iRowID [-u] [-cpath] [column names to show] 

**-p** path to custom config file
**-u** show record URL 

Example:

```shell
~$ fbget -v -u -c /etc/abraflexi/localhost-client.json -e adresar -i 666 kod nazev
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

AbraFlexi PUT
-------------

Insert or update record data in AbraFlexi

Usage:

    fbput -eevidence -iRowID [-cpath] [-u] [--colum-name=value] [--colum-name2=value2] ...

**-p** path to custom config file
**-u** show record URL 

Example:

fbput.php --evidence adresar -i 333 -u --nazev=Zmeneno

```
https://demo.abraflexi.eu:5434/c/demo/adresar/333
{"winstrom":{"@version":"1.0","success":"true","stats":{"created":"0","updated":"1","deleted":"0","skipped":"0","failed":"0"},"results":[{"id":"333","request-id":"333","ref":"
```

![fbcp](fbcp.svg?raw=true)

AbraFlexi company Copy
---------------------

Copy one AbraFlexi company to another AbraFlexi

Usage:

    fbcp https://user:password@abraflexi.source.cz:5434/c/firma_a_s_  https://user:password@abraflexi.destination.cz:5434/c/firma_a_s_  [production] 

Use **production** parameter to keep EET,  Auto Sending Mails and WebHooks enabled in restored company.

Example:

```
fbcp https://lgn:pwd@company.abraflexi.eu:5434/c/company_name https://lgn2:pwd2@vitexsoftware.abraflexi.eu:5434/c/company_name
04/14/18 13:57:18 `FlexiPeeHP\Company`  ⓘ saving backup
04/14/18 13:57:25 `FlexiPeeHP\Company`  ❁ backup saved
04/14/18 13:57:26 `FlexiPeeHP\Company`  ⓘ Remove company before restore
04/14/18 13:57:27 `FlexiPeeHP\Company`  ☠ JSON Decoder: Syntax error
04/14/18 13:57:27 `FlexiPeeHP\Company`  ⚙ ok
04/14/18 13:57:27 `FlexiPeeHP\Company`  ❁ restore begin
04/14/18 13:57:58 `FlexiPeeHP\Company`  ❁ backup restored
```


Create New Company in AbraFlexi
------------------------------

```
    fbnc  new_company_name
    fbnc  https://user:password@abraflexi.source.cz:5434/c/nova_firma_a_s_
```

Delete Company in AbraFlexi
--------------------------

```
    fbdc company_to_delete
    fbdc https://user:password@abraflexi.source.cz:5434/c/smazat_firma_a_s_
```

Configuration file example
--------------------------

```json
{
    "ABRAFLEXI_URL": "https:\/\/demo.abraflexi.eu:5434",
    "ABRAFLEXI_LOGIN": "winstrom",
    "ABRAFLEXI_PASSWORD": "winstrom",
    "ABRAFLEXI_COMPANY": "demo"
}
```
Default config file location is /etc/abraflexi/client.json ( also provided by [abraflexi-client-config](https://github.com/VitexSoftware/abraflexi-client-config) debian package )

WebHooks Wipe
-------------

Drop all webHooks

```
    fbwhwipe [custom config file] 
```

WebHook establish
-----------------

Register new webhook in AbraFlexi

```
    fbwh  http://webhook.processor/url [xml|json] [custom/config.json]
```

Fake Address Generator
----------------------


```shell
abraflexi-fake-address --config=../tests/client.json -i 10
```

create 10 fake address


Benchmark
---------

Options:

 * -p   - prepare database for test
 * -c   - num of cycles
 * -s   - sleep x seconds after each operation

```shell
abraflexi-benchmark -p -c 10 -d 10
```

Count time of several operations speed upon given company/database.

![Result](benchmark-result.png?raw=true)


Certificate Updater
--------------------

Generate or renew HTTPS certificate

```shell
abraflexi-certbot
```


## MultiFlexi

Tools for AbraFlexi is ready for run as [MultiFlexi](https://multiflexi.eu) application.
See the full list of ready-to-run applications within the MultiFlexi platform on the [application list page](https://www.multiflexi.eu/apps.php).

[![MultiFlexi App](https://github.com/VitexSoftware/MultiFlexi/blob/main/doc/multiflexi-app.svg)](https://www.multiflexi.eu/apps.php)

## Installation

There is repository for Debian/Ubuntu Linux distributions:

```shell
sudo apt install lsb-release wget apt-transport-https bzip2

wget -qO- https://repo.vitexsoftware.com/keyring.gpg | sudo tee /etc/apt/trusted.gpg.d/vitexsoftware.gpg
echo "deb [signed-by=/etc/apt/trusted.gpg.d/vitexsoftware.gpg]  https://repo.vitexsoftware.com  $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/vitexsoftware.list
sudo apt update

sudo apt install abraflexi-tools
```

![Debian Installation](https://raw.githubusercontent.com/VitexSoftware/AbraFlexi-Tools/master/debian-screenshot.png "Debian example")

We use:

  * [PHP Language](https://secure.php.net/)
  * [PHP AbraFlexi](https://github.com/Spoje-NET/php-abraflexi) - Library for Interaction with [AbraFlexi](https://abraflexi.eu/)
  * [Ease Core](https://github.com/VitexSoftware/php-ease-core) - Glue & Tool Set 

Thanks to:
----------

 * [PureHTML](https://purehtml.cz/) & [Spoje.Net]( https://spoje.net/ )  for support
 * [Abra](https://abra.eu) for [AbraFlexi](https://abraflexi.eu/)
