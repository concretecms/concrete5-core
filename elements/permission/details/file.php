<?php
defined('C5_EXECUTE') or die("Access Denied.");
?>

<?php $pk = PermissionKey::getByID($_REQUEST['pkID']);
$pk->setPermissionObject($f);
?>

<?php Loader::element("permission/detail", array('permissionKey' => $pk)); ?>

<script>
var ccm_permissionDialogURL = CCM_DISPATCHER_FILENAME + '/ccm/system/dialogs/permissions/file?fID=<?=$f->getFileID()?>'; 
</script>