<?php
namespace Concrete\Core\Backup\ContentImporter\Importer\Routine;

use Concrete\Core\Page\Stack\Folder\FolderService;
use Concrete\Core\Page\Stack\Stack;

class ImportStacksStructureRoutine extends AbstractPageStructureRoutine implements SpecifiableHomePageRoutineInterface
{
    public function getHandle()
    {
        return 'stacks';
    }

    public function setHomePage($page)
    {
        $this->home = $page;
    }

    public function import(\SimpleXMLElement $sx)
    {
        if (!isset($sx->stacks)) {
            return;
        }
        $folderService = app(FolderService::class);
        $siteTree = null;
        if (isset($this->home)) {
            $siteTree = $this->home->getSiteTreeObject();
        }
        $nodes = [];
        foreach ($sx->stacks->children() as $child) {
            $nodes[] = $child;
        }
        $nodes = $this->sortElementsByPath($nodes);
        foreach ($nodes as $p) {
            $name = (string) $p['name'];
            if ($p->getName() == 'folder') {
                $type = 'folder';
            } else {
                $type = (string) $p['type'];
            }
            $pathSlugs = isset($p['path']) ? preg_split('{/}', (string) $p['path'], -1, PREG_SPLIT_NO_EMPTY) : [];
            $path = $pathSlugs === [] ? '' : ('/' . implode('/', $pathSlugs));
            if (count($pathSlugs) > 1) {
                $parentPath = '/' . implode('/', array_slice($pathSlugs, 0, -1));
                $parent = $folderService->getByPath($parentPath);
            } else {
                $parent = null;
            }
            switch ($type) {
                case 'folder':
                    $folder = $path === '' ? null : $folderService->getByPath($path);
                    if (!$folder) {
                        $folderService->add($name, $parent);
                    }
                    break;
                case 'global_area':
                    $globalArea = Stack::getByName($name, 'RECENT', $siteTree);
                    if (!$globalArea) {
                        Stack::addGlobalArea($name, $siteTree);
                    }
                    break;
                default:
                    // Stack
                    $stack = $path === '' ? null : Stack::getByPath($path, 'RECENT', $siteTree);
                    if (!$stack) {
                        Stack::addStack($name, $parent);
                    }
                    break;
            }
        }
    }
}
