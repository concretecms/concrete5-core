<?php

namespace Concrete\Core\Export\Item;

use Concrete\Core\Area\Area as AreaObject;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Multilingual\Page\Section\Section as SectionObject;
use Concrete\Core\Page\Stack\Stack as StackObject;
use SimpleXMLElement;

defined('C5_EXECUTE') or die("Access Denied.");

class Stack implements ItemInterface
{
    /**
     * @param \Concrete\Core\Page\Stack\Stack $stack
     *
     * {@inheritDoc}
     * @see \Concrete\Core\Export\Item\ItemInterface::export()
     */
    public function export($stack, SimpleXMLElement $xml)
    {
        if (!$stack->isNeutralStack()) {
            return [];
        }
        $newNodes = [
            $this->exportStack($stack, $xml)
        ];
        $sections = SectionObject::getList($stack->getSite());
        foreach ($sections as $section) {
            $localizedStack = $stack->getLocalizedStack($section);
            if ($localizedStack !== null) {
                $newNodes[] = $this->exportStack($localizedStack, $xml, $section);
            }
        }

        return $newNodes;
    }

    /**
     * @return \SimpleXMLElement
     */
    private function exportStack(StackObject $stack, SimpleXMLElement $xml, SectionObject $section = null)
    {
        $db = app(Connection::class);
        $node = $xml->addChild('stack');
        $node->addAttribute('name', $stack->getCollectionName());
        $type = $stack->getStackTypeExportText();
        if ($type) {
            $node->addAttribute('type', $type);
        }
        $node->addAttribute('path', substr($stack->getCollectionPath(), strlen(STACKS_PAGE_PATH)));
        if ($section !== null) {
            $node->addAttribute('section', $section->getLocale());
        }

        // We should have just a 'Main' area in stacks, but just in case.
        $r = $db->executeQuery('select arHandle from Areas where cID = ? and arParentID = 0', [$stack->getCollectionID()]);
        while (($row = $r->fetch()) !== false) {
            $ax = AreaObject::get($stack, $row['arHandle']);
            if ($ax) {
                $ax->export($node, $stack);
            }
        }

        return $node;
    }
}
