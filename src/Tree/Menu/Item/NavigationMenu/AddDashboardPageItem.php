<?php

namespace Concrete\Core\Tree\Menu\Item\NavigationMenu;

use Concrete\Core\Tree\Menu\Item\Category\CategoryItem;

class AddDashboardPageItem extends CategoryItem
{

    public function getDialogTitle()
    {
        return t('Add Page');
    }

    public function getAction()
    {
        return 'add-dashboard-page';
    }

    public function getActionURL()
    {
        return \URL::to('/ccm/system/dialogs/tree/node/add/dashboard_page?treeNodeID=' . $this->category->getTreeNodeID());
    }

    public function getItemName()
    {
        return t('Add Page');
    }


}