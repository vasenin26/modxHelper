# modxHelper

Small console helper for create snippets, chunks and templates

For use this you need php in console. Put helper file in your project folder and call it from console

```` php
php modxhelper.php
````

helper works in the input wait mode, follow the tips to work.

You can see available commands if send 'help' command or empty input.

#Fast input

You can send all options for command after command name

## Example create chunk 
````
D:\www\projectfolder>php modxhelper.php

Hi, i'm ModHelper. Input command
--> chunk test
Chunk created
````

## Example create new template
````
D:\www\projectfolder>php modxhelper.php

Hi, i'm ModHelper. Input command
--> template ? test TestName
Template created
````

# Elements namespace

You can change name space for your elements, change variable $namespace in helper file

```` php
$nameSpace = 'modxParts';
````

files for your elements will be created in folder

````
[MODX_CORE_PATH]/components/[NAME_SPACE]/[ELEMENT_TYPE]/[ELEMENT_NAME]