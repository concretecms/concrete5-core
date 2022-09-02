<?php
namespace Concrete\Core\Health\Report\Test\Test\Search;

use Concrete\Core\Entity\Attribute\Value\Value\TextValue;
use Concrete\Core\Health\Report\Finding\Message\Search\SimpleAttributeMessage;
use Concrete\Core\Health\Report\Runner;
use Concrete\Core\Health\Report\Test\TestInterface;
use Concrete\Core\Health\Search\Traits\SearchContentTrait;
use Doctrine\ORM\EntityManager;

class SearchSimpleAttributeContentTest implements TestInterface
{
    use SearchContentTrait;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function run(Runner $report): void
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('v')->from(TextValue::class, 'v');

        if (!$this->applyQueryFilters($report, $qb, true, 'v.value')) {
            return;
        }

        /** @var TextValue $value */
        foreach ($this->iterateQuery($qb->getQuery()) as $value) {
            // Turn the TextValue into a real attribute value. It's a convoluted process.
            $genericValue = $value->getGenericValue();
            $report->warning(new SimpleAttributeMessage($genericValue));
        }

    }

}
