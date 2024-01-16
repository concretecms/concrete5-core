<?php
namespace Concrete\Core\Install\StartingPoint\Installer\Routine\Base;

use Concrete\Core\Install\StartingPoint\Installer\Routine\AbstractRoutine;

class SetDefaultConversationSubscribersRoutine extends AbstractRoutine
{

    public function getText(): string
    {
        return t('Setting default conversation subscribers.');
    }


}