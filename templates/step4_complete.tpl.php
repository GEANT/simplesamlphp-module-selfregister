<?php 

$this->data['header'] = $this->t('{selfregister:selfregister:link_newuser}');
$this->data['head'] = '<link rel="stylesheet" href="resources/selfregister.css" type="text/css">';

$this->includeAtTemplateBase('includes/header.php'); ?>

<div style="margin: 1em">
  <h1><?php echo $this->t('new_complete_head'); ?></h1>
  <p><?php echo $this->t('new_complete_para1', $this->data['systemName']); ?></p>
  <ul>
    <li><a href="reviewUser.php"><?php echo $this->t('link_review'); ?></a></li>
    <li><a href="lostPassword.php"><?php echo $this->t('link_lostpw'); ?></li>
    <li><a href="changePassword.php"><?php echo $this->t('link_changepw'); ?></li>
    <li><a href="delUser.php"><?php echo $this->t('link_deluser'); ?></li>
  </ul>
</div>

<?php $this->includeAtTemplateBase('includes/footer.php'); ?>
