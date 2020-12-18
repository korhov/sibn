<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        // ...
        if (in_array($this->getEnvironment(), array('dev', 'test'), true)) {
            // ...
            if ('test' === $this->getEnvironment()) {
                $bundles[] = new Liip\FunctionalTestBundle\LiipFunctionalTestBundle();
            }
        }

        return $bundles;
    }

    // ...
}