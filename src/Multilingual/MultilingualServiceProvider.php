<?php
namespace Concrete\Core\Multilingual;

use Concrete\Core\Foundation\Service\Provider as ServiceProvider;

class MultilingualServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind('multilingual/interface/flag', Service\UserInterface\Flag::class);
        $this->app->bind('multilingual/extractor', Service\Extractor::class);

        $singletons = array(
            'multilingual/interface/flag' => '\Concrete\Core\Multilingual\Service\UserInterface\Flag',
        );

        foreach ($singletons as $key => $value) {
            $this->app->singleton($key, $value);
        }
    }
}
