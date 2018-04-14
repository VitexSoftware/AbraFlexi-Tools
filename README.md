# FlexiBee-TestingTools

Set of commandline tools related to testing FlexiBee functionality


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

fbput.php --evidence adresar -i333 --nazev=Zmeneno"

```
https://demo.flexibee.eu:5434/c/demo/adresar/333
{"winstrom":{"@version":"1.0","success":"true","stats":{"created":"0","updated":"1","deleted":"0","skipped":"0","failed":"0"},"results":[{"id":"333","request-id":"333","ref":"
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
