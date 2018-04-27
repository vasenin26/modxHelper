<?php

if (!defined('MODX_CORE_PATH')) {
    define('MODX_CORE_PATH', './core/');
}
if (!defined('MODX_CONFIG_KEY')) {
    define('MODX_CONFIG_KEY', 'config');
}
require_once(MODX_CORE_PATH . 'model/modx/modx.class.php');

$nameSpace = 'dubai';

(new ModxHelper($nameSpace))->waitCommand();

/* --- helper class --- */

Class ModxHelper
{

    private $modx;
    private $nameSpace;

    public function __construct($nameSpace)
    {
        $this->modx = new modX();
        $this->modx->initialize('mgr');

        $this->nameSpace = $nameSpace;

    }

    public function waitCommand()
    {

        $command = $this->waitInput('Hi, i\'m ModHelper. Input command', true);

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

    public function comChunk()
    {

        $chunkName = $this->waitInput('Input chunk name');

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

    public function comSnippet()
    {

        $snippetName = $this->waitInput('Input snippet name');

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

    public function comTemplate()
    {

        $templateId = $this->waitInput('Input template id (empty for create new template)');

        $template = null;

        if ($templateId) {
            $template = $this->modx->getObject('modTemplate', $templateId);
        }

        $templateContent = '';
        $templateFileName = $this->waitInput('Input template filename');

        if (empty($template)) {

            $template = $this->modx->newObject('modTemplate');

            $templateTitle = $this->waitInput('Input template title (empty for use template file name)');
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

    public function comClear()
    {
        return $this->comClearCache();
    }

    public function comClearCache()
    {

        $delFolder = function ($dir) use (&$delFolder) {
            $files = array_diff(scandir($dir), array('.', '..'));
            foreach ($files as $file) {
                (is_dir("$dir/$file")) ? $delFolder("$dir/$file") : unlink("$dir/$file");
            }
            return rmdir($dir);
        };

        return $delFolder(MODX_CORE_PATH . '/cache/.');

    }

    public function comHelp(...$args)
    {

        $this->say('\nAvailable commands:');

        $this->say('chunk - create chunk');
        $this->say('snippet - create snippet');
        $this->say('template - create template');
        $this->say('clear|clearCache - clear cache folder');

        $this->say("\nThat's all you need to start. let's go!\n");

        $this->waitCommand();

    }

    /* private actions */

    private function firstToUpper($string)
    {
        $string[0] = strtoupper($string[0]);

        return $string;
    }

    private function waitInput($message, $slice = false)
    {

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

    private function say($message){
        echo "$message\n";
    }
}