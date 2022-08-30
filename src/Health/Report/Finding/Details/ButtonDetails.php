<?php
namespace Concrete\Core\Health\Report\Finding\Details;

use Concrete\Core\Health\Report\Finding\Details\Formatter\ButtonFormatter;
use Concrete\Core\Health\Report\Finding\Details\Formatter\FormatterInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class ButtonDetails implements DetailsInterface
{

    /**
     * @var LocationInterface
     */
    protected $location;

    public function __construct(LocationInterface $location = null)
    {
        $this->location = $location;
    }

    /**
     * @param LocationInterface $location
     */
    public function setLocation(?LocationInterface $location): void
    {
        $this->location = $location;
    }

    /**
     * @return LocationInterface
     */
    public function getLocation(): LocationInterface
    {
        return $this->location;
    }

    public function getFormatter(): FormatterInterface
    {
        return new ButtonFormatter();
    }

    public function jsonSerialize()
    {
        $data = [
            'class' => static::class,
            'location' => $this->location,
        ];
        return $data;
    }

    public function denormalize(DenormalizerInterface $denormalizer, $data, string $format = null, array $context = [])
    {
        $location = $denormalizer->denormalize($data['location'], $data['location']['class']);
        $this->location = $location;
    }



}
