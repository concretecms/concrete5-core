<?php
namespace Concrete\Core\Backup\ContentImporter\Importer\Routine;

use Concrete\Core\Attribute\Category\PageCategory;
use Concrete\Core\Page\Page;
use SimpleXMLElement;

class ImportPageContentRoutine extends AbstractPageContentRoutine implements SpecifiableHomePageRoutineInterface
{
    /**
     * @var \Concrete\Core\Page\Page|null
     */
    protected $home;

    public function getHandle()
    {
        return 'page_content';
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
        $siteTree = $this->home ? $this->home->getSiteTreeObject() : null;
        $pageAttributeCategory = app(PageCategory::class);
        foreach ($sx->pages->page as $pageElement) {
            $path = '/' . trim((string) $pageElement['path'], '/');
            if ($path !== '/') {
                $page = Page::getByPath($path, 'RECENT', $siteTree);
            } else {
                $page = $this->home ?: Page::getByID(Page::getHomePageID(), 'RECENT');
            }
            if (isset($pageElement->area)) {
                $this->importPageAreas($page, $pageElement);
            }
            if (isset($pageElement->attributes)) {
                foreach ($pageElement->attributes->children() as $attr) {
                    $handle = (string) $attr['handle'];
                    $ak = $pageAttributeCategory->getAttributeKeyByHandle($handle);
                    if ($ak) {
                        $value = $ak->getController()->importValue($attr);
                        $page->setAttribute($handle, $value);
                    }
                }
            }
            $page->reindex();
        }
    }
}
