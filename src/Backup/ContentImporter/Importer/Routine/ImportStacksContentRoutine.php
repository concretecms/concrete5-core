<?php

namespace Concrete\Core\Backup\ContentImporter\Importer\Routine;

use Concrete\Core\Multilingual\Page\Section\Section;
use Concrete\Core\Page\Stack\Stack;
use SimpleXMLElement;

class ImportStacksContentRoutine extends AbstractPageContentRoutine implements SpecifiableHomePageRoutineInterface
{
    /**
     * @var \Concrete\Core\Page\Page|null
     */
    protected $home;

    public function getHandle()
    {
        return 'stacks_content';
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Backup\ContentImporter\Importer\Routine\SpecifiableHomePageRoutineInterface::setHomePage()
     */
    public function setHomePage($page)
    {
        $this->home = $page;
    }

    public function import(SimpleXMLElement $sx)
    {
        if (!isset($sx->stacks)) {
            return;
        }
        $site = $this->home ? $this->home->getSite() : null;
        $siteTree = $this->home ? $this->home->getSiteTreeObject() : null;
        foreach ($sx->stacks->stack as $p) {
            $pathSlugs = preg_split('{/}', (string) $p['path'], -1, PREG_SPLIT_NO_EMPTY);
            $path = '/' . implode('/', $pathSlugs);
            if ($path !== '/') {
                $stack = Stack::getByPath($path, 'RECENT', $siteTree);
            } else {
                $stack = Stack::getByName((string) $p['name'], 'RECENT', $siteTree);
            }
            $locale = isset($p['section']) ? (string) $p['section'] : '';
            if ($locale !== '') {
                $section = Section::getByLocale($locale, $site);
                if (!$section) {
                    continue;
                }
                $localizedStack = $stack->getLocalizedStack($section);
                $stack = $localizedStack ?: $stack->addLocalizedStack($section);
            }
            if (isset($p->area)) {
                $this->importPageAreas($stack, $p);
            }
        }
    }
}
