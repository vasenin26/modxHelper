<?php

if (!defined('MODX_CONFIG_KEY')) {
    define('MODX_CONFIG_KEY', 'config');
}

if (!defined('MODX_CORE_PATH')) {
    define('MODX_CORE_PATH', './core/');
}

define('SNAPSHOT_FOLDER', MODX_CORE_PATH . 'snapshot/');

require_once(MODX_CORE_PATH . 'model/modx/modx.class.php');

define('MODX_HELPER_NAME_SPACE', 'modxParts');

//cli command support
$command = empty($argv) ? null : array_slice($argv, 1);

(new ModxHelper($nameSpace))->waitCommand($command);

/* --- helper class --- */

Class ModxHelper
{

    private $modx;
    private $nameSpace;

    public function __construct($nameSpace)
    {
        $this->modx = new modX();
        $this->modx->initialize('mgr');

        $this->nameSpace = defined('MODX_HELPER_NAME_SPACE') ? MODX_HELPER_NAME_SPACE : 'modxParts';

    }

    public function waitCommand($command = null)
    {

        $command = $this->waitInput('Hi, i\'m ModHelper. Input command', true, $command);

        $this->runCommand($command[0], array_slice($command, 1));

    }

    public function runCommand($command, $options)
    {

        $commandName = 'com' . $this->firstToUpper($command);

        if (!method_exists($this, $commandName)) {
            echo "Command [$command] not exist\n";
            $commandName = 'comHelp';
        }

        call_user_func_array([$this, $commandName], $options);

    }

    /* commands */

    public function comChunk(...$args)
    {

        $chunkName = $this->waitInput('Input chunk name', false, $this->takeArgument($args));

        $chunk = $this->modx->getObject('modChunk', ['name' => $chunkName]);
        $chunkContent = '';

        if (empty($chunk)) {
            $chunk = $this->modx->newObject('modChunk');
            $chunk->name = $chunkName;
        } else {
            $chunkContent = $chunk->snippet;
            $chunk->snippet = null;
        }

        $chunk->source = 1;
        $chunk->static = 1;
        $chunk->static_file = $this->createFile('chunks/' . $chunkName . '.html', $chunkContent);

        $chunk->save();

        echo 'Chunk created';

    }

    public function comSnippet(...$args)
    {

        $snippetName = $this->waitInput('Input snippet name', false, $this->takeArgument($args));

        $snippet = $this->modx->getObject('modSnippet', ['name' => $snippetName]);
        $snippetContent = '';

        if (empty($snippet)) {
            $snippet = $this->modx->newObject('modSnippet');
            $snippet->name = $snippetName;
        } else {
            $snippetContent = $snippet->snippet;
            $snippet->snippet = null;
        }

        $snippetContent = "<?php\n\n" . $snippetContent;

        $snippet->source = 1;
        $snippet->static = 1;
        $snippet->static_file = $this->createFile('snippets/' . $snippetName . '.php', $snippetContent);

        $snippet->save();

        echo 'Snippet created';

    }

    public function comTemplate(...$args)
    {

        $templateId = $this->waitInput('Input template id (empty of ? for create new template)', false, $this->takeArgument($args));

        $template = null;

        if ($templateId && $templateId !== '?') {
            $template = $this->modx->getObject('modTemplate', $templateId);
        }

        $templateContent = '';
        $templateFileName = $this->waitInput('Input template filename', false, $this->takeArgument($args));

        if (empty($template)) {

            $template = $this->modx->newObject('modTemplate');

            $templateTitle = $this->waitInput('Input template title (empty for use template file name)', false, $this->takeArgument($args));
            $template->templatename = empty($templateTitle) ? $templateFileName : $templateTitle;
            $template->description = '';

        } else {

            $templateContent = $template->content;
            $template->content = null;
        }

        $template->source = 1;
        $template->static = 1;
        $template->static_file = $this->createFile('templates/' . $templateFileName . '.html', $templateContent);

        $template->save();

        echo 'Template created';

    }

    public function comClear(...$args)
    {
        return $this->comClearCache(...$args);
    }

    public function comClearCache(...$args)
    {

        $rmdir = function ($dir) use (&$rmdir) {
            $files = array_diff(scandir($dir), array('.', '..'));
            foreach ($files as $file) {
                (is_dir("$dir/$file")) ? $rmdir("$dir/$file") : unlink("$dir/$file");
            }
            return rmdir($dir);
        };

        $rmdir(MODX_CORE_PATH . '/cache/.');

        $this->say('MODX cache successfully cleared');

    }

    public function comRegNameSpace(...$args)
    {

        $nameSpace = $this->waitInput("Input namespace (empty for register default namespace ($this->nameSpace)",
            false, $this->takeArgument($args));

        if (empty($nameSpace)) {
            $nameSpace = $this->nameSpace;
        }

        $nameSpaceObject = $this->modx->getObject('modNamespace', $nameSpace);

        if (empty($nameSpaceObject)) {
            $nameSpaceObject = $this->modx->newObject('modNamespace');
            $nameSpaceObject->name = $nameSpace;
        }

        $nameSpaceObject->path = '{core_path}/components/' . $nameSpace;

        if (!is_dir(MODX_CORE_PATH . 'components/' . $nameSpace)) {
            mkdir(MODX_CORE_PATH . 'components/' . $nameSpace, 0700, true);
            $this->say('Namespace folder was created');
        }

        $nameSpaceObject->save();

        $this->say("Namespace [$nameSpace] was registered");

    }

    public function comLexicon(...$args)
    {

        $lang = $templateFileName = $this->waitInput('Input language code', false, $this->takeArgument($args));
        if (empty($lang)) {
            $this->say("Language must by set. Exit");
            exit;
        }

        $theme = $templateFileName = $this->waitInput('Input theme name [default is empty]', false, $this->takeArgument($args));

        if (empty($theme)) $theme = 'default';

        $file = "lexicon/$lang/$theme.inc.php";

        if (!$this->fileExist($file)) {
            $this->createFile($file, "<?php\n\n");
        }

        $fd = $this->getFileDescription($file, 'a');

        while ($command = $this->waitInput('Input lexicon key [and value] or empty fo exit', true)) {

            $key = $command[0];
            if (empty($key)) {
                $this->say("Key is empty. End lexicon [$lang/$theme] editing");
                break;
            }

            $value = null;
            if (count($command) > 1) {
                $value = join(' ', array_slice($command, 1));
            }

            $value = $this->waitInput('Input lexicon value', false, $value);

            $lexicon = '$_lang[\'' . $key . '\'] = ';
            $lexicon .= "'" . addslashes($value) . "'";
            $lexicon .= ";\n";

            $this->say("Add line: $lexicon");

            fputs($fd, $lexicon);

        }

        fclose($fd);

    }

    public function comSchema(...$args)
    {

        $manager = $this->modx->getManager();
        $generator = $manager->getGenerator();

        list($nameSpace, $fileName) = $this->waitInput("Input namespace (empty for create default namespace scheme ($this->nameSpace)\n
You can send filename after namespace, to separate options use whitespace",

            true, $this->takeArgument($args));

        if (empty($nameSpace)) {
            $nameSpace = $this->nameSpace;
        }

        if (empty($fileName)) {
            $fileName = $nameSpace;
        }

        $packagePath = $this->modx->getOption('core_path') . 'components/' . $nameSpace . '/';
        $modelPath = $packagePath . 'model/';
        $schemaPath = $modelPath . 'schema/';

        //to try find file or send proposal input other filename
        do {

            $schemaFile = $schemaPath . $fileName . '.mysql.schema.xml';

            if (is_file($schemaFile)) {
                break;
            }

            $this->say('Not found scheme file: [' . $schemaFile . ']');
            $fileName = $this->waitInput('Input schema file name or empty for exit:',
                false, $this->takeArgument($args));

            if (empty($fileName)) {
                return;
            }

        } while (!is_file($schemaFile));

        $generator->parseSchema($schemaFile, $modelPath);

        $this->say('Schema was success created. Do not forget to register the namespace!');
    }

    public function comSnapshot(...$args)
    {

        $types = [
            'modDocument' => ['content'],
            'modChunk' => ['snippet'],
            'modSnippet' => ['snippet'],
        ];

        foreach ($types as $type => $fields) {
            $objects = $this->modx->getCollection($type);

            $objectsDir = SNAPSHOT_FOLDER . $type . '/';

            if (!is_dir($objectsDir)) {
                mkdir($objectsDir, 0777, true);
            }

            foreach ($objects as $object) {

                $objectDir = $objectsDir . $object->id . '/';

                if (!is_dir($objectDir)) {
                    mkdir($objectDir, 0777, true);
                }

                $data = $object->toArray();

                foreach ($fields as $field) {
                    $fieldData = $data[$field] ?? null;
                    unset($data[$field]);

                    $fileName = $objectDir . $field . '.txt';

                    file_put_contents($fileName, $fieldData);
                    $this->say("Object $type $object->id field '$field'' put in $fileName");
                }

                $fileName = $objectDir . '_.json';
                $data = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                file_put_contents($fileName, $data);

                $this->say("Object $type ID $object->id data put in $fileName");
            }
        }

    }

    public function comHelp(...$args)
    {

        $this->say('\nAvailable commands:');

        $this->say('chunk - create chunk');
        $this->say('snippet - create snippet');
        $this->say('template - create template');
        $this->say('clear|clearCache - clear cache folder');
        $this->say('regNameSpace [namespace] - register new namespace');
        $this->say('schema - generate database map from xPDO schema');
        $this->say('snapshot - make dump of modDocument, modChunk and modSnippet into files');
        $this->say('lexicon - manage lexicons');

        $this->say("\nThat's all you need to start. let's go!\n");

        $this->waitCommand();

    }

    /* private actions */

    private function firstToUpper($string)
    {
        $string[0] = strtoupper($string[0]);

        return $string;
    }

    private function waitInput($message, $slice = false, $input = null)
    {

        if (!empty($input)) {
            return $input;
        }

        echo "\n$message\n--> ";

        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        $line = trim($line);

        if ($slice) {
            $line = explode(' ', $line);
        }

        fclose($handle);

        return $line;
    }

    private function createFile($filename, $content = '')
    {

        $relativePath = $this->nameSpace . '/' . $filename;
        $fullPath = MODX_CORE_PATH . 'components/' . $relativePath;
        $folder = dirname($fullPath);

        if (is_file($fullPath)) {
            die("File $fullPath already exist");
        }

        mkdir($folder, 0700, true);

        file_put_contents($fullPath, $content);

        return 'core/components/' . $relativePath;
    }

    private function fileExist($file)
    {
        return is_file(MODX_CORE_PATH . 'components/' . $this->nameSpace . '/' . $file);
    }

    private function getFileDescription($file, $option = 'r')
    {
        return fopen(MODX_CORE_PATH . 'components/' . $this->nameSpace . '/' . $file, $option);
    }

    private function say($message)
    {
        echo "$message\n";
    }

    private function takeArgument(&$args)
    {

        if (!is_array($args)) {
            return null;
        }

        $arg = current($args);

        if ($arg === false) {
            return null;
        }

        next($args);

        return $arg;
    }
}