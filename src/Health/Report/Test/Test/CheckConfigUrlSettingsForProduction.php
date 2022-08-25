<?php
namespace Concrete\Core\Health\Report\Test\Test;

use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Health\Report\Finding\Details\DashboardPageDetails;
use Concrete\Core\Health\Report\Runner;
use Concrete\Core\Health\Report\Test\TestInterface;
use Concrete\Core\Site\Service;
use Concrete\Core\Url\Url;

class CheckConfigUrlSettingsForProduction implements TestInterface
{

    /**
     * @var Service
     */
    protected $service;

    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    public function run(Runner $report): void
    {
        $site = $this->service->getDefault();
        $url = $site->getSiteCanonicalURL();

        if (!$url) {
            $report->alert(
                'No canonical URL set! You ought to set canonical URLs for all sites running in production.',
                new DashboardPageDetails('/dashboard/system/seo/urls')
            );
        } else {
            $url = Url::createFromUrl($url);
            if ($url->getScheme() !== 'https') {
                $report->warning(
                    'Canonical URL set but not running SSL. SSL is strongly encouraged.',
                    new DashboardPageDetails('/dashboard/system/seo/urls')
                );
            }

        }
	}

}
