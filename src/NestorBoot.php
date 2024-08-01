<?php

namespace Daerisimber;

use Daerisimber\Utils\Traits\SingletonTrait;

class NestorBoot
{
    use SingletonTrait;

    public function init()
    {
        // load all commands here from an external php file
        $commands  = config('app.commands', []);

        $application = new \Symfony\Component\Console\Application();

        foreach ($commands as $class) {
            if (!class_exists($class)) {
                throw new RuntimeException(sprintf('Class %s does not exist', $class));
            }
            $command = new $class();
            $application->add($command);
        }

        $application->run();
    }
}
