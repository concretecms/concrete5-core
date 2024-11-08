<?php
namespace Concrete\Core\Backup\ContentImporter\Importer\Routine;

use Concrete\Core\Error\UserMessageException;
use Concrete\Core\Localization\Locale\Service;
use Concrete\Core\Page\Page;
use Concrete\Core\Page\Template;
use Concrete\Core\Page\Type\Type;
use Concrete\Core\User\UserInfoRepository;
use Doctrine\ORM\EntityManagerInterface;
use SimpleXMLElement;

class ImportPageStructureRoutine extends AbstractPageStructureRoutine implements SpecifiableHomePageRoutineInterface
{
    /**
     * @var \Concrete\Core\Page\Page|null
     */
    protected $home;

    /**
     * @var \Concrete\Core\Entity\Site\Tree|null
     */
    private $defaultSiteTree;

    public function getHandle()
    {
        return 'page_structure';
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Backup\ContentImporter\Importer\Routine\SpecifiableHomePageRoutineInterface::setHomePage()
     */
    public function setHomePage($c)
    {
        $this->home = $c;
    }

    public function import(SimpleXMLElement $sx)
    {
        if (!isset($sx->pages) || !isset($sx->pages->page)) {
            return;
        }

        if (!$this->home || $this->home->isError()) {
            $this->home = Page::getByID(Page::getHomePageID(), 'RECENT');
            if (!$this->home || $this->home->isError()) {
                throw new UserMessageException(t('Unable to find the home page'));
            }
        }
        $this->defaultSiteTree = $this->home->getSiteTreeObject();

        $pageElements = [];
        foreach ($sx->pages->page as $pageElement) {
            $pageElements[] = $pageElement;
        }
        $pageElements = $this->sortElementsByPath($pageElements);
        foreach ($pageElements as $pageElement) {
            $localeInfo = $this->extractLocale($pageElement);
            $this->getOrCreatePage($pageElement, $localeInfo);
        }
    }

    /**
     * @return \Concrete\Core\Page\Page
     */
    private function getOrCreatePage(SimpleXMLElement $pageElement, array $localeInfo = null)
    {
        $userName = (string) $pageElement['user'];
        $userInfo = $userName === '' ? null : app(UserInfoRepository::class)->getByName($userName);
        $package = static::getPackageObject($pageElement['package']);
        $cName = isset($pageElement['name']) ? (string) $pageElement['name'] : '';
        $cDescription = isset($pageElement['description']) ? (string) $pageElement['description'] : '';
        $cDatePublic = (string) $pageElement['public-date'];
        $pageTemplate = Template::getByHandle($pageElement['template']);
        $pageTypeHandle = isset($pageElement['pagetype']) ? (string) $pageElement['pagetype'] : '';
        $pageType = $pageTypeHandle === '' ? null : Type::getByHandle((string) $pageElement['pagetype']);

        $pathSlugs = isset($pageElement['path']) ? preg_split('{/}', (string) $pageElement['path'], -1, PREG_SPLIT_NO_EMPTY) : [];
        if ($pathSlugs === []) {
            $page = $this->home;
        } else {
            $pagePath = '/' . implode('/', $pathSlugs);
            $page = Page::getByPath($pagePath, 'RECENT', $this->defaultSiteTree);
            if (!$page || $page->isError() && $this->defaultSiteTree === null) {
                $page = Page::getByPath($pagePath, 'RECENT');
            }
        }

        if ($page && !$page->isError()) {
            if ($localeInfo !== null) {
                $this->updateExistingLocale($page, $localeInfo);
            }
            $page->update([
                'cName' => $cName === '' ? null : $cName,
                'cDescription' => $cDescription,
                'cDatePublic' => $cDatePublic === '' ? null : $cDatePublic,
                'ptID' => $pageType === null ? null : $pageType->getPageTypeID(),
                'pTemplateID' => $pageTemplate === null ? null : $pageTemplate->getPageTemplateID(),
                'uID' => $userInfo === null ? USER_SUPER_ID : $userInfo->getUserID(),
                'pkgID' => $package === null ? null : $package->getPackageID(),
            ]);
        } else {
            $slugs = $pathSlugs;
            $cHandle = array_pop($slugs);
            if ($slugs === []) {
                $parent = $this->home;
            } else {
                $parentPagePath = '/' . implode('/', $slugs);
                $parent = Page::getByPath($parentPagePath, 'RECENT', $this->defaultSiteTree);
                if ((!$parent || $parent->isError()) && $this->defaultSiteTree !== null) {
                    $parent = Page::getByPath($parentPagePath, 'RECENT');
                }
                if (!$parent || $parent->isError()) {
                    throw new UserMessageException(t('Missing the page with path %s', $parentPagePath));
                }
            }
            if ($localeInfo === null || $this->localeAlreadyExists($localeInfo)) {
                $page = $parent->add($pageType, [
                    'uID' => $userInfo === null ? USER_SUPER_ID : $userInfo->getUserID(),
                    'pkgID' => $package === null ? 0 : $package->getPackageID(),
                    'cName' => $cName,
                    'cHandle' => $cHandle,
                    'cDescription' => $cDescription,
                    'cDatePublic' => $cDatePublic === '' ? null : $cDatePublic,
                ], $pageTemplate);
            } else {
                if (!$pageTemplate) {
                    throw new UserMessageException(t('Missing page template when creating the home of a language'));
                }
                app('multilingual/detector')->assumeEnabled();
                $service = app(Service::class);
                $locale = $service->add($this->home->getSite(), $localeInfo['language'], $localeInfo['country']);
                $page = $service->addHomePage($locale, $pageTemplate, $cName === '' ? 'Home' : $cName, $cHandle);
                $page->update([
                    'cDescription' => $cDescription,
                    'cDatePublic' => $cDatePublic === '' ? null : $cDatePublic,
                    'ptID' => $pageType === null ? null : $pageType->getPageTypeID(),
                    'uID' => $userInfo === null ? USER_SUPER_ID : $userInfo->getUserID(),
                    'pkgID' => $package === null ? 0 : $package->getPackageID(),
                ]);
            }
        }
    }

    private function extractLocale(SimpleXMLElement $pageElement)
    {
        if (!isset($pageElement->locale)) {
            return null;
        }
        $localeElement = $pageElement->locale;
        $language = isset($localeElement['language']) ? (string) $localeElement['language'] : '';
        if ($language === '') {
            return null;
        }
        $country =  isset($localeElement['country']) ? (string) $localeElement['country'] : '';
        if ($country === '') {
            return null;
        }
        return [
            'language' => $language,
            'country' => $country,
        ];
    }

    private function updateExistingLocale(Page $page, array $localeInfo)
    {
        $pageTree = $page->getSiteTreeObject();
        if (!$pageTree || $pageTree->getSiteHomePageID() != $page->getCollectionID()) {
            return;
        }
        $editingLocale = $pageTree->getLocale();
        if ($editingLocale->getLanguage() === $localeInfo['language'] && $editingLocale->getCountry() === $localeInfo['country']) {
            return;
        }
        if ($this->localeAlreadyExists($localeInfo)) {
            return;
        }
        $editingLocale->setLanguage($localeInfo['language']);
        $editingLocale->setCountry($localeInfo['country']);
        $service = app(Service::class);
        $service->updatePluralSettings($editingLocale);
        $em = app(EntityManagerInterface::class);
        $em->flush();
    }

    /**
     * @return bool
     */
    private function localeAlreadyExists(array $localeInfo)
    {
        foreach ($this->home->getSite()->getLocales() as $locale) {
            if ($locale->getLanguage() === $localeInfo['language'] && $locale->getCountry() === $localeInfo['country']) {
                return true;
            }
        }

        return false;
    }
}
