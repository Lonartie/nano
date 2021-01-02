# Interface design

## Table of contents
- [Using a service](#using-a-service)
    - [Retrieve a list of all services](#retrieve-a-list-of-all-services)
    - [Retrieve a list of all versions](#retrieve-a-list-of-all-versions)
    - [Retrieve a list of all methods](#retrieve-a-list-of-all-methods)
    - [Calling a service method](#calling-a-service-method)

## Using a service

### Retrieve a list of all services
#### Request
```url
GET nano.php
```
#### Response
```json
{
    "success": true,
    "data": {
        "info": "append a valid service name to the url. To see the system documentation use '~/docs' instead of '~/nano.php'",
        "services": {
            "service_manager": "managing services",
            "service_companions": "Manages companion relations"
        }
    }
}
```

### Retrieve a list of all versions
#### Request
```url
GET nano.php/service_manager
```
#### Response
```json
{
    "success": true,
    "data": {
        "info": "append a valid version to the url",
        "versions": {
            "0.0.1": "2020-12-25 16:29:00"
        }
    }
}
```

### Retrieve a list of all methods
#### Request
```url
GET nano.php/service_manager/0.0.1
```
#### Response
```json
{
    "success": true,
    "data": {
        "info": "append a valid method + parameters to the url. syntax: '~/nano.php/$service/$version/$method/$parameter1:$value1/$parameter2:$value2'",
        "methods": {
            "add": [
                "bin"
            ],
            "remove_version": [
                "name",
                "version"
            ],
            "remove_all": [
                "name"
            ]
        }
    }
}
```

### Calling a service method
#### Request
```url
GET nano.php/service_manager/0.0.1/remove_version/name:service_companions/version:0.0.1
```
#### Response
```json
{
    "success": true
}
```