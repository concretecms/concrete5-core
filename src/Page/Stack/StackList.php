<?php
namespace Concrete\Core\Page\Stack;

use Concrete\Core\Multilingual\Page\Section\Section;
use Concrete\Core\Page\Stack\Folder\Folder;
use Concrete\Core\Page\PageList;
use Concrete\Core\Search\StickyRequest;
use Doctrine\DBAL\Query\QueryBuilder;

class StackList extends PageList
{
    /**
     * @var bool
     */
    protected $foldersFirst = false;

    /**
     * @var \Concrete\Core\Multilingual\Page\Section\Section|null
     */
    private $languageSection;

    public function __construct()
    {
        parent::__construct();
        /* retreive most recent version to include stacks that have no approved versions */
        $this->pageVersionToRetrieve = self::PAGE_VERSION_RECENT;
        $this->query->leftJoin('p', 'Stacks', 's', 's.cID = p.cID');
        $this->ignorePermissions();
        $this->filterByPath(STACKS_PAGE_PATH);
        $this->includeSystemPages();
        $this->sortByName();
    }

    public function performAutomaticSorting(StickyRequest $request = null)
    {
        parent::performAutomaticSorting($request);
        if ($this->foldersFirst) {
            $previousOrderBy = $this->query->getQueryPart('orderBy');
            $this->query->orderBy('pt.ptHandle', 'desc');
            $this->query->add('orderBy', $previousOrderBy, true);
        }
    }

    /**
     * @deprecated Use getLanguageSection/setLanguageSection
     */
    public function filterByLanguageSection(Section $ms)
    {
        $this->setLanguageSection($ms);
    }

    public function getLanguageSection(): ?Section
    {
        return $this->languageSection;
    }

    /**
     * @return $this
     */
    public function setLanguageSection(?Section $value = null): self
    {
        $this->languageSection = $value;

        return $this;
    }

    /**
     * Should we list stack folders first?
     *
     * @param bool $value
     */
    public function setFoldersFirst($value)
    {
        $this->foldersFirst = (bool) $value;
    }

    /**
     * Should we list stack folders first?
     *
     * @return bool
     */
    public function getFoldersFirst()
    {
        return $this->foldersFirst;
    }

    public function filterByFolder(Folder $folder)
    {
        $this->filterByParentID($folder->getPage()->getCollectionID());
    }

    public function filterByGlobalAreas()
    {
        $this->filter('stType', Stack::ST_TYPE_GLOBAL_AREA);
    }

    public function excludeGlobalAreas()
    {
        $this->filter(false, 'stType != '.Stack::ST_TYPE_GLOBAL_AREA.' or stType is null');
    }

    public function filterByUserAdded()
    {
        $this->filter('stType', Stack::ST_TYPE_USER_ADDED);
    }

    public function filterByStackCategory(StackCategory $category)
    {
        $this->filterByParentID($category->getPage()->getCollectionID());
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Page\PageList::finalizeQuery()
     */
    public function finalizeQuery(QueryBuilder $query)
    {
        $query = parent::finalizeQuery($query);
        $languageSection = $this->getLanguageSection();
        if ($languageSection === null) {
            $query->andWhere('s.stMultilingualSection IS NULL OR s.stMultilingualSection = 0');
        } else {
            $query->andWhere('s.stMultilingualSection = ' . $query->createNamedParameter($languageSection->getCollectionID()));
        }

        return $query;
    }

    /**
     * @param $queryRow
     *
     * @return \Stack
     */
    public function getResult($queryRow)
    {
        $stack = Stack::getByID($queryRow['cID'], 'ACTIVE');

        return $stack ?: parent::getResult($queryRow);
    }

}
