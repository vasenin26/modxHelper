# modxHelper

Small console helper for create snippets, chunks and templates

For use this you need php in console. Put helper file in your project folder and call it from console

```` php
php modxhelper.php
````

Helper works in the interactive mode, follow the tips to work. You can also transfer all commands as call parameters.

````
D:\www\projectfolder>php modxhelper.php clearCache
MODX cache successfully cleared
````

You can see available commands if send 'help' command or empty input.

# Fast input

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

# Available Commands

````
D:\www\projectfolder>php modxhelper.php help
Available commands:
chunk - create chunk
snippet - create snippet
template - create template
clear|clearCache - clear cache folder
regNameSpace [namespace] - register new namespace
schema - generate database map from xPDO schema
snapshot - make dump of modDocument, modChunk and modSnippet into files for your git commit. It help to merge database changes with the remote site copy.
lexicon - manage lexicons

````

# Elements namespace

You can change name space for your elements, change constant MODX_HELPER_NAME_SPACE in helper file

```` php
define('MODX_HELPER_NAME_SPACE', 'modxParts');
````

files for your elements will be created in folder

````
[MODX_CORE_PATH]/components/[NAME_SPACE]/[ELEMENT_TYPE]/[ELEMENT_NAME]