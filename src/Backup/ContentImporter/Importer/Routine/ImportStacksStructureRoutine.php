<?php
namespace Concrete\Core\Backup\ContentImporter\Importer\Routine;

use Concrete\Core\Page\Stack\Stack;
use SimpleXMLElement;

class ImportStacksStructureRoutine extends AbstractPageStructureRoutine implements SpecifiableHomePageRoutineInterface
{
    use StackTrait;

    /**
     * @var \Concrete\Core\Page\Page|null
     */
    protected $home;

    /**
     * @var \Concrete\Core\Entity\Site\Tree|null
     */
    private $siteTree;

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Backup\ContentImporter\Importer\Routine\RoutineInterface::getHandle()
     */
    public function getHandle()
    {
        return 'stacks';
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
        $this->siteTree = $this->home ? $this->home->getSiteTreeObject(): null;
        $nodes = [];
        foreach ($sx->stacks->children() as $child) {
            $nodes[] = $child;
        }
        $nodes = $this->sortElementsByPath($nodes);
        for ($step = 1; $step <= 2; $step++) {
            foreach ($nodes as $p) {
                $this->importElement($p, $step);
            }
        }
    }

    /**
     * @param int $step 1: folders, 2: anything else
     */
    private function importElement(SimpleXMLElement $p, $step)
    {
        $name = (string) $p['name'];
        $path = '/' . trim((string) $p['path'], '/');
        if ($p->getName() == 'folder') {
            $type = 'folder';
        } else {
            $type = (string) $p['type'];
        }
        switch ($type) {
            case 'global_area':
                if ($step === 2) {
                    $globalArea = Stack::getByName($name, 'RECENT', $this->siteTree);
                    if (!$globalArea) {
                        Stack::addGlobalArea($name, $this->siteTree);
                    }
                }
                break;
            case 'folder':
                if ($step === 1) {
                    $parentFolder = $this->getOrCreateFolderByPath($path);
                    $folderPath = rtrim($path, '/') . '/' . $name;
                    if (!array_key_exists($folderPath, $this->getExistingFolders())) {
                        $this->createFolder($name, $folderPath, $parentFolder);
                    }
                }
                break;
            default:
                // Stack
                if ($step === 2) {
                    $parent = $this->getOrCreateFolderByPath($path);
                    if ($this->getStackIDByName($name, $parent) === null) {
                        Stack::addStack($name, $parent);
                    }
                }
                break;
        }
    }
}
