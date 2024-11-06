<?php
namespace Concrete\Core\Page;

use Concrete\Core\Area\Area;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Entity\Site\Locale;
use Concrete\Core\Export\Item\ItemInterface;
use Concrete\Core\Multilingual\Page\Section\Section;
use Concrete\Core\User\UserInfoRepository;
use Doctrine\ORM\EntityManagerInterface;
use SimpleXMLElement;

class Exporter implements ItemInterface
{
    /**
     * @param \Concrete\Core\Page\Page $mixed
     */
    public function export($mixed, SimpleXMLElement $element)
    {
        $p = $element->addChild('page');
        $p->addAttribute('name', $mixed->getCollectionName());
        $p->addAttribute('path', $mixed->getCollectionPath());
        $p->addAttribute('public-date', $mixed->getCollectionDatePublic());
        $p->addAttribute('filename', $mixed->getCollectionFilename());
        $p->addAttribute('pagetype', $mixed->getPageTypeHandle());
        $locale = $this->getLocaleForHome($mixed);
        if ($locale !== null) {
            $this->exportLocaleRoot($p, $locale);
        }
        $hrefLangMap = $this->getHrefLangMap($mixed);
        if ($hrefLangMap !== []) {
            $this->exportHrefLangMap($p, $hrefLangMap);
        }
        $templateID = $mixed->getPageTemplateID();
        if ($templateID) {
            $template = app(EntityManagerInterface::class)->find(\Concrete\Core\Entity\Page\Template::class, $templateID);
            if ($template) {
                $p->addAttribute('template', $template->getPageTemplateHandle());
            }
        }
        $uiRepository = app(UserInfoRepository::class);
        $ui = null;
        $uID = $mixed->getCollectionUserID();
        if ($uID) {
            $ui = $uiRepository->getByID($uID);
        }
        if ($ui === null) {
            $ui = $uiRepository->getByID(USER_SUPER_ID);
        }
        $p->addAttribute('user', $ui->getUserName());
        $p->addAttribute('description', $mixed->getCollectionDescription());
        $p->addAttribute('package', $mixed->getPackageHandle());
        if ($mixed->getCollectionParentID() == 0) {
            if ($mixed->getSiteTreeID() == 0) {
                $p->addAttribute('global', 'true');
            } else {
                $p->addAttribute('root', 'true');
            }
        }

        $attribs = $mixed->getSetCollectionAttributes();
        if ($attribs !== []) {
            $attributes = $p->addChild('attributes');
            foreach ($attribs as $ak) {
                $av = $mixed->getAttributeValueObject($ak);
                $cnt = $ak->getController();
                $cnt->setAttributeValue($av);
                $akx = $attributes->addChild('attributekey');
                $akx->addAttribute('handle', $ak->getAttributeKeyHandle());
                $cnt->exportValue($akx);
            }
        }

        $r = app(Connection::class)->executeQuery('select arHandle from Areas where cID = ? and arIsGlobal = 0 and arParentID = 0', [$mixed->getCollectionID()]);
        while ($row = $r->FetchRow()) {
            $ax = Area::get($mixed, $row['arHandle']);
            $ax->export($p, $mixed);
        }
    }

    /**
     * @return \Concrete\Core\Entity\Site\Locale|null
     */
    private function getLocaleForHome(Page $page)
    {
        $siteTreeID = $page->getSiteTreeID();
        if (!$siteTreeID) {
            return null;
        }
        $section = Section::getByID($page->getCollectionID());

        return $section ? $section->getLocaleObject() : null;
    }

    private function exportLocaleRoot(SimpleXMLElement $parentElement, Locale $locale)
    {
        $localeElement = $parentElement->addChild('locale');
        $localeElement->addAttribute('language', $locale->getLanguage());
        $country = (string) $locale->getCountry();
        if ($country !== '') {
            $localeElement->addAttribute('country', $country);
        }
    }

    private function getHrefLangMap(Page $page)
    {
        $pageSection = Section::getBySectionOfSite($page);
        if (!$pageSection) {
            return [];
        }
        $site = $pageSection->getSite();
        if (!$site) {
            return [];
        }
        $map = [];
        foreach (Section::getList($site) as $relatedSection) {
            $relatedLocale = $relatedSection->getLocale();
            if ($pageSection->getLocale() === $relatedLocale) {
                continue;
            }
            $relatedPageID = $relatedSection->getTranslatedPageID($page);
            if (!$relatedPageID) {
                continue;
            }
            $relatedPage = Page::getByID($relatedPageID);
            if (!$relatedPage || $relatedPage->isError()) {
                continue;
            }
            $relatedPagePath = (string) $relatedPage->getCollectionPath();
            if ($relatedPagePath === '' && Section::isMultilingualSection($relatedPageID)) {
                $relatedPagePath = (string) $relatedSection->getCollectionPath();
                if ($relatedPagePath === '') {
                    $relatedPagePath = '/';
                }
            }
            if ($relatedPagePath === '') {
                continue;
            }
            $map[$relatedLocale] = $relatedPagePath;
        }
        return $map;
    }

    private function exportHrefLangMap(SimpleXMLElement $parentElement, array $map)
    {
        $hrefLangElement = $parentElement->addChild('hreflang');
        foreach ($map as $locale => $path) {
            $alternateElement = $hrefLangElement->addChild('alternate');
            $alternateElement->addAttribute('locale', $locale);
            $alternateElement->addAttribute('path', $path);
        }
    }
}
