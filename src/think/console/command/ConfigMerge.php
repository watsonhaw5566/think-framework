<?php
namespace think\console\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

class ConfigMerge extends Command
{
    protected function configure()
    {
        $this->setName('config:merge')
             ->setDescription('merge all config file to runtimepath');
    }

    protected function execute(Input $input, Output $output)
    {
        // 加载配置文件
        $this->app->loadConfig();

        $mergeFile = $this->app->getRuntimePath() . 'config.php';
        $config    = $this->app->config->get();
        $content   = '<?php ' . PHP_EOL . 'return ' . var_export($config, true) . ';';
        if (file_put_contents($mergeFile, $content)) {
            $output->writeln("<info>config files has merge to :{$mergeFile}</info>");
        } else {
            $output->writeln("<error>config merge fail</error>");
        }
    }
}
