# FlexiBee-TestingTools

Set of commandline tools related to testing FlexiBee functionality


FlexiBee Get
------------

Obtain record data from FlexiBee


Usage:

    flexibeeget -e|--evidence evidence-name -i|--id rowID [-u|--show-url] [-c|--config path] [column names to show] 

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
