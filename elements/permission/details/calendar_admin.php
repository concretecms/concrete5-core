<?php
defined('C5_EXECUTE') or die("Access Denied.");
?>

<?php $pk = PermissionKey::getByID($_REQUEST['pkID']);?>

<?php Loader::element("permission/detail", array('permissionKey' => $pk)); ?>

<script>
var ccm_permissionDialogURL = '<?=URL::to("/ccm/calendar/dialogs/permissions", "calendar_admin")?>';
</script>
